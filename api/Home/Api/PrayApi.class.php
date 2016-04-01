<?php
namespace Home\Api;

use Think\Controller;

class PrayApi extends BaseApi
{

    //获取玩家免费祈愿时间
    public function getUtime($tid = null)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        //获取配置
        $prayConfig = D('Static')->access('pray');

        //获取数据
        $prayList = D('GPray')->getAll($this->mTid);

        //遍历
        $list = array();
        foreach ($prayConfig as $key => $value) {

            //是否为特殊抽卡
            if ($key >= 10000) {
                continue;
            }

            //是否开启
            if ($value['show_in'] == '0') {
                continue;
            }

            //创建数据
            if (!isset($list[$key])) {
                $list[$key]['id'] = $key;
                $list[$key]['free'] = 0;
                $list[$key]['utime'] = 0;
                $list[$key]['count'] = 0;
            }

            //获取数据
            foreach ($prayList as $val) {
                if ($val['pray_id'] == $key) {
                    if ($val['is_free'] == 1) {
                        $list[$key]['free'] = D('LPray')->getTodayFreeCount($this->mTid, $key);//今天已经免费抽取次数
                        $list[$key]['utime'] = $val['utime'];//上次免费抽卡时间
                    }
                    $list[$key]['count'] += $val['count'];//上次免费抽卡时间
                }
            }

        }

