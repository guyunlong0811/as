<?php
namespace Home\Api;

use Think\Controller;

class UserApi extends BaseApi
{

    const PRE_MAX = 10000;

    //快速游戏
    public function fast()
    {

        //验证渠道ID是否合法
        $flag = false;
        $serverList = get_server_list();
        foreach ($serverList[C('G_SID')]['channel'] as $key => $value) {
            if ($key == $_POST['channel_id']) {
                $flag = true;
                break;
            }
        }
        if (!$flag) {
            C('G_ERROR', 'channel_not_exist');
            return false;
        }


        //查询服务器是否可以进入
        if ($this->mServer[C('G_SID')]['channel'][$_POST['channel_id']]['type'] == '-1') {
            $arr = explode('#', IP_WHITE);
            if (!in_array(get_ip(), $arr)) {
                C('G_ERROR', 'server_maintenance');
                return false;
            }
        }

        //检查是否需要版本更新
        if (!$this->checkBaseVersion($_POST['base_version'])) {
            return false;
        }

        $channelInfo = array();
        switch ($_POST['channel_id']) {

            case '1001':
            case '1002':
            case '2001':
            case '3001':
                break;

            default:
                switch($_POST['channel_type']){
                    case '1':
                        $loginInfo = D('Msdk')->wx_check_token($_POST['channel_uid'], $_POST['channel_token']);
                        break;
                    case '2':
                        $loginInfo = D('Msdk')->verify_login($_POST['channel_uid'], $_POST['channel_token']);
                        break;
                }

                if (!$loginInfo) {
                    //参数错误
                    C('G_DEBUG_PARAMS', 'channel_type');
                    C('G_ERROR', 'params_error');
                    return false;
                }else if ($loginInfo['ret'] != 0) {
                    C('G_ERROR', 'platform_login_error');
                    C('G_DEBUG_PT_ERROR', $loginInfo);
                    return false;
                } else {
                    $channelInfo['id'] = $_POST['channel_uid'];
                }

        }
        if (!$return = $this->toLogin('fast')) {
            return false;
        }
        $return['channel_info'] = $channelInfo;
        return $return;
    }

    //游戏登陆
    public function login()
    {

        //查询服务器是否可以进入
        if ($this->mServer[C('G_SID')]['channel'][$_POST['channel_id']]['type'] == '-1') {
            $arr = explode('#', IP_WHITE);
            if (!in_array(get_ip(), $arr)) {
                C('G_ERROR', 'server_maintenance');
                return false;
            }
        }

        //检查是否需要版本更新
        if (!$this->checkBaseVersion($_POST['base_version'])) {
            return false;
        }

        return $this->toLogin('login');
    }

    //登录逻辑
    private function toLogin($type)
    {

        //UC验证
        $params = $_POST;
        $params['sid'] = C('G_SID');
        $params['gid'] = C('GAME_ID');
        if ($type == 'fast') {
            $params['ip'] = get_ip();
        }
        if (!$return = uc_link($params, 'User.' . $type)) {
            return false;
        }

        //查询帐号数量
        if ($_POST['tid'] > 0) {
            $tidList[] = $_POST['tid'];
        } else {
            $tidList = D('GTeam')->getTeamIds($return['uid'], $_POST['channel_id']);
        }

        $teamCount = count($tidList);
        //无角色
        if ($teamCount == 0) {

            //查询服务器是否允许建新号
            if ($this->mServer[C('G_SID')]['channel'][$_POST['channel_id']]['type'] == '-2') {
                C('G_ERROR', 'server_close_reg');
                return false;
            }

            //写入战队数组
            if ($return['activation'] == '0') {
                $tidList[] = '-1';
            } else {
                $tidList[] = '0';
            }
        }

        //tid
        $data['tid'] = $tidList;

        //多角色时不作游戏登录操作
        if ($teamCount >= 2) {
            $in = sql_in_condition($tidList);
            $data['list'] = D('GTeam')->join('`g_vip` on `g_team`.`tid`=`g_vip`.`tid`')->field('`g_team`.`tid`,`g_team`.`nickname`,`g_team`.`icon`,`g_team`.`level`,`g_vip`.`index` as `vip_index`')->where("`g_team`.`tid`{$in}")->select();
        } else {

            //查看是否是新玩家
            $tid = $tidList[0];
            $data['token'] = $return['token'];
            C('G_TOKEN', $return['token']);

            //获取数据
            if ($tid > 0) {

                //进入游戏
                $this->enter($tid);

                //获取账户信息
                $allData = A('Team', 'Api')->getMainInfo($tid, $return['silence']);

                //合并信息
                $data = $data + $allData;

            }

            if ($tid >= 0) {
                //生成登陆token flag -- true:覆盖登录;false:新登录;
                D('GTeam')->createLoginToken($return['uid'], $return['token'], $return['silence'], $tid, C('G_SID'), $allData['team']);
            }

        }

        //用户中心登录成功
        return $data;
    }

