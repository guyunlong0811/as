<?php
namespace Home\Api;

use Think\Controller;

class RandomBattleApi extends BBattleApi
{
    //开始副本
    public function fight()
    {
        return $this->instanceFight('RandomBattle', $_POST['instance_id'], $_POST['partner']);
    }

    //副本胜利
    public function win()
    {
        if (false === $drop = $this->instanceWin($_POST['instance_id'])) {
            return false;
        }
        //返回掉落列表
        return $drop;
    }

    //副本失败
    public function lose()
    {
        return $this->instanceLose($_POST['instance_id']);
    }
}