<?php
namespace Home\Model;

use Think\Model;

class GEquipModel extends BaseModel
{

    const TYPE = 2;
    protected $_auto = array(
        array('level', 0),
        array('extra_1_type', 0),
        array('extra_1_id', 0),
        array('extra_1_value', 0),
        array('extra_1_lock', 0),
        array('extra_2_type', 0),
        array('extra_2_id', 0),
        array('extra_2_value', 0),
        array('extra_2_lock', 0),
        array('extra_3_type', 0),
        array('extra_3_id', 0),
        array('extra_3_value', 0),
        array('extra_3_lock', 0),
        array('extra_4_type', 0),
        array('extra_4_id', 0),
        array('extra_4_value', 0),
        array('extra_4_lock', 0),
    );

    //创建装备
    public function cData($tid, $group, $partnerGroup)
    {
        $config = D('Static')->access('equipment', $group);
        foreach ($config as $value) {
            if ($value['is_init'] == 1) {
                $equipConfig = $value;
                break;
            }
        }
        $add['tid'] = $tid;
        $add['group'] = $group;
        $add['index'] = $equipConfig['index'];
        $add['partner_group'] = $partnerGroup;
        if (!$this->CreateData($add)) {
            return false;
        }
        return true;
    }

    //装备进阶
    public function upgrade($tid, $group, $target)
    {
        //进阶
        $where['tid'] = $tid;
        $where['group'] = $group;
        $data['index'] = $target;
        $data['level'] = 0;
        if (false === $this->UpdateData($data, $where)) {
            return false;
        }
        //记录日志
        D('LEquipUpgrade')->cLog($tid, $group, $target);
        return true;
    }

    //装备强化
    public function strengthen($tid, $group, $before, $after)
    {
        $where['tid'] = $tid;
        $where['group'] = $group;
        if (!$this->IncreaseData($where, 'level', $after - $before)) {
            return false;
        }
        //记录日志
        D('LEquipStrengthen')->cLog($tid, $group, $after - $before, $after);
        return true;
    }

    //获取多伙伴数据
    public function getPartnersList($tid, $partnerGroup)
    {
        $field = array('partner_group', 'group', 'index', 'level', 'extra_1_type', 'extra_1_id', 'extra_1_value', 'extra_1_lock', 'extra_2_type', 'extra_2_id', 'extra_2_value', 'extra_2_lock', 'extra_3_type', 'extra_3_id', 'extra_3_value', 'extra_3_lock', 'extra_4_type', 'extra_4_id', 'extra_4_value', 'extra_4_lock',);
        $where['tid'] = $tid;
        $where['partner_group'] = array('in', $partnerGroup);
        $select = $this->field($field)->where($where)->select();
        $list = array();
        foreach ($select as $value) {
            $list[$value['partner_group']][] = $value;
        }
        return $list;
    }

    //获取单条数据
    public function getAll($tid, $partnerGroup)
    {
        $field = array('group', 'index', 'level', 'extra_1_type', 'extra_1_id', 'extra_1_value', 'extra_1_lock', 'extra_2_type', 'extra_2_id', 'extra_2_value', 'extra_2_lock', 'extra_3_type', 'extra_3_id', 'extra_3_value', 'extra_3_lock', 'extra_4_type', 'extra_4_id', 'extra_4_value', 'extra_4_lock',);
        $where['tid'] = $tid;
        $where['partner_group'] = $partnerGroup;
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        return $select;
    }

    //获取单条数据
    public function getRow($tid, $group, $field = null)
    {
        $where['tid'] = $tid;
        $where['group'] = $group;
        $data = $this->getRowCondition($where, $field);
        if (empty($data)) {
            C('G_ERROR', 'equip_not_exist');
            return false;
        }
        return $data;
    }

    //获取某种装备的数量
    public function getCount($tid, $group)
    {
        $where['tid'] = $tid;
        $where['group'] = $group;
        return $this->where($where)->count();
    }

    //锁定属性
    public function lock($tid, $group, $extra, $status = 1)
    {
        $where['tid'] = $tid;
        $where['group'] = $group;
        $data['extra_' . $extra . '_lock'] = $status;
        return $this->UpdateData($data, $where);
    }

}