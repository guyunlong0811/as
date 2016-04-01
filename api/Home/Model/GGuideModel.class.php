<?php
namespace Home\Model;

use Think\Model;

class GGuideModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
        array('utime', 'time', 3, 'function'), //任何时候把utime字段设置为当前时间
    );

    //查询当前背包情况
    public function getList($tid)
    {
        $field = array('step1', 'step2');
        $where['tid'] = $tid;
        $list = $this->field($field)->where($where)->select();
        if (empty($list)) {
            return array();
        }
        return $list;
    }

    //获取单条数据
    public function getRow($tid, $step1)
    {
        $where['tid'] = $tid;
        $where['step1'] = $step1;
        return $this->getRowCondition($where);
    }

    //获取引导完成情况
    public function getComplete($tid)
    {
        $select = $this->getList($tid);
        $list = array();
        if (!empty($select)) {
            foreach ($select as $value) {
                $list[$value['step1']] = $value['step2'];
            }
        }
        return $list;
    }

}