<?php
namespace Home\Model;

use Think\Model;

class GParamsModel extends BaseModel
{

    public function getValue($key)
    {
        $key = strtoupper($key);
        $value = D('Predis')->cli('game')->hget('g_params', $key);
        if (empty($value)) {
            $select = $this->select();
            foreach ($select as $val) {
                $list[$val['index']] = $val['value'];
            }
            D('Predis')->cli('game')->hmset('g_params', $list);//存储缓存
            $value = $list[$key];
        }
        return $value;
    }

}