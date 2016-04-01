<?php
namespace Home\Api;

use Think\Controller;

class EmblemApi extends BaseApi
{

    //获取纹章列表
    public function getList($tid = null)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }
        //返回
        return D('GEmblem')->getAll($this->mTid);
    }

    //纹章装备
    public function equip()
    {

        //查看伙伴是否合法
        if (!D('GPartner')->isExist($this->mTid, $_POST['partner'])) {
            return false;
        }

        //获取数据
        $data = D('GEmblem')->getRow($_POST['emblem_id']);
        if (empty($data)) {
            C('G_ERROR', 'emblem_not_exist');
            return false;
        }

        //查看是否纹章是否属于自己
        if ($data['tid'] != $this->mTid) {
            C('G_ERROR', 'emblem_not_belong_player');
            return false;
        }

        //查看纹章是否已经装备了
        if ($data['slot'] > 0) {
            C('G_ERROR', 'emblem_equip_already');
            return false;
        }

        //获取最大孔位
        $max = D('Static')->access('params', 'EMBLEM_MAX_COUNT');
        if ($max < $_POST['slot']) {
            C('G_ERROR', 'emblem_slot_max');
            return false;
        }

        //查看伙伴槽位是否已经解锁
        $partnerConfig = D('Static')->access('partner', D('GPartner')->getAttr($this->mTid, $_POST['partner'], 'index'));
        switch ($partnerConfig['emblem_' . $_POST['slot'] . '_activate_type']) {
            case '1'://伙伴等级
                if (!$this->verify($partnerConfig['emblem_' . $_POST['slot'] . '_activate_value'], 'partnerLevel', $_POST['partner'])) {
                    return false;
                }
                break;
            case '2'://伙伴好感度
                if (!$this->verify($partnerConfig['emblem_' . $_POST['slot'] . '_activate_value'], 'favour', $_POST['partner'])) {
                    return false;
                }
                break;
            case '3'://伙伴品质
                if (!$this->verify($partnerConfig['emblem_' . $_POST['slot'] . '_activate_value'], 'partnerQuality', $_POST['partner'])) {
                    return false;
                }
                break;
        }

        //获取纹章配置
        $emblemConfig = D('Static')->access('emblem', $data['index']);

        //查看佩戴限制
        for ($i = 1; $i <= 2; ++$i) {

            switch ($emblemConfig['restrict_' . $i . '_type']) {

                case '0'://无
                    break;
                case '1'://等级
                    if (!$this->verify($emblemConfig['restrict_' . $i . '_value'], 'partnerLevel', $_POST['partner'])) {
                        return false;
                    }
                    break;

                case '2'://品质
                    if (!$this->verify($emblemConfig['restrict_' . $i . '_value'], 'partnerQuality', $_POST['partner'])) {
                        return false;
                    }
                    break;

            }

        }

        //开始事务
        $this->transBegin();

        //卸下已经装备的
        if (false === D('GEmblem')->unload($this->mTid, $_POST['partner'], $_POST['slot'])) {
            goto end;
        }

        //装备所选
        if (false === D('GEmblem')->equip($_POST['emblem_id'], $_POST['partner'], $_POST['slot'])) {
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

    //纹章卸下
    public function unload()
    {

        //查看伙伴是否合法
        if (!D('GPartner')->isExist($this->mTid, $_POST['partner'])) {
            return false;
        }

        //卸下
        if (false === D('GEmblem')->unload($this->mTid, $_POST['partner'], $_POST['slot'])) {
            return false;
        }

        //返回
        return true;

    }

    //纹章分解
    public function decompose()
    {

        //获取数据
        $data = D('GEmblem')->getRow($_POST['emblem_id']);
        if (empty($data)) {
            C('G_ERROR', 'emblem_not_exist');
            return false;
        }

        //查看是否纹章是否属于自己
        if ($data['tid'] != $this->mTid) {
            C('G_ERROR', 'emblem_not_belong_player');
            return false;
        }

        //开始事务
        $this->transBegin();

        //获取静态数据
        $emblemConfig = D('Static')->access('emblem', $data['index']);

        //加宝箱
        if (false === $item = $this->produce('box', $emblemConfig['decompose'], 1)) {
            goto end;
        }

        //销毁纹章
        if (false === D('GEmblem')->destroy($data)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return $item;

    }

    //纹章出售
    public function sell()
    {

        //获取数据
        $data = D('GEmblem')->getRow($_POST['emblem_id']);
        if (empty($data)) {
            C('G_ERROR', 'emblem_not_exist');
            return false;
        }

        //查看是否纹章是否属于自己
        if ($data['tid'] != $this->mTid) {
            C('G_ERROR', 'emblem_not_exist');
            return false;
        }

        //开始事务
        $this->transBegin();

        //获取静态数据
        $emblemConfig = D('Static')->access('emblem', $data['index']);

        //加钱
        if (!$this->produce('gold', $emblemConfig['sell_gold'])) {
            goto end;
        }

        //销毁纹章
        if (false === D('GEmblem')->destroy($data)) {
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

    //纹章合成
    public function combine()
    {
        //获取配方
        $emblemConfig = D('Static')->access('emblem');
        $emblemCombineConfig = D('Static')->access('emblem_combine');

        //获取玩家的金币
        $goldNow = $gGoldNow = D('GTeam')->getAttr($this->mTid, 'gold');

        //获取玩家道具情况
        $itemList = $gItemList = D('GItem')->getList($this->mTid);

        //获取玩家纹章情况
        $emblemList = $gEmblemList = D('GEmblem')->getList($this->mTid);

        //初始化消耗物品
        $needGold = 0;
        $needMaterial = array();

        //递归检查物品是否足够&累加所需物品
        if (!$this->combineLogic($_POST['emblem_combine_id'], $emblemConfig, $emblemCombineConfig, $gGoldNow, $gItemList, $gEmblemList, $needGold, $needMaterial)) {
            if ($needGold > $goldNow) {
                C('G_ERROR', 'not_enough_gold');
            } else {
                C('G_ERROR', 'emblem_material_not_enough');
            }
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣除道具
        foreach ($needMaterial as $key => $value) {

            foreach ($value as $k => $val) {

                switch ($key) {

                    case '1'://扣除灰烬
                        if (!$this->recover('item', $k, $val)) {
                            goto end;
                        }
                        break;

                    case '2'://扣除纹章
                        if (!D('GEmblem')->dec($this->mTid, $k, $val)) {
                            goto end;
                        }
                        break;

                }

            }

        }

        //扣钱
        if (!$this->recover('gold', $needGold, null, $goldNow)) {
            goto end;
        }

        //增加纹章
        if (false === $this->produce('emblem', $emblemCombineConfig[$_POST['emblem_combine_id']]['target_emblem_id'], 1)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        D('GCount')->incAttr($this->mTid, 'emblem_combine');
        return D('GEmblem')->getAll($this->mTid);

    }

    //合成递归逻辑
    private function combineLogic($emblemCombineId, $emblemConfig, $emblemCombineConfig, &$goldNow, &$itemList, &$emblemList, &$needGold, &$needMaterial)
    {

        //获取配方
        $combineConfig = $emblemCombineConfig[$emblemCombineId];

        //增加金币消耗
        $needGold += $combineConfig['consume_gold'];

        //判断金币是否足够
        if ($goldNow < $combineConfig['consume_gold']) {
            return false;
        } else {
            $goldNow -= $combineConfig['consume_gold'];
        }

        //判断材料是否足够
        for ($i = 1; $i <= 2; ++$i) {

            switch ($combineConfig['material_' . $i . '_type']) {
                case '0':
                    break;
                case '1':

                    //增加材料消耗
                    $needMaterial[$combineConfig['material_' . $i . '_type']][$combineConfig['material_' . $i . '_id']] += $combineConfig['material_' . $i . '_count'];

                    //判断是否有足够的灰烬
                    if ($itemList[$combineConfig['material_' . $i . '_id']] < $combineConfig['material_' . $i . '_count']) {
                        return false;
                    } else {
                        $itemList[$combineConfig['material_' . $i . '_id']] -= $combineConfig['material_' . $i . '_count'];
                    }

                    break;
                case '2':

                    //制造数据
                    if (!isset($emblemList[$combineConfig['material_' . $i . '_id']])) {
                        $emblemList[$combineConfig['material_' . $i . '_id']] = 0;
                    }

                    //判断是否有此纹章
                    if ($emblemList[$combineConfig['material_' . $i . '_id']] < $combineConfig['material_' . $i . '_count']) {

                        //计算缺少的个数
                        $lackEmblemCount = $combineConfig['material_' . $i . '_count'] - $emblemList[$combineConfig['material_' . $i . '_id']];

                        //记录材料消耗
                        if($emblemList[$combineConfig['material_' . $i . '_id']] > 0){
                            $needMaterial[$combineConfig['material_' . $i . '_type']][$combineConfig['material_' . $i . '_id']] += $emblemList[$combineConfig['material_' . $i . '_id']];
                        }

                        //消耗纹章个数
                        $emblemList[$combineConfig['material_' . $i . '_id']] = 0;

                        //获取空缺纹章的合成ID
                        $lackEmblemCombineId = $emblemConfig[$combineConfig['material_' . $i . '_id']]['emblem_combine_index'];

                        //判断纹章是否能够合成
                        if ($lackEmblemCombineId == '0') {
                            //记录材料消耗
                            $needMaterial[$combineConfig['material_' . $i . '_type']][$combineConfig['material_' . $i . '_id']] += $combineConfig['material_' . $i . '_count'];
                            return false;
                        } else {

                            //没有纹章则进入下一级合成
                            for ($j = 1; $j <= $lackEmblemCount; ++$j) {

                                //递归循环
                                if (!$this->combineLogic($lackEmblemCombineId, $emblemConfig, $emblemCombineConfig, $goldNow, $itemList, $emblemList, $needGold, $needMaterial)) {

                                    //记录材料消耗
                                    $needMaterial[$combineConfig['material_' . $i . '_type']][$combineConfig['material_' . $i . '_id']] += $combineConfig['material_' . $i . '_count'];
                                    return false;

                                }

                            }

                        }

                    } else {

                        //消耗纹章个数
                        $emblemList[$combineConfig['material_' . $i . '_id']] -= $combineConfig['material_' . $i . '_count'];

                        //记录材料消耗
                        $needMaterial[$combineConfig['material_' . $i . '_type']][$combineConfig['material_' . $i . '_id']] += $combineConfig['material_' . $i . '_count'];

                    }
                    break;

            }

        }

        return true;

    }

}