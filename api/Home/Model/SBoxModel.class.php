<?php
namespace Home\Model;

use Think\Model;

class SBoxModel extends StaticModel
{

    const MAX_RAND = 1000000;//最大随机值
    const MAX_ITEM = 10;//宝箱最多包含道具数
    const MAX_SAFE = 20;//最大安全值
    private $mSafe = 0;

    //开宝箱
    public function open($index, $num = 1)
    {

        //抽概率
        $total = array();//合计
        $totalSimple = array();
        for ($i = 1; $i <= $num; ++$i) {

            $totalSimple[$i] = array();

            //单个宝箱
            $rs = $this->open1($index, $i);

            //合并奖励
            foreach ($rs as $value) {
                if (isset($total[$value['type']][$value['id']])) {
                    $totalSimple[$i][$value['type']][$value['id']] += $value['count'];
                    $total[$value['type']][$value['id']] += $value['count'];
                } else {
                    $totalSimple[$i][$value['type']][$value['id']] = $value['count'];
                    $total[$value['type']][$value['id']] = (int)$value['count'];
                }
            }

        }

        //数据整理
        $list = array();//开宝箱结果(分开)
        foreach ($totalSimple as $key => $value) {
            $all = array();
            foreach ($value as $type => $val) {
                foreach ($val as $id => $count) {
                    $arr['type'] = $type;
                    $arr['id'] = $id;
                    $arr['count'] = $count;
                    $all[] = $arr;

                }
            }
            $list[] = $all;
        }

        //返回
//        dump($list);
//        dump($total);
        $return['list'] = $list;
        $return['total'] = $total;
        return $return;

    }

    //开一个宝箱
    public function open1($index, $time = 1)
    {

        //宝箱ID保护
        if ($index == 0) {
            return array();
        }

        //重置安全层数
        if ($time > 0) {
            $this->mSafe = 0;
        }

        //安全层数
        ++$this->mSafe;
        if ($this->mSafe >= self::MAX_SAFE) {
            return array();
        }

        $config = $this->access('box', $index);//获取宝箱配置
        $result = array();//宝箱结果

        if ($config['prop_type'] == 1) {//独立概率

            //对宝箱所有的物品都计算概率
            for ($i = 1; $i <= self::MAX_ITEM; ++$i) {

                $rand = rand(1, self::MAX_RAND);//获取随机数
                $rate = $config['prop_' . $i . '_value'] + ($config['prop_' . $i . '_change'] * ($time - 1));//当次概率
                if ($rate < 0) $rate = 0;
                if ($rate > self::MAX_RAND) $rate = self::MAX_RAND;

                //抽中了
                if ($rand <= $rate) {

                    if ($config['item_' . $i . '_type'] == 8) {//如果宝箱里还是一个宝箱
                        for ($j = 1; $j <= $config['item_' . $i . '_count']; ++$j) {
                            $boxList[] = $this->open1($config['item_' . $i], 0);//开宝箱
                        }
                    } else {
                        if ($config['item_' . $i . '_type'] > 0 && $config['item_' . $i] > 0 && $config['item_' . $i . '_count'] > 0) {
                            $info['type'] = $config['item_' . $i . '_type'];
                            $info['id'] = $config['item_' . $i];
                            $info['count'] = $config['item_' . $i . '_count'];
                            $result[] = $info;
                        }
                    }

                }

            }

        } else if ($config['prop_type'] == 2) {//权重概率

            //计算概率
            $arrRate = array();
            for ($i = 1; $i <= self::MAX_ITEM; ++$i) {
                $rate = $config['prop_' . $i . '_value'] + ($config['prop_' . $i . '_change'] * ($time - 1));//当次概率
                if ($rate < 0) $rate = 0;
                $arrRate[$i] = $rate;
            }

            //权重算法
            $rs = weight($arrRate);
            if ($config['item_' . $rs . '_type'] == 8) {//如果宝箱里还是一个宝箱
                for ($j = 1; $j <= $config['item_' . $rs . '_count']; ++$j) {
                    $boxList[] = $this->open1($config['item_' . $rs], 0);//开宝箱
                }
            } else {
                if ($config['item_' . $rs . '_type'] > 0 && $config['item_' . $rs] > 0 && $config['item_' . $rs . '_count'] > 0) {
                    $info['type'] = $config['item_' . $rs . '_type'];
                    $info['id'] = $config['item_' . $rs];
                    $info['count'] = $config['item_' . $rs . '_count'];
                    $result[] = $info;
                }
            }

        }

        //如果结果中有宝箱嵌套宝箱
        if (!empty($boxList)) {
            foreach ($boxList as $value) {
                $result = array_merge($result, $value);//合并嵌套宝箱所有的物品
            }
        }

        //返回
        return $result;

    }

}