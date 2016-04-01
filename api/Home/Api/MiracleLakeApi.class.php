<?php
namespace Home\Api;

use Think\Controller;

class MiracleLakeApi extends BEventApi
{

    const GROUP = 3;

    public function _initialize()
    {
        parent::_initialize();
        if (!$this->event(self::GROUP)) {
            exit;
        }//活动条件
    }

    //领取奖励
    public function drop()
    {

        //查看玩家是否有足够的献祭道具
        switch ($_POST['drop_type']) {
            case '1'://道具
                if (!$count = $this->verify($_POST['drop_count'], 'item', $_POST['drop_id'])) {
                    return false;
                }
                break;
            case '2'://神力
                if (!$count = $this->verify($_POST['drop_count'], 'soul', $_POST['drop_id'])) {
                    return false;
                }
                break;
        }

        //检查是否有足够的祭品
        if ($count < $_POST['drop_count']) {
            C('G_ERROR', 'offering_not_enough');
            return false;
        }

//        $box = lua('event','miracle_lake',array($_POST['drop_type'],$_POST['drop_id'],$_POST['drop_count'],));
        $box = 1;

        //开始事务
        $this->transBegin();

        //扣除物品
        switch ($_POST['drop_type']) {
            case '1'://道具
                if (!$this->recover('item', $_POST['drop_id'], $_POST['drop_count'])) {
                    goto end;
                }
                break;
            case '2'://神力
                if (!$this->recover('soul', $_POST['drop_id'], $_POST['drop_count'])) {
                    goto end;
                }
                break;
        }

        //发放奖励
        if (!$bonusList = $this->produce('box', $box, $_POST['drop_count'])) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return $bonusList;

    }

}