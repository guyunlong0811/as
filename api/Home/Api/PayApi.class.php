<?php
namespace Home\Api;

use Think\Controller;

class PayApi extends BaseApi
{

    //获取充值列表
    public function getList()
    {
        //获取是否是首次充值
        $return['pay'] = D('GVip')->getAttr($this->mTid, 'first_pay');

        //获取会员情况
        $member = D('GMember')->getList($this->mTid);
        if (!empty($member)) {
            foreach ($member as $key => $value) {
                $member[$key]['expire'] = strtotime($value['expire']);
            }
        }
        $return['member'] = $member;

        //获取用户渠道
        $channelId = D('GTeam')->getAttr($this->mTid, 'channel_id');

        //渠道充值关闭
        $return['list'] = array();
        $closeList = C('CASH_CLOSE');
        if (!in_array($channelId, $closeList)) {

            //已购列表
            $buy = D('GPay')->getBoughtList($this->mTid);

            //获取cash配置
            $cashConfig = D('Static')->access('cash');
            $cashChannelConfig = D('StaticDyn')->access('cash');

            //获取渠道配置
            if (isset($cashChannelConfig[$channelId])) {
                $cashChannelConfig = $cashChannelConfig[$channelId];
            } else {
                $cashChannelConfig = $cashChannelConfig[0];
            }

            //遍历当前开放的充值方案
            $payList = array();
            foreach ($cashChannelConfig as $payId => $goodId) {
                $payInfo = $cashConfig[$payId];
                $payInfo['goods_id'] = $goodId;
                if (in_array($payId, $buy)) {
                    $payInfo['status'] = 1;
                } else {
                    $payInfo['status'] = 0;
                }
                $payList[] = $payInfo;
            }

            //充值列表
            $return['list'] = $payList;

        }

        return $return;
    }

    //发起充值请求
    public function launch()
    {
        //获取商品信息
        $cashConfig = D('Static')->access('cash', $_POST['cash_id']);
        if (!$cashConfig) {
            return false;
        }
        //如果是会员卡
        if ($cashConfig['type'] == '2') {
            $day = D('Static')->access('params', 'MEMBER_PAY_DAY');
            if ($day != '-1') {
                //查询会员卡过期时间
                $expireTime = D('GMember')->getRow($this->mTid, $cashConfig['member_id'], 'expire');
                if (!empty($expireTime)) {
                    $expireTime = strtotime($expireTime);
                    if (time() < ($expireTime - ($day * 86400))) {
                        C('G_ERROR', 'member_buy_not_allow');
                        return false;
                    }
                }
            }
        }
        //生成订单号
        $order = create_order_id($this->mTid);
        //查看是否已经存在订单号
        $where['order_id'] = $order;
        $count = M('GOrder')->where($where)->count();
        if ($count > 0) {
            C('G_ERROR', 'pay_launch_too_fast');
            return false;
        }
        //创建订单
        $add['order_id'] = $order;
        $add['tid'] = $this->mTid;
        $add['cash_id'] = $_POST['cash_id'];
        $add['price'] = $cashConfig['price'];
        $add['channel_id'] = $this->mSessionInfo['channel_id'];
        if (!D('GOrder')->CreateData($add)) {
            return false;
        }
        $return['order_id'] = $order;

        //返回回掉地址
        $serverList = get_server_list();
        $return['callback'] = $serverList[C('G_SID')]['channel'][$this->mSessionInfo['channel_id']]['callback'];
        $return['gateway_id'] = $serverList[C('G_SID')]['platform']['sid'];

        //返回
        return $return;
    }

