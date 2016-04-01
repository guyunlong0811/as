<?php
namespace Home\Api;

use Think\Controller;

class AbyssBattleApi extends BEventApi
{

    const GROUP = 4;
    private $mUserRedisInfo;
    private $mBattleConfig;

    public function _initialize()
    {

        parent::_initialize();
        if (!$this->event(self::GROUP)) exit;
        $this->mBattleConfig = D('Static')->access('abyss_battle');

        //玩家信息
        $last = D('Predis')->cli('fight')->get('ab:t:' . $this->mTid);
        $damage1 = D('Predis')->cli('fight')->get('ab:m:1:t:' . $this->mTid);
        $damage2 = D('Predis')->cli('fight')->get('ab:m:2:t:' . $this->mTid);
        $damage3 = D('Predis')->cli('fight')->get('ab:m:3:t:' . $this->mTid);
        $this->mUserRedisInfo['last'] = $last ? $last : 0;
        $this->mUserRedisInfo['damage1'] = $damage1 ? $damage1 : 0;
        $this->mUserRedisInfo['damage2'] = $damage2 ? $damage2 : 0;
        $this->mUserRedisInfo['damage3'] = $damage3 ? $damage3 : 0;

        //计算cd时间
        if (empty($this->mUserRedisInfo['last'])) {
            $this->mEventRemainCount = 0;
        } else {
            $now = time();
            $cdConfig = D('Static')->access('params', 'BATTLE_TO_ABYSS_CD');
            $this->mEventRemainCount = $cdConfig <= ($now - $this->mUserRedisInfo['last']) ? 0 : $cdConfig - ($now - $this->mUserRedisInfo['last']);
        }

        return;
    }

    //获取BOSS战信息
    public function getList()
    {

        //玩家情况
        $userInfo['cd'] = $this->mEventRemainCount;
        $userInfo['buy'] = $this->mEventBuyCount;

        //BOSS情况
        $monsterList = array();
        foreach ($this->mBattleConfig as $key => $value) {

            $monsterInfo = array();
            $monsterInfo['id'] = $key;

            //获取BOSS缓存信息
            $monsterRedis = D('Predis')->cli('fight')->hgetall('ab:m:' . $value['index']);
            if (empty($monsterRedis)) {
                $this->createBoss($key, true);
                $monsterRedis = D('Predis')->cli('fight')->hgetall('ab:m:' . $value['index']);
            }

            $monsterInfo = $monsterInfo + $monsterRedis;
            $monsterInfo['rank'] = json_decode($monsterInfo['rank'], true);
            $monsterInfo['last_rank'] = json_decode($monsterInfo['last_rank'], true);
            $monsterInfo['drop'] = json_decode($monsterInfo['drop'], true);

            //掉落个数
            $monsterInfo['drop_count'] = 0;
            foreach ($monsterInfo['drop'] as $val) {
                $monsterInfo['drop_count'] += $val;
            }

            $monsterList[] = $monsterInfo;

        }

        //返回
        $return['info'] = $userInfo;
        $return['list'] = $monsterList;
        return $return;

    }

    //生成BOSS
    private function createBoss($id, $start = false)
    {
        //清除玩家伤害
        $keys = D('Predis')->cli('fight')->keys('ab:m:' . $id . ':t:*');
        if(!empty($keys)){
            D('Predis')->cli('fight')->del($keys);
        }

        //获取上一次boss
        $redis = D('Predis')->cli('fight')->hgetall('ab:m:' . $id);

        //获取副本信息
        $instanceConfig = D('Static')->access('instance_info', $this->mBattleConfig[$id]['instance_info']);

        //BOSS信息
        $monsterInfo['damage'] = '0';//总伤害
        if ($start === true) {
            $monsterInfo['open'] = time();//开放时间
            $monsterInfo['dead'] = time() - 1;//上次死亡时间
        } else {
            $monsterInfo['open'] = time() + ($this->mBattleConfig[$id]['refresh_time'] * 60);//开放时间
            $monsterInfo['dead'] = time();//上次死亡时间
        }
        $monsterInfo['rank'] = '[]';//排名
        $monsterInfo['last_rank'] = $redis['rank'] ? $redis['rank'] : '[]';//排名
        $loot = $this->loot($instanceConfig);//掉落物品
        $monsterInfo['drop'] = json_encode($loot['box']);//掉落物品
        $monsterInfo['gold'] = $instanceConfig['bonus_gold'];//击杀金币
        $lifeTime = $redis['open'] ? time() - $redis['open'] : 0;
        $avgForceTopInTop100 = M()->query("select avg(`force_top`) as `force_top` from (select `force_top` from `g_count` order by `force_top` DESC limit 100) as `a`");//服务器前100名平均小队最高战力
        $avgForceTopInTop100 = round($avgForceTopInTop100[0]['force_top']);
        $monsterInfo['hp'] = lua('abyss_battle', 'abyss_monster_hp', array((int)$instanceConfig['battle_1_monster'], (int)$avgForceTopInTop100, (int)$lifeTime));//总血量

        //写入redis
        D('Predis')->cli('fight')->hmset('ab:m:' . $id, $monsterInfo);

        return $monsterInfo;
    }

