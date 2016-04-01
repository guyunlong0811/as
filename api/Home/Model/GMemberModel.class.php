<?php
namespace Home\Model;

use Think\Model;

class GMemberModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
        array('utime', 'time', 3, 'function'),
        array('last_receive_date', '0000-00-00'),
        array('count', 1),
        array('receive', 0),
    );

    public function getAll($tid)
    {
        $field = array('type', 'last_receive_date', 'expire',);
        $where['tid'] = $tid;
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        foreach ($select as $value) {
            $list[$value['type']] = $value;
            unset($list[$value['type']]['type']);
        }
        return $list;
    }

    //获取当前会员信息
    public function getList($tid)
    {
        $field = array('type', 'expire',);
        $where['tid'] = $tid;
        $list = $this->field($field)->where($where)->select();
        if (empty($list)) {
            return array();
        }
        return $list;
    }

    public function getRow($tid, $type, $field = null)
    {
        $where['tid'] = $tid;
        $where['type'] = array('like', $type . '%');
        return $this->getRowCondition($where, $field);
    }

    //收取奖励
    public function receive($tid, $type)
    {
        $now = time();
        $today = time2format(null, 2);
        $sql = "update `g_member` set `receive`=`receive`+1,`last_receive_date`='{$today}',`utime`='{$now}' where `tid`='{$tid}' && `type` like '{$type}%' limit 1";
        return $this->ExecuteData($sql);
    }

    //充值
    public function pay($tid, $type, $day)
    {
        //查询当前会员信息
        $row = $this->getRow($tid, $type);
        //更新会员信息
        if (empty($row)) {
            $add['tid'] = $tid;
            $add['type'] = $type;
            $add['expire'] = time2format(time() + (($day - 1) * 86400), 2);
            if (false === $this->CreateData($add)) {
                return false;
            }
        } else {
            $data['count'] = $row['count'] + 1;
            $today = strtotime('today');
            $expire = strtotime($row['expire']);
            if ($today > $expire) {
                $data['expire'] = time2format(time() + (($day - 1) * 86400), 2);
            } else {
                $data['expire'] = time2format($expire + ($day * 86400), 2);
            }
            $where['tid'] = $tid;
            $where['type'] = $type;
            if (false === $this->UpdateData($data, $where)) {
                return false;
            }
        }
        //加会员充值水晶
        $diamond = D('Static')->access('member', $type, 'diamond_bonus');
        if (false === D('GTeam')->incAttr($tid, 'diamond_pay', $diamond)) {
            return false;
        }
        //返回
        return true;
    }

    //查询玩家会员是否已经过期
    public function expireDay($tid, $type)
    {
        $where['tid'] = $tid;
        $where['type'] = array('like', $type . '%');
        $expire = $this->where($where)->getField('expire');
        if (empty($expire)) {
            return 0;
        } else {
            $expire = strtotime($expire);
            $today = strtotime('today');
            $day = (($expire - $today) / 86400) + 1;
            $day = $day >= 0 ? $day : 0;
            return $day;
        }
    }

}