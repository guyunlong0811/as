<?php
namespace Home\Api;

use Think\Controller;

class ExpireBattleApi extends BBattleApi
{
    //获取显示副本情况
    public function getList()
    {
        //当前时间
        $now = time();

        //获取活动情况
        $configDyn = D('StaticDyn')->access('activity_time');
        $config = D('Static')->access('activity_time');

        //获取当前服务器ID
        $serverId = C('G_SID');

        //获取当前渠道ID
        $channelId = $this->mSessionInfo['channel_id'];

        //遍历
        $list = array();
        foreach ($configDyn as $value) {

            //状态
            if ($value['status'] != '1') {
                continue;
            }

            //检查服务器
            if ($value['server'] != 0 && $value['server'] != $serverId) {
                $serverList = explode('#', $value['server']);
                if (!in_array($serverId, $serverList)) {
                    continue;
                }
            }

            //检查渠道
            if ($value['channel'] != 0 && $value['channel'] != $channelId) {
                $channelList = explode('#', $value['channel']);
                if (!in_array($channelId, $channelList)) {
                    continue;
                }
            }

            //检查活动时间
            if ($now > $value['endtime']) {
                continue;
            }

            //数据
            $arr['battle_id'] = $value['instance'];
            $arr['starttime'] = $value['starttime'];
            $arr['endtime'] = $value['endtime'];
            $arr['count'] = 0;
            $instanceId = D('Static')->access('activity_time', $value['instance'], 'instance_info');
            $isComplete = D('GInstance')->isComplete($this->mTid, $instanceId);
            $arr['isComplete'] = $isComplete ? 1 : 0;

            //如果活动已经开放则查询已创建次数
            if ($value['starttime'] <= $now && $now <= $value['endtime']) {
                $arr['count'] = D('LInstance')->getTodayCount($this->mTid, $config[$value['instance']]['instance_info']);
            }

            $list[] = $arr;

        }

        //返回
        return $list;
    }

    //开始副本
    public function fight()
    {
        //当前时间
        $now = time();

        //检查副本是否开放
        $config = D('StaticDyn')->access('activity_time');

        //获取当前服务器ID
        $serverId = C('G_SID');

        //获取当前渠道ID
        $channelId = $this->mSessionInfo['channel_id'];

        //遍历
        $flag = false;
        foreach ($config as $value) {

            //状态
            if ($value['status'] != '1') {
                continue;
            }

            //检查服务器
            if ($value['server'] != 0 && $value['server'] != $serverId) {
                $serverList = explode('#', $value['server']);
                if (!in_array($serverId, $serverList)) {
                    continue;
                }
            }

            //检查渠道
            if ($value['channel'] != 0 && $value['channel'] != $channelId) {
                $channelList = explode('#', $value['channel']);
                if (!in_array($channelId, $channelList)) {
                    continue;
                }
            }

            //检查活动
            if ($value['instance'] != $_POST['battle_id']) {
                continue;
            }

            //检查活动时间
            if ($value['starttime'] > $now || $now > $value['endtime']) {
                continue;
            }

            //数据
            $flag = true;
            break;

        }

        if(!$flag){
            C('G_ERROR', 'instance_not_open');
            return false;
        }

        //获取副本ID
        $instanceId = D('Static')->access('activity_time', $_POST['battle_id'], 'instance_info');

        //开始副本
        return $this->instanceFight('ExpireBattle', $instanceId, $_POST['partner']);
    }

    //副本胜利
    public function win()
    {
        //获取副本ID
        $instanceId = D('Static')->access('activity_time', $_POST['battle_id'], 'instance_info');

        if (false === $drop = $this->instanceWin($instanceId)) {
            return false;
        }
        //返回掉落列表
        return $drop;
    }

    //副本失败
    public function lose()
    {
        //获取副本ID
        $instanceId = D('Static')->access('activity_time', $_POST['battle_id'], 'instance_info');
        return $this->instanceLose($instanceId);
    }

