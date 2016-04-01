<?php
namespace Home\Api;

use Think\Controller;

class PrayTimedApi extends BaseApi
{

    //确定当前的限时抽卡ID
    private function getConfig()
    {
        //初始参数
        $status = 0;
        $minStartTime = 0;
        $config = array();
        $now = time();

        //获取所有配置
        $prayConfig = D('StaticDyn')->access('pray');

        //查看配置
        foreach ($prayConfig as $key => $value) {

            //活动是否开启
            if ($value['status'] == '0') {
                continue;
            }

            //是否是当前服务器
            if ($value['server'] != '0') {
                //查询是不是当前服务器
                $arrServer = explode('#', $value['server']);
                if (!in_array(C('G_SID'), $arrServer)) {
                    continue;
                }
            }

            //活动已经结束
            if ($value['endtime'] < $now) {
                continue;
            }

            //活动尚未开始
            if ($value['starttime'] > $now) {
                if ($minStartTime == 0 || $minStartTime > $value['starttime']) {
                    $config = $value;
                    $minStartTime = $value['starttime'];
                }
                continue;
            }

            //活动正在进行
            $status = 1;
            $config = $value;
            break;
        }

        //返回
        $return['status'] = $status;
        $return['config'] = $config;
        return $return;
    }

    //获取玩家免费祈愿时间
    public function getInfo($tid = null)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        //数据
        $info = array();

        //获取配置
        $rs = $this->getConfig();
        $status = $rs['status'];
        $config = $rs['config'];
        $dynId = $config['index'];

        //无数据
        if (empty($config)) {
            $info['starttime'] = 0;
            $info['endtime'] = 0;
        } else {

            //通用数据
            $info['starttime'] = $config['starttime'];
            $info['endtime'] = $config['endtime'];
            $info['partner'] = $config['partner'];
            $info['des'] = $config['des'];

            //排名信息
            if ($status == 0) {
                $info['rank'] = 0;
                $info['point'] = 0;
                $info['rank_list'] = array();
            } else {
                $row = D('GPrayTimed')->getRow($this->mTid, $dynId, array('point', 'utime'));
                $info['rank'] = D('GPrayTimed')->getRank($this->mTid, $dynId, $row);
                $info['point'] = $row['point'] ? $row['point'] : 0;
                $info['rank_list'] = D('GPrayTimed')->rank($dynId, 10);
            }

        }

        //获取更新时间
        $prayConfig = D('Static')->access('pray');
        $prayList = D('GPray')->getAll($this->mTid);
        $list = array();
        foreach ($prayConfig as $key => $value) {

            //是否为特殊抽卡
            if ($key < 10000) {
                continue;
            }

            //是否开启
            if ($value['show_in'] == '0') {
                continue;
            }

            //创建数据
            if (!isset($list[$key])) {
                $list[$key]['id'] = $key;
                $list[$key]['free'] = 0;
                $list[$key]['utime'] = 0;
                $list[$key]['count'] = 0;
            }

            //获取数据
            foreach ($prayList as $val) {
                if ($val['pray_id'] == $key) {
                    if ($val['is_free'] == 1) {
                        $list[$key]['free'] = D('LPray')->getTodayFreeCount($this->mTid, $key);//今天已经免费抽取次数
                        $list[$key]['utime'] = $val['utime'];//上次免费抽卡时间
                    }
                    $list[$key]['count'] += $val['count'];//上次免费抽卡时间
                }
            }

        }
        $info['list'] = array_values($list);

        //返回数据
        return $info;
    }

    //免费祈愿
    public function drawFree()
    {
        //获取活动配置
        $rs = $this->getConfig();
        $status = $rs['status'];
        $config = $rs['config'];
        $dynId = $config['index'];

        //查看活动是否开启
        if ($status != 1) {
            C('G_ERROR', 'activity_not_in_open_time');
            return false;
        }

        //抽卡
        if (false === $itemList = A('Pray', 'Api')->drawFree($this->mTid)) {
            return false;
        }

        //处理活动数据
        $times = $_POST['pray_id'] % 1000;
        $point = $config['point_' . $times];
        if (false === D('GPrayTimed')->record($this->mTid, $dynId, $point, 1, $times)) {
            return false;
        }

        //返回
        $row = D('GPrayTimed')->getRow($this->mTid, $dynId, array('point', 'utime'));
        $info['rank'] = D('GPrayTimed')->getRank($this->mTid, $dynId, $row);
        $info['point'] = $row['point'];
        $info['rank_list'] = D('GPrayTimed')->rank($dynId, 10);
        $info['list'] = $itemList;
        return $info;

    }

    //付费祈愿
    public function drawNow()
    {
        //获取活动配置
        $rs = $this->getConfig();
        $status = $rs['status'];
        $config = $rs['config'];
        $dynId = $config['index'];

        //查看活动是否开启
        if ($status != 1) {
            C('G_ERROR', 'activity_not_in_open_time');
            return false;
        }

        //抽卡
        if (false === $itemList = A('Pray', 'Api')->drawNow($this->mTid)) {
            return false;
        }

        //处理活动数据
        $times = $_POST['pray_id'] % 1000;
        $point = $config['point_' . $times];
        if (false === D('GPrayTimed')->record($this->mTid, $dynId, $point, 0, $times)) {
            return false;
        }

        //返回
        $row = D('GPrayTimed')->getRow($this->mTid, $dynId, array('point', 'utime'));
        $info['rank'] = D('GPrayTimed')->getRank($this->mTid, $dynId, $row);
        $info['point'] = $row['point'];
        $info['rank_list'] = D('GPrayTimed')->rank($dynId, 10);
        $info['list'] = $itemList;
        return $info;

    }

}