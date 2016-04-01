<?php
namespace Home\Api;

use Think\Controller;

class LeagueBossApi extends BBattleApi
{

    private $mLeagueId;//公会ID

    public function _initialize()
    {
        parent::_initialize();
        //检查公会情况
        $this->mLeagueId = $this->mSessionInfo['league_id'];
        if (empty($this->mLeagueId)) {
            C('G_ERROR', 'league_not_attended');
            exit;
        }
        return;
    }

    //获取BOSS战信息
    public function getList()
    {

        //联盟ID
        $return['league_id'] = $this->mLeagueId;

        //获取联盟信息
        $return['boss_level'] = D('GLeague')->getAttr($this->mLeagueId, 'boss_level');
        $return['activity'] = D('GLeague')->getActivity($this->mLeagueId);

        //成员信息
        $info = D('GLeagueTeam')->getRow($this->mTid, array('position', 'boss_buff'));
        $return = array_merge($return, $info);

        //返回
        $list = array();

        //获取redis中所有已经生成的boss实例
        $keysList = D('Predis')->cli('fight')->keys('lb:s:' . $this->mLeagueId . ':*');

        //获取公会BOSS当天可挑战次数
        $count = D('Static')->access('params', 'LEAGUE_BOSS_COUNT');

        //获取今天公会boss挑战次数
        $countList = D('TDailyCount')->getLeagueBossCountList($this->mTid);

        //遍历获取信息
        if (!empty($keysList)) {
            foreach ($keysList as $value) {
                $info = D('Predis')->cli('fight')->hgetall($value);
                $arr = array();
                $arr['site'] = $info['site'];
                $remain = $count - $countList[$info['site']];//计算剩余挑战次数
                $arr['count'] = $remain >= 0 ? $remain : 0;
                $arr['boss'] = $info['boss'];
                $arr['hp'] = $info['hp'];
                $arr['damage'] = $info['damage'];
                $arr['rank'] = json_decode($info['rank'], true);
                $list[] = $arr;
            }
        }

        //返回
        $return['list'] = $list;
        return $return;
    }

