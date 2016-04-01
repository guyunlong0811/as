<?php
namespace Home\Model;

use Think\Model;

class GEventModel extends BaseModel
{

    //获取全部
    public function getAll()
    {
        $select = $this->select();
        if (empty($select))
            return array();
        foreach ($select as $value) {
            $list[$value['id']] = $value;
        }
        return $list;
    }

    //获取活动组
    public function getGroup($group)
    {
        $where['group'] = $group;
        $select = $this->where($where)->select();
        if (empty($select))
            return array();
        foreach ($select as $value) {
            $list[$value['id']] = $value;
        }
        return $list;
    }

}