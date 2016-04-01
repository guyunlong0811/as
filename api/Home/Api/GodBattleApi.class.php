<?php
namespace Home\Api;

use Think\Controller;

class GodBattleApi extends BEventApi
{

    private $group;
    private $mBattleInfoConfig;

    public function _initialize()
    {
        parent::_initialize();
        $this->group = $_POST['event_group'];
        if (!$this->event($this->group)) {
            exit;
        }

        //获取副本配置
        if (isset($_POST['battle_id'])) {
            $this->mBattleInfoConfig = D('Static')->access('god_battle_info', $_POST['battle_id']);
        }

        return;
    }

    //获取剩余次数
    public function getCount()
    {
        $return['count'] = $this->mEventRemainCount >= 0 ? $this->mEventRemainCount : 0;
        $return['buy'] = $this->mEventBuyCount >= 0 ? $this->mEventBuyCount : 0;
        return $return;
    }

    //开始副本
    public function fight()
    {

        //检查挑战次数
        if ($this->mEventRemainCount <= 0) {
            C('G_ERROR', 'god_battle_count_not_enough');
            return false;
        }

        //检查等级
        if (!$this->verify($this->mBattleInfoConfig['need_level'], 'level')) {
            return false;
        }

        //创建副本
        return $this->instanceFight('GodBattle', $this->mBattleInfoConfig['index'], $_POST['partner']);

    }

    //副本胜利
    public function win()
    {

        //检查挑战次数
        if ($this->mEventRemainCount <= 0) {
            C('G_ERROR', 'god_battle_count_not_enough');
            return false;
        }

        //检查伙伴
        switch ($this->mBattleInfoConfig['limit_play']) {
            case '0':
                break;
            case '1':
                foreach ($_POST['partner'] as $value) {
                    $gender = D('Static')->access('partner', $value['index'], 'gender');
                    if ($gender != '1') {
                        C('G_ERROR', 'god_battle_not_comply_rule');
                        return false;
                    }
                }
                break;
            case '2':
                foreach ($_POST['partner'] as $value) {
                    $gender = D('Static')->access('partner', $value['index'], 'gender');
                    if ($gender != '2') {
                        C('G_ERROR', 'god_battle_not_comply_rule');
                        return false;
                    }
                }
                break;
        }

        //胜利逻辑
        if (false === $drop = $this->instanceWin($this->mBattleInfoConfig['index'])) {
            return false;
        }

        //减少活动次数
        $this->useEventCount();

        //返回掉落列表
        return $drop;
    }

    //副本失败
    public function lose()
    {

        //检查挑战次数
        if ($this->mEventRemainCount <= 0) {
            C('G_ERROR', 'god_battle_count_not_enough');
            return false;
        }

        //失败逻辑
        return $this->instanceLose($this->mBattleInfoConfig['index']);
    }

    //购买挑战次数
    public function buy()
    {
        //查看是否还有免费次数
        if ($this->mEventRemainCount > 0) {
            C('G_ERROR', 'god_battle_remain_count');
            return false;
        }

        //检查玩家是否还有购买资格
        if (!D('GVip')->checkCount($this->mTid, 'god_battle_' . $_POST['event_group'], $this->mEventBuyCount)) {
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