<?php
namespace Home\Model;

use Think\Model;

class GDailyRegisterModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
        array('utime', 'time', 3, 'function'), //更新时间
    );

    //获取本月签到天数
    public function getAll($tid)
    {
        $utime = get_daily_utime();
        $startDate = date('Y-m', $utime) . '-01 ' . C('DAILY_UTIME');
        $start = strtotime($startDate);
        $end = strtotime('+1 month ' . $startDate) - 1;
        $field = array('day', 'ctime', 'status',);
        $where['tid'] = $tid;
        $where['ctime'] = array('between', array($start, $end));
        $select = $this->field($field)->where($where)->order('`day` ASC')->select();
        if (empty($select)) {
            return array();
        }
        foreach ($select as $value) {
            $data[$value['day']] = $value;
        }
        return $data;
    }

    //查询单条数据
    public function getRow($tid, $day)
    {
        $where['tid'] = $tid;
        $where['day'] = $day;
        return $this->where($where)->find();
    }

    //查看今天是否可以领取
    public function isReceiveFree($tid)
    {
        //获取当月天数
        $dayAll = date('t', get_daily_utime());
        $dayReceived = $this->getCount($tid);
        if ($dayAll > $dayReceived) {
            return $this->isReceived($tid);
        } else {
            return true;
        }
    }

    //查看今天是否已经领取过
    public function isReceived($tid)
    {
        $where['tid'] = $tid;
        $where['ctime'] = array('egt', get_daily_utime());
        $count = $this->where($where)->count();
        if ($count > 0) {
            return true;
        }
        return false;
    }

    //查看玩家本月已经领取过几天
    public function getCount($tid)
    {
        $where['tid'] = $tid;
        return $this->where($where)->max('day');
    }

    //查看玩家本月已经付费领取过几天
    public function getPayCount($tid)
    {
        $where['tid'] = $tid;
        $where['pay'] = 1;
        return $this->where($where)->count();
    }

}