    //清除cd时间
    public function clearCd()
    {

        //查看cd时间是否已经结束
        if ($this->mEventRemainCount == 0) {
            C('G_ERROR', 'abyss_battle_cd_over');
            return false;
        }

        //查询今天付费次数
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

        //记录购买
        if (!$this->buyEventCount()) {
            goto end;
        }

        //扣除货币
        if (!$this->recover($this->mMoneyType[$needType], $needValue, null, $now)) {
            goto end;
        }

        //清除cd
        D('Predis')->cli('fight')->del('ab:t:' . $this->mTid);

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return true;

    }

    //开始副本
    public function fight()
    {

        //检查BOSS是否开放
        $redis = D('Predis')->cli('fight')->hgetall('ab:m:' . $_POST['battle_id']);//开放时间
        if (time() < $redis['open'] || $redis['status' . $redis['count']] > '0') {
            C('G_ERROR', 'abyss_battle_lock');
            return false;
        }

        //检查是否在CD
        if ($this->mEventRemainCount > 0) {
            C('G_ERROR', 'abyss_battle_cd');
            return false;
        }

        //创建副本
        $return = $this->instanceFight('AbyssBattle', $this->mBattleConfig[$_POST['battle_id']]['instance_info'], $_POST['partner']);
        $return['damage'] = D('Predis')->cli('fight')->hget('ab:m:' . $_POST['battle_id'], 'damage');//获取怪物伤害值
        return $return;

    }

