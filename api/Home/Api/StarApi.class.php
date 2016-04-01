<?php
namespace Home\Api;

use Think\Controller;

class StarApi extends BaseApi
{

    const EXCHANGE_TYPE = 15;

    //获取纹章列表
    public function getList($tid = null)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }
        //获取重置次数
        $return['reset'] = D('GCount')->getAttr($this->mTid, 'star_reset');
        //获取星灵列表
        $return['list'] = D('GStar')->getAll($this->mTid);
        //返回
        return $return;
    }

    //升级星位
    public function levelup()
    {

        //检查星位是否已经开启
        $starConfig = D('Static')->access('star', $_POST['position']);

        //获取星位信息
        if (false === $starInfo = $this->getStarInfo($starConfig)) {
            C('G_ERROR', 'star_not_open');
            return false;
        }

        //获取星位升阶配置
        $starUpgradeConfig = D('Static')->access('star_upgrade');

        //检查星数是否足够
        $levelNew = $starInfo['level'] + 1;

        //是否已经达到上限
        if (!isset($starUpgradeConfig[$levelNew])) {
            C('G_ERROR', 'star_level_max');
            return false;
        }

        //配置
        $starUpgradeConfig = $starUpgradeConfig[$levelNew];

        //检查金币是否足够
        if (!$gold = $this->verify($starUpgradeConfig['consume_gold'], 'gold')) {
            return false;
        }

        //检查道具是否足够
        if (!$this->verify($starUpgradeConfig['consume_item_count'], 'item', $starUpgradeConfig['consume_item_id'])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣金币
        if (false === $this->recover('gold', $starUpgradeConfig['consume_gold'], null, $gold)) {
            goto end;
        }

        //扣道具
        if (false === $this->recover('item', $starUpgradeConfig['consume_item_id'], $starUpgradeConfig['consume_item_count'])) {
            goto end;
        }

        //升级
        if (!D('GStar')->levelup($this->mTid, $_POST['position'])) {
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

    //装备伙伴
    public function equip()
    {
        //检查星位是否已经开启
        $starConfig = D('Static')->access('star', $_POST['position']);

        //获取星位信息
        if (false === $starInfo = $this->getStarInfo($starConfig)) {
            C('G_ERROR', 'star_not_open');
            return false;
        }

        //查看星位上是不是已经是这个伙伴了
        if ($starInfo['partner'] == $_POST['partner']) {
            return true;
        }

        //查看伙伴是否合法
        if (!D('GPartner')->isExist($this->mTid, $_POST['partner'])) {
            return false;
        }

        //卸下伙伴伙伴
        if (false === D('GStar')->unloadPartner($this->mTid, $_POST['partner'])) {
            return false;
        }

        //装备伙伴
        if (false === D('GStar')->equip($this->mTid, $_POST['position'], $_POST['partner'])) {
            return false;
        }

        //返回
        return true;

    }

    //卸下伙伴
    public function unload()
    {

        //获取星位信息
        $starInfo = D('GStar')->getRow($this->mTid, $_POST['position']);
        if (empty($starInfo)) {
            return true;
        }

        if ($starInfo['partner'] == '0') {
            return true;
        }

        //装备伙伴
        if (false === D('GStar')->unload($this->mTid, $_POST['position'])) {
            return false;
        }

        //返回
        return true;

    }

    //重置星位
    public function reset()
    {

        //获取玩家重置星灵系统次数
        $count = D('GCount')->getAttr($this->mTid, 'star_reset');
        ++$count;

        //查询购买需要的货币
        $exchange = $this->exchangeMoney(self::EXCHANGE_TYPE, $count);
        $needType = $exchange['needType'];
        $needValue = $exchange['needValue'];

        //检查玩家当前货币是否足够
        if (!$now = $this->verify($needValue, $this->mMoneyType[$needType])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣钱
        if (!$this->recover($this->mMoneyType[$needType], $needValue, null, $now)) {
            goto end;
        }

        //增加重置次数
        if (!D('GCount')->incAttr($this->mTid, 'star_reset')) {
            goto end;
        }

        //team表清零
        $star = D('GTeam')->getAttr($this->mTid, 'star');
        if (!D('GTeam')->decAttr($this->mTid, 'star', $star, $star)) {
            goto end;
        }

        //重置
        if (!D('GStar')->reset($this->mTid)) {
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

    //金币洗炼
    public function baptizeGold()
    {
        //获取星位配置
        $starConfig = D('Static')->access('star', $_POST['position']);

        //查看金币是否足够
        if (!$goldNow = $this->verify($starConfig['gold_random_consumegold'], 'gold')) {
            return false;
        }

        //查看道具是否足够
        if (!$this->verify($starConfig['gold_random_consume_value'], 'item', $starConfig['gold_random_consume_type'])) {
            return false;
        }

        //获取星位信息
        if (false === $starInfo = $this->getStarInfo($starConfig)) {
            C('G_ERROR', 'star_not_open');
            return false;
        }

        //lua随机
        $rand = lua('star', 'star_gold_refresh', array((int)$starInfo['gold_count'], (int)$starInfo['diamond_count']));

        //开始事务
        $this->transBegin();

        //扣钱
        if (!$this->recover('gold', $starConfig['gold_random_consumegold'], null, $goldNow)) {
            goto end;
        }

        //扣道具
        if (!$this->recover('item', $starConfig['gold_random_consume_type'], $starConfig['gold_random_consume_value'])) {
            goto end;
        }

        //保存数据
        $return['attr1'] = $save['cache_attr1'] = $rand[0];
        $return['attr2'] = $save['cache_attr2'] = $rand[1];
        $return['gold_count'] = $save['gold_count'] = $starInfo['gold_count'] + 1;
        $return['diamond_count'] = $starInfo['diamond_count'];
        $where['tid'] = $this->mTid;
        $where['position'] = $_POST['position'];
        if (false === D('GStar')->UpdateData($save, $where)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return $return;
    }

    //水晶洗炼
    public function baptizeDiamond()
    {
        //获取星位配置
        $starConfig = D('Static')->access('star', $_POST['position']);

        //查看水晶是否足够
        if (!$diamondNow = $this->verify($starConfig['diamond_random_consume'], 'diamond')) {
            return false;
        }

        //获取星位信息
        if (false === $starInfo = $this->getStarInfo($starConfig)) {
            C('G_ERROR', 'star_not_open');
            return false;
        }

        //lua随机
        $rand = lua('star', 'star_diamond_refresh', array((int)$starInfo['gold_count'], (int)$starInfo['diamond_count']));

        //开始事务
        $this->transBegin();

        //扣钱
        if (!$this->recover('diamond', $starConfig['diamond_random_consume'], null, $diamondNow)) {
            goto end;
        }

        //保存数据
        $return['attr1'] = $save['cache_attr1'] = $rand[0];
        $return['attr2'] = $save['cache_attr2'] = $rand[1];
        $return['gold_count'] = $starInfo['gold_count'];
        $return['diamond_count'] = $save['diamond_count'] = $starInfo['diamond_count'] + 1;
        $where['tid'] = $this->mTid;
        $where['position'] = $_POST['position'];
        if (false === D('GStar')->UpdateData($save, $where)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return $return;
    }

    //洗炼覆盖
    public function baptizeCover()
    {
        //获取星位信息
        $starInfo = D('GStar')->getRow($this->mTid, $_POST['position']);
        if (empty($starInfo)) {
            C('G_ERROR', 'star_not_open');
            return false;
        }

        //查询是否洗炼过
        if ($starInfo['cache_attr1'] == '0' && $starInfo['cache_attr2'] == '0') {
            C('G_ERROR', 'star_not_baptize');
            return false;
        }

        //保存数据
        $save['attr1'] = $starInfo['cache_attr1'];
        $save['attr2'] = $starInfo['cache_attr2'];
        $save['cache_attr1'] = 0;
        $save['cache_attr2'] = 0;
        $where['tid'] = $this->mTid;
        $where['position'] = $_POST['position'];
        if (false === D('GStar')->UpdateData($save, $where)) {
            return false;
        }

        //返回
        return true;
    }

    //获取星位信息
    private function getStarInfo($starConfig)
    {

        //获取星位信息
        $starInfo = D('GStar')->getRow($this->mTid, $_POST['position']);
        if (empty($starInfo)) {

            //检查等级
            if ($starConfig['team_level'] > 1) {
                if (!$this->verify($starConfig['team_level'], 'level')) {
                    return false;
                }
            }

            //检查VIP等级
            if ($starConfig['vip_level'] >= 1) {
                if (!$this->verify($starConfig['vip_level'], 'vip')) {
                    return false;
                }
            }

            //创建信息
            if (false === D('GStar')->cData($this->mTid, $_POST['position'])) {
                return false;
            }

            $starInfo = D('GStar')->getRow($this->mTid, $_POST['position']);

        }

        //返回
        return $starInfo;

    }

}