<?php
namespace Home\Model;

use Think\Model;

class GChatModel
{

    //发送私聊
    public function sendPrivateMsg($send_tid, $receive_tid, $msg)
    {

        //查询目前的私聊ID
        $id = D('Predis')->cli('social')->get('p:id');

        //没有ID则从0开始
        if (!$id) {
            if (false === D('Predis')->cli('social')->set('p:id', 0)) {
                C('G_ERROR', 'redis_error');
                return false;
            }
        }

        //记录ID自增
        if (!$id = D('Predis')->cli('social')->incr('p:id')) {
            C('G_ERROR', 'redis_error');
            return false;
        }

//      $send['tid'] = $send_tid;
        $send['m'] = $msg;
        $send['t'] = time();

        //添加聊天记录
        D('Predis')->cli('social')->hmset('p:' . $id, $send);

        //添加索引列表
        D('Predis')->cli('social')->rpush('p:' . $send_tid . ':' . $receive_tid, $id);

        return true;

    }

    //接收私聊列表
    public function getPrivateList($receive_tid)
    {

        //获取所有发给我的好友列表
        $keysList = D('Predis')->cli('social')->keys('p:?:' . $receive_tid);
        if (!$keysList) {
            return array();
        }

        $list = array();
        foreach ($keysList as $value) {
            $row = array();
            //获取对方信息
            $arr = explode(':', $value);
            $row['tid'] = $arr[1];
            //对方昵称
            $row['nick'] = D('GTeam')->tid2nick($row['tid']);
            //消息数量
            $row['count'] = D('Predis')->cli('social')->llen($value);
            $list[] = $row;
        }

        return $list;

    }

    //查看单个玩家私聊信息
    public function getPrivateMsg($send_tid, $receive_tid)
    {

        //获取留言列表
        $lrange = D('Predis')->cli('social')->lrange('p:' . $send_tid . ':' . $receive_tid, 0, -1);

        if (!$lrange)
            return array();

        //列表获取具体消息
        $list = array();
        foreach ($lrange as $value) {
            $redis = D('Predis')->cli('social')->hgetall('p:' . $value);
            $row['msg'] = $redis['m'];
            $row['ts'] = $redis['t'];
            D('Predis')->cli('social')->del('p:' . $value);
            $list[] = $row;
        }

        //删除服务端留言
        D('Predis')->cli('social')->del('p:' . $send_tid . ':' . $receive_tid);

        return $list;

    }

    //世界频道发言
    public function sendWorldMsg($send_tid, $msg, $info)
    {

        //查询目前的世界ID
        $id = D('Predis')->cli('social')->get('w:id');

        //没有ID则从0开始
        if (!$id) {
            if (false === D('Predis')->cli('social')->set('w:id', 0)) {
                C('G_ERROR', 'redis_error');
                return false;
            }
        }

        //记录ID自增
        if (!$id = D('Predis')->cli('social')->incr('w:id')) {
            C('G_ERROR', 'redis_error');
            return false;
        }

        //聊天数据
        $send['c'] = $send_tid;
        if (!is_complete_string($msg)) {
            $msg = mb_substr($msg, 0, -1);
        }
        $send['m'] = $msg;
        $send['nn'] = $info['nickname'];
        $send['i'] = $info['icon'];
        $send['l'] = $info['level'];
        $send['t'] = time();
        if (false === D('Predis')->cli('social')->hmset('w:' . $id, $send)) {
            C('G_ERROR', 'redis_error');
            return false;
        }

        //设置聊天数据过期时间
        if (false === D('Predis')->cli('social')->expire('w:' . $id, get_config('REDIS_CHAT_WORLD_TIME'))) {
            C('G_ERROR', 'redis_error');
            return false;
        }

        return true;

    }

    //查看世界频道最近聊天情况
    public function getWorldMsg($last)
    {

        //查询目前的世界ID
        $id = D('Predis')->cli('social')->get('w:id');

        //没有ID则从0开始
        if (!$id) {
            if (false === D('Predis')->cli('social')->set('w:id', 0)) {
                C('G_ERROR', 'redis_error');
                return false;
            }
        }

        //如果没有消息
        if (!($id > 0)) {
            return array();
        }

        $end = $id;//最后一条
        $start = $id - get_config('REDIS_CHAT_WORLD_ROW');//第一条
        //第一条条件
        if ($last == 0)
            $start = 1;
        if ($last > $start)
            $start = $last + 1;
        else if ($start <= 0)
            $start = 1;

        $list = array();//聊天记录
        for ($i = $start; $i <= $end; ++$i) {
            $redis = D('Predis')->cli('social')->hgetall('w:' . $i);
            if (empty($redis)) {
                continue;
            }
            $row = array();
            $row['id'] = $i;
            $row['tid'] = $redis['c'];
            $row['msg'] = $redis['m'];
            $row['nick'] = $redis['nn'];
            $row['icon'] = $redis['i'];
            $row['level'] = $redis['l'];
            $row['ts'] = $redis['t'];
            $list[] = $row;
        }
        return $list;

    }

