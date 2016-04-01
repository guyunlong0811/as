<?php
namespace Home\Api;

use Think\Controller;

class ItemApi extends BaseApi
{

    static private $achievement = array(
        2063001 => 'gold_box_use',
        2064001 => 'silver_box_use',
    );

    //获取背包列表
    public function getList($tid = null)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        //查询所有正式道具背包的物品
        return D('GItem')->getAll($this->mTid);
    }

    //道具出售
    public function sell()
    {

        //查询玩家拥有道具总数
        if (!$this->verify($_POST['count'], 'item', $_POST['item'])) {
            return false;
        }

        //查询道具配置
        $gold = D('Static')->access('item', $_POST['item'], 'sell_gold');
        if ($gold == 0) {
            C('G_ERROR', 'item_cannot_sell');//道具不能分解
            return false;
        }
        $gold = $gold * $_POST['count'];

        //开始事务
        $this->transBegin();

        //扣除道具
        if (!$this->recover('item', $_POST['item'], $_POST['count'])) {
            goto end;
        }

        //加钱
        if (!$this->produce('gold', $gold)) {
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

    //批量卖出道具
    public function sellAll()
    {

        //查询需要卖出道具的现有数量
        $currList = D('GItem')->getCounts($this->mTid, $_POST['list']);

        //开始事务
        $this->transBegin();

        //遍历物品
        $goldAll = 0;
        foreach ($currList as $itemId => $count) {
            if (!empty($count) && $count > 0) {
                //检查物品是否都可出售
                $gold = D('Static')->access('item', $itemId, 'sell_gold');
                if ($gold == 0) {
                    C('G_ERROR', 'item_cannot_sell');//道具不能分解
                    return false;
                }

                //扣除道具
                if (!$this->recover('item', $itemId, $count)) {
                    goto end;
                }

                //计算总金币获得
                $goldAll += $gold * $count;
            }
        }

        //加钱
        if (!$this->produce('gold', $goldAll)) {
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

    //使用道具
    public function toUse()
    {

        //查询玩家拥有道具总数
        if (!$now = $this->verify($_POST['count'], 'item', $_POST['item'])) {
            return false;
        }

        //查询道具配置
        $itemConfig = D('Static')->access('item', $_POST['item']);

        //道具是否能够使用
        if ($itemConfig['use_effect'] == '0') {
            C('G_ERROR', 'item_cannot_use');//道具不能使用
            return false;
        }

        //等级限制
        if (!$this->verify($itemConfig['need_level'], 'level')) {
            return false;
        }

        //是否有足够消耗道具
        if ($itemConfig['consume_1'] > 0 && $itemConfig['consume_1_count'] > 0) {
            $needItem1 = $itemConfig['consume_1_count'] * $_POST['count'];
            if (!$this->verify($needItem1, 'item', $itemConfig['consume_1'])) {
                return false;
            }
        }
        if ($itemConfig['consume_2'] > 0 && $itemConfig['consume_2_count'] > 0) {
            $needItem2 = $itemConfig['consume_2_count'] * $_POST['count'];
            if (!$this->verify($needItem2, 'item', $itemConfig['consume_2'])) {
                return false;
            }
        }

        //更改公会名字需检查权限
        if ($itemConfig['use_effect'] == '7' && false == $leagueId = $this->leaguePermission($this->mTid, 1)) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //返回列表
        $list = array();

        //道具效果
        switch ($itemConfig['use_effect']) {
            case '1'://增加用户体力
                $effect = $itemConfig['use_effect_value'] * $_POST['count'];
                if (!$this->produce('vality', $effect)) {
                    goto end;
                }
                break;
            case '2'://增加伙伴经验数值
                $effect = $itemConfig['use_effect_value'] * $_POST['count'];
                if (!$rs = $this->produce('partnerExp', $_POST['partner'], $effect)) {
                    goto end;
                }
                //查看是否已经满了
                if ($rs['level'] == 0 && $rs['exp'] == 0) {
                    C('G_ERROR', 'partner_exp_max');
                    goto end;
                }
                break;
            case '3'://打开宝箱
                for ($i = 1; $i <= $_POST['count']; ++$i) {
                    if (false === $itemList = $this->produce('box', $itemConfig['use_effect_value'], 1)) {
                        goto end;
                    }
                    $list[] = $itemList;
                }
                break;
            case '4'://增加伙伴好感度
                $effect = $itemConfig['use_effect_value'] * $_POST['count'];
                if (!$this->produce('favour', $_POST['partner'], $effect)) {
                    goto end;
                }
                break;
            case '5'://增加技能点
                $effect = $itemConfig['use_effect_value'] * $_POST['count'];
                if (!$this->produce('skillPoint', $effect)) {
                    goto end;
                }
                break;
            case '8'://修改昵称
                $data['nickname'] = $_POST['params'];
                $where['tid'] = $this->mTid;
                if (!D('GTeam')->UpdateData($data, $where)) {
                    goto end;
                }
                break;
            case '9'://修改公会名称
                $data['name'] = $_POST['params'];
                $where['id'] = D('GLeagueTeam')->getAttr($this->mTid, 'league_id');
                if (!D('GLeague')->UpdateData($data, $where)) {
                    goto end;
                }
                break;
        }

        //扣除道具
        if ($itemConfig['consume_1'] > 0 && $itemConfig['consume_1_count'] > 0) {
            if (!$this->recover('item', $itemConfig['consume_1'], $needItem1)) {
                goto end;
            }
        }
        if ($itemConfig['consume_2'] > 0 && $itemConfig['consume_2_count'] > 0) {
            if (!$this->recover('item', $itemConfig['consume_2'], $needItem2)) {
                goto end;
            }
        }

        //记录成就物品使用情况
        foreach(self::$achievement as $itemId => $countField){
            if($itemId == $itemConfig['consume_1']){
                D('GCount')->incAttr($this->mTid, $countField, $needItem1);
            }
            if($itemId == $itemConfig['consume_2']){
                D('GCount')->incAttr($this->mTid, $countField, $needItem2);
            }
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //如果是开宝箱并且抽出的伙伴是SS级则发送全服公告
        if ($itemConfig['use_effect'] == '3') {

            //遍历物品
            foreach ($list as $value) {

                //如果有开出伙伴
                if ($value['type'] == '3') {

                    //获取该伙伴配置
                    $partnerGroupConfig = D('Static')->access('partner_group', $value['id']);
                    foreach ($partnerGroupConfig as $val) {
                        if ($val['is_init'] == 1) {
                            $partnerConfig = $val;
                            break;
                        }
                    }

                    //查看伙伴是否是SS级
                    if ($partnerConfig['partner_class'] == '9') {
                        //发送公告
                        $params['nickname'] = D('GTeam')->getAttr($this->mTid, 'nickname');
                        $params['item_name'] = $itemConfig['name'];
                        $params['partner_name'] = $partnerConfig['name'];
                        $noticeConfig = D('SEventString')->getConfig('ITEM_PARTNER', $params);
                        D('GChat')->sendNoticeMsg($this->mTid, $noticeConfig['des'], $noticeConfig['show_level']);
                    }

                }

            }

        }

        //返回
        return $list;

    }

}