    //获取客户端所需信息
    private function enter($tid)
    {

        if ($tid > 0) {

            //获取玩家数据
            $row = D('GTeam')->getRow($tid, array('login_continuous', 'last_login_time'));

            //获取更新时间
            $today = get_daily_utime();

            //当日首次登录
            if ($row['last_login_time'] < $today) {

                //登录成就
                D('GCount')->login($tid);
                if ($row['last_login_time'] >= ($today - 86400)) {
                    $updateTeam['login_continuous'] = $row['login_continuous'] + 1;
                } else {
                    $updateTeam['login_continuous'] = 1;
                }

                //连续登录成就
                D('GCount')->loginContinuous($tid, $updateTeam['login_continuous']);

                //生死门守护者奖励
                if ($row['level'] >= 17) {
                    $mailId = lua('life_death_battle', 'guard_mail', array((int)$row['level']));
                    if ($mailId > 0) {
                        D('GMail')->send($mailId, 0, $tid);
                    }
                }

            }

            //更新最后登录时间
            $where['tid'] = $this->mTid = $tid;
            $updateTeam['last_login_time'] = time();
            D('GTeam')->UpdateData($updateTeam, $where);

            //写入登录记录
            D('LLogin')->cLog($tid);

            //同步diamond_pay
            if(false === $rs = D('Msdk')->get_balance_m($_POST['channel_type'], $_POST['channel_uid'], $_POST['channel_token'], $_POST['paytoken'], $_POST['pf'], $_POST['pfkey'])){
                C('G_ERROR', 'msdk_error');
                return false;
            }else{
                $this->synPay($rs);
            }

        }
        return true;
    }

