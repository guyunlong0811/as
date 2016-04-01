<?php
namespace Home\Api;

use Think\Controller;

class BabelApi extends BEventApi
{

    const GROUP = 2;
    private $mBabelInfo;
    private $mBabelConfig;
    private $mInstanceConfig;

    public function _initialize()
    {
        parent::_initialize();
        if (!$this->event(self::GROUP)) exit;
        $this->mBabelInfo = D('GBabel')->getRow($this->mTid);

        //没有数据则创建新数据
        if (empty($this->mBabelInfo)) {
            D('GBabel')->open($this->mTid);
            $this->mBabelInfo = D('GBabel')->getRow($this->mTid);
        }
        $this->mBabelInfo['partner'] = json_decode($this->mBabelInfo['partner'], true);

        //获取基本数据
        $this->mBabelConfig = D('Static')->access('babel', $this->mBabelInfo['floor']);
        $this->mInstanceConfig = D('Static')->access('instance_info', $this->mBabelConfig['instance_info']);
    }

    //重置通天塔
    private function reset()
    {
        $where['tid'] = $this->mTid;
        $data['floor'] = 1;
        $data['partner'] = '[]';
        $data['sweep_starttime'] = 0;
        $data['status'] = 0;
        return D('GBabel')->UpdateData($data, $where);
    }

    //获取当前楼层信息
    public function getInfo()
    {
        //其他数据
        $info['remain'] = $this->mEventRemainCount;
        $info['buy'] = $this->mEventBuyCount;
        $info['floor'] = $this->mBabelInfo['floor'];
        $info['partner'] = $this->mBabelInfo['partner'];
        $info['max'] = $this->mBabelInfo['max'];
        $info['max_sweep'] = $this->mBabelInfo['max_sweep'];
        $info['sweep_starttime'] = $this->mBabelInfo['sweep_starttime'];
        $info['status'] = $this->mBabelInfo['status'];
        return $info;
    }

    //发起挑战
    public function fight()
    {

        //通天塔已经完成
        if ($this->mBabelInfo['status'] == '1') {
            C('G_ERROR', 'babel_completed');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '2') {
            C('G_ERROR', 'babel_reward_first');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '3') {
            C('G_ERROR', 'babel_sweeping');
            return false;
        }

        //查询上场伙伴中是否有已经阵亡的
        $partner = array_intersect($_POST['partner'], $this->mBabelInfo['partner']);
        if (!empty($partner)) {
            C('G_ERROR', 'babel_partner_dead');
            return false;
        }

        //实例化PVE
        return $this->instanceFight('Babel', $this->mInstanceConfig['index'], $_POST['partner']);

    }

