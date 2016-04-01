<?php
namespace Home\Api;

use Think\Controller;

class LuckyCatApi extends BEventApi
{

    private $group;
    private $mBattleInfoConfig;

    public function _initialize()
    {
        parent::_initialize();
        if (isset($_POST['event_group'])) {
            $this->group = $_POST['event_group'];
        } else {
            //获取副本配置
            $this->mBattleInfoConfig = D('Static')->access('lucky_cat', $_POST['battle_id']);
            //活动组ID
            $this->group = $this->mBattleInfoConfig['group'];
        }
        if (!$this->event($this->group)) {
            exit;
        }
        return;
    }

    //获取剩余次数
    public function getCount()
    {
        $result['event_group'] = $_POST['event_group'];
        $result['count'] = $this->mEventRemainCount >= 0 ? $this->mEventRemainCount : 0;
        $result['buy'] = $this->mEventBuyCount >= 0 ? $this->mEventBuyCount : 0;
        return $result;
    }

    //开始副本
    public function fight()
    {

        //检查挑战次数
        if ($this->mEventRemainCount <= 0) {
            C('G_ERROR', 'lucky_cat_not_enough');
            return false;
        }

        //检查等级
        if (!$this->verify($this->mBattleInfoConfig['need_level'], 'level')) {
            return false;
        }

        //创建副本
        return $this->instanceFight('LuckyCat', $this->mBattleInfoConfig['index'], $_POST['partner']);

    }

    //副本结束
    public function end()
    {

        //完成
        if (!$this->instanceEnd($_POST['battle_id'])) {
            return false;
        }

        //计算奖励
        $gold = 0;
        $box = 0;
        $item = array();

        //获取战队等级
        $level = D('GTeam')->getAttr($this->mTid, 'level');
        switch ($this->group) {
            case '19'://金币
                $gold = lua('cat', 'cat_gold', array((int)$level, (int)$_POST['combo'], (int)$_POST['damage'],));
                $gold = round($gold);
                break;
            case '20'://经验
                $box = lua('cat', 'cat_exp', array((int)$level, (int)$_POST['combo'], (int)$_POST['damage'],));
                $box = floor($box);
                break;
        }

        //开始事务
        $this->transBegin();

        //减少活动次数
        $this->useEventCount();

        //增加金币
        if ($gold > 0) {
            if (!$this->produce('gold', $gold)) {
                return false;
            }
        }

        //增加宝箱
        if ($box > 0) {
            if (!$item = $this->produce('box', $box, 1)) {
                return false;
            }
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回掉落列表
        $drop['gold'] = $gold;
        $drop['drop'] = $item;
        return $drop;
    }

    //购买挑战次数
    public function buy()
    {
        //查看是否还有免费次数
        if ($this->mEventRemainCount > 0) {
            C('G_ERROR', 'lucky_cat_remain_count');
            return false;
        }

        //检查玩家是否还有购买资格
        if (!D('GVip')->checkCount($this->mTid, 'lucky_cat_count', $this->mEventBuyCount)) {
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