<?php
namespace Home\Api;

use Think\Controller;

class LeagueFightApi extends BEventApi
{

    const GROUP = 15;
    const HOLD_NUM = 3;
    const KIT_NUM = 3;
    const BUFF_NUM = 2;

    private $mAutoAdd;//自动加次数
    private $mLeagueId;//公会ID
    private $mInfo;//公会数据
    private $mLeagueInfo;//公会数据
    private $mTargetLeagueInfo = array();//对手公会数据

    private $mTeamKey;//战队redis-key
    private $mLeagueKey;//公会redis-key
    private $mTargetLeagueKey;//对手公会redis-key

    private $mMaxAssault;//最大鼓舞

    public function _initialize()
    {

        parent::_initialize();

        //查询玩家是否已经参加公会
        $this->mLeagueId = $this->mSessionInfo['league_id'];
        if (empty($this->mLeagueId)) {
            C('G_ERROR', 'league_not_attended');
            exit;
        }

        //redis-key
        $this->mTeamKey = 'lf:t:' . $this->mTid . ':' . $this->mLeagueId;

        //匹配信息
        $this->mInfo = D('Predis')->cli('fight')->hgetall($this->mTeamKey);
        if (D('Predis')->cli('fight')->exists('lf:end') && empty($this->mInfo)) {
            //查询信息
            $team = D('GTeam')->getRow($this->mTid, array('nickname', 'level'));

            //创建信息
            $info['tid'] = $this->mTid;
            $info['nickname'] = $team['nickname'];
            $info['level'] = $team['level'];
            $info['auto_add'] = 0;//自动增加次数
            $info['buy'] = 0;//购买次数
            $info['used'] = 0;//使用次数
            $info['damage'] = 0;//造成伤害
            $info['hold'] = 0;//正在攻打据点ID(战场表现用)
            $info['utime'] = time();//最近自动增加次数时间戳

            //buff使用情况
            $info['assault_used'] = 0;//突击使用次数
            for ($i = 1; $i <= self::BUFF_NUM; ++$i) {
                $info['buff' . $i . '_used'] = 0;//buff使用次数
            }

            //锦囊数量
            for ($i = 1; $i <= self::KIT_NUM; ++$i) {
                $info['k' . $i] = 0;
            }

            //覆盖变量
            $this->mInfo = $info;

            //写入内存
            D('Predis')->cli('fight')->hmset($this->mTeamKey, $info);
        }

        //查看是否到了最大购买次数
        $this->mMaxAssault = D('Static')->access('params', 'LEAGUE_BATTLE_COUNT_ALLBUY_MAX');

        //查询联盟信息
        $this->mLeagueKey = 'lf:l:' . $this->mLeagueId;
        $this->mLeagueInfo = D('Predis')->cli('fight')->hgetall($this->mLeagueKey);
        if (!empty($this->mLeagueInfo)) {
            $this->mTargetLeagueKey = 'lf:l:' . $this->mLeagueInfo['target_league_id'];
            $this->mTargetLeagueInfo = D('Predis')->cli('fight')->hgetall($this->mTargetLeagueKey);

            $this->mLeagueInfo['assault'] = $this->mLeagueInfo['assault'] > $this->mMaxAssault ? $this->mMaxAssault : $this->mLeagueInfo['assault'];
            for ($i = 1; $i <= self::HOLD_NUM; ++$i) {
                $this->mLeagueInfo['h:' . $i . ':damage'] = $this->mLeagueInfo['h:' . $i . ':damage'] >= 0 ? $this->mLeagueInfo['h:' . $i . ':damage'] : 0;
                $this->mTargetLeagueInfo['h:' . $i . ':damage'] = $this->mTargetLeagueInfo['h:' . $i . ':damage'] >= 0 ? $this->mTargetLeagueInfo['h:' . $i . ':damage'] : 0;
            }
        }

        return true;

    }