        //返回
        return array_values($list);
    }

    //免费祈愿
    public function drawFree($tid = null)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        //获取祈愿配置
        $prayConfig = D('Static')->access('pray', $_POST['pray_id']);
        if (empty($prayConfig) || $prayConfig['show_in'] == '0') {
            C('G_ERROR', 'pray_not_open');
            return false;
        }

        //是否达到VIP最低限制
        if ($prayConfig['vip_limit'] > 0) {
            $vipLevel = D('GVip')->getLevel($this->mTid);
            if ($prayConfig['vip_limit'] > $vipLevel) {
                C('G_ERROR', 'pray_not_open');
                return false;
            }
        }

        //该类型是否可以免费祈愿
        if ($prayConfig['free_period'] == '0') {
            C('G_ERROR', 'pray_type_no_free');
            return false;
        }

        //今天免费祈愿次数是否已经用完
        $count = D('LPray')->getTodayFreeCount($this->mTid, $_POST['pray_id']);
        if ($count >= $prayConfig['free_count_max']) {
            C('G_ERROR', 'pray_free_max_today');
            return false;
        }

        //是否到了免费祈愿时间
        $utime = D('GPray')->getUtime($this->mTid, $_POST['pray_id']);
        if (($utime + ($prayConfig['free_period'] * 60)) > time()) {
            C('G_ERROR', 'pray_not_in_free_time');
            return false;
        }

        //开始事务
        $this->transBegin();

        //加物品
        if (false === $itemList = $this->lottery($prayConfig['script'], $prayConfig['show_in'], 1)) {
            goto end;
        }

        //加必得物品
        if (!$this->produce('item', $prayConfig['constant_item'], $prayConfig['constant_item_count'])) {
            goto end;
        }

        //记录免费时间
        if (!D('GPray')->cData($this->mTid, $_POST['pray_id'], 1)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //记录日志
        $log['tid'] = $this->mTid;
        $log['pray_id'] = $_POST['pray_id'];
        $log['bonus'] = json_encode($itemList);
        $log['is_free'] = 1;
        D('LPray')->CreateData($log);

        //返回
        return $itemList;

    }

    //付费祈愿
    public function drawNow($tid = null)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        //获取祈愿配置
        $prayConfig = D('Static')->access('pray', $_POST['pray_id']);
        if (empty($prayConfig) || $prayConfig['show_in'] == '0') {
            C('G_ERROR', 'pray_not_open');
            return false;
        }

        //是否达到VIP最低限制
        if ($prayConfig['vip_limit'] > 0) {
            $vipLevel = D('GVip')->getLevel($this->mTid);
            if ($prayConfig['vip_limit'] > $vipLevel) {
                C('G_ERROR', 'pray_not_open');
                return false;
            }
        }

        //检查玩家当前道具是否足够
        if ($prayConfig['consume_item'] > '0') {

            //验证
            if (!$this->verify($prayConfig['consume_count'], 'item', $prayConfig['consume_item'])) {
                //检查玩家当前货币是否足够
                if (!$now = $this->verify($prayConfig['consume_value'], $this->mMoneyType[$prayConfig['consume_money']])) {
                    return false;
                }
                $consume = 'money';

            } else {
                $consume = 'item';
            }

        } else {

            //检查玩家当前货币是否足够
            if (!$now = $this->verify($prayConfig['consume_value'], $this->mMoneyType[$prayConfig['consume_money']])) {
                return false;
            }
            $consume = 'money';
        }

        //开始事务
        $this->transBegin();

        //扣除
        if ($consume == 'money') {
            //扣除货币
            if (!$this->recover($this->mMoneyType[$prayConfig['consume_money']], $prayConfig['consume_value'], null, $now)) {
                goto end;
            }
        } else if ($consume == 'item') {
            //扣除道具
            if (!$this->recover('item', $prayConfig['consume_item'], $prayConfig['consume_count'])) {
                goto end;
            }
        } else {
            goto end;
        }

        //加必得物品
        if (!$this->produce('item', $prayConfig['constant_item'], $prayConfig['constant_item_count'])) {
            goto end;
        }

        //加物品
        if (false === $itemList = $this->lottery($prayConfig['script'], $prayConfig['show_in'], 0)) {
            goto end;
        }

        //记录免费时间
        if (!D('GPray')->cData($this->mTid, $_POST['pray_id'], 0)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //记录日志
        $log['tid'] = $this->mTid;
        $log['pray_id'] = $_POST['pray_id'];
        $log['bonus'] = json_encode($itemList);
        $log['is_free'] = 0;
        D('LPray')->CreateData($log);

        //返回
        return $itemList;

    }

    //抽奖
    private function lottery($script, $show_in, $type)
    {

        //获取宝箱
        $vipLevel = D('GVip')->getLevel($this->mTid);//VIP等级
        $isFirst = D('GPray')->isFirst($this->mTid, $_POST['pray_id'], $type);//是否是第一次抽这个类型
        $pray = D('GPray')->getList($this->mTid, $script);//历史抽卡情况
        $level = D('GTeam')->getAttr($this->mTid, 'level');//战队等级
        $boxList = lua('pray', 'pray_' . $script, array((int)$show_in, (int)$type, (int)$vipLevel, (int)$isFirst, (int)$pray[1], (int)$pray[0], (int)$level));//获得宝箱ID
        $list = array();
        if (empty($boxList)) {
            C('G_ERROR', 'lua_error');
            return false;
        } else {

            //开宝箱
            foreach ($boxList as $value) {

                //加物品
                if (false === $box = $this->produce('box', $value, 1)) {
                    return false;
                }

                //如果有开出伙伴
                if ($box[0]['type'] == '3') {

                    //获取该伙伴配置
                    $partnerGroupConfig = D('Static')->access('partner_group', $box[0]['id']);
                    foreach ($partnerGroupConfig as $value) {
                        if ($value['is_init'] == 1) {
                            $partnerConfig = $value;
                            break;
                        }
                    }

                    //查看伙伴是否是SS级
                    if ($partnerConfig['partner_class'] == '9') {
                        //发送公告
                        $params['nickname'] = D('GTeam')->getAttr($this->mTid, 'nickname');
                        $params['partner_name'] = $partnerConfig['name'];
                        $noticeConfig = D('SEventString')->getConfig('PRAY_PARTNER', $params);
                        D('GChat')->sendNoticeMsg($this->mTid, $noticeConfig['des'], $noticeConfig['show_level']);
                    }

                }

                $list[] = empty($box[0]) ? array() : $box[0];
            }

        }

        return $list;
    }

}