    //生成BOSS
    private function createBoss($site, $isForce = false)
    {
        //清除玩家伤害
        $keys = D('Predis')->cli('fight')->keys('lb:t:' . $this->mLeagueId . ':' . $site . ':*');
        if (!empty($keys)) {
            D('Predis')->cli('fight')->del($keys);
        }

        //获取槽位配置
        $bossConfig = D('Static')->access('league_boss_group', $site);
        $bossConfig = array_values($bossConfig);

        //随机副本
        $rand = rand(0, (count($bossConfig) - 1));
        $bossConfig = $bossConfig[$rand];

        //获取副本信息
        $instanceConfig = D('Static')->access('instance_info', $bossConfig['instance_info']);

        //BOSS信息
        $siteInfo['site'] = (int)$site;//BOSS槽位
        $siteInfo['boss'] = $bossConfig['index'];//boss.index
        $siteInfo['isForce'] = $isForce ? 1 : 0;//是否为强制召唤
        $siteInfo['instance_info'] = $bossConfig['instance_info'];//instance
        $siteInfo['monster'] = $instanceConfig['battle_1_monster'];//BOSS
        $siteInfo['damage'] = 0;//总伤害
        $siteInfo['rank'] = '[]';//排名
        $loot = $this->loot($instanceConfig);//掉落物品
        $siteInfo['drop'] = json_encode($loot['box']);//掉落物品

        //获取公会成员
        $memberList = D('GLeagueTeam')->where("`league_id`='{$this->mLeagueId}'")->getField('tid', true);
        $where['tid'] = array('in', $memberList);
        //计算公会战力
        if (count($memberList) >= 10) {
            $leagueForce = D('GCount')->where($where)->order('`force` DESC')->limit(10)->avg('force_top');
        } else {
            $leagueForce = D('GCount')->where($where)->max('force_top');
        }
        $leagueForce = (int)round($leagueForce);
        //获取怪物HP
        $siteInfo['hp'] = lua('league_boss', 'league_boss_hp', array($siteInfo['site'], $leagueForce,));//总血量

        //资源消耗
        if (!$isForce) {

            //获取当前活跃度
            $activityNow = D('GLeague')->getActivity($this->mLeagueId);
            if ($activityNow < $bossConfig['active_value']) {
                C('G_ERROR', 'league_activity_not_enough');
            }

            //扣除活跃度
            $after = D('Predis')->cli('game')->hincrby('lg:' . $this->mLeagueId, 'activity', -$bossConfig['active_value']);
            if ($after < 0) {
                D('Predis')->cli('game')->hincrby('lg:' . $this->mLeagueId, 'activity', $bossConfig['active_value']);
                C('G_ERROR', 'league_activity_not_enough');
            }

            //记录日志
            $log['league_id'] = $this->mLeagueId;
            $log['attr'] = 'activity';
            $log['value'] = $bossConfig['active_value'];
            $log['before'] = $after + $bossConfig['active_value'];
            $log['after'] = $after;
            $log['behave'] = C('G_BEHAVE') > 0 ? C('G_BEHAVE') : get_config('behave', array(C('G_BEHAVE'), 'code',));//获取改变原因
            D('LLeague')->CreateData($log);//日志

        } else {

            //检查水晶是否足够
            if (false === $diamondNow = $this->verify($bossConfig['boss_reset_diamond'], 'diamond')) {
                return false;
            }

            //消耗水晶
            if (false === $this->recover('diamond', $bossConfig['boss_reset_diamond'])) {
                return false;
            }

            //发放强制召唤奖励
            if (false === $this->bonus($bossConfig, 'boss_reset_')) {
                return false;
            }

            //获取邮件ID
            $mailId = D('Static')->access('params', 'LEAGUE_BOSS_RESET_DIAMOND_MAIL');

            //邮件参数
            $params['nickname'] = D('GTeam')->getAttr($this->mTid, 'nickname');
            $params['monstername'] = D('Static')->access('monster', $siteInfo['monster'], 'name');

            //发送公会邮件
            $mailList = $this->sendLeagueMail($mailId, $params);
            D('GMail')->sendAll($mailList);

        }

        //写入redis
        D('Predis')->cli('fight')->hmset('lb:s:' . $this->mLeagueId . ':' . $site, $siteInfo);

        //返回
        return $siteInfo;
    }

    //开始副本
    public function fight()
    {

        //检查BOSS是否开放
        $siteInfo = D('Predis')->cli('fight')->hgetall('lb:s:' . $this->mLeagueId . ':' . $_POST['site']);//开放时间
        if (empty($siteInfo) || $siteInfo['damage'] >= $siteInfo['hp']) {
            C('G_ERROR', 'league_boss_lock');
            return false;
        }

        //获取公会BOSS当天可挑战次数
        $count = D('Static')->access('params', 'LEAGUE_BOSS_COUNT');

        //检查次数
        $fight = D('TDailyCount')->getCount($this->mTid, (100 + $_POST['site']));
        if ($count - $fight <= 0) {
            C('G_ERROR', 'league_boss_fight_max_today');
            return false;
        }

        //记录挑战
        D('GCount')->incAttr($this->mTid, 'league_boss');

        //创建副本
        $return = $this->instanceFight('LeagueBoss', $siteInfo['instance_info'], $_POST['partner']);
        $return['damage'] = D('Predis')->cli('fight')->hget('lb:s:' . $this->mLeagueId . ':' . $_POST['site'], 'damage');//获取怪物伤害值
        $return['buff'] = D('GLeagueTeam')->getAttr($this->mTid, 'boss_buff');
        return $return;

    }

