<?php
namespace Home\Api;

use Think\Controller;

class FateApi extends BaseApi
{

    //获取活动数据
    public function getInfo($configAll = array(), $index = 0)
    {
        //活动状态
        $return['status'] = 0;
        $return['need_diamond'] = 0;
        $return['need_vip'] = 0;
        $return['starttime'] = 0;
        $return['endtime'] = 0;
        $return['list'] = array();

        //获取活动开放时间
        $now = time();
        $json = D('GParams')->getValue('FATE_OPEN_TIME');
        $arrTime = json_decode($json, true);
        foreach ($arrTime as $value) {

            $start = strtotime($value['starttime']);
            $end = strtotime($value['endtime']);

            //活动已经结束
            if ($now > $end) {
                continue;
            }

            //活动未开始
            if ($now < $start && ($return['starttime'] == 0 || $return['starttime'] > $start)) {
                $return['starttime'] = $start;
                $return['endtime'] = $end;
            }

            //正在活动时间
            if ($start <= $now && $now <= $end) {
                $return['starttime'] = $start;
                $return['endtime'] = $end;
                break;
            }
        }

        //获取活动配置
        if (empty($configAll)) {
            $configAll = D('Static')->access('fate');
        }

        //获取玩家参与活动情况
        if (!($index > 0)) {
            $index = D('GFate')->getCurrent($this->mTid);
        }

        //获取活动配置
        $config = $configAll[$index];

        //查看是否已经完成
        if (empty($config)) {
            $return['status'] = 1;//已完成全部活动
            $config = end($configAll);
        }

        //整理数据
        $return['need_diamond'] = $config['need_diamond'];
        $return['need_vip'] = $config['need_vip'];
        for ($i = 1; $i <= 10; ++$i) {
            $return['list'][] = $config['bonus_diamond_' . $i];
        }

        //返回
        return $return;

    }

    //获取活动奖励
    public function round()
    {
        //获取活动配置
        $configAll = D('Static')->access('fate');

        //获取玩家参与活动情况
        $index = D('GFate')->getCurrent($this->mTid);

        //获取活动配置
        $config = $configAll[$index];
        if (empty($config)) {
            C('G_ERROR', 'fate_over');
            return false;
        }

        //检查vip情况
        if (false === $this->verify($config['need_vip'], 'vip')) {
            return false;
        }

        //检查水晶情况
        if (false === $diamondNow = $this->verify($config['need_diamond'], 'diamond')) {
            return false;
        }

        //计算概率
        $arrRate = array();
        for ($i = 1; $i <= 10; ++$i) {
            $arrRate[$i] = $config['bonus_diamond_' . $i . '_prop'];
        }

        //权重算法
        $rs = weight($arrRate);
        $addDiamond = $config['bonus_diamond_' . $rs];

        //增加水晶
        $realDiamond = $addDiamond - $config['need_diamond'];

        //开始事务
        $this->transBegin();

        //改变水晶
        if ($realDiamond > 0) {
            //增加情况
            if (false === $this->produce('diamondFree', $realDiamond, null, $diamondNow)) {
                goto end;
            }
        } else {
            //减少情况
            $valueDiamond = abs($realDiamond);
            if (false === $this->recover('diamond', $valueDiamond, null, $diamondNow)) {
                goto end;
            }
        }

        //记录
        if (false === D('GFate')->round($this->mTid, $index, $addDiamond)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        $return['result'] = $rs;
        $return['diamond'] = $realDiamond;
        $info = $this->getInfo($configAll, $index + 1);
        $return = array_merge($return, $info);
        return $return;

    }

}