<?php
namespace Home\Model;

use Think\Model;

class SEventStringModel extends StaticModel
{

    //获取配置，覆盖通配字符串
    public function getConfig($index, $params)
    {
        $index = strtoupper($index);
        $config = $this->access('event_string', $index);
        foreach ($params as $key => $value) {
            $config['des'] = str_replace('@' . $key . '@', $value, $config['des']);
        }
        return $config;
    }

}