    //副本结束
    public function end()
    {

        //获取玩家最强小队战力
        $forceTop = D('GCount')->getAttr($this->mTid, 'force_top');

        //获取普通奖励
        $instance = $this->mBattleConfig[$_POST['battle_id']]['instance_info'];
        $monster = D('Static')->access('instance_info', $instance, 'battle_1_monster');

        //伤害检测
        $damageVerify = lua('abyss_battle', 'abyss_damage_alert', array((int)$monster, (int)$forceTop,));
        if ($_POST['damage'] > $damageVerify) {

            $errorLog = $this->mTid . '&' . $_POST['battle_id'] . '&' . $_POST['damage'] . '&' . $forceTop;
            if (C('WARNING_TYPE') == 'File') {
                write_log($errorLog, 'error/abyss/');
            } else if (C('WARNING_TYPE') == 'Mail') {
                think_send_mail('error_report@forevergame.com', 'error_report', 'ABYSS_DAMAGE_ERROR(' . APP_STATUS . ')', $errorLog);
            }

            $_POST['damage'] = 0;
        }

        //胜利逻辑
        if (!$this->instanceEnd($this->mBattleConfig[$_POST['battle_id']]['instance_info'])) {
            return false;
        }

        //查询信息
        $monsterRedis = D('Predis')->cli('fight')->hgetall('ab:m:' . $_POST['battle_id']);

        //玩家是否是BOSS本次的最后击杀者
        $isKiller = false;

        //增加BOSS伤害
        $realDamage = 0;
        $damageAll = D('Predis')->cli('fight')->hincrby('ab:m:' . $_POST['battle_id'], 'damage', $_POST['damage']);
        $hpAll = $monsterRedis['hp'];

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
                D('GCount')->incAttr($this->mTid, 'abyss_kill');
            }
        }

        //增加伤害
        if ($realDamage > 0) {
            D('Predis')->cli('fight')->incrby('ab:m:' . $_POST['battle_id'] . ':t:' . $this->mTid, $realDamage);//设置伤害
        }

        //开始事务
        $this->transBegin();

        //奖励
        $bonus = lua('abyss_battle', 'abyss_battle_everyrew', array((int)$_POST['damage'], (int)$monster, (int)$monsterRedis['hp'],));

        //道具
        if ($bonus[2] > 0) {
            if (!$reward = $this->produce('box', $bonus[2], 1)) {
                goto end;
            }
        }

        //金币
        if ($bonus[1] > 0) {
            if (!$this->produce('gold', $bonus[1])) {
                goto end;
            }
        }

        //伙伴经验
        if ($bonus[0] > 0) {
            foreach ($_POST['partner'] as $value) {
                if (!$this->produce('partnerExp', $value, $bonus[0])) {
                    goto end;
                }
            }
        }

        //击杀者
        $dropItemList = array();
        if ($isKiller) {

            //强行重新排名
            $this->rank();

            //发放掉落奖励
            if (false === $dropItemList = $this->getLoot($monsterRedis['drop'], 'AbyssBattle')) {
                goto end;
            }

            //发放副本金币奖励
            if (!$this->produce('gold', D('Predis')->cli('fight')->hget('ab:m:' . $_POST['battle_id'], 'gold'))) {
                goto end;
            }

            //记录击杀
            $log['battle_id'] = $_POST['battle_id'];
            $log['rank_list'] = D('Predis')->cli('fight')->hget('ab:m:' . $_POST['battle_id'], 'rank');
            $log['last_tid'] = $this->mTid;
            $log['drop'] = json_encode($dropItemList);
            D('LAbyss')->CreateData($log);

            //刷新boss
            $this->createBoss($_POST['battle_id']);

            //发送击杀公告
            $params['nickname'] = D('GTeam')->getAttr($this->mTid, 'nickname');
            $params['monstername'] = D('Static')->access('abyss_battle', $_POST['battle_id'], 'name');
            $noticeConfig = D('SEventString')->getConfig('ABYSS_KILL_STRING', $params);
            D('GChat')->sendNoticeMsg($this->mTid, $noticeConfig['des'], $noticeConfig['show_level']);

            //获取排名奖励配置
            $rankBonusConfig = D('Static')->access('rank_bonus', $this->mBattleConfig[$_POST['battle_id']]['rank_bonus']);

            //获取当前排行榜
            $rankList = json_decode($log['rank_list'], true);

            //发送前十邮件
            foreach ($rankBonusConfig as $value) {

                for ($i = $value['rank_start']; $i <= $value['rank_end']; ++$i) {
                    $receiveTid = $rankList[$i - 1]['tid'];
                    if (isset($receiveTid)) {
                        //发邮件
                        $params['rank'] = $i;
                        D('GMail')->send($value['rank_mail'], $this->mTid, $receiveTid, $params);
                        //记录成就
                        D('GCount')->abyss($receiveTid, $i);
                    }

                }

            }

        } else {
            //查看排名情况
            if ($realDamage > 0) {
                $this->rank($monsterRedis['rank']);
            }
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //记录挑战
        D('GCount')->incAttr($this->mTid, 'abyss');

        //结束副本
        $cdConfig = D('Static')->access('params', 'BATTLE_TO_ABYSS_CD');
        D('Predis')->cli('fight')->setex('ab:t:' . $this->mTid, $cdConfig, time());

        //返回
        $return['gold'] = $bonus[1];
        $return['exp'] = $bonus[0];
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
            $myDamage = D('Predis')->cli('fight')->get('ab:m:' . $_POST['battle_id'] . ':t:' . $this->mTid);

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
        $keys = D('Predis')->cli('fight')->keys('ab:m:' . $_POST['battle_id'] . ':t:*');
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
        $playerList = array_slice($playerList, 0, 10);

        //获取排行榜tid
        foreach ($playerList as $value) {
            $tidList[] = $value['tid'];
        }

        //查新玩家昵称&等级
        $field = array('tid', 'nickname', 'level',);
        $where['tid'] = array('in', $tidList);
        $select = M('GTeam')->field($field)->where($where)->select();
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
        D('Predis')->cli('fight')->hset('ab:m:' . $_POST['battle_id'], 'rank', json_encode($playerList));
        return;

    }

}