    //战斗胜利
    public function win()
    {

        $result = 1;//战斗结果

        //通天塔已经完成
        if ($this->mBabelInfo['status'] == '1') {
            C('G_ERROR', 'babel_completed');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '2') {
            C('G_ERROR', 'babel_reward_first');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '3') {
            C('G_ERROR', 'babel_sweeping');
            return false;
        }

        //副本胜利
        if (false === $dropList = $this->instanceWin($this->mInstanceConfig['index'])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //结束战斗逻辑
        if (!$this->end($result)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //数据异常返回
        if ($result == -1) {
            C('G_ERROR', 'battle_anomaly');
            return false;
        }

        //返回
//        return true;
        return $dropList;

    }

    //战斗失败
    public function lose()
    {

        $result = 0;//战斗结果

        //通天塔已经完成
        if ($this->mBabelInfo['status'] == '1') {
            C('G_ERROR', 'babel_completed');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '2') {
            C('G_ERROR', 'babel_reward_first');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '3') {
            C('G_ERROR', 'babel_sweeping');
            return false;
        }

        //战斗结算
        if (false === $this->instanceLose($this->mInstanceConfig['index'])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //结束战斗逻辑
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
    public function end($result)
    {

        //记录阵亡名单
        if (!empty($_POST['partner_dead'])) {
            $partnerList = array_merge($_POST['partner_dead'], $this->mBabelInfo['partner']);
            $partnerList = array_unique($partnerList);
            $save['partner'] = json_encode($partnerList);
        }

        if ($result == 1) {

            //发放当前层奖励
            if (false === $this->bonus($this->mBabelConfig, 'battle_')) {
                return false;
            }

            //记录最高层数
            if ($this->mBabelInfo['floor'] > $this->mBabelInfo['max']) {
                $save['max'] = $this->mBabelInfo['floor'];
                $save['max_time'] = time();
            }

            //记录可扫荡最高层数
            if (empty($_POST['partner_dead']) && empty($this->mBabelInfo['partner']) && $this->mBabelInfo['floor'] > $this->mBabelInfo['max_sweep']) {
                $save['max_sweep'] = $this->mBabelInfo['floor'];
            }

            //如果是boss关则设置成领取boss奖励状态
            if ($this->mBabelConfig['type'] == 2) {
                $save['status'] = 2;
            } else {//非BOSS关去下层
                $config = D('Static')->access('babel');
                $next = $this->mBabelInfo['floor'] + 1;
                if (isset($config[$next])) {
                    $save['floor'] = $next;
                } else {
                    $save['status'] = 1;
                }
            }

        }

        //保存数据
        $where['tid'] = $this->mTid;
        return D('GBabel')->UpdateData($save, $where);

    }

    //领取奖励
    public function reward()
    {
        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '0') {
            C('G_ERROR', 'babel_not_win');
            return false;
        }

        //通天塔已经完成
        if ($this->mBabelInfo['status'] == '1') {
            C('G_ERROR', 'babel_completed');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '3') {
            C('G_ERROR', 'babel_sweeping');
            return false;
        }

        //开始事务
        $this->transBegin();

        //发放当前层BOSS奖励
        if (false === $this->bonus($this->mBabelConfig, 'boss_')) {
            goto end;
        }

        //生成下一层数据
        $config = D('Static')->access('babel');
        $next = $this->mBabelInfo['floor'] + 1;
        if (isset($config[$next])) {
            $save['floor'] = $next;
            $save['status'] = 0;
        } else {
            $save['status'] = 1;
        }

        //保存数据
        $where['tid'] = $this->mTid;
        if (false === D('GBabel')->UpdateData($save, $where)) {
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

    //免费重置
    public function refresh()
    {

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '2') {
            C('G_ERROR', 'babel_reward_first');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '3') {
            C('G_ERROR', 'babel_sweeping');
            return false;
        }

        //检查是否有重置次数
        if ($this->mEventRemainCount < 1) {
            C('G_ERROR', 'babel_refresh_count_not_enough');
            return false;
        }

        //检查是否已经战斗过
        if ($this->mBabelInfo['floor'] == '1' && $this->mBabelInfo['status'] == '0' && empty($this->mBabelInfo['partner'])) {
            C('G_ERROR', 'babel_not_start');
            return false;
        }

        //开始事务
        $this->transBegin();

        //记录使用
        if (!$this->useEventCount()) {
            goto end;
        }

        //重置
        if (false === $this->reset()) {
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

    //付费重置
    public function refreshNow()
    {

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '2') {
            C('G_ERROR', 'babel_reward_first');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '3') {
            C('G_ERROR', 'babel_sweeping');
            return false;
        }

        //查看是否还有免费次数
        if ($this->mEventRemainCount > 0) {
            C('G_ERROR', 'babel_free_refresh_first');
            return false;
        }

        //检查是否已经战斗过
        if ($this->mBabelInfo['floor'] == '1' && $this->mBabelInfo['status'] == '0' && empty($this->mBabelInfo['partner'])) {
            C('G_ERROR', 'babel_not_start');
            return false;
        }

        //检查玩家是否还有购买资格
        if (!D('GVip')->checkCount($this->mTid, 'babel_count', $this->mEventBuyCount)) {
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

        //记录使用
        if (!$this->useEventCount()) {
            goto end;
        }

        //扣除货币
        if (!$this->recover($this->mMoneyType[$needType], $needValue, null, $now)) {
            goto end;
        }

        //重置
        $this->reset();

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return true;

    }

    //开始扫荡通天塔
    public function sweepStart()
    {
        //通天塔已经完成
        if ($this->mBabelInfo['status'] == '1') {
            C('G_ERROR', 'babel_completed');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '2') {
            C('G_ERROR', 'babel_reward_first');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '3') {
            C('G_ERROR', 'babel_sweeping');
            return false;
        }

        //查看用户是否符合扫荡条件
        if ($this->mBabelInfo['floor'] > $this->mBabelInfo['max_sweep']) {
            C('G_ERROR', 'babel_overstep_sweep_max');
            return false;
        }

        //保存数据
        $save['sweep_starttime'] = time();
        $save['status'] = 3;
        $where['tid'] = $this->mTid;
        if (false === D('GBabel')->UpdateData($save, $where)) {
            return false;
        }

        return true;

    }

    //扫荡完成
    public function sweepComplete()
    {

        //需要先收取奖励
        if ($this->mBabelInfo['status'] != '3') {
            C('G_ERROR', 'babel_not_sweeping');
            return false;
        }

        //计算扫荡开始至今可扫荡的层数
        $now = time();
        $time4one = D('Static')->access('params', 'BABEL_RAIDS_TIME');
        $floor = floor(($now - $this->mBabelInfo['sweep_starttime']) / $time4one);

        //扫荡启始层
        $start = $this->mBabelInfo['floor'];

        //如果一层都不够则返回0
        if ($floor > 0) {

            //计算需要扫荡的层数
            $needSweepFloor = $this->mBabelInfo['max_sweep'] - $this->mBabelInfo['floor'] + 1;

            //计算实际扫荡层数
            if ($floor >= $needSweepFloor) {
                $floor = $needSweepFloor;
            }

        }

        //开始事务
        $this->transBegin();

        //发放扫荡奖励
        if (false === $this->sweepBonus($start, $floor)) {
            goto end;
        }

        //保存数据
        $config = D('Static')->access('babel');
        $next = $start + $floor;
        if (isset($config[$next])) {
            $save['floor'] = $next;
            $save['status'] = 0;
        } else {
            $save['floor'] = $next - 1;
            $save['status'] = 1;
        }
        $save['sweep_starttime'] = 0;
        $where['tid'] = $this->mTid;
        if (false === D('GBabel')->UpdateData($save, $where)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        $return['start'] = $start;
        $return['floor'] = $floor;
        $return['status'] = $save['status'];
        return $return;

    }

    //付费完成扫荡
    public function sweepCompleteNow()
    {

        //通天塔已经完成
        if ($this->mBabelInfo['status'] == '1') {
            C('G_ERROR', 'babel_completed');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] == '2') {
            C('G_ERROR', 'babel_reward_first');
            return false;
        }

        //需要先收取奖励
        if ($this->mBabelInfo['status'] != '3') {
            C('G_ERROR', 'babel_not_sweeping');
            return false;
        }

        //查看用户是否符合扫荡条件
        if ($this->mBabelInfo['floor'] > $this->mBabelInfo['max_sweep']) {
            C('G_ERROR', 'babel_overstep_sweep_max');
            return false;
        }

        //计算扫荡开始至今可扫荡的层数
        $now = time();
        $time4one = D('Static')->access('params', 'BABEL_RAIDS_TIME');
        $diamond4one = D('Static')->access('params', 'BABEL_RAIDS_MONEY');
        if ($this->mBabelInfo['sweep_starttime'] > 0) {
            $floorFree = floor(($now - $this->mBabelInfo['sweep_starttime']) / $time4one);
        } else {
            $floorFree = 0;
        }

        //扫荡启始层
        $start = $this->mBabelInfo['floor'];

        //计算需要扫荡的层数
        $needSweepFloor = $this->mBabelInfo['max_sweep'] - $this->mBabelInfo['floor'] + 1;

        //计算实际扫荡层数
        if ($floorFree >= $needSweepFloor) {
            C('G_ERROR', 'babel_sweep_completed');
            return false;
        }

        //计算需要付费的层数
        $payFloor = $needSweepFloor - $floorFree;
        $needDiamond = $diamond4one * $payFloor;

        //检查水晶是否则够
        if (!$diamondNow = $this->verify($needDiamond, 'diamond')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //发放扫荡奖励
        if (false === $this->sweepBonus($start, $needSweepFloor)) {
            goto end;
        }

        //扣除水晶
        if (!$this->recover('diamond', $needDiamond, null, $diamondNow)) {
            goto end;
        }

        //保存数据
        $config = D('Static')->access('babel');
        $next = $this->mBabelInfo['max_sweep'] + 1;
        if (isset($config[$next])) {
            $save['floor'] = $next;
            $save['status'] = 0;
        } else {
            $save['floor'] = $next - 1;
            $save['status'] = 1;
        }
        $save['sweep_starttime'] = 0;
        $where['tid'] = $this->mTid;
        if (false === D('GBabel')->UpdateData($save, $where)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        $return['start'] = $start;
        $return['floor'] = $needSweepFloor;
        $return['diamond'] = $needDiamond;
        $return['status'] = $save['status'];
        return $return;

    }

    //扫荡处理
    private function sweepBonus($start, $floor)
    {

        //如果扫荡不够一层
        if ($floor < 1) {
            return true;
        }

        //日志
        $logBase['tid'] = $this->mTid;
        $logBase['module'] = 'Babel';
        $logBase['difficulty'] = '0';
        $logBase['partner'] = '';
        $logBase['drop'] = '[]';
        $logBase['result'] = '1';
        $logBase['starttime'] = $this->mBabelInfo['sweep_starttime'];
        $logBase['endtime'] = time();
        $logBase['is_sweep'] = '1';

        //查询配置
        $config = D('Static')->access('babel');

        //遍历
        $rewardList = array();
        $logAll = array();
        foreach ($config as $key => $value) {
            if ($start <= $key && $key < $start + $floor) {
                //获取奖励信息
                for ($i = 1; $i <= 4; ++$i) {
                    if ($value['battle_bonus_' . $i . '_type'] > 0) {
                        $rewardList[$value['battle_bonus_' . $i . '_type']][$value['battle_bonus_' . $i . '_value_1']] += $value['battle_bonus_' . $i . '_value_2'];
                    }
                    if ($value['type'] == 2 && $value['boss_bonus_' . $i . '_type'] > 0) {
                        $rewardList[$value['boss_bonus_' . $i . '_type']][$value['boss_bonus_' . $i . '_value_1']] += $value['boss_bonus_' . $i . '_value_2'];
                    }
                }
                //获取日志信息
                $log = $logBase;
                $log['instance'] = $value['instance_info'];
                $log['group'] = D('Static')->access('instance_info', $value['instance_info'], 'group');
                $logAll[] = $log;
            } else {
                continue;
            }
        }

        //发放奖励
        $flag = true;
        foreach ($rewardList as $type => $value) {
            foreach ($value as $id => $count) {
                if (!$this->produce($this->mBonusType[$type], $id, $count)) {
                    $flag = false;
                }
            }
        }

        //记录日志
        D('LInstance')->CreateAllData($logAll);

        //返回
        return $flag;
    }

}