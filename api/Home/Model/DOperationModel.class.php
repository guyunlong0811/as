<?php
namespace Home\Model;

use Think\Model;

class DOperationModel extends StaticDynModel
{
    private $mModule = array(
        //PVE
        'InstanceNormal' => '1001',
        'InstanceAdvance' => '1002',
        'GodBattle' => '1003',
        'AbyssBattle' => '1004',
        'LuckyCat' => '1005',
        'Babel' => '1006',

        //PVP
        'Arena' => '1101',
        'LifeDeathBattle' => '1102',
    );

    //获取当前倍率
    public function getRate($server, $channel, $module)
    {
        //时间戳
        $now = time();

        //是否存在此模块
        if (!isset($this->mModule[$module])) {
            return 1;
        }

        //获取配置
        $config = $this->access('operation');

        //默认值
        $rate = 1;

        //遍历
        if (!empty($config)) {

            foreach ($config as $value) {

                if ($value['status'] == '1' && $value['starttime'] <= $now && $now <= $value['endtime']) {

                    //判断模块
                    if ($value['module'] != $this->mModule[$module]) {
                        continue;
                    }

                    //判断服务器
                    if ($value['server'] != 0) {
                        $arrServer = explode('#', $value['server']);
                        if (!in_array($server, $arrServer)) {
                            continue;
                        }
                    }

                    //判断渠道
                    if ($value['channel'] != 0) {
                        $arrChannel = explode('#', $value['channel']);
                        if (!in_array($channel, $arrChannel)) {
                            continue;
                        }
                    }

                    //获取倍率
                    $rate = round($value['rate'] / 100);
                    break;

                }

            }

        }

        //返回
        return (int)$rate;
    }

}