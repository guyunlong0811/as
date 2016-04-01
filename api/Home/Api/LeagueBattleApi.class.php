<?php
namespace Home\Api;

use Think\Controller;

class LeagueBattleApi extends BEventApi
{

    const GROUP = 7;
    const FORMATION = 45;//保护时间

    private $mLeagueInfo;//公会数据
    private $mBattleInfo;//公会数据

    private function eventVerify()
    {

        if (!$this->event(self::GROUP)) {
            return false;
        }//活动限制

        //查询玩家是否已经参加公会
        $this->mLeagueInfo = D('GLeague')->getRowMember($this->mTid);
        if (empty($this->mLeagueInfo)) {
            C('G_ERROR', 'league_not_attended');
            return false;
        }

        //查询该公会是否可以参加活动
        $minLevel = D('Static')->access('params', 'LEAGUE_AREA_LEVEL');
        if ($minLevel > $this->mLeagueInfo['center_level']) {
            C('G_ERROR', 'league_center_level_low');
            return false;
        }

        return true;
    }

    //查询公会成员战斗信息
    public function getListSchedule()
    {

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //获取公会占领情况
        $list = D('GLeagueTeam')->getSchedule($this->mLeagueInfo['id']);

        //获取成员列表
        foreach ($list as $value) {
            $arrTid[] = $value['tid'];
        }
        $in = sql_in_condition($arrTid);
        $where = "`tid`{$in} && `event_id`='{$this->mEventConfig['index']}'";

        //获取成员挑战情况
        $select = M($this->mEventConfig['tablename'])->field(array('tid', 'type', 'count',))->where($where)->select();
        if (empty($select)) {
            $countList = array();
        } else {
            foreach ($select as $value) {
                $countList[$value['tid']][$value['type']] = $value['count'];
            }
        }

        //计算挑战情况
        foreach ($list as $key => $value) {
            $used = isset($countList[$value['tid']][1]) ? $countList[$value['tid']][1] : 0;
            $buy = isset($countList[$value['tid']][2]) ? $countList[$value['tid']][2] : 0;
            $add = isset($countList[$value['tid']][3]) ? $countList[$value['tid']][3] : 0;
            $list[$key]['buy'] = $buy;
            $list[$key]['remain'] = ($this->mEventConfig['count'] + $add - $used);
        }

        //返回
        return $list;
    }

    //获取失灭之战距点信息
    public function getList()
    {

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //获取本次活动所有的据点信息
        $battleConfig = D('Static')->access('instance_info_map', $this->mEventInfo['map']);

        //查询本公会所有据点的战况
        $battleList = D('GLeagueBattle')->getAll($this->mLeagueInfo['id']);
//        dump($battleList);
        //查看据点状态
        $list = array();
        foreach ($battleConfig as $value) {
            if (!isset($battleList[$value['index']]) || $battleList[$value['index']]['status'] == '0') {//没有战斗记录
                $list[$value['index']] = '0';
            } else if ($battleList[$value['index']]['status'] == '1') {//已经获胜
                $list[$value['index']] = '1';
            } else if ($battleList[$value['index']]['status'] == '2') {//正在编组
                $now = time();
                if ($now - $battleList[$value['index']]['utime'] >= self::FORMATION) {//超过保护时间
                    $list[$value['index']] = '0';
                } else {//未超过保护时间
                    $list[$value['index']] = '2';
                }
            } else if ($battleList[$value['index']]['status'] == '3') {//正在攻打
                $now = time();
                if ($now - $battleList[$value['index']]['utime'] >= $value['activate_period'] + 10) {//超过保护时间
                    $list[$value['index']] = '0';
                } else {//未超过保护时间
                    $list[$value['index']] = '3';
                }
            }
        }

        //返回
        $return['remain'] = $this->mEventRemainCount;
        $return['buy'] = $this->mEventBuyCount;
        $return['map_id'] = $this->mEventInfo['map'];
        $return['list'] = $list;
        return $return;

    }

