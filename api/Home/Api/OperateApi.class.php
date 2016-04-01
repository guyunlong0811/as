<?php
namespace Home\Api;

use Think\Controller;

class OperateApi extends BaseApi
{

    //获取运营公告
    public function notice()
    {
        $list = array();
        $noticeConfig = D('StaticDyn')->access('notice');
        $sid = C('G_SID');
        $channelId = D('GTeam')->getAttr($this->mTid, 'channel_id');
        $ts = time();
        foreach ($noticeConfig as $value) {
            //查看状态
            if ($value['status'] == '0') {
                continue;
            }
            //查看渠道号是否匹配
            $serverList = explode('#', $value['server']);
            if ($value['server'] != '0' && !in_array($sid, $serverList)) {
                continue;
            }
            //查看渠道号是否匹配
            $channelList = explode('#', $value['channel']);
            if ($value['channel'] != '0' && !in_array($channelId, $channelList)) {
                continue;
            }
            //查看开始时间
            if ($value['starttime'] > $ts) {
                continue;
            }
            //查看结束时间
            if ($value['endtime'] != '0' && $value['endtime'] < $ts) {
                continue;
            }
            $arr = array();
            $arr['tab'] = $value['tab'];
            $arr['title'] = $value['title'];
            $arr['content'] = $value['content'];
            $arr['hot'] = $value['hot'];
            $arr['priority'] = $value['priority'];
            $arr['ts'] = $value['starttime'];
            $list[] = $arr;
        }
        return $list;
    }

    //获取客服信息
    public function service()
    {
        return D('GParams')->getValue('GAME_SERVICE');
    }

    //兑换码兑换
    public function exchange()
    {
        $params['sid'] = C('G_SID');
        $params['gid'] = C('GAME_ID');
        $params['uid'] = (int)$this->mUid;
        $params['channel_id'] = (int)D('GTeam')->getAttr($this->mTid, 'channel_id');
        $params['code'] = $_POST['code'];
        $params['level'] = D('GTeam')->getAttr($this->mTid, 'level');
        if (false === $return = uc_link($params, 'Exchange.index')) {
            return false;
        }
        if (!D('GMail')->send($return['goods'], $this->mTid, $this->mTid, null, true)) {
            return false;
        }
        return true;
    }

    //激活游戏
    public function activation()
    {
        $params['sid'] = C('G_SID');
        $params['gid'] = C('GAME_ID');
        $params['uid'] = (int)$this->mUid;
        $params['channel_id'] = (int)D('GTeam')->getAttr($this->mTid, 'channel_id');
        $count = D('GTeam')->where("`uid`='{$params['uid']}'")->count();
        if ($count > 0) {
            C('G_ERROR', 'user_existed');
            return false;
        }
        $params['code'] = $_POST['code'];
        if (false === $return = uc_link($params, 'Exchange.activation')) {
            return false;
        }
        return true;
    }

    //作弊
    public function cheat()
    {
        //发送邮件
        $errorLog = $this->mTid . '#' . $_POST['type'] . '#' . $_POST['value'] . '#' . $_POST['normal'];
        if (C('WARNING_TYPE') == 'File') {
            write_log($errorLog, 'error/cheat/');
        } else if (C('WARNING_TYPE') == 'Mail') {
            think_send_mail('error_report@forevergame.com', 'error_report', 'CHEAT_WARNING(' . APP_STATUS . ')', $errorLog);
        }

        if (false === D('LCheat')->cLog($this->mTid, $_POST['type'], $_POST['value'], $_POST['normal'])) {
            return false;
        }
        //踢下线
        D('Predis')->cli('game')->del('u:' . $this->mUid);
        D('Predis')->cli('game')->del('t:' . $this->mToken);
        D('Predis')->cli('game')->del($this->mSessionKey);
        //返回
        return true;
    }

    //HOME退出场景记录
    public function quit()
    {
        if (false === D('GCount')->setAttr($this->mTid, 'quit_scene', $_POST['scene'])) {
            return false;
        }
        return true;
    }