    //扫荡
    public function sweep()
    {

        //当前时间
        $now = time();

        //检查副本是否开放
        $config = D('StaticDyn')->access('activity_time');

        //获取当前服务器ID
        $serverId = C('G_SID');

        //获取当前渠道ID
        $channelId = $this->mSessionInfo['channel_id'];

        //遍历
        $flag = false;
        foreach ($config as $value) {

            //状态
            if ($value['status'] != '1') {
                continue;
            }

            //检查服务器
            if ($value['server'] != 0 && $value['server'] != $serverId) {
                $serverList = explode('#', $value['server']);
                if (!in_array($serverId, $serverList)) {
                    continue;
                }
            }

            //检查渠道
            if ($value['channel'] != 0 && $value['channel'] != $channelId) {
                $channelList = explode('#', $value['channel']);
                if (!in_array($channelId, $channelList)) {
                    continue;
                }
            }

            //检查活动
            if ($value['instance'] != $_POST['battle_id']) {
                continue;
            }

            //检查活动时间
            if ($value['starttime'] > $now || $now > $value['endtime']) {
                continue;
            }

            //数据
            $flag = true;
            break;

        }

        if(!$flag){
            C('G_ERROR', 'instance_not_open');
            return false;
        }

        //获取副本ID
        $instanceId = D('Static')->access('activity_time', $_POST['battle_id'], 'instance_info');

        //查询副本配置
        $instanceInfoConfig = D('Static')->access('instance_info', $instanceId);
        if (empty($instanceInfoConfig)) {
            C('G_ERROR', 'instance_error');
            return false;
        }

        //查询副本完成列表
        $instanceInfo = D('GInstance')->getRow($this->mTid, $instanceId);
        if (empty($instanceInfo)) {
            C('G_ERROR', 'instance_not_complete');
            return false;
        }

        //查询体力是否足够
        $needVality = $_POST['times'] * $instanceInfoConfig['need_vality'];
        if (!$valityNow = $this->verify($needVality, 'vality')) {
            return false;
        }

        //查询玩家当前道具
        $needItem = $_POST['times'] * $instanceInfoConfig['need_item_count'];
        if (!$this->verify($needItem, 'item', $instanceInfoConfig['need_item'])) {
            return false;
        }

        //查询今天进入副本的次数
        $count = D('LInstance')->getTodayCount($this->mTid, $instanceId);
        if ($instanceInfoConfig['create_times'] != '-1' && ($count + $_POST['times']) > $instanceInfoConfig['create_times']) {
            C('G_ERROR', 'instance_count_max_today');
            return false;
        }

        //查询价格
        $sweepItemId = D('Static')->access('params', 'INSTANCE_CLEAR_ITEM');
        $needDiamond = D('Static')->access('params', 'RAIDS_CONSUME_DIAMOND');

        //获取当前扫荡券
        $sweepItemCount = $this->schedule('item', $sweepItemId);
        if ($sweepItemCount >= $_POST['times']) {
            $use['item'] = $_POST['times'];//使用扫荡券
        } else if ($sweepItemCount > 0 && $sweepItemCount < $_POST['times']) {
            $use['item'] = $sweepItemCount;//使用扫荡券
            $use['diamond'] = $needDiamond * ($_POST['times'] - $sweepItemCount);//使用水晶
        } else {
            $use['diamond'] = $needDiamond * $_POST['times'];//使用水晶
        }

        //检查水晶是否足够
        if (isset($use['diamond'])) {
            if (!$diamondNow = $this->verify($use['diamond'], 'diamond')) {
                return false;
            }
        }

        //开始事务
        $this->transBegin();

        //扣除体力
        if ($needVality > 0) {
            if (!$this->recover('vality', $needVality, null, $valityNow)) {
                goto end;
            }
        }

        //扣除扫荡券&水晶
        foreach ($use as $key => $value) {
            switch ($key) {
                case 'item'://扫荡券
                    if (!$this->recover('item', $sweepItemId, $value)) {
                        goto end;
                    }
                    break;
                case 'diamond'://水晶
                    if (!$this->recover('diamond', $value, null, $diamondNow)) {
                        goto end;
                    }
                    break;
            }
        }

        //扣除道具
        if ($needItem > 0) {
            if (!$this->recover('item', $instanceInfoConfig['need_item'], $needItem)) {
                goto end;
            }
        }

        //计算奖励
        $sqlLog = "insert into `l_instance` (`tid`,`module`,`instance`,`group`,`difficulty`,`partner`,`drop`,`result`,`starttime`,`endtime`,`is_sweep`) values ";

        //掉落奖励
        if (false === $dropList = D('GItem')->openBox($this->mTid, $instanceInfoConfig['sweep_loot'], $_POST['times'], 0)) {
            goto end;
        }

        //获取难度
        $difficulty = $this->getInstanceDiff($_POST['instance_id']);

        //记录日志
        for ($i = 0; $i < $_POST['times']; ++$i) {
            $drop = json_encode($dropList[$i]);
            $sqlLog .= "('{$this->mTid}','Instance','{$instanceId}','{$instanceInfoConfig['group']}','{$difficulty}','','{$drop}','1','{$now}','{$now}','1'),";
        }
        $sqlLog = substr($sqlLog, 0, -1) . ';';
        D('LInstance')->ExecuteData($sqlLog);

        //加金币
        $addGold = $instanceInfoConfig['bonus_gold'] * $_POST['times'];
        if (!$this->produce('gold', $addGold)) {
            goto end;
        }

        //加战队经验
        $addExp = $instanceInfoConfig['bonus_team_exp'] * $_POST['times'];
        if (!$this->produce('teamExp', $addExp)) {
            goto end;
        }

        //加道具
        if (!$this->produce('item', $instanceInfoConfig['sweep_item'], $instanceInfoConfig['sweep_item_count'] * $_POST['times'])) {
            goto end;
        }

        //记录日志
        if (!D('GInstance')->complete($this->mTid, $instanceId, $_POST['times'])) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return $dropList;

    }


}