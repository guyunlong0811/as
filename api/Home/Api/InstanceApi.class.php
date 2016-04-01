<?php
namespace Home\Api;

use Think\Controller;

class InstanceApi extends BBattleApi
{

    //获取所有主线副本情况
    public function getAllList($tid = null)
    {

        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        //获取地图信息
        $mapConfig = D('Static')->access('map');
        $instanceConfig = D('Static')->access('instance_info');

        //获取当前战队等级
        $level = D('GTeam')->getAttr($this->mTid, 'level');

        //获取玩家已经完成的最大主线副本ID
//        dump($mapConfig);
        $max = 0;
        foreach ($mapConfig as $value) {
            //判断难度
            if ($value['difficulty'] != '1') {
                continue;
            }

            //判断等级
            if ($level < $value['need_level']) {
                break;
            }

            //判断前置副本
            if ($value['pre_instance'] == '') {
                $max = $value['map'];
            } else {
                $flag = true;
                $arr = explode('#', $value['pre_instance']);
                foreach ($arr as $val) {
                    $count = D('GInstance')->getCount($this->mTid, $val);
                    if (!($count > 0)) {
                        $flag = false;
                        break;
                    }
                }
                if ($flag) {
                    $max = $value['map'];
                } else {
                    break;
                }

            }

        }
        $return['max_map_id'] = $max;

        //获取副本信息

        //获取主线副本连击记录
        $this->mComboRecordList = $this->getInstanceComboList();

        //获取玩家完成副本情况
        $instanceList = D('GInstance')->getList($this->mTid);

        //获取玩家今天的创建次数
        $createList = D('LInstance')->getTodayList($this->mTid);

        //获取玩家今天重置的情况
        $resetList = D('TDailyInstance')->getList($this->mTid);

        //遍历地图配置
        $list = array();
        foreach ($mapConfig as $config) {
            //如果小于等于开放地图
            if ($config['map'] <= ($max + 1)) {
                $arrInstance = explode('#', $config['instance']);
                //遍历副本ID
                foreach ($arrInstance as $instance) {
                    $info['index'] = $instance;
                    //副本状态
                    if (isset($instanceList[$instance])) {
                        $info['star'] = $instanceList[$instance]['star'] ? $instanceList[$instance]['star'] : 0;
                        $info['status'] = '1';
                    } else if ($instanceConfig[$instance]['pre_instance'] == 0 || isset($instanceList[$instanceConfig[$instance]['pre_instance']])) {
                        $info['star'] = $instanceList[$instance]['star'] ? $instanceList[$instance]['star'] : 0;
                        $info['status'] = '0';
                    } else {
                        continue;
                    }
                    //副本创建情况
                    if ($instanceConfig[$instance]['create_times'] == '-1') {
                        $info['reset'] = 0;
                        $info['remain'] = -1;
                    } else {
                        $createTime = isset($createList[$instance]) ? $createList[$instance] : 0;
                        $resetTime = isset($resetList[$instance]) ? $resetList[$instance] : 0;
                        $info['reset'] = $resetTime;
                        $info['remain'] = $instanceConfig[$instance]['create_times'] * (1 + $resetTime) - $createTime;
                    }

                    //连击记录
                    $info['combo_tid'] = $this->mComboRecordList[$instance]['tid'] ? $this->mComboRecordList[$instance]['tid'] : 0;
                    $info['combo_nickname'] = $this->mComboRecordList[$instance]['nickname'] ? $this->mComboRecordList[$instance]['nickname'] : '';
                    $info['combo_count'] = $this->mComboRecordList[$instance]['combo'] ? $this->mComboRecordList[$instance]['combo'] : 0;
                    $list[] = $info;

                }
            }
        }
        $return['list'] = $list;

        //获取宝箱信息
        $return['bonus'] = D('GMapStarBonus')->getList($this->mTid);
        return $return;

    }

