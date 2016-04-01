<?php
namespace Home\Api;

use Think\Controller;

class EquipApi extends BaseApi
{

    //装备一键强化
    public function strengthenAll()
    {

        //获取伙伴等级
        $partnerLevel = D('GPartner')->getAttr($this->mTid, $_POST['group'], 'level');

        //获取装备情况
        $equipList = D('GEquip')->getAll($this->mTid, $_POST['group']);

        //获取强化配置
        $equipmentStrengthenConfig = D('Static')->access('equipment_strengthen');

        //初始
        $flag = false;
        $needGold = 0;

        //遍历装备
        $upEquip = array();
        foreach ($equipList as $value) {
            $maxLevel = D('Static')->access('equipment_upgrade', $value['index'], 'max_strengthen_level');
            $topLevel = min($partnerLevel, $maxLevel);
            if ($value['level'] < $topLevel) {
                //升级验证
                $flag = true;
                //装备对应等级
                $upEquip[$value['group']]['level'] = $value['level'];
                $upEquip[$value['group']]['top'] = $topLevel;
                //计算金币
                for ($i = $value['level'] + 1; $i <= $topLevel; ++$i) {
                    $needGold += $equipmentStrengthenConfig[$i]['consume_gold'];
                }
            }
        }

        //没有可升级
        if (!$flag) {
            C('G_ERROR', 'equip_level_max');
            return false;
        }

        //检查玩家是否有足够的金币
        if (!$gold = $this->verify($needGold, 'gold')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //强化
        foreach ($upEquip as $key => $value) {
            if (!D('GEquip')->strengthen($this->mTid, $key, $value['level'], $value['top'])) {
                goto end;
            }
        }

        //扣钱
        if (!$this->recover('gold', $needGold, null, $gold)) {
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

    //装备升级
    public function strengthen()
    {

        //查看是否达到了开放条件
//        if (!D('SOpenProcess')->checkOpen($this->mTid, 1001)) {
//            return false;
//        }

        //获取当前装备情况
        if (!$equipInfo = D('GEquip')->getRow($this->mTid, $_POST['group'])) {
            C('G_ERROR', 'partner_not_exist');
            return false;
        }

        //获取升阶信息
        $upgrade = D('Static')->access('equipment_upgrade', $equipInfo['index']);

        //检查装备等级是否超过当前品质可强化的最大等级
        if ($upgrade['max_strengthen_level'] < $_POST['level']) {
            C('G_ERROR', 'equip_upgrade_low');
            return false;
        }

        //获取伙伴信息
        $where['tid'] = $this->mTid;
        $where['group'] = $equipInfo['partner_group'];
        if (false === $partnerLevel = D('GPartner')->getAttr($this->mTid, $equipInfo['partner_group'], 'level')) {
            return false;
        }

        //检查装备等级是否超过伙伴等级
        if ($partnerLevel < $_POST['level']) {
            C('G_ERROR', 'partner_level_low');
            return false;
        }

        //等级出错
        if ($_POST['level'] <= $equipInfo['level']) {
            C('G_ERROR', 'equip_level_max');
            return false;
        }

        //检查玩家是否有足够的金币
        $config = D('Static')->access('equipment_strengthen');
        $needGold = 0;
        for ($i = $equipInfo['level'] + 1; $i <= $_POST['level']; ++$i) {
            $needGold += $config[$i]['consume_gold'];
        }
        if (!$gold = $this->verify($needGold, 'gold')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //强化
        if (!D('GEquip')->strengthen($this->mTid, $_POST['group'], $equipInfo['level'], $_POST['level'])) {
            goto end;
        }

        //扣钱
        if (!$this->recover('gold', $needGold, null, $gold)) {
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

    //装备升阶
    public function upgrade()
    {

        //查看是否达到了开放条件
//        if (!D('SOpenProcess')->checkOpen($this->mTid, 1002)) {
//            return false;
//        }

        //获取当前装备情况
        if (!$equipInfo = D('GEquip')->getRow($this->mTid, $_POST['group'])) {
            C('G_ERROR', 'partner_not_exist');
            return false;
        }

        //查询装备进阶数据
        $upgrade = D('Static')->access('equipment_upgrade', $equipInfo['index']);
        if (empty($upgrade) || $upgrade['next_quality'] == '0') {//是否可以进阶
            C('G_ERROR', 'equip_no_upgrade');//装备到达顶级
            return false;
        }

        //查看是否达到可以精炼的等级
        if ($equipInfo['level'] < $upgrade['max_strengthen_level']) {
            C('G_ERROR', 'equip_level_low');
            return false;
        }

        //是否有足够的材料
        for ($i = 1; $i <= 4; $i++) {//四种合成材料
            if (!$this->verify($upgrade['consume_item_' . $i . '_value'], 'item', $upgrade['consume_item_' . $i])) {
                return false;
            }
        }

        //是否有足够的金钱
        if (!$gold = $this->verify($upgrade['consume_gold'], 'gold')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //进阶
        if (!D('GEquip')->upgrade($this->mTid, $_POST['group'], $upgrade['next_quality'])) {
            goto end;
        }

        //扣钱
        if (!$this->recover('gold', $upgrade['consume_gold'], null, $gold)) {
            goto end;
        }

        //扣除合成材料
        for ($i = 1; $i <= 4; $i++)
            if (!$this->recover('item', $upgrade['consume_item_' . $i], $upgrade['consume_item_' . $i . '_value'])) {
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

    //装备附魔属性锁定
    public function enchantLock()
    {
        //查询装备信息
        $equipInfo = D('GEquip')->getRow($this->mTid, $_POST['group']);
        if (empty($equipInfo)) {
            C('G_ERROR', 'equip_not_exist');
            return false;
        }

        //查看属性是否已经锁定
        if ($equipInfo['extra_' . $_POST['extra'] . '_lock'] == '1') {
            C('G_ERROR', 'equip_enchant_locked');
            return false;
        }

        //获取当前已经锁定的属性个数
        $locked = 0;
        for ($i = 1; $i <= 4; ++$i) {
            if($equipInfo['extra_' . $i . '_lock'] == '1'){
                ++$locked;
            }
        }

        //获取当前装备附魔条目数
        $enchantsNum = D('Static')->access('equipment', $equipInfo['group'], array($equipInfo['index'], 'enchants_num'));
        if($locked + 1 == $enchantsNum){
            C('G_ERROR', 'equip_enchant_lock_max');
            return false;
        }

        //获取锁定所需水晶数量
        $exchange = $this->exchangeMoney(18, $locked + 1);

        //检查水晶是否足够
        if (!$moneyNow = $this->verify($exchange['needValue'], $this->mMoneyType[$exchange['needType']])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣钱
        if (!$this->recover($this->mMoneyType[$exchange['needType']], $exchange['needValue'], null, $moneyNow)) {
            goto end;
        }

        //锁定
        if (!D('GEquip')->lock($this->mTid, $_POST['group'], $_POST['extra'])) {
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

    //装备附魔属性解锁
    public function enchantUnlock()
    {
        //查询装备信息
        $equipInfo = D('GEquip')->getRow($this->mTid, $_POST['group']);
        if (empty($equipInfo)) {
            C('G_ERROR', 'equip_not_exist');
            return false;
        }

        //查看属性是否已经锁定
        if ($equipInfo['extra_' . $_POST['extra'] . '_lock'] == '0') {
            C('G_ERROR', 'equip_enchant_unlocked');
            return false;
        }

        //解锁
        if (!D('GEquip')->lock($this->mTid, $_POST['group'], $_POST['extra'], 0)) {
            return false;
        }

        //返回
        return true;

    }

    //普通装备附魔
    public function enchantNormal()
    {
        //查询装备信息
        $equipInfo = D('GEquip')->getRow($this->mTid, $_POST['group']);
        if (empty($equipInfo)) {
            C('G_ERROR', 'equip_not_exist');
            return false;
        }

        //获取装备品质
        $quality = D('Static')->access('equipment', $_POST['group'], array($equipInfo['index'], 'quality'));

        //获取附魔需要的资源
        $consume = lua('enchant', 'equipment_enchant_normal', array($quality));

        //检查金币是否足够
        if ($consume[0] > 0 && !$goldNow = $this->verify($consume[0], 'gold')) {
            return false;
        }

        //检查积分是否足够
        if ($consume[1] > 0 && !$materialScoreNow = $this->verify($consume[1], 'material_score')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣除金币
        if ($consume[0] > 0 && !$this->recover('gold', $consume[0], null, $goldNow)) {
            goto end;
        }

        //扣除积分
        if ($consume[1] > 0 && !$this->recover('material_score', $consume[1], null, $materialScoreNow)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        return $this->enchant($equipInfo);

    }

    //水晶装备附魔
    public function enchantDiamond()
    {
        //查询装备信息
        $equipInfo = D('GEquip')->getRow($this->mTid, $_POST['group']);
        if (empty($equipInfo)) {
            C('G_ERROR', 'equip_not_exist');
            return false;
        }

        //获取装备品质
        $quality = D('Static')->access('equipment', $_POST['group'], array($equipInfo['index'], 'quality'));

        //获取附魔需要的资源
        $consume = lua('enchant', 'equipment_enchant_diamond', array($quality));

        //检查水晶是否足够
        if (!$diamondNow = $this->verify($consume, 'diamond')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣除水晶
        if (!$this->recover('diamond', $consume, null, $diamondNow)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        return $this->enchant($equipInfo);
    }

    //装备附魔
    private function enchant($equipInfo)
    {
        //获取装备配置
        $equipConfig = D('Static')->access('equipment', $equipInfo['group'], $equipInfo['index']);

        //随机附魔属性
        $enchantsInfo['group'] = $_POST['group'];
        for ($i = 1; $i <= 4; ++$i) {

            //如果当前品质无此属性
            if ($equipConfig['enchants_num'] < $i) {
                $enchantsInfo['extra_' . $i . '_type'] = 0;
                $enchantsInfo['extra_' . $i . '_id'] = 0;
                $enchantsInfo['extra_' . $i . '_value'] = 0;
                continue;
            }

            //如果属性锁定
            if ($equipInfo['extra_' . $i . '_lock'] == '1') {
                $enchantsInfo['extra_' . $i . '_type'] = $equipInfo['extra_' . $i . '_type'];
                $enchantsInfo['extra_' . $i . '_id'] = $equipInfo['extra_' . $i . '_id'];
                $enchantsInfo['extra_' . $i . '_value'] = $equipInfo['extra_' . $i . '_value'];
                continue;
            }

            //获取附魔配置
            $enchantsInfo['extra_' . $i . '_type'] = $equipConfig['enchants_effect_' . $i . '_type'];
            switch ($equipConfig['enchants_effect_' . $i . '_type']) {
                case '1'://属性
                    $enchantsConfig = D('Static')->access('enchant_attribute', $equipConfig['enchants_effect_' . $i . '_group']);
                    break;

                case '2'://技能
                    $enchantsConfig = D('Static')->access('enchant_skill', $equipConfig['enchants_effect_' . $i . '_group']);
                    break;
            }

            //随机属性
            $arrRate = array();
            foreach ($enchantsConfig as $index => $value) {
                $arrRate[$index] = $value['probability'];
            }
            $enchantsInfo['extra_' . $i . '_id'] = weight($arrRate);

            //随机值
            switch ($equipConfig['enchants_effect_' . $i . '_type']) {
                case '1'://属性
                    $enchantsInfo['extra_' . $i . '_value'] = rand($enchantsConfig[$enchantsInfo['extra_' . $i . '_id']]['attribute_min'], $enchantsConfig[$enchantsInfo['extra_' . $i . '_id']]['attribute_max']);
                    break;

                case '2'://技能
                    $enchantsInfo['extra_' . $i . '_value'] = 0;
                    break;
            }

        }

        //存入redis
        D('Predis')->cli('game')->hset('s:' . $this->mTid, 'enchants', json_encode($enchantsInfo));

        //返回
        return $enchantsInfo;
    }


    //附魔覆盖
    public function enchantCover()
    {
        //获取附魔数据
        $enchantsInfo = D('Predis')->cli('game')->hget('s:' . $this->mTid, 'enchants');
        $enchantsInfo = json_decode($enchantsInfo, true);

        //检查装备是否一致
        if(empty($enchantsInfo) || $_POST['group'] != $enchantsInfo['group']){
            C('G_ERROR', 'equip_enchant_expired');
            return false;
        }

        //覆盖
        $where['tid'] = $this->mTid;
        $where['group'] = $_POST['group'];
        $data = $enchantsInfo;
        unset($data['group']);
        if (false === D('GEquip')->UpdateData($data, $where)) {
            return false;
        }

        //返回
        return true;
    }

    //附魔献祭
    public function enchantOffer()
    {

        //获取材料配置
        $materialConfig = D('Static')->access('enchant_material', $_POST['material_id']);

        //检查材料是否足够
        if (!$now = $this->verify($_POST['count'], $this->mBonusType[$materialConfig['type']], $materialConfig['id'])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣除道具
        if (!$this->recover($this->mBonusType[$materialConfig['type']], $materialConfig['id'], $_POST['count'], $now)) {
            goto end;
        }

        //增加积分
        if (!D('GTeam')->incAttr($this->mTid, 'material_score', $materialConfig['score'] * $_POST['count'])) {
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