    private function eventVerify()
    {
        //活动限制
        if (!$this->event(self::GROUP)) {
            return false;
        }

        //战斗未开始
        if (empty($this->mLeagueInfo)) {
            C('G_ERROR', 'league_not_join_fight');
            return false;
        }

        //检查战斗是否已经结束
//        if ($this->mLeagueInfo['result'] == '1') {
//            C('G_ERROR', 'league_fight_win');
//            return false;
//        } else if ($this->mLeagueInfo['result'] == '0') {
//            C('G_ERROR', 'league_fight_lose');
//            return false;
//        }

        //购买次数
        $this->mEventBuyCount = $this->mInfo['buy'];

        //总次数
        $this->mEventRemainCount = $this->mEventConfig['count'] + $this->mInfo['auto_add'] + $this->mInfo['buy'] - $this->mInfo['used'];
        $this->mEventRemainCount = $this->mEventRemainCount >= 0 ? $this->mEventRemainCount : 0;

        //自动加次数
        $this->mAutoAdd = $this->autoAdd();

        //重新计算剩余次数
        $this->mEventRemainCount = $this->mEventRemainCount + $this->mAutoAdd['add'];

        //返回
        return true;
    }

    //查询公会成员战斗信息
    public function getTargetInfo()
    {
        //活动开放标识
        $return['open'] = $this->isOpen(self::GROUP) ? 1 : 0;

        //获取匹配信息
        if (empty($this->mLeagueInfo)) {

            //查询公会排名信息
            $return['info']['rank'] = D('GLeagueRank')->getRankLeague($this->mLeagueId);
            $return['info']['point'] = D('GLeague')->getAttr($this->mLeagueId, 'point');

            //我方信息
            $return['fight']['league_id'] = $this->mLeagueId;
            $info = D('GLeague')->getRow($this->mLeagueId, array('name', 'center_level'));
            $return['fight']['league_name'] = $info['name'];
            $return['fight']['league_level'] = $info['center_level'];
            $return['fight']['league_count'] = D('GLeagueTeam')->getMemberCount($this->mLeagueId);

            //敌方信息
            $return['fight']['target_league_id'] = 0;
            $return['fight']['target_league_name'] = '';
            $return['fight']['target_league_level'] = 0;
            $return['fight']['target_league_rank'] = 0;
            $return['fight']['target_league_point'] = 0;

            //敌方据点副本ID
            for ($i = 1; $i <= self::HOLD_NUM; ++$i) {
                $return['fight']['target_league_hold_' . $i] = 0;
            }

            //相对我放状态
            $return['fight']['result'] = 2;

        } else {

            //查询公会排名信息
            $return['info']['rank'] = $this->mLeagueInfo['league_rank'];
            $return['info']['point'] = $this->mLeagueInfo['league_point'];

            //我方信息
            $return['fight']['league_id'] = $this->mLeagueInfo['league_id'];
            $return['fight']['league_name'] = $this->mLeagueInfo['league_name'];
            $return['fight']['league_level'] = $this->mLeagueInfo['league_level'];
            $return['fight']['league_count'] = $this->mLeagueInfo['league_count'];

            //敌方信息
            $return['fight']['target_league_id'] = $this->mLeagueInfo['target_league_id'];
            $return['fight']['target_league_name'] = $this->mLeagueInfo['target_league_name'];
            $return['fight']['target_league_level'] = $this->mLeagueInfo['target_league_level'];
            $return['fight']['target_league_rank'] = $this->mLeagueInfo['target_league_rank'];
            $return['fight']['target_league_point'] = $this->mLeagueInfo['target_league_point'];

            //敌方据点副本ID
            for ($i = 1; $i <= self::HOLD_NUM; ++$i) {
                $return['fight']['league_hold_' . $i] = $this->mLeagueInfo['h:' . $i . ':ins'] ? $this->mLeagueInfo['h:' . $i . ':ins'] : 0;
                $return['fight']['league_hold_' . $i . '_hp'] = $this->mLeagueInfo['h:' . $i . ':hp'] ? $this->mLeagueInfo['h:' . $i . ':hp'] : 0;
                $return['fight']['target_league_hold_' . $i] = $this->mTargetLeagueInfo['h:' . $i . ':ins'] ? $this->mTargetLeagueInfo['h:' . $i . ':ins'] : 0;
                $return['fight']['target_league_hold_' . $i . '_hp'] = $this->mTargetLeagueInfo['h:' . $i . ':hp'] ? $this->mTargetLeagueInfo['h:' . $i . ':hp'] : 0;
            }

            //相对我方状态
            $return['fight']['result'] = $this->mLeagueInfo['result'];
        }

        //返回
        return $return;

    }