    //重置挑战次数
    public function resetCount()
    {
        //获取副本配置
        $instanceConfig = D('Static')->access('instance_info', $_POST['instance_id']);
        if ($instanceConfig['create_times'] == '-1') {
            C('G_ERROR', 'instance_create_time_no_limit');
            return false;
        }
        //计算剩余次数
        $todayUseCount = D('LInstance')->getTodayCount($this->mTid, $_POST['instance_id']);
        $reset_time = D('TDailyInstance')->getCount($this->mTid, $_POST['instance_id']);
        $remain = $instanceConfig['create_times'] * (1 + $reset_time) - $todayUseCount;

        //没有用完不能清除
        if ($remain > 0) {
            C('G_ERROR', 'instance_create_time_remain');
            return false;
        }
        //查看有没有购买资格
        $vip_id = D('GVip')->getAttr($this->mTid, 'index');

        //查询副本难度
        $difficulty = 0;
        $mapConfig = D('Static')->access('map');
        foreach ($mapConfig as $value) {
            $arr = explode('#', $value['instance']);
            if (in_array($_POST['instance_id'], $arr)) {
                $difficulty = $value['difficulty'];
                break;
            }
        }

        //查看重置所需数据
        switch ($difficulty) {
            case '1':
                $field = 'normal_instance';
                $exchange = 9;
                break;
            case '2':
                $field = 'hero_instance';
                $exchange = 10;
                break;
            default:
                C('G_ERROR', 'instance_reset_not_allow');
                return false;
        }

        //获取VIP配置
        $vipConfig = D('Static')->access('vip', $vip_id);
        if ($reset_time >= $vipConfig[$field]) {
            C('G_ERROR', 'instance_reset_count_max');
            return false;
        }

        //计算本次重置次数
        $count = $reset_time + 1;

        //查询购买需要的货币
        $exchange = $this->exchangeMoney($exchange, $count);
        $needType = $exchange['needType'];
        $needValue = $exchange['needValue'];

        //检查玩家当前货币是否足够
        if (!$now = $this->verify($needValue, $this->mMoneyType[$needType])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //记录购买
        if (!D('TDailyInstance')->record($this->mTid, $_POST['instance_id'])) {
            goto end;
        }

        //扣除货币
        if (!$this->recover($this->mMoneyType[$needType], $needValue, null, $now)) {
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

    //扫荡
    public function sweep()
    {

        //当前时间
        $now = time();

        //获取难度
        $difficulty = $this->getInstanceDiff($_POST['instance_id']);

        //不是主线副本
        if ($difficulty == 0) {
            C('G_ERROR', 'instance_not_allow_sweep');
            return false;
        }

        //查询副本配置
        $instanceInfoConfig = D('Static')->access('instance_info', $_POST['instance_id']);
        if (empty($instanceInfoConfig)) {
            C('G_ERROR', 'instance_error');
            return false;
        }

        //查询副本完成列表
        $instanceInfo = D('GInstance')->getRow($this->mTid, $_POST['instance_id']);
        if (empty($instanceInfo)) {
            C('G_ERROR', 'instance_not_complete');
            return false;
        }

        //玩家未达到3星
        if ($instanceInfo['star'] != '7') {
            //扫荡需要的VIP等级
            $needVipLevel = D('Static')->access('params', 'RAIDS_MIN_VIP_LEVEL');

            //检查VIP等级是否满足
            if (!$this->verify($needVipLevel, 'vip')) {
                return false;
            }
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

        //查询当天重置次数
        $resetTime = D('TDailyInstance')->getCount($this->mTid, $_POST['instance_id']);

        //查询今天进入副本的次数
        $count = D('LInstance')->getTodayCount($this->mTid, $_POST['instance_id']);
        if ($instanceInfoConfig['create_times'] != '-1' && $count + $_POST['times'] > $instanceInfoConfig['create_times'] * ($resetTime + 1)) {
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

        //记录日志
        for ($i = 0; $i < $_POST['times']; ++$i) {
            $drop = json_encode($dropList[$i]);
            $sqlLog .= "('{$this->mTid}','Instance','{$_POST['instance_id']}','{$instanceInfoConfig['group']}','{$difficulty}','','{$drop}','1','{$now}','{$now}','1'),";
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
        if (!D('GInstance')->complete($this->mTid, $_POST['instance_id'], $_POST['times'])) {
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

    //领取宝箱
    public function receiveMapBonus()
    {

        //查询是否已经完成成就
        if (D('GMapStarBonus')->isReceived($this->mTid, $_POST['bonus_id'])) {
            C('G_ERROR', 'instance_map_bonus_received');
            return false;
        }

        //获取宝箱信息
        $bonusConfig = D('Static')->access('map_star_bonus', $_POST['bonus_id']);

        //检查星数是否足够
        $mapConfig = D('Static')->access('map', $bonusConfig['map_index']);
        $instance = explode('#', $mapConfig['instance']);
        $star = D('GInstance')->getStarCount($this->mTid, $instance);
        if ($star < $bonusConfig['need_star']) {
            C('G_ERROR', 'star_not_enough');
            return false;
        }

        //开始事务
        $this->transBegin();

        //记录成就达成
        if (false === D('GMapStarBonus')->receive($this->mTid, $_POST['bonus_id'])) {
            goto end;
        }

        //获得奖励
        if (!$this->bonus($bonusConfig)) {
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

    //开始副本
    public function fight()
    {
        //获取地图信息
        $mapConfig = D('Static')->access('map');

        //计算地图
        $difficulty = substr($_POST['instance_id'], 0, 1);
        $map = substr($_POST['instance_id'], 2, 1);

        //遍历
        foreach ($mapConfig as $value) {
            if ($value['map'] == $map && $value['difficulty'] == $difficulty) {
                if ($value['need_level'] > 1) {
                    if (!$this->verify($value['need_level'], 'level')) {
                        return false;
                    }
                } else {
                    break;
                }
            }
        }

        return $this->instanceFight();
    }

    //开始副本
    public function win()
    {
        //战斗胜利逻辑
        if (false === $return['drop'] = $this->instanceWin()) {
            return false;
        }

        //查看是不是第一次胜利
        $combo = array();
        $count = D('GInstance')->getCount($this->mTid, $_POST['instance_id']);
        if ($count == 1) {

            //获取主线副本连击记录
            $this->mComboRecordList = $this->getInstanceComboList();

            //获取副本配置
            $instanceInfoConfig = D('Static')->access('instance_info');
            foreach ($instanceInfoConfig as $instanceId => $value) {
                if ($instanceId < 30000 && $value['pre_instance'] == $_POST['instance_id']) {
                    $info['index'] = $instanceId;
                    $info['combo_tid'] = $this->mComboRecordList[$instanceId]['tid'] ? $this->mComboRecordList[$instanceId]['tid'] : 0;
                    $info['combo_nickname'] = $this->mComboRecordList[$instanceId]['nickname'] ? $this->mComboRecordList[$instanceId]['nickname'] : '';
                    $info['combo_count'] = $this->mComboRecordList[$instanceId]['combo'] ? $this->mComboRecordList[$instanceId]['combo'] : 0;
                    $combo[] = $info;
                }
            }
        }
        $return['combo'] = $combo;

        //返回
        return $return;

    }

    //开始副本
    public function lose()
    {
        return $this->instanceLose();
    }

    //获取指定副本最高COMBO信息
    public function getComboInfo()
    {

        //获取主线副本连击记录
        $this->mComboRecordList = $this->getInstanceComboList();

        //遍历副本列表
        $list = array();
        foreach ($_POST['instance'] as $value) {
            $info['combo_tid'] = $this->mComboRecordList[$value]['tid'] ? $this->mComboRecordList[$value]['tid'] : 0;
            $info['combo_nickname'] = $this->mComboRecordList[$value]['nickname'] ? $this->mComboRecordList[$value]['nickname'] : '';
            $info['combo_count'] = $this->mComboRecordList[$value]['combo'] ? $this->mComboRecordList[$value]['combo'] : 0;
            $list[] = $info;
        }

        //返回
        return $list;

    }

}