    //公会频道发言
    public function sendLeagueMsg($tid, $leagueId, $msg, $info)
    {

        //查询目前的公会的消息ID
        $id = D('Predis')->cli('social')->get('l:' . $leagueId . ':id');

        //没有ID则从0开始
        if (!$id) {
            if (false === D('Predis')->cli('social')->set('l:' . $leagueId . ':id', 0)) {
                C('G_ERROR', 'redis_error');
                return false;
            }
        }

        //记录ID自增
        if (!$id = D('Predis')->cli('social')->incr('l:' . $leagueId . ':id')) {
            C('G_ERROR', 'redis_error');
            return false;
        }

        //聊天数据
        $send['c'] = $tid;
        $send['m'] = $msg;
        $send['nn'] = $info['nickname'];
        $send['i'] = $info['icon'];
        $send['l'] = $info['level'];
        $send['t'] = time();
        if (false === D('Predis')->cli('social')->hmset('l:' . $leagueId . ':' . $id, $send)) {
            C('G_ERROR', 'redis_error');
            return false;
        }

        //设置聊天数据过期时间
        D('Predis')->cli('social')->expire('l:' . $leagueId . ':' . $id, get_config('REDIS_CHAT_LEAGUE_TIME'));

        return true;

    }

    //查看公会频道最近聊天情况
    public function getLeagueMsg($leagueId, $last)
    {

        //查询目前的公会消息ID
        $id = D('Predis')->cli('social')->get('l:' . $leagueId . ':id');
        //没有ID则从0开始
        if (!$id) {
            if (false === D('Predis')->cli('social')->set('l:' . $leagueId . ':id', 0)) {
                C('G_ERROR', 'redis_error');
                return false;
            }
        }

        //如果没有消息
        if (!($id > 0)) {
            return array();
        }

        $end = $id;//最后一条
        $start = $id - get_config('REDIS_CHAT_LEAGUE_ROW');//第一条

        //第一条条件
        if ($last == 0)
            $start = 1;
        else if ($last > $start)
            $start = $last + 1;
        else if ($start <= 0)
            $start = 1;

        $list = array();//聊天记录
        for ($i = $start; $i <= $end; ++$i) {
            $redis = D('Predis')->cli('social')->hgetall('l:' . $leagueId . ':' . $i);
            if (empty($redis)) {
                continue;
            }
            $row = array();
            $row['id'] = $i;
            $row['tid'] = $redis['c'];
            $row['msg'] = $redis['m'];
            $row['nick'] = $redis['nn'];
            $row['icon'] = $redis['i'];
            $row['level'] = $redis['l'];
            $row['ts'] = $redis['t'];
            $list[] = $row;
        }

        return $list;

    }

    //发送系统公告
    public function sendNoticeMsg($send_tid, $msg, $level, $endtime = 0, $interval = 0)
    {

        if ($interval == 0) {
            $interval = round(get_config('REDIS_CHAT_NOTICE_TIME') / 60) + 1;
        }

        if ($endtime == 0) {
            $endtime = time() + get_config('REDIS_CHAT_NOTICE_TIME');
        }

        //查询目前的公告ID
        $id = D('Predis')->cli('social')->get('n:id');

        //没有ID则从0开始
        if (!$id) {
            if (false === D('Predis')->cli('social')->set('n:id', 0)) {
                C('G_ERROR', 'redis_error');
                return false;
            }
        }

        //记录ID自增
        if (!$id = D('Predis')->cli('social')->incr('n:id')) {
            C('G_ERROR', 'redis_error');
            return false;
        }

        //聊天数据
        $now = time();
        $send['c'] = $send_tid;
        $send['m'] = $msg;
        $send['t'] = $now;
        $send['l'] = $level;
        $send['et'] = $endtime;
        $send['iv'] = $interval * 60;
        if (false === D('Predis')->cli('social')->hmset('n:' . $id, $send)) {
            C('G_ERROR', 'redis_error');
            return false;
        }

        //设置聊天数据过期时间
        $ttl = $endtime == 0 ? get_config('REDIS_CHAT_NOTICE_TIME') : $endtime - $now;
        D('Predis')->cli('social')->expire('n:' . $id, $ttl);

        //发送系统公告同时需要发送世界频道
//        if(!$this->sendWorldMsg($send_tid,$msg,0)){return false;}

        return true;

    }