    //编组
    public function formation()
    {

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //查询副本配置
        $battleConfig = D('Static')->access('instance_info_map', $this->mEventInfo['map'], $_POST['instance_id']);
        if (empty($battleConfig)) {
            C('G_ERROR', 'instance_error');
            return false;
        }

        //查询战役信息
        $this->mBattleInfo = D('GLeagueBattle')->getRow($this->mLeagueInfo['id'], $_POST['instance_id']);
        if (!empty($this->mBattleInfo)) {

            //查看据点是否已经被占领
            if ($this->mBattleInfo['status'] == '1') {
                C('G_ERROR', 'league_battle_hold');
                return false;
            }

            //战役是否超时
            if ($this->mBattleInfo['status'] == '2') {
                $now = time();
                if ($now - $this->mBattleInfo['utime'] < self::FORMATION) {//超过保护时间
                    C('G_ERROR', 'league_battle_start');
                    return false;
                }
            }

            //战役是否超时
            if ($this->mBattleInfo['status'] == '3') {
                $limit_time = $battleConfig['activate_period'] + 10;
                $now = time();
                if ($now - $this->mBattleInfo['utime'] < $limit_time) {//超过保护时间
                    C('G_ERROR', 'league_battle_start');
                    return false;
                }
            }

        }

        //查看挑战次数
        if ($this->mEventRemainCount < 1) {
            C('G_ERROR', 'league_battle_challenges_not_enough');
            return false;
        }

        //开始事务
        $this->transBegin();

        //结束未完成的公会副本
        $rs = D('GLeagueBattle')->destroy($this->mTid, $_POST['instance_id']);
        if ($rs === false) {
            goto end;
        } else if ($rs > 0 && $rs !== true) {
            for ($i = 1; $i <= $rs; ++$i) {
                if (false === $this->useEventCount()) {
                    goto end;
                }
            }
        }

        //修改据点状态
        if (empty($this->mBattleInfo)) {//新增记录
            $loot = $this->loot($battleConfig);//计算掉落概率
            $add['league_id'] = $this->mLeagueInfo['id'];
            $add['instance'] = $_POST['instance_id'];
            $add['drop_list'] = json_encode($loot['list']);
            $add['drop_item'] = json_encode($loot['item']);
            $add['last_tid'] = $this->mTid;
            if (!D('GLeagueBattle')->CreateData($add)) {
                goto end;
            }
        } else {//修改状态
            $where['league_id'] = $this->mLeagueInfo['id'];
            $where['instance'] = $_POST['instance_id'];
            $update['last_tid'] = $this->mTid;
            $update['status'] = 2;
            if (false === D('GLeagueBattle')->UpdateData($update, $where)) {
                goto end;
            }
        }

        //检查是否需要重置伙伴状态
        $add = D($this->mEventConfig['tablename'])->getCount($this->mTid, $this->mEventConfig['index'], 3);
        $refresh = D($this->mEventConfig['tablename'])->getCount($this->mTid, $this->mEventConfig['index'], 4);
        if ($add > $refresh) {//如果有重置机会
            $where['tid'] = $this->mTid;
            if (false === D('GLeagueBattlePartner')->DeleteList($where)) {
                goto end;
            }
            if (false === D($this->mEventConfig['tablename'])->record($this->mTid, $this->mEventConfig['index'], 4, $this->mEventConfig['group'])) {
                goto end;
            }
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //查询玩家伙伴状态
        $return['partner'] = D('GLeagueBattlePartner')->getAll($this->mTid);
        $return['airship'] = D('GLeagueBattleAirship')->getAll($this->mTid);
        return $return;

    }

    //退出编组
    public function quit()
    {

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //检查副本信息
        $this->mBattleInfo = D('GLeagueBattle')->getRow($this->mLeagueInfo['id'], $_POST['instance_id']);
        if ($this->mBattleInfo['last_tid'] != $this->mTid) {
            C('G_ERROR', 'league_battle_formation_timeout');
            return false;
        }
        //修改状态
        $where['league_id'] = $this->mLeagueInfo['id'];
        $where['instance'] = $_POST['instance_id'];
        $update['status'] = 0;
        if (false === D('GLeagueBattle')->UpdateData($update, $where)) {
            return false;
        }
        return true;
    }

    //开始战斗
    public function fight()
    {

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //查询战役信息
        $this->mBattleInfo = D('GLeagueBattle')->getRow($this->mLeagueInfo['id'], $_POST['instance_id']);
        if ($this->mBattleInfo['last_tid'] != $this->mTid || $this->mBattleInfo['status'] != '2') {
            C('G_ERROR', 'league_battle_formation_timeout');
            return false;
        }

        //开始事务
        $this->transBegin();

        //修改据点状态
        $where['league_id'] = $this->mLeagueInfo['id'];
        $where['instance'] = $_POST['instance_id'];
        $update['last_tid'] = $this->mTid;
        $update['partner'] = json_encode($_POST['partner']);
        $update['airship'] = $_POST['airship'];
        $update['status'] = 3;
        if (false === D('GLeagueBattle')->UpdateData($update, $where)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        $return['battle'] = $this->mBattleInfo['battle'];
        $return['monster'] = $this->mBattleInfo['monster'];
        $return['loot'] = json_decode($this->mBattleInfo['drop_list'], true);
        return $return;

    }

    //战斗胜利
    public function win()
    {

        $result = 1;//战斗结果

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //战役是否超时
        $limit_time = D('Static')->access('instance_info_map', $this->mEventInfo['map'], array($_POST['instance_id'], 'activate_period',));

        //查询战役信息
        $where['league_id'] = $this->mLeagueInfo['id'];
        $where['instance'] = $_POST['instance_id'];
        $where['last_tid'] = $this->mTid;
        $where['status'] = 3;
        $this->mBattleInfo = D('GLeagueBattle')->getRowCondition($where);

        //找不到数据
        if (empty($this->mBattleInfo)) {
            $result = 0;
            C('G_ERROR', 'league_battle_error');
        }

        //开始事务
        $this->transBegin();

        if ($result == 1) {
            //发放掉落奖励
            if (!empty($this->mBattleInfo['drop_item'])) {
                $bonus = json_decode($this->mBattleInfo['drop_item'], true);
                foreach ($bonus as $key => $value) {
                    if (!$this->produce('item', $key, $value)) {
                        goto end;
                    }//掉落道具
                }
            }
            //发放占领据点奖励
            $mail_id = D('Static')->access('params', 'LEAGUE_AREA_POINTS');
            $params['leaguename'] = $this->mLeagueInfo['name'];//获取公会名称
            D('GMail')->send($mail_id, $this->mTid, $this->mTid, $params);
        }

        //记录伙伴当前情况
        $partnerList = $_POST['current_partner'];
        foreach ($partnerList as $key => $value) {
            if ($result == 1) {
                $hp = $value['hp'];
                $xp = $value['xp'];
            } else {
                $hp = 0;
                $xp = 0;
            }
            if (false === D('GLeagueBattlePartner')->record($this->mTid, $key, $hp, $xp)) {
                goto end;
            }
        }

        //记录飞船当前情况
        $airship = $_POST['current_airship'];
        $xp = $result == 1 ? $airship[$this->mBattleInfo['airship']]['xp'] : 0;
        if (false === D('GLeagueBattleAirship')->record($this->mTid, $this->mBattleInfo['airship'], $xp)) {
            goto end;
        }

        if (!$this->end($result)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        if ($result == 1) {

            //查询公会据点是否已经全部打完
            $where = array();
            $where['league_id'] = $this->mLeagueInfo['id'];
            $where['status'] = 1;
            //查询该公会当前完成的副本
            $select = M('GLeagueBattle')->field('instance')->where($where)->select();
            foreach ($select as $value)
                $complete[] = (int)$value['instance'];

            //查询所有副本
            $battleConfig = D('Static')->access('instance_info_map', $this->mEventInfo['map']);
            foreach ($battleConfig as $value)
                $all[] = (int)$value['index'];

            sort($complete);
            sort($all);

            //如果全部完成
            if ($complete === $all) {
                //活动结束
                $this->mEventInfo['league'] = $this->mLeagueInfo['id'];
                $ps = json_encode($this->mEventInfo);
                D('Predis')->cli('game')->set('event:' . self::GROUP . ':status', '0');
                D('Predis')->cli('game')->del('event:' . self::GROUP . ':index');
                D('Predis')->cli('game')->del('event:' . self::GROUP . ':ps');
                //活动结束
                $where['group'] = self::GROUP;
                $where['status'] = 1;
                $data['ps'] = $ps;
                $data['status'] = 2;
                D('GEvent')->UpdateData($data, $where);
                //记录结果
                $data['league_id'] = $this->mLeagueInfo['id'];
                $data['league_name'] = $this->mLeagueInfo['name'];
                $data['idol_tid'] = 0;
                D('LLeagueBattleResult')->CreateData($data);
                //开启英雄商店
                $hero['tid'] = '0';
                $hero['type'] = '202';
                $dtime = D('Static')->access('shop', $hero['type'], 'show_time');
                $hero['dtime'] = time() + ($dtime * 60);
                D('GShop')->CreateData($hero);
                //开启神像
                $this->createIdol();
            }

        } else {
            return false;
        }

        //返回获得道具
        $itemList = json_decode($this->mBattleInfo['drop_item'], true);
        if (empty($itemList)) {
            $list = array();
        } else {
            foreach ($itemList as $key => $value) {
                $drop['item'] = $key;
                $drop['count'] = $value;
                $list[] = $drop;
            }
        }
        return $list;

    }

    //战斗失败
    public function lose()
    {

        $result = 0;

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //战役是否超时
        $limit_time = D('Static')->access('instance_info_map', $this->mEventInfo['map'], array($_POST['instance_id'], 'activate_period',));

        //查询战役信息
        $where['league_id'] = $this->mLeagueInfo['id'];
        $where['instance'] = $_POST['instance_id'];
        $where['last_tid'] = $this->mTid;
        $this->mBattleInfo = D('GLeagueBattle')->getRowCondition($where);
        if ($this->mBattleInfo['status'] != '3') {
            C('G_ERROR', 'league_battle_timeout');
        }
        //战斗超时
        if (empty($this->mBattleInfo)) {
            C('G_ERROR', 'league_battle_timeout');
        }

        if (time() > ($this->mBattleInfo['utime'] + $limit_time)) {
            C('G_ERROR', 'league_battle_timeout');
        }

        //开始事务
        $this->transBegin();

        //记录伙伴当前情况
        $partnerList = json_decode($this->mBattleInfo['partner'], true);
        foreach ($partnerList as $value) {
            if (false === D('GLeagueBattlePartner')->record($this->mTid, $value, 0, 0)) {
                goto end;
            }
        }

        //记录飞船当前情况
        if (false === D('GLeagueBattleAirship')->record($this->mTid, $this->mBattleInfo['airship'], 0)) {
            goto end;
        }

        if (!$this->end($result)) {
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

    //战斗结束
    private function end($result)
    {

        //记录怪物当前情况&改变据点状态
        if ($result == 1) {
            $where['league_id'] = $this->mLeagueInfo['id'];
            $where['instance'] = $_POST['instance_id'];
            $data['hold_tid'] = $this->mTid;
            $data['status'] = 1;
        } else if ($result == 0) {
            if (!$this->useEventCount()) {
                return false;
            }//记录使用
            $where['league_id'] = $this->mLeagueInfo['id'];
            $where['instance'] = $_POST['instance_id'];
            $data['battle'] = $_POST['battle'];
            $data['monster'] = json_encode($_POST['monster']);
            $data['status'] = 0;
        } else if ($result == -1) {
            if (!$this->useEventCount()) {
                return false;
            }//记录使用
            $where['league_id'] = $this->mLeagueInfo['id'];
            $where['instance'] = $_POST['instance_id'];
            $data['status'] = 0;
        }
        if (false === D('GLeagueBattle')->UpdateData($data, $where)) {
            return false;
        }

        //发放奖励
        $contribution = D('Static')->access('params', 'LEAGUE_AREA_BATTLE_CONTRIBUTION');
        if (!$this->produce('contribution', $contribution, $this->mLeagueInfo['id'])) {
            return false;
        }

        //记录战斗日志
        $log['tid'] = $this->mTid;
        $log['league_id'] = $this->mLeagueInfo['id'];
        $log['instance'] = $_POST['instance_id'];
        $log['partner'] = field2string($this->mBattleInfo['partner']);
        $log['airship'] = $this->mBattleInfo['airship'];
        $log['result'] = $result;
        D('LLeagueBattle')->CreateData($log);

        //作弊日志
        if ($result == -1) {
            D('LCheat')->cLog($this->mTid);
        }

        return true;

    }

    //购买挑战次数
    public function buy()
    {

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //查询信息
        $target = $_POST['target_tid'] == '0' ? $this->mTid : $_POST['target_tid'];

        //查看对方是不是本公会
        if ($target != $this->mTid) {
            if ($this->mSessionInfo['league_id'] != $this->mLeagueInfo['id']) {
                C('G_ERROR', 'target_not_in_league');
                return false;
            }
        }

        //获取购买信息
        $count = $this->mEventBuyCount + 1;

        //查询购买需要的货币
        $exchange = $this->exchangeMoney($this->mEventConfig['exchange'], $count);
        $needType = $exchange['needType'];
        $needValue = $exchange['needValue'];

        //检查玩家当前货币是否足够
        if (!$now = $this->verify($needValue, $this->mMoneyType[$needType])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣除货币
        if (!$this->recover($this->mMoneyType[$needType], $needValue, null, $now)) {
            goto end;
        }

        //记录购买
        if (!$this->buyEventCount($target)) {
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

    //获取神像信息
    public function getIdol()
    {
        $idol = D('Predis')->cli('game')->hgetall('idol');
        if (empty($idol)) {
            $idol = $this->createIdol();
        }
        return $idol;
    }

    //创建神像信息
    private function createIdol()
    {
        //查询最近一场的信息
        $info = M('LLeagueBattleResult')->order('`ctime` DESC')->find();
        $idol['league_id'] = $info['league_id'];
        $idol['league_name'] = $info['league_name'];
        if ($info['league_id'] == '0') {
            $idol['tid'] = 0;
            $idol['nickname'] = '';
        } else {
            if ($info['idol_tid'] == '0') {
                $idol['tid'] = D('GLeague')->getAttr($info['league_id'], 'president_tid');
            } else {
                $idol['tid'] = $info['idol_tid'];
            }
            $idol['nickname'] = D('GTeam')->getAttr($idol['tid'], 'nickname');
        }
        D('Predis')->cli('game')->del('idol');
        D('Predis')->cli('game')->hmset('idol', $idol);
        return $idol;
    }

    //激活神像
    public function activationIdol()
    {
        //检查权限
        if (false == $leagueId = $this->leaguePermission($this->mTid)) {
            return false;
        }

        //查询最近一场的信息
        $resultInfo = M('LLeagueBattleResult')->order('`ctime` DESC')->find();
        if ($leagueId != $resultInfo['league_id']) {
            C('G_ERROR', 'league_battle_lose');
            return false;
        }

        //查看公会活动状态
        if ($resultInfo['idol_tid'] > 0) {
            C('G_ERROR', 'league_battle_idol_activation');
            return false;
        }

        //查询公会开启所需水晶
        $need_diamond = D('Static')->access('params', 'CONSUME_ACTIVATION_IDOL_DIAMOND');

        //查看是否有足够的水晶
        if (!$diamond = $this->verify($need_diamond, 'diamond')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣除货币
        if (!$this->recover('diamond', $need_diamond, null, $diamond)) {
            goto end;
        }

        //开启神像
        $data['id'] = $resultInfo['id'];
        $data['idol_tid'] = $this->mTid;
        if (!D('LLeagueBattleResult')->UpdateData($data)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //更新redis神像信息
        $this->createIdol();

        //全服公告
        $send_tid = $this->mTid;
        $msg = '神像已激活';
        $level = D('Static')->access('params', 'NEED_WORSHIP_LEVEL');
        D('GChat')->sendNoticeMsg($send_tid, $msg, $level);

        return true;

    }

    //参拜神像
    public function worship()
    {

        //获取神像信息
        $idol = $this->getIdol();
        if ($idol['tid'] == '0') {
            C('G_ERROR', 'league_battle_idol_not_activation');
            return false;
        }

        //查看等级是否足够
        $need_level = D('Static')->access('params', 'NEED_WORSHIP_LEVEL');

        //查看是否有足够的水晶
        if (!$level = $this->verify($need_level, 'level')) {
            return false;
        }

        //查看是否已经领取
        $time = get_daily_utime();
        $count = D('TDailyCount')->getCount($this->mTid, 4);
        if ($count > 0) {
            C('G_ERROR', 'league_battle_already_worship_today');
            return false;
        }

        //开始事务
        $this->transBegin();

        //加金币
        $gold = D('Static')->access('params', 'WORSHIP_REW_GOLD');
        if (!$this->produce('gold', $gold)) {
            goto end;
        }

        //加体力
        $vality = D('Static')->access('params', 'WORSHIP_REW_VALITY');
        if (!$this->produce('vality', $vality)) {
            goto end;
        }

        //记录
        if (!D('TDailyCount')->record($this->mTid, 4)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        return true;

    }

}