    //腾讯服登录充值数据与现有不同自动创建订单
    private function synPay($pt){

        //定义行为
        $nowBehave = C('G_BEHAVE');
        C('G_BEHAVE', 'pay');
        $now = time();
        $orderStatus = 1;

        //同步diamond_pay
        D('GTeam')->updateAttr($this->mTid, 'diamond_pay', $pt['diamond_pay']);

        //获取玩家已充值的钱数
        $payTotal = D('GVip')->getAttr($this->mTid, 'pay');
        $payTotalDiamond = $payTotal / 100 * C('MONEY_RATE');
        if($pt['pay'] <= $payTotalDiamond){
            return true;
        }

        //计算双方数据差异
        $payDiamondAmount = $pt['pay'] - $payTotalDiamond;

        //确认
        $cashId = 0;
        $cashConfig = D('Static')->access('cash');
        foreach ($cashConfig as $key => $value) {
            if($value['value'] < $payDiamondAmount){
                $cashId = $key;
            }else if($value['value'] == $payDiamondAmount){
                $cashId = $key;
                break;
            }else if($value['value'] > $payDiamondAmount){
                if($cashId == 0){
                    $cashId = $key;
                }
                break;
            }
        }
        $needDiamond = $cashConfig[$cashId]['price'] / 100 * C('MONEY_RATE');

        //订单日志
        $add['tid'] = $this->mTid;
        $add['cash_id'] = $cashId;
        $add['price'] = $payDiamondAmount / C('MONEY_RATE') * 100;
        $add['channel_id'] = $_POST['channel_id'];
        $add['order_id'] = create_order_id($this->mTid);
        $add['platform_order_id'] = '';
        $add['verify'] = '';
        $add['level'] = D('GTeam')->getAttr($this->mTid, 'level');
        $add['starttime'] = $now;
        $add['comment'] = '';

        //检查价格是否正确
        if ($needDiamond > $payDiamondAmount && $payDiamondAmount >= 0) {
            $orderStatus = 2;//订单金额有误
        }

        //开始事务
        $this->transBegin();

        //正常充值
        if ($orderStatus == 1) {
            if (false === $this->delivery($add['tid'], $add['cash_id'])) {
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
            if (false === $this->abnormalDelivery($add['tid'], $abnormal, $isCount)) {
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

        //返回
        C('G_BEHAVE', $nowBehave);
        return true;


    }

    //用户注册（用户中心）
    public function register()
    {
        $params = $_POST;
        $params['sid'] = C('G_SID');
        $params['gid'] = C('GAME_ID');
        $params['ip'] = get_ip();
        return uc_link($params, 'User.register');
    }

    //开始新游戏
    public function newGame()
    {
        //敏感字符
//        if(!shield($_POST['nickname'])){
//            C('G_ERROR', 'nickname_shield');
//            return false;
//        }

        //验证渠道ID是否合法
        $flag = false;
        $serverList = get_server_list();
        foreach ($serverList[C('G_SID')]['channel'] as $key => $value) {
            if ($key == $_POST['channel_id']) {
                $flag = true;
                break;
            }
        }
        if (!$flag) {
            C('G_ERROR', 'channel_not_exist');
            return false;
        }

        //查询是否已经创建角色
        if (D('GTeam')->isUidExist($this->mUid, $_POST['channel_id'])) {
            C('G_ERROR', 'user_existed');
            return false;
        }

        //查询昵称是否已被使用
        if (D('GTeam')->isNicknameExist($_POST['nickname'])) {
            C('G_ERROR', 'nickname_existed');
            return false;
        }

        //查询是否存在预创建帐号
        $bool = D('Predis')->cli('game')->exists('pre');
        $pre = $bool ? D('Predis')->cli('game')->incr('pre') : null;
        $isPre = false;
        $tid = 0;
        if (!is_null($pre) && $pre <= self::PRE_MAX) {
            $tid = $pre + (C('G_SID') * 1000000);//获取tid
            $uid = D('GTeam')->getAttr($tid, 'uid');
            if ($uid == '0') {
                $isPre = true;
            }
        }

        //创建帐号
        if (!$isPre) {

            //获取默认创角ID
            if (!$teamIndex = D('Static')->access('params', 'INIT_TEAM_ID')) {
                return false;
            }

            //获取默认角色基础属性
            if (!$teamConfig = D('Static')->access('team_creation', $teamIndex)) {
                return false;
            }

            //开始事务
            $this->transBegin();

            //创建角色
            if (false === $tid = D('GTeam')->cData($this->mUid, $_POST['nickname'], $_POST['channel_id'], $teamConfig)) {
                goto end;
            }

            //创建主角(伙伴)
            $charIndex = $teamConfig['init_char'];
            if (!D('GPartner')->cPartner($tid, $charIndex)) {
                goto end;
            }

            //发放初始道具
            for ($i = 1; $i <= 4; ++$i) {
                if ($teamConfig['init_item_' . $i] > 0 && $teamConfig['init_item_' . $i . '_count'] > 0) {
                    if (false === D('GItem')->inc($tid, $teamConfig['init_item_' . $i], $teamConfig['init_item_' . $i . '_count'])) {
                        goto end;
                    }
                }
            }

            //创建玩家设备信息记录
//            if(!D('GDevice')->cData($tid)){goto end;}

            //创建成就&排行榜信息
            if (!D('GCount')->cData($tid)) {
                goto end;
            }

            //创建VIP信息
            if (!D('GVip')->cData($tid)) {
                goto end;
            }

            //结束事务
            C('G_TRANS_FLAG', true);
            end:
            if (!$this->transEnd()) {
                return false;
            }

            //角色属性改变记录
//            if($teamConfig['init_level'] > 0)D('LTeam')->cLog($tid,'level',$teamConfig['init_level'],0);
//            if($teamConfig['init_exp'] > 0)D('LTeam')->cLog($tid,'exp',$teamConfig['init_exp'],0);
//            if($teamConfig['init_vality'] > 0)D('LTeam')->cLog($tid,'vality',$teamConfig['init_vality'],0);
//            if($teamConfig['init_energy'] > 0)D('LTeam')->cLog($tid,'energy',$teamConfig['init_energy'],0);
//            if($teamConfig['init_gold'] > 0)D('LTeam')->cLog($tid,'gold',$teamConfig['init_gold'],0);
//            if($teamConfig['init_fame'] > 0)D('LTeam')->cLog($tid,'fame',$teamConfig['init_fame'],0);

        } else {

            //使用预创建帐号
            if (false === D('GTeam')->usePreCreate($tid, $this->mUid, $_POST['nickname'], $_POST['channel_id'])) {
                return false;
            }

        }

        //预下载奖励领取
        $preDownload = json_decode(D('GParams')->getValue('PRE_DOWNLOAD_BONUS'), true);
        if (time() <= strtotime($preDownload['endtime'])) {
            D('GMail')->send($preDownload['mail_id'], 0, $tid);
        }

        //进入游戏
        $this->enter($tid);//返回登录所需信息
        $arr = D('Predis')->cli('game')->hgetall($this->mSessionKey);
        $arr['tid'] = $tid;
        $arr['league_id'] = 0;
        $arr['channel_id'] = $_POST['channel_id'];
        D('Predis')->cli('game')->hmset('s:' . $tid, $arr);
        D('Predis')->cli('game')->del($this->mSessionKey);
        $this->mSessionKey = 's:' . $tid;
        D('Predis')->cli('game')->set('t:' . $this->mToken, $tid);

        //返回
        return A('Team', 'Api')->getMainInfo($tid, $this->mSilence);

    }

    //注册时检查用户名是否可用（用户中心）
    public function usernameCheck()
    {
        return uc_link($_POST, 'User.usernameCheck');
    }

    //修改用户密码（用户中心）
    public function changePwd()
    {
        $_POST['uid'] = $this->mUid;
        return uc_link($_POST, 'User.changePwd');
    }

    //完成注册(弱账号升级为正式账号)
    public function complete()
    {
        $_POST['uid'] = $this->mUid;
        return uc_link($_POST, 'User.complete');
    }

    //绑定电子邮件
    public function email()
    {
        $params['uid'] = $this->mUid;
        $params['email'] = $_POST['email'];
        $ret = uc_link($params, 'User.email');
        if (false === $ret) {
            return false;
        }
        if ($ret['row'] == 0) {
            C('G_ERROR', 'no_update');
            return false;
        }
        return true;
    }

    //绑定手机号码
    public function phone()
    {
        $params['uid'] = $this->mUid;
        $params['phone'] = $_POST['phone'];
        $ret = uc_link($params, 'User.phone');
        if (false === $ret) {
            return false;
        }
        if ($ret['row'] == 0) {
            C('G_ERROR', 'no_update');
            return false;
        }
        return true;
    }

    //绑定防沉迷信息
    public function ident()
    {
        //获取uid
        $_POST['uid'] = $this->mUid;
        //绑定防沉迷信息
        if (!uc_link($_POST, 'User.ident')) {
            return false;
        }
        return true;
    }


    //绑定账号(弱账号绑定已有正式账号，弱账号依然存在)
    public function binding()
    {
        //获取绑定前后的UID
        $change = uc_link($_POST, 'User.binding');
        //更改UID
        $where['tid'] = $this->mTid;
        $update['uid'] = $change['after'];
        if (false === D('GTeam')->UpdateData($update, $where)) {
            return false;
        }
        return true;
    }

    //更新设备信息
    public function device()
    {
        //设备信息
        $data['tid'] = $this->mTid;
        if (isset($_POST['name'])) $data['name'] = $_POST['name'];
        if (isset($_POST['system'])) $data['system'] = $_POST['system'];
        if (isset($_POST['version'])) $data['version'] = $_POST['version'];
        if (isset($_POST['token'])) $data['token'] = $_POST['token'];
        $row = D('GDevice')->uData($data);//更新数据
        if ($row == 0) {
            C('G_ERROR', 'no_update');
            return false;
        }
        return true;
    }

    //保持连接
    public function stay()
    {
        return true;
    }

    //刷新连接
    public function refresh()
    {
        return $this->refreshSDK($_POST['pf'], $_POST['pfkey'], $_POST['paytoken']);
    }

}