    //取消订单
    public function cancel()
    {
        //查询订单情况
        $where['order_id'] = $_POST['order_id'];
        $orderInfo = M('GOrder')->where($where)->find();
        if (empty($orderInfo)) {
            C('G_ERROR', 'order_not_available');
            return false;
        }
        //检查订单是否属于玩家
        if ($orderInfo['tid'] != $this->mTid) {
            C('G_ERROR', 'order_id_error');
            return false;
        }
        //开始事务
        $this->transBegin();
        //订单日志
        $add['tid'] = $orderInfo['tid'];
        $add['cash_id'] = $orderInfo['cash_id'];
        $add['channel_id'] = $orderInfo['channel_id'];
        $add['order_id'] = $orderInfo['order_id'];
        $add['platform_order_id'] = '';
        $add['verify'] = '';
        $add['starttime'] = $orderInfo['ctime'];
        $add['status'] = -1;//正常取消订单
        if (!D('LOrder')->CreateData($add)) {
            goto end;
        }
        //删除订单
        if (false === D('GOrder')->DeleteData($where)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return true;

    }

    //ios充值成功，客户端通知成功
    public function successIOS()
    {

        //查询订单情况
        $where['order_id'] = $_POST['order_id'];
        $orderInfo = M('GOrder')->where($where)->find();
        if (empty($orderInfo)) {
            C('G_ERROR', 'order_not_available');
            return false;
        }

        $add['tid'] = $orderInfo['tid'];
        $add['cash_id'] = $orderInfo['cash_id'];
        $add['channel_id'] = $orderInfo['channel_id'];
        $add['order_id'] = $orderInfo['order_id'];
        $add['starttime'] = $orderInfo['ctime'];

        //AppStore验证
        $verify = verify_app_store($_POST['receipt'], $_POST['is_sandbox']);
        if ($verify !== false) {
            //订单日志
            $add['platform_order_id'] = $verify['receipt']['transaction_id'];
            $add['verify'] = json_encode($verify);
            //检查凭证
            if (D('LIap')->isExist($verify['receipt']['transaction_id'])) {
                $add['status'] = -4;//重复订单
                D('LOrder')->CreateData($add);
                D('GOrder')->DeleteData($where);
                C('G_ERROR', 'receipt_handle_already');
                return false;
            }
        } else {
            //订单日志
            $add['platform_order_id'] = '';
            $add['verify'] = '';
            $add['status'] = -3;//验证失败
        }

        if ($verify !== false) {

            //开始事务
            $this->transBegin();

            //发放商品
            if (false === $this->delivery($orderInfo['tid'], $orderInfo['cash_id'])) {
                goto end;
            }

            C('G_TRANS_FLAG', true);
            end:
            if (!$this->transEnd()) {
                $flagDelivery = false;
            } else {
                $flagDelivery = true;
            }

        }

        //发放商品
        if (isset($flagDelivery)) {
            if (!$flagDelivery) {
                $add['status'] = -5;//发货失败
            } else {
                $add['status'] = 1;//成功
            }
            //记录内购处理日志
            D('LIap')->cLog($verify['receipt']['transaction_id']);
        }

        //记录日志
        D('LOrder')->CreateData($add);

        //删除订单
        D('GOrder')->DeleteData($where);

        //返回
        return $add['status'];

    }

    //订单确认
    public function confirm()
    {
        //更新sdk信息
        $this->refreshSDK($_POST['pf'], $_POST['pfkey'], $_POST['paytoken']);

        //查询用户充值情况
        if(false === $rs = D('Msdk')->get_balance_m($this->mSessionInfo['channel_type'], $this->mSessionInfo['channel_uid'], $this->mSessionInfo['channel_token'], $this->mSessionInfo['pay_token'], $this->mSessionInfo['pf'], $this->mSessionInfo['pfkey'])){
            C('G_ERROR', 'msdk_error');
            return false;
        }

        //定义行为
        C('G_BEHAVE', 'pay');
        $orderStatus = 1;
        $orderId = $_POST['order_id'];

        //同步diamond_pay
        D('GTeam')->updateAttr($this->mTid, 'diamond_pay', $rs['diamond_pay']);

        //查询订单
        $where['order_id'] = $orderId;
        $orderInfo = M('GOrder')->where($where)->find();
        if (empty($orderInfo)) {
            return $orderStatus;
        }

        //获取玩家已充值的钱数
        $payTotal = D('GVip')->getAttr($this->mTid, 'pay');
        $payTotalDiamond = $payTotal / 100 * C('MONEY_RATE');

        //比较应用宝数据与当前数据是否有差异
        if($rs['pay'] <= $payTotalDiamond){
            C('G_ERROR', 'msdk_error');
            return $orderStatus;
        }else{
            $payDiamondAmount = $rs['pay'] - $payTotalDiamond;
        }

        //获取Cash信息
        $cashConfig = D('Static')->access('cash', $orderInfo['cash_id']);
        $price = $cashConfig['price'];
        $needDiamond = $cashConfig['price'] / 100 * C('MONEY_RATE');

        //检查价格是否正确
        if ($needDiamond > $payDiamondAmount && $payDiamondAmount >= 0) {
            $orderStatus = 2;//订单金额有误
        }

        //订单日志
        $add['tid'] = $orderInfo['tid'];
        $add['cash_id'] = $orderInfo['cash_id'];
        $add['price'] = $price;
        $add['channel_id'] = $orderInfo['channel_id'];
        $add['order_id'] = $orderInfo['order_id'];
        $add['platform_order_id'] = '';
        $add['verify'] = '';
        $add['level'] = D('GTeam')->getAttr($orderInfo['tid'], 'level');
        $add['starttime'] = $orderInfo['ctime'];
        $add['comment'] = '';

        //开始事务
        $this->transBegin();

        //正常充值
        if ($orderStatus == 1) {
            if (false === $this->delivery($orderInfo['tid'], $orderInfo['cash_id'])) {
                goto end;
            }
        }

        //异常充值
        if ($needDiamond != $payDiamondAmount && $payDiamondAmount > 0) {

            //计算实际获得金钱
            $add['price'] = $payDiamondAmount / C('MONEY_RATE') * 100;

            //计算异常量
            if ($needDiamond > $payDiamondAmount) {
                $abnormal = $payDiamondAmount;
                $isCount = true;
            } else {
                $abnormal = $payDiamondAmount - $needDiamond;
                $isCount = false;
            }

            //增加异常数值水晶
            if (false === $this->abnormalDelivery($orderInfo['tid'], $abnormal, $isCount)) {
                goto end;
            }

        }

        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            $orderStatus = -5;//增加商品失败
        }

        //记录日志
        $add['status'] = $orderStatus;
        D('LOrder')->CreateData($add);

        //删除订单
        D('GOrder')->DeleteData($where);

        //返回
        return $orderStatus;

    }

    //订单失败
    public function fail()
    {
        //查询订单情况
        $where['order_id'] = $_POST['order_id'];
        $orderInfo = M('GOrder')->where($where)->find();
        if (empty($orderInfo)) {
            C('G_ERROR', 'order_not_available');
            return false;
        }
        //检查订单是否属于玩家
        if ($orderInfo['tid'] != $this->mTid) {
            C('G_ERROR', 'order_id_error');
            return false;
        }
        //开始事务
        $this->transBegin();
        //订单日志
        $add['tid'] = $orderInfo['tid'];
        $add['cash_id'] = $orderInfo['cash_id'];
        $add['channel_id'] = $orderInfo['channel_id'];
        $add['order_id'] = $orderInfo['order_id'];
        $add['platform_order_id'] = '';
        $add['verify'] = '';
        $add['starttime'] = $orderInfo['ctime'];
        $add['status'] = 0;//正常取消订单
        $add['comment'] = $_POST['comment'];//正常取消订单
        if (!D('LOrder')->CreateData($add)) {
            goto end;
        }
        //删除订单
        if (false === D('GOrder')->DeleteData($where)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return true;
    }

}