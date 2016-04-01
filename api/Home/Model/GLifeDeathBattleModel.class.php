<?php
namespace Home\Model;

use Think\Model;

class GLifeDeathBattleModel extends BaseModel
{

    protected $_auto = array(

        array('floor', 1),
        array('reward', '[]'),
        array('reward_last', '[]'),
        array('max', 0),
        array('ctime', 'time', 1, 'function'),//创建时间
        array('utime', 'time', 3, 'function'),//更新时间
        array('status', 0),
    );

    //获取单条数据
    public function getAttr($tid, $attr)
    {
        $where['tid'] = $tid;
        return $this->where($where)->getField($attr);
    }

    //获取单条数据
    public function getRow($tid, $field = null)
    {
        $where['tid'] = $tid;
        return $this->getRowCondition($where, $field);
    }

    //创建数据
    public function open($tid, $opponent, $next)
    {
        $data['tid'] = $tid;
        $data['opponent'] = $opponent;
        $data['reward_next'] = $next;
        if ($this->CreateData($data)) {
            return true;
        } else {
            return false;
        }
    }

    //修改状态
    public function status($tid, $status)
    {
        $where['tid'] = $tid;
        $data['status'] = $status;
        if ($this->UpdateData($data, $where)) {
            return true;
        } else {
            return false;
        }
    }

}