    //副本结束
    public function end()
    {

        //检查BOSS是否开放
        $siteInfo = D('Predis')->cli('fight')->hgetall('lb:s:' . $this->mLeagueId . ':' . $_POST['site']);
        $leagueBossConfig = D('Static')->access('league_boss', $siteInfo['boss']);

        /*//获取玩家最强小队战力
        $forceTop = D('GCount')->getAttr($this->mTid, 'force_top');

        //伤害检测
        $damageVerify = lua('league_boss', 'league_boss_alert', array((int)$siteInfo['monster'], (int)$forceTop,));
        if ($_POST['damage'] > $damageVerify) {
            if (APP_STATUS != 'local') {
                think_send_mail('error_report@forevergame.com', 'error_report', 'LEAGUE_BOSS_DAMAGE_ERROR(' . APP_STATUS . ')', $this->mTid . '&' . $siteInfo['monster'] . '&' . $_POST['damage'] . '&' . $forceTop);
            }
            $_POST['damage'] = 0;
        }*/

        //胜利逻辑
        if (!$this->instanceEnd($siteInfo['instance_info'])) {
            if (C('G_ERROR') == 'instance_error') {
                C('G_ERROR', 'league_boss_reborn');
            }
            return false;
        }

        //玩家是否是BOSS本次的最后击杀者
        $isKiller = false;

        //增加BOSS伤害
        $realDamage = 0;
        $damageAll = D('Predis')->cli('fight')->hincrby('lb:s:' . $this->mLeagueId . ':' . $_POST['site'], 'damage', $_POST['damage']);
        $hpAll = $siteInfo['hp'];

        //查看血量是否溢出
        if ($damageAll < $hpAll) {
            //非击杀情况
            $realDamage = $_POST['damage'];//实际伤害
        } else {
            //击杀情况
            if ($damageAll - $hpAll < $_POST['damage']) {
                $isKiller = true;//击杀标识
                $realDamage = $_POST['damage'] - ($damageAll - $hpAll);//去除溢出伤害
                //记录击杀
                D('GCount')->incAttr($this->mTid, 'league_boss_kill');
            }
            //去除伤害溢出
//            D('Predis')->cli('fight')->hset('lb:s:' . $this->mLeagueId . ':' . $_POST['site'], 'damage', $hpAll);//设置伤害
        }

        //增加伤害
        if ($realDamage > 0) {
            D('Predis')->cli('fight')->incrby('lb:t:' . $this->mLeagueId . ':' . $_POST['site'] . ':' . $this->mTid, $realDamage);//设置伤害
        }

        //开始事务
        $this->transBegin();

        //扣除战斗次数
        if (false === D('TDailyCount')->record($this->mTid, (100 + $_POST['site']))) {
            goto end;
        }

        //扣除buff
        if (false === D('GLeagueTeam')->decAttr($this->mTid, 'boss_buff', 1)) {
            goto end;
        }

        //奖励宝箱
        $bonus = lua('league_boss', 'league_boss_everyrew', array((int)$_POST['damage'], (int)$siteInfo['monster']));

        //固定贡献度奖励
        if (false === $this->produce('contribution', $bonus[0])) {
            goto end;
        }

        //金币
        if ($bonus[0] > 0) {
            if (!$this->produce('gold', $bonus[1])) {
                goto end;
            }
        }

        //道具
        if ($bonus[1] > 0) {
            if (!$reward = $this->produce('box', $bonus[1], 1)) {
                goto end;
            }
        }

        //击杀者
        $dropItemList = array();
        if ($isKiller) {

            //强行重新排名
            $this->rank();

            //公会资金
            if (!D('GLeague')->incAttr($this->mLeagueId, 'fund', $leagueBossConfig['boss_bonus'])) {
                goto end;
            }

            //发放掉落奖励
            if (false === $dropItemList = $this->getLoot($siteInfo['drop'], 'LeagueBoss')) {
                goto end;
            }

            //记录击杀
            $log['league_id'] = $this->mLeagueId;
            $log['site'] = $_POST['site'];
            $log['last_tid'] = $this->mTid;
            $log['rank_list'] = D('Predis')->cli('fight')->hget('lb:s:' . $this->mLeagueId . ':' . $_POST['site'], 'rank');
            $log['drop'] = json_encode($dropItemList);
            D('LLeagueBoss')->CreateData($log);

            //发送击杀公告
            $params['league_name'] = D('GLeague')->getAttr($this->mLeagueId, 'name');
            $params['monstername'] = D('Static')->access('monster', $siteInfo['monster'], 'name');
            $noticeConfig = D('SEventString')->getConfig('LEAGUE_BOSS_TIPS', $params);
            D('GChat')->sendNoticeMsg($this->mTid, $noticeConfig['des'], $noticeConfig['show_level']);

            //获取排名奖励配置
            $rankBonusConfig = D('Static')->access('rank_bonus', 100 + $_POST['site']);

            //获取当前排行榜
            $rankList = json_decode($log['rank_list'], true);

            //发送排行奖励邮件
            $mailList = array();
            foreach ($rankBonusConfig as $value) {
                for ($i = $value['rank_start']; $i <= $value['rank_end']; ++$i) {
                    $receiveTid = $rankList[$i - 1]['tid'];
                    if (isset($receiveTid)) {
                        $mail = array();
                        $mail['mail_id'] = $value['rank_mail'];
                        $mail['tid'] = $this->mTid;
                        $mail['target_tid'] = $receiveTid;
                        $mail['params']['rank'] = $i;
                        if (1 <= $i && $i <= 5) {
                            $paramsLeagueMail['Player_Name' . $i] = D('GTeam')->getAttr($receiveTid, 'nickname');
                        }
                        $mailList[] = $mail;
                    }
                }
            }

            //获取邮件ID
            $mailId = D('Static')->access('params', 'LEAGUE_BOSS_DEATH_MAIL');

            //发送公会邮件
            $paramsLeagueMail['nickname'] = D('GTeam')->getAttr($this->mTid, 'nickname');
            $paramsLeagueMail['league_gold'] = $leagueBossConfig['boss_bonus'];//公会资金
            $leagueMailList = $this->sendLeagueMail($mailId, $paramsLeagueMail);

            //与其他邮件合并
            $mailList = array_merge($mailList, $leagueMailList);

            //返回
            if (false === D('GMail')->sendAll($mailList)) {
                goto end;
            }

        } else {
            //查看排名情况
            if ($realDamage > 0) {
                $this->rank($siteInfo['rank']);
            }
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        $return['gold'] = $bonus[0];
        $return['drop'] = $reward;
        $return['kill'] = $dropItemList;
        return $return;

    }

    //重新排名
    private function rank($list = null)
    {

        //标识是否需要更新
        $isNeed = false;

        //如果有排名列表
        if (!empty($list)) {

            $list = json_decode($list, true);

            //获取玩家当前最新伤害值
            $myDamage = D('Predis')->cli('fight')->get('lb:t:' . $this->mLeagueId . ':' . $_POST['site'] . ':' . $this->mTid);

            //如果排行榜
            if (count($list) < 10) {
                $isNeed = true;
            } else {
                //遍历排名并查看是否需要更新排名
                foreach ($list as $value) {
                    //如果排行榜中有自己则更新
                    if ($value['tid'] == $this->mTid) {
                        $isNeed = true;
                        break;
                    }
                    //如果玩家伤害比排行榜上的人高则更新
                    if ($value['damage'] < $myDamage) {
                        $isNeed = true;
                        break;
                    }
                }
            }

        } else {
            $isNeed = true;
        }

        //如果不需要重新排名
        if ($isNeed == false) {
            return;
        }

        //重新排名
        $keys = D('Predis')->cli('fight')->keys('lb:t:' . $this->mLeagueId . ':' . $_POST['site'] . ':*');
        foreach ($keys as $value) {
            $damage = D('Predis')->cli('fight')->get($value);
            $arr = explode(':', $value);
            $tid = end($arr);
            $playerList[$damage] = array(
                'tid' => $tid,
                'damage' => $damage,
            );
        }

        //排序&Top10
        krsort($playerList);
        $playerList = array_values($playerList);
        $playerList = array_slice($playerList, 0, 5);

        //获取排行榜tid
        foreach ($playerList as $value) {
            $tidList[] = $value['tid'];
        }

        //查新玩家昵称&等级
        $field = array('tid', 'nickname', 'level',);
        $where['tid'] = array('in', $tidList);
        $select = M('GTeam')->field($field)->where($where)->select();
        $teamList = array();
        foreach ($select as $value) {
            $teamList[$value['tid']] = array(
                'nickname' => $value['nickname'],
                'level' => $value['level'],
            );
        }

        //合并信息
        foreach ($playerList as $key => $value) {
            $playerList[$key] = array_merge($value, $teamList[$value['tid']]);
        }

        //排行榜写入内存
        D('Predis')->cli('fight')->hset('lb:s:' . $this->mLeagueId . ':' . $_POST['site'], 'rank', json_encode($playerList));
        return;

    }

    //普通召唤
    public function call()
    {
        //检查权限
        if (false == $this->leaguePermission($this->mTid, 2)) {
            return false;
        }

        //获取当前公会BOSS情况
        $siteInfo = D('Predis')->cli('fight')->hgetall('lb:s:' . $this->mLeagueId . ':' . $_POST['site']);
        if ($siteInfo['damage'] < $siteInfo['hp']) {
            C('G_ERROR', 'league_boss_alive');
            return false;
        }

        //获取BOSS建筑等级
        $bossLevel = D('GLeague')->getAttr($this->mLeagueId, 'boss_level');
        $siteMax = D('Static')->access('league_bossgroove', $bossLevel, 'boss_site');
        if ($siteMax < $_POST['site']) {
            C('G_ERROR', 'league_boss_site_lock');
            return false;
        }

        //开始事务
        $this->transBegin();

        //创建boss(扣钱&发奖)
        if (false === $info = $this->createBoss($_POST['site'])) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //获取公会BOSS当天可挑战次数
        $count = D('Static')->access('params', 'LEAGUE_BOSS_COUNT');

        //获取今天公会boss挑战次数
        $countToday = D('TDailyCount')->getCount($this->mTid, 100 + $info['site']);

        //整理怪物信息
        $return = array();
        $return['site'] = $info['site'];
        $remain = $count - $countToday;//计算剩余挑战次数
        $return['count'] = $remain >= 0 ? $remain : 0;
        $return['boss'] = $info['boss'];
        $return['hp'] = $info['hp'];
        $return['damage'] = $info['damage'];
        $return['rank'] = json_decode($info['rank'], true);

        //返回
        return $return;

    }

    //强制召唤
    public function callForce()
    {
        //获取当前公会BOSS情况
        $siteInfo = D('Predis')->cli('fight')->hgetall('lb:s:' . $this->mLeagueId . ':' . $_POST['site']);
        if ($siteInfo['damage'] < $siteInfo['hp']) {
            C('G_ERROR', 'league_boss_alive');
            return false;
        }

        //获取BOSS建筑等级
        $bossLevel = D('GLeague')->getAttr($this->mLeagueId, 'boss_level');
        $siteMax = D('Static')->access('league_bossgroove', $bossLevel, 'boss_site');
        if ($siteMax >= $_POST['site']) {
            C('G_ERROR', 'league_boss_site_unlock');
            return false;
        }

        //获取VIP情况
        $vipId = D('GVip')->getAttr($this->mTid, 'index');

        //检查可越级范围
        $siteOver = D('Static')->access('vip', $vipId, 'boss_reset');
        if ($siteMax + $siteOver < $_POST['site']) {
            C('G_ERROR', 'vip_level_low');
            return false;
        }

        //开始事务
        $this->transBegin();

        //创建boss(扣钱&发奖)
        if (false === $info = $this->createBoss($_POST['site'], true)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //获取公会BOSS当天可挑战次数
        $count = D('Static')->access('params', 'LEAGUE_BOSS_COUNT');

        //获取今天公会boss挑战次数
        $countToday = D('TDailyCount')->getCount($this->mTid, 100 + $info['site']);

        //整理怪物信息
        $return = array();
        $return['site'] = $info['site'];
        $remain = $count - $countToday;//计算剩余挑战次数
        $return['count'] = $remain >= 0 ? $remain : 0;
        $return['boss'] = $info['boss'];
        $return['hp'] = $info['hp'];
        $return['damage'] = $info['damage'];
        $return['rank'] = json_decode($info['rank'], true);

        //返回
        return $return;
    }

    //发送全公会邮件
    public function sendLeagueMail($mailId, $params)
    {
        //获取联盟成员列表
        $tidList = D('GLeagueTeam')->where("`league_id`='{$this->mLeagueId}'")->getField('tid', true);

        //发送公会邮件
        $mailList = array();
        foreach ($tidList as $tid) {
            $mail = array();
            $mail['mail_id'] = $mailId;
            $mail['tid'] = $this->mTid;
            $mail['target_tid'] = $tid;
            $mail['params'] = $params;
            $mailList[] = $mail;
        }
        return $mailList;
    }

    //单人buff
    public function buff()
    {
        //查询当前值
        $buffNow = D('GLeagueTeam')->getAttr($this->mTid, 'boss_buff');

        //查询自己是否已经达到buff最大值
        $buffMax = D('Static')->access('params', 'LEAGUE_BOSS_SKILL_MAX');
        if ($buffNow >= $buffMax) {
            C('G_ERROR', 'league_boss_buff_max');
            return false;
        }

        //获取单人鼓舞所需贡献度
        $need = D('Static')->access('params', 'LEAGUE_BOSS_SKILL_SINGLE');

        //检查水晶是否足够
        if (false === $diamondNow = $this->verify($need, 'diamond')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣除水晶
        if (false === $this->recover('diamond', $need, null, $diamondNow)) {
            goto end;
        }

        //加buff
        if (false === D('GLeagueTeam')->incAttr($this->mTid, 'boss_buff', 1, $buffNow)) {
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

    //全体buff
    public function buffAll()
    {
        //全体buff所需水晶
        $need = D('Static')->access('params', 'LEAGUE_BOSS_SKILL_DIAMOND_ALL');

        //检查贡献度是否足够
        if (false === $diamond = $this->verify($need, 'diamond')) {
            return false;
        }

        //查询自己是否已经达到buff最大值
        $buffMax = D('Static')->access('params', 'LEAGUE_BOSS_SKILL_MAX');

        //开始事务
        $this->transBegin();

        //扣除水晶
        if (false === $this->recover('diamond', $need, null, $diamond)) {
            goto end;
        }

        //获取所有在最大值以下的用户tid
        $where['league_id'] = $this->mLeagueId;
        $where['boss_buff'] = array('lt', $buffMax);
        $select = D('GLeagueTeam')->field(array('tid', 'boss_buff'))->where($where)->select();

        //加属性
        $where = array();
        $tidList = array();
        $all = array();
        $now = time();
        foreach ($select as $value) {
            $tidList[] = $value['tid'];
            $arr = array();
            $arr['tid'] = $value['tid'];
            $arr['league_id'] = $this->mLeagueId;
            $arr['attr'] = 'boss_buff';
            $arr['value'] = 1;
            $arr['before'] = $value['boss_buff'];
            $arr['after'] = $arr['value'] + $arr['before'];
            $arr['behave'] = C('G_BEHAVE');
            $arr['ctime'] = $now;
            $all[] = $arr;
        }
        $where['tid'] = array('in', $tidList);
        if (false === D('GLeagueTeam')->IncreaseData($where, 'boss_buff', 1)) {
            goto end;
        }

        //记录
        if (false === D('LLeagueTeam')->CreateAllData($all)) {
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