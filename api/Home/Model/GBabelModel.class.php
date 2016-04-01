<?php
namespace Home\Model;

use Think\Model;

class GBabelModel extends BaseModel
{

    protected $_auto = array(
        array('floor', 1),
        array('partner', '[]'),
        array('max', 0),
        array('max_time', 0),
        array('max_sweep', 0),
        array('sweep_starttime', 0),
        array('ctime', 'time', 1, 'function'),//创建时间
        array('utime', 'time', 3, 'function'),//更新时间
        array('status', 0),
    );

    //获取属性
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
    public function open($tid)
    {
        $data['tid'] = $tid;
        if ($this->CreateData($data)) {
            return true;
        } else {
            return false;
        }
    }

    //获取扫荡时间
    public function getSweepInfo($tid)
    {
        $field = array('floor', 'max_sweep', 'sweep_starttime', 'status');
        $where['tid'] = $tid;
        $select = $this->getRowCondition($where, $field);
        if (empty($select)) {
            $data['floor'] = 0;
            $data['max_sweep'] = 0;
            $data['sweep_starttime'] = 0;
            $data['status'] = 0;
        } else {
            return $select;
        }
    }

}