    //运营活动情况
    public function getActivityList()
    {
        //当前时间
        $now = time();

        //获取活动情况
        $config = D('StaticDyn')->access('event');

        //获取当前服务器ID
        $serverId = C('G_SID');

        //获取当前渠道ID
        $channelId = $this->mSessionInfo['channel_id'];

        //整理数据
        $list = array();
        foreach ($config as $value) {
            $group = array();
            $set = array();
            $bonus = array();

            //检查活动状态
            if ($value['status'] != '1') {
                continue;
            }

            //检查活动时间
            if ($value['starttime'] > $now || $now > $value['endtime']) {
                continue;
            }

            //检查服务器
            if ($value['server'] != 0 && $value['server'] != $serverId) {
                $serverList = explode('#', $value['server']);
                if (!in_array($serverId, $serverList)) {
                    continue;
                }
            }

            //检查渠道
            if ($value['channel'] != 0 && $value['channel'] != $channelId) {
                $channelList = explode('#', $value['channel']);
                if (!in_array($channelId, $channelList)) {
                    continue;
                }
            }

            //处理数据
            if (isset($list[$value['group']])) {
                $set['value'] = $value['value'];
                $set['receive_max'] = $value['receive_max'];
                for ($i = 1; $i <= 8; ++$i) {
                    if ($value['bonus_' . $i . '_type'] > 0) {
                        $bonus['type'] = $value['bonus_' . $i . '_type'];
                        $bonus['value_1'] = $value['bonus_' . $i . '_value_1'];
                        $bonus['value_2'] = $value['bonus_' . $i . '_value_2'];
                        $set['bonus'][] = $bonus;
                    }
                }
                $list[$value['group']]['set'][] = $set;
            } else {
                $group['group'] = $value['group'];
                $group['type1'] = $value['type1'];
                $group['type2'] = $value['type2'];
                $group['name'] = $value['name'];
                $group['icon'] = $value['icon'];
                $group['des'] = $value['des'];
                $group['starttime'] = $value['starttime'];
                $group['endtime'] = $value['endtime'];

                //获取当前完成值
                if (!isset($completeValue[$value['type1'] . '_' . $value['type2']])) {
                    $where = "`tid` = '{$this->mTid}'";

                    //充值or消费
                    switch ($value['type1']) {
                        case '1':
                            $where .= " && `attr`='diamond_pay' && `value` > 0";
                            break;
                        case '2':
                            $where .= " && (`attr`='diamond_pay' || `attr`='diamond_free') && `value` < 0";
                            break;
                    }

                    //累计or每日
                    switch ($value['type2']) {
                        case '1':
                            $where .= " && `ctime` between '{$value['starttime']}' and '{$value['endtime']}'";
                            break;
                        case '2':
                            $dayStart = get_daily_utime();
                            $dayEnd = $dayStart + 86399;
                            $where .= " && `ctime` between '{$dayStart}' and '{$dayEnd}'";
                            break;
                    }

                    $sum = D('LTeam')->where($where)->sum('value');
                    $completeValue[$value['type1'] . '_' . $value['type2']] = abs($sum);
                }
                $group['complete_value'] = $completeValue[$value['type1'] . '_' . $value['type2']];

                $set['value'] = $value['value'];
                $set['receive_max'] = $value['receive_max'];
//                    $set['complete'] = isset($complete[$value['index']]) ? $complete[$value['index']] : 0;
                for ($i = 1; $i <= 8; ++$i) {
                    if ($value['bonus_' . $i . '_type'] > 0) {
                        $bonus['type'] = $value['bonus_' . $i . '_type'];
                        $bonus['value_1'] = $value['bonus_' . $i . '_value_1'];
                        $bonus['value_2'] = $value['bonus_' . $i . '_value_2'];
                        $set['bonus'][] = $bonus;
                    }
                }
                $group['set'][] = $set;
                $list[$value['group']] = $group;
            }

        }

        //返回
        $list = array_values($list);
        return $list;
    }

    //运营活动开放数量
    public function getActivityCount()
    {
        //当前时间
        $now = time();

        //获取活动情况
        $config = D('StaticDyn')->access('event');

        //遍历
        $list = array();
        foreach ($config as $value) {
            if ($value['status'] == '1' && $value['starttime'] <= $now && $now <= $value['endtime']) {
                $list[] = $value['group'];
            }
        }

        //计算数量
        $list = array_unique($list);
        $count = count($list);

        //转盘开放
        $json = D('GParams')->getValue('FATE_OPEN_TIME');
        $arrTime = json_decode($json, true);
        foreach ($arrTime as $value) {

            $start = strtotime($value['starttime']);
            $end = strtotime($value['endtime']);

            //正在活动时间
            if ($start <= $now && $now <= $end) {
                $count = $count + 1;
                break;
            }
        }

        //返回
        return $count;

    }

}