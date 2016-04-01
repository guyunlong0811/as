<?php
namespace Home\Api;

use Think\Controller;

class NoviceLoginApi extends BEventApi
{

    const GROUP = 8;
    private $mOpen = true;

    public function _initialize()
    {
        parent::_initialize();
        if (!$this->event(self::GROUP)) {
            $this->mOpen = false;
        }
    }

    //返回现在是否可领
    public function getInfo()
    {
        //查询累计登录天数
        $return['login'] = D('GCount')->getAttr($this->mTid, 'login');
        //查询领取奖励记录
        $return['list'] = D('GNoviceLogin')->getAll($this->mTid);
        return $return;
    }


    //领取奖励
    public function receive()
    {

        //查询是否有登录记录
        if (D('GNoviceLogin')->isReceived($this->mTid, $_POST['day'])) {
            C('G_ERROR', 'novice_login_bonus_received');
            return false;
        }

        //查询累计登录天数
        $where['tid'] = $this->mTid;
        $day = D('GCount')->where($where)->getField('login');

        //查看等否领取
        if ($day < $_POST['day']) {
            C('G_ERROR', 'novice_login_not_enough');
            return false;
        }

        //查询奖品
        $config = D('Static')->access('novice_login');
        if (empty($config[$_POST['day']])) {
            C('G_ERROR', 'novice_login_finished');
            return false;
        }
        $config = $config[$_POST['day']];

        //开始事务
        $this->transBegin();

        //修改领取标记
        $add['tid'] = $this->mTid;
        $add['day'] = $_POST['day'];
        if (false === D('GNoviceLogin')->CreateData($add)) {
            goto end;
        }

        //获得奖励
        if (!$this->bonus($config)) {
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