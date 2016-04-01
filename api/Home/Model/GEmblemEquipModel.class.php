<?php
namespace Home\Model;

use Think\Model;

class GEmblemEquipModel extends BaseModel
{

    //查询当前情况
    public function getAll($tid)
    {
        $field = array('tid', 'ctime');
        $where['tid'] = $tid;
        $list = $this->field($field, true)->where($where)->select();
        if (empty($list)) {
            return array();
        }
        return $list;
    }

    //获取多伙伴装备情况
    public function getPartnersList($tid, $partners = array())
    {
        $field = array('partner', 'slot', 'emblem');
        $where['tid'] = $tid;
        if (!empty($partners)) {
            $where['group'] = array('in', $partners);
        }
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        $list = array();
        foreach ($select as $value) {
            $list[$value['partner']][] = array('slot' => $value['slot'], 'emblem' => $value['emblem']);
        }
        return $list;
    }

    //获取装备情况
    public function getEquipList($tid, $partner)
    {
        $field = array('emblem');
        $where['tid'] = $tid;
        $where['partner'] = $partner;
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        $list = array();
        foreach ($select as $value) {
            $list[] = $value['emblem'];
        }
        return $list;
    }

    //获取单条数据
    public function getRow($tid, $partner, $slot)
    {
        $where['tid'] = $tid;
        $where['partner'] = $partner;
        $where['slot'] = $slot;
        return $this->where($where)->find();
    }

    //装备纹章
    public function cData($tid, $partner, $slot, $emblem)
    {
        $add['tid'] = $tid;
        $add['partner'] = $partner;
        $add['slot'] = $slot;
        $add['emblem'] = $emblem;
        return $this->CreateData($add);
    }

    //替换纹章
    public function uData($tid, $partner, $slot, $emblem)
    {
        $where['tid'] = $tid;
        $where['partner'] = $partner;
        $where['slot'] = $slot;
        $data['emblem'] = $emblem;
        return $this->UpdateData($data, $where);
    }

    //卸下装备
    public function dData($tid, $partner, $slot)
    {
        $where['tid'] = $tid;
        $where['partner'] = $partner;
        $where['slot'] = $slot;
        if (false === $this->DeleteData($where)) {
            return false;
        }
        return true;
    }

}