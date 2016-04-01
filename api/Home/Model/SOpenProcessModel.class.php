<?php
namespace Home\Model;

use Think\Model;

class SOpenProcessModel extends StaticModel
{

    //检查功能是否开放
    public function checkOpen($tid, $index, $config = array())
    {
        //查询开放配置
        if (empty($config)) {
            $config = $this->access('open_process');
        }

        if (!isset($config[$index])) {
            return true;
        } else {
            $config = $config[$index];
        }

        //等级
        if ($config['open_level'] > 1) {
            $levelNow = D('GTeam')->getAttr($tid, 'level');//当前等级
            if ($levelNow < $config['open_level']) {
                goto end;
            }
        }

        //VIP等级
        if ($config['open_vip_level'] > 0) {
            $vipLevelNow = D('GVip')->getLevel($tid);//当前VIP等级
            if ($vipLevelNow < $config['open_vip_level']) {
                goto end;
            }
        }

        //副本
        if ($config['open_instance_index'] > 0) {
            if (!D('GInstance')->isComplete($tid, $config['open_instance_index'])) {//是否完成副本
                goto end;
            }
        }

        //满足条件
        return true;

        //未满足条件
        end:
        C('G_ERROR', 'open_require_not_meet');
        return false;
    }

    //检查是否有开放(升级等级数量,原来VIP的ID)
    public function checkNewOpen($tid, $type, $params)
    {
        //查询开放配置
        $config = $this->access('open_process');
        $levelNow = D('GTeam')->getAttr($tid, 'level');//当前等级
        $vipLevelNow = D('GVip')->getLevel($tid);//当前VIP等级
        foreach ($config as $key => $value) {
            switch ($type) {
                case 1:
                    $levelLast = $levelNow - $params;//升级前等级
                    if ($levelLast < $value['open_level'] && $value['open_level'] <= $levelNow) {
                        if ($this->checkOpen($tid, $key, $config)) {
                            $this->open($tid, $key);
                        }
                    }
                    break;
                case 2:
                    $vipLevelLast = $this->access('vip', $params, 'level');//查询升级前等级
                    if ($vipLevelLast < $value['open_vip_level'] && $value['open_vip_level'] <= $vipLevelNow) {
                        if ($this->checkOpen($tid, $key, $config)) {
                            $this->open($tid, $key);
                        }
                    }
                    break;
                case 3:
                    if ($value['open_instance_index'] == $params) {
                        if ($this->checkOpen($tid, $key, $config)) {
                            $this->open($tid, $key);
                        }
                    }
                    break;
            }
        }

        return true;

    }

    //开放
    private function open($tid, $key)
    {
        switch ($key) {
            case '2001'://竞技场
                return D('GArena')->open($tid);
                break;
            case '2002'://通天塔
                return true;
                break;
        }
    }

}