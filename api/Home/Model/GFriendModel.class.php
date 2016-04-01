<?php
namespace Home\Model;

use Think\Model;

class GFriendModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
        array('utime', 'time', 3, 'function'), //任何时候把utime字段设置为当前时间
        array('status', 2), //好友状态
    );

    //查询好友
    public function getRow($tid_1, $tid_2)
    {
        $where = "(`tid_1`={$tid_1} && `tid_2`={$tid_2}) || (`tid_1`={$tid_2} && `tid_2`={$tid_1})";
        return $this->getRowCondition($where);
    }

    //查询好友列表(tid)
    public function getList($tid, $status = null)
    {
        $where = "(`tid_1`='{$tid}' || `tid_2`='{$tid}')";
        if ($status)
            $where .= " && `status`='{$status}'";
        $friend = $this->field('`tid_1`,`tid_2`')->where($where)->select();
        $friendList = array();
        foreach ($friend as $value) {
            if ($value['tid_1'] != $tid)
                $friendList[] = $value['tid_1'];
            if ($value['tid_2'] != $tid)
                $friendList[] = $value['tid_2'];
        }
        return $friendList;
    }

    //改变属性
    public function updateStatus($tid_1, $tid_2, $value)
    {
        $where = "(`tid_1`={$tid_1} && `tid_2`={$tid_2}) || (`tid_1`={$tid_2} && `tid_2`={$tid_1})";
        $data['status'] = $value;
        if (false === $this->UpdateData($data, $where))
            return false;
        return true;
    }

}