    //查看战场信息(锦囊&挑战次数)
    public function getBattleInfo()
    {

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //自动加挑战次数
        $info['add_time'] = $this->mAutoAdd['remain'];

        //获取购买挑战次数
        $info['buy'] = $this->mEventBuyCount;

        //获取挑战次数
        $info['remain'] = $this->mEventRemainCount;

        //公会购买突击次数
        $info['assault'] = $this->mLeagueInfo['assault'];

        //我&敌方据点血量
        for ($i = 1; $i <= self::HOLD_NUM; ++$i) {
            $info['hold_' . $i] = $this->mLeagueInfo['h:' . $i . ':damage'];
            $info['target_hold_' . $i] = $this->mTargetLeagueInfo['h:' . $i . ':damage'];
        }

        //锦囊数量
        for ($i = 1; $i <= self::KIT_NUM; ++$i) {
            $info['kit_' . $i] = $this->mInfo['k' . $i];
        }

        //突击buff
        $info['buff_assault'] = $this->mLeagueInfo['assault'] - $this->mInfo['assault_used'];

        //锦囊buff
        for ($i = 1; $i <= self::BUFF_NUM; ++$i) {
            $info['buff_' . $i] = $this->mLeagueInfo['buff' . $i] - $this->mInfo['buff' . $i . '_used'];
        }

        //战场跑马灯
        $info['notice'] = D('GChat')->getLeagueFightNoticeMsg($this->mLeagueId, $_POST['last']);

        //战场动态
        $feed = $this->getFeed();
        $info = $info + $feed;

        //返回
        return $info;

    }

    //自动加次数
    private function autoAdd()
    {

        $return['add'] = 0;
        $return['remain'] = -1;

        $now = time();

        //获取上次更新时间
        $utime = $this->mInfo['utime'];

        //获取玩家目前剩余次数大于等于默认次数,不增加次数，并修改时间为当前时间
        if ($this->mEventRemainCount >= $this->mEventConfig['count']) {
            D('Predis')->cli('fight')->hset($this->mTeamKey, 'utime', $now);
            return $return;
        }

        //获取缺少的次数
        $lack = $this->mEventConfig['count'] - $this->mEventRemainCount;

        //获取次数增加间隔时间
        $time = D('Static')->access('params', 'LEAGUE_BATTLE_COUNT_TIME');

        //查看时间是否已经满足
        $add = floor(($now - $utime) / $time);

        //如果不足加次数,则不处理
        if ($add <= 0) {
            $return['remain'] = $time - ($now - $utime);
            return $return;
        }

        //如果不能补足
        if ($add < $lack) {
            D('Predis')->cli('fight')->hincrby($this->mTeamKey, 'auto_add', $add);
            D('Predis')->cli('fight')->hincrby($this->mTeamKey, 'utime', $add * $time);
            $return['add'] = $add;
            $return['remain'] = $time - ($now - ($utime + $add * $time));
        } else {
            D('Predis')->cli('fight')->hset($this->mTeamKey, 'auto_add', $lack);
            D('Predis')->cli('fight')->hset($this->mTeamKey, 'utime', $now);
            $return['add'] = $lack;
        }

        //返回
        return $return;

    }

    //获取战场动态
    private function getFeed()
    {
        $return = array();
        for ($i = 1; $i <= self::HOLD_NUM; ++$i) {
            $return['feed']['hold_' . $i] = 0;
            $return['target_feed']['hold_' . $i] = 0;
        }

        //获取我方
        $keys = D('Predis')->cli('fight')->keys('lf:t:*:' . $this->mLeagueId);

        //遍历
        foreach ($keys as $value) {
            $hold = D('Predis')->cli('fight')->hget($value, 'hold');
            if (!($hold > 0)) {
                continue;
            }
            $return['feed']['hold_' . $hold] = $return['feed']['hold_' . $hold] + 1;
        }

        //获取敌方
        $keys = D('Predis')->cli('fight')->keys('lf:t:*:' . $this->mLeagueInfo['target_league_id']);

        //遍历
        foreach ($keys as $value) {
            $hold = D('Predis')->cli('fight')->hget($value, 'hold');
            if (!($hold > 0)) {
                continue;
            }
            $return['target_feed']['hold_' . $hold] = $return['target_feed']['hold_' . $hold] + 1;
        }

        return $return;

    }