    //取消系统公告
    public function cancelNoticeMsg($send_tid, $msg, $level, $endtime = 0, $interval = 0)
    {
        //获取当前所有公告的key
        $keys = D('Predis')->cli('social')->keys('n:?');

        //遍历公告
        foreach ($keys as $value) {
            if ($value != 'n:id') {
                $notice = D('Predis')->cli('social')->hgetall($value);
                if ($notice['c'] == $send_tid && $notice['m'] == $msg && $notice['l'] == $level && $notice['et'] == $endtime && $notice['iv'] == $interval * 60) {
                    D('Predis')->cli('social')->del($value);
                }
            }
        }
        return true;

    }

    //查看系统公告最近情况
    public function getNoticeMsg($last)
    {

        //查询目前的公告ID
        $id = D('Predis')->cli('social')->get('n:id');

        //没有ID则从0开始
        if (!$id) {
            if (false === D('Predis')->cli('social')->set('n:id', 0)) {
                C('G_ERROR', 'redis_error');
                return false;
            }
        }

        //如果没有消息
        if (!($id > 0)) {
            return array();
        }

        $end = $id;//最后一条
        $start = $id - get_config('REDIS_CHAT_NOTICE_ROW');//第一条

        //第一条条件
        if ($last == 0)
            $start = 1;
        if ($last > $start)
            $start = $last + 1;
        else if ($start <= 0)
            $start = 1;

        $list = array();//聊天记录
        for ($i = $start; $i <= $end; ++$i) {
            $row = array();
            $redis = D('Predis')->cli('social')->hgetall('n:' . $i);
            if (empty($redis)) {
                continue;
            }
            $row['id'] = $i;
//            $row['tid'] = $redis['c'];
            $row['msg'] = $redis['m'];
            $row['ts'] = $redis['t'];
            $row['lvl'] = $redis['l'];
            $row['end'] = $redis['et'];
            $row['interval'] = $redis['iv'];
            $list[] = $row;
        }

        //获取昵称
//        foreach ($list as $key => $value)
//            if ($value['tid'] == $tid)//查看发言是不是自己
//                unset($list[$key]);//删除发言不返回客户端
//            else
//                $list[$key]['nick'] = D('GTeam')->tid2nick($value['tid']);

        return $list;

    }

    //发送公会战跑马灯
    public function sendLeagueFightNoticeMsg($send_tid, $leagueId, $sendLeagueId, $nickname, $msg, $type)
    {

        //查询目前的公告ID
        $id = D('Predis')->cli('social')->get('lfn:' . $leagueId . ':id');

        //没有ID则从0开始
        if (!$id) {
            if (false === D('Predis')->cli('social')->set('lfn:' . $leagueId . ':id', 0)) {
                C('G_ERROR', 'redis_error');
                return false;
            }
        }

        //记录ID自增
        if (!$id = D('Predis')->cli('social')->incr('lfn:' . $leagueId . ':id')) {
            C('G_ERROR', 'redis_error');
            return false;
        }

        //聊天数据
        $send['c'] = $send_tid;
        $send['lid'] = $sendLeagueId;
        $send['nn'] = $nickname;
        $send['m'] = $msg;
        $send['tp'] = $type;
        $send['t'] = time();
        if (false === D('Predis')->cli('social')->hmset('lfn:' . $leagueId . ':' . $id, $send)) {
            C('G_ERROR', 'redis_error');
            return false;
        }

        //设置聊天数据过期时间
        D('Predis')->cli('social')->expire('lfn:' . $leagueId . ':' . $id, get_config('REDIS_CHAT_LEAGUE_NOTICE_TIME'));

        return true;

    }


    //查看公会战跑马灯
    public function getLeagueFightNoticeMsg($leagueId, $last)
    {

        $row_max = 90;

        //查询目前的公会消息ID
        $id = D('Predis')->cli('social')->get('lfn:' . $leagueId . ':id');
        //没有ID则从0开始
        if (!$id) {
            if (false === D('Predis')->cli('social')->set('lfn:' . $leagueId . ':id', 0)) {
                C('G_ERROR', 'redis_error');
                return false;
            }
        }

        //如果没有消息
        if (!($id > 0)) {
            return array();
        }

        $end = $id;//最后一条
        $start = $id - $row_max;//第一条

        //第一条条件
        if ($last == 0)
            $start = 1;
        else if ($last > $start)
            $start = $last + 1;
        else if ($start <= 0)
            $start = 1;

        $list = array();//聊天记录
        for ($i = $start; $i <= $end; ++$i) {
            $redis = D('Predis')->cli('social')->hgetall('lfn:' . $leagueId . ':' . $i);
            if (empty($redis)) {
                continue;
            }
            $row = array();
            $row['id'] = $i;
            $row['tid'] = $redis['c'];
            $row['league_id'] = $redis['lid'];
            $row['nickname'] = $redis['nn'];
            $row['msg'] = $redis['m'];
            $row['type'] = $redis['tp'];
            $row['ts'] = $redis['t'];
            $list[] = $row;
        }

        return $list;

    }

}