    //获取战况
    public function getSituation()
    {

        //获取本公会成员信息
        $keyList = D('Predis')->cli('fight')->keys('lf:t:*:' . $this->mLeagueId);
        if (empty($keyList)) {
            return array();
        }

        //遍历成员信息
        $list = array();
        foreach ($keyList as $value) {
            $arr = D('Predis')->cli('fight')->hgetall($value);
            if (!($arr['damage'] > 0)) {
                continue;
            }
            $data['tid'] = $arr['tid'];
            $data['nickname'] = $arr['nickname'];
            $data['level'] = $arr['level'];
            $data['damage'] = $arr['damage'];
            $data['count'] = $arr['used'];
            $list[] = $data;
        }

        return $list;
    }

    //使用锦囊
    public function useKit()
    {

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //据点
        $hold_id = 0;

        //获取玩家锦囊个数
        $kit = $this->mInfo['k' . $_POST['kit_id']];
        if ($kit <= 0) {
            C('G_ERROR', 'league_fight_kit_not_enough');
            return false;
        }

        //使用
        switch ($_POST['kit_id']) {
            case '1':
                D('Predis')->cli('fight')->hincrby($this->mLeagueKey, 'buff1', 1);//增加公会锦囊buff1
                break;
            case '2':
                D('Predis')->cli('fight')->hincrby($this->mLeagueKey, 'buff2', 1);//增加公会锦囊buff2
                break;
            case '3'://加据点HP
                $hold_id = 0;
                for ($i = 1; $i <= self::HOLD_NUM; ++$i) {
                    $hp = $this->mLeagueInfo['h:' . $i . ':hp'];
                    $damage = $this->mLeagueInfo['h:' . $i . ':damage'];
                    if ($damage == 0 || $damage >= $hp) {
                        continue;
                    }
                    if (!isset($damageMax) || $damageMax < $damage) {
                        $hold_id = $i;
                        $damageMax = $damage;
                    }
                }

                //回复血量
                if ($hold_id == 0) {
                    C('G_ERROR', 'league_fight_hold_hp_max');
                    return false;
                }
                $add = D('Static')->access('params', 'LEAGUE_BATTLE_SKILL_3');

                //增加血量
                $damageNew = D('Predis')->cli('fight')->hincrby($this->mLeagueKey, 'h:' . $hold_id . ':damage', -$add);

                //检查是否有溢出
                if ($damageNew < 0) {
                    D('Predis')->cli('fight')->hset($this->mLeagueKey, 'h:' . $hold_id . ':damage', 0);
                }

                break;
        }

        //扣除锦囊数量
        D('Predis')->cli('fight')->hincrby($this->mTeamKey, 'k' . $_POST['kit_id'], -1);

        //发送公告
        $this->sendSystemNotice('LEAGUE_BATTLE_TIPS_SKILL_' . $_POST['kit_id']);

        return $hold_id;

    }

    //购买突击次数
    public function buyAssault()
    {

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //查询购买需要的货币
        $needDiamond = D('Static')->access('params', 'LEAGUE_ASSAULT_DIAMOND');

        //检查玩家当前货币是否足够
        if (!$now = $this->verify($needDiamond, 'diamond')) {
            return false;
        }

        //购买
        $count = D('Predis')->cli('fight')->hincrby($this->mLeagueKey, 'assault', 1);
        if ($count > $this->mMaxAssault) {
            C('G_ERROR', 'league_fight_buy_assault_max');
            return false;
        }

        //扣除货币
        if (!$this->recover('diamond', $needDiamond, null, $now)) {
            return false;
        }

        //发送公告
        $this->sendSystemNotice('LEAGUE_BATTLE_TIPS_ALL');

        //返回
        return true;

    }

    //购买挑战次数
    public function buyCount()
    {

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //查看是否到了最大购买次数
        $max = D('Static')->access('params', 'LEAGUE_BATTLE_COUNT_BUY_MAX');
        if ($this->mEventBuyCount >= $max) {
            C('G_ERROR', 'league_fight_buy_count_max');
            return false;
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
        if (false === D('Predis')->cli('fight')->hincrby($this->mTeamKey, 'buy', 1)) {
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

    //开始战斗
    public function fight()
    {

        //活动验证
        if (!$this->eventVerify()) {
            return false;
        }

        //检查挑战次数是否足够
        if ($this->mEventRemainCount <= 0) {
            C('G_ERROR', 'league_fight_count_not_enough');
            return false;
        }

        //检查据点是否已经被占领
        if ($this->mTargetLeagueInfo['h:' . $_POST['hold_id'] . ':kill'] > 0) {
            C('G_ERROR', 'league_fight_hold_already_occupied');
            return false;
        }

        //更新时间
        if ($this->mEventRemainCount == $this->mEventConfig['count'])//当前次数等于默认次数时，更新时间
            D('Predis')->cli('fight')->hset($this->mTeamKey, 'utime', time());

        //增加公会贡献度
        $contribution = D('Static')->access('params', 'LEAGUE_BATTLE_REW_CONTRIBUTIONS');
        if (!$this->produce('contribution', $contribution)) {
            return false;
        }

        //创建副本
        if (!$info = $this->instanceFight('LeagueFight', $this->mTargetLeagueInfo['h:' . $_POST['hold_id'] . ':ins'], $_POST['partner'])) {
            return false;
        }

        //设置攻打据点
        D('Predis')->cli('fight')->hset($this->mTeamKey, 'hold', $_POST['hold_id']);

        //设置攻打副本
        $info['instance_id'] = $this->mTargetLeagueInfo['h:' . $_POST['hold_id'] . ':ins'];

        //返回公会战所收到的伤害值
        $info['damage'] = $this->mTargetLeagueInfo['h:' . $_POST['hold_id'] . ':damage'];

        //增加使用次数
        D('Predis')->cli('fight')->hincrby($this->mTeamKey, 'used', 1);

        //增加突击buff使用次数
        $remain = $this->mLeagueInfo['assault'] - $this->mInfo['assault_used'];
        if ($remain > 0) {
            $info['buff_assault'] = 1;
            D('Predis')->cli('fight')->hincrby($this->mTeamKey, 'assault_used', 1);
        } else {
            $info['buff_assault'] = 0;
        }

        //增加锦囊buff使用次数
        for ($i = 1; $i <= self::BUFF_NUM; ++$i) {
            $remain = $this->mLeagueInfo['buff' . $i] - $this->mInfo['buff' . $i . '_used'];
            if ($remain > 0) {
                $info['buff_' . $i] = 1;
                D('Predis')->cli('fight')->hincrby($this->mTeamKey, 'buff' . $i . '_used', 1);
            } else {
                $info['buff_' . $i] = 0;
            }
        }

        //返回
        return $info;

    }


    //副本胜利
    public function end()
    {

        //活动限制
//        if (!$this->eventVerify()) {
//            return false;
//        }

        //完成副本
        if (!$this->instanceEnd($this->mTargetLeagueInfo['h:' . $_POST['hold_id'] . ':ins'])) {
            return false;
        }

        //增加据点伤害
        $realDamage = 0;
        $damageAll = D('Predis')->cli('fight')->hincrby($this->mTargetLeagueKey, 'h:' . $_POST['hold_id'] . ':damage', $_POST['damage']);
        $hpAll = $this->mTargetLeagueInfo['h:' . $_POST['hold_id'] . ':hp'];

        //查看血量是否溢出
        if ($damageAll < $hpAll) {
            //非击杀情况
            $realDamage = $_POST['damage'];//实际伤害
        } else {

            //击杀情况
            if ($damageAll - $hpAll < $_POST['damage']) {

                //检查公会战是否结束
                $occupied = D('Predis')->cli('fight')->hincrby($this->mLeagueKey, 'occupied', 1);

                //查看是否是最后一据点
                if ($occupied == self::HOLD_NUM) {

                    $status = D('Predis')->cli('fight')->hincrby('lf:end', 'g' . $this->mLeagueInfo['group'], 1);

                    if ($status == 1) {

                        //设置战斗结果
                        D('Predis')->cli('fight')->hset($this->mLeagueKey, 'result', 1);
                        D('Predis')->cli('fight')->hset($this->mTargetLeagueKey, 'result', 0);

                        //发送公告
                        $this->sendSystemNotice('LEAGUE_BATTLE_TIPS_HOLD');

                        //去除溢出伤害
                        $realDamage = $_POST['damage'] - ($damageAll - $hpAll);

                        //设置击杀
                        D('Predis')->cli('fight')->hset($this->mTargetLeagueKey, 'h:' . $_POST['hold_id'] . ':damage', $hpAll);
                        D('Predis')->cli('fight')->hset($this->mTargetLeagueKey, 'h:' . $_POST['hold_id'] . ':kill', $this->mTid);

                    } else {
                        D('Predis')->cli('fight')->hincrby($this->mTargetLeagueKey, 'h:' . $_POST['hold_id'] . ':damage', -$_POST['damage']);
                    }

                } else if ($occupied < self::HOLD_NUM) {

                    //去除溢出伤害
                    $realDamage = $_POST['damage'] - ($damageAll - $hpAll);

                    //设置击杀
                    D('Predis')->cli('fight')->hset($this->mTargetLeagueKey, 'h:' . $_POST['hold_id'] . ':damage', $hpAll);
                    D('Predis')->cli('fight')->hset($this->mTargetLeagueKey, 'h:' . $_POST['hold_id'] . ':kill', $this->mTid);

                }

            } else {
                D('Predis')->cli('fight')->hset($this->mTargetLeagueKey, 'h:' . $_POST['hold_id'] . ':damage', $hpAll);
            }

        }

        //计算伤害值
        if ($realDamage > 0) {
            D('Predis')->cli('fight')->hincrby($this->mTeamKey, 'damage', $realDamage);
        }

        //重置战斗信息
        D('Predis')->cli('fight')->hset($this->mTeamKey, 'hold', 0);

        //查看战斗获得锦囊
        $reward = array();
        $arr = lua('league_battle', 'league_battle_reward_skill', array($_POST['hold_id'], $_POST['damage']));
        if ($arr[0] > 0) {
            D('Predis')->cli('fight')->hincrby($this->mTeamKey, 'k' . $arr[0], 1);
            $reward[] = $this->spoils('kit_' . $arr[0], 1);
        }

        //道具
        if ($arr[1] > 0) {
            if ($rewardBox = $this->produce('box', $arr[1], 1)) {
                $reward = array_merge($reward, $rewardBox);
            }
        }

        return $reward;

    }

    //聊天弹幕
    public function sendChatNotice()
    {

        //活动限制
        if (!$this->eventVerify()) {
            return false;
        }

        //发送消息
        $nickname = D('Predis')->cli('fight')->hget($this->mTeamKey, 'nickname');//昵称

        //我方聊天记录
        D('GChat')->sendLeagueFightNoticeMsg($this->mTid, $this->mLeagueId, $this->mLeagueId, $nickname, $_POST['msg'], 2);

        //敌方聊天记录
        $targetLeagueId = D('Predis')->cli('fight')->hget($this->mLeagueKey, 'target_league_id');
        D('GChat')->sendLeagueFightNoticeMsg($this->mTid, $targetLeagueId, $this->mLeagueId, $nickname, $_POST['msg'], 2);
        return true;

    }

    //系统消息弹幕
    private function sendSystemNotice($index)
    {

        switch ($index) {
            case 'LEAGUE_BATTLE_TIPS_ALL':
            case 'LEAGUE_BATTLE_TIPS_SKILL_1':
            case 'LEAGUE_BATTLE_TIPS_SKILL_2':
            case 'LEAGUE_BATTLE_TIPS_SKILL_3':
                $params['nickname'] = $this->getName('nick');
                break;
        }

        //发送消息
        $params['league_name'] = D('Predis')->cli('fight')->hget($this->mLeagueKey, 'league_name');//公会名称
        $params['nickname'] = D('Predis')->cli('fight')->hget($this->mTeamKey, 'nickname');//昵称
        $config = D('SEventString')->getConfig($index, $params);

        //我方聊天记录
        D('GChat')->sendLeagueFightNoticeMsg($this->mTid, $this->mLeagueId, $this->mLeagueId, $params['nickname'], $config['des'], 1);

        //敌方聊天记录
        $targetLeagueId = D('Predis')->cli('fight')->hget($this->mLeagueKey, 'target_league_id');
        D('GChat')->sendLeagueFightNoticeMsg($this->mTid, $targetLeagueId, $this->mLeagueId, $params['nickname'], $config['des'], 1);
        return true;

    }

    //获取属性
    private function getName($attr)
    {
        switch ($attr) {
            case 'league':
                return D('Predis')->cli('fight')->hget($this->mLeagueKey, 'league_name');
            case 'nick':
                return D('Predis')->cli('fight')->hget($this->mTeamKey, 'nickname');
        }
    }

}