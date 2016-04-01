<?php
namespace Home\Model;

use Think\Model;

class GStarModel extends BaseModel
{

    protected $_auto = array(
        array('level', 0),
        array('partner', 0),
        array('cache_attr1', 0),
        array('cache_attr2', 0),
        array('gold_count', 0),
        array('diamond_count', 0),
    );

    //查询星位情况
    public function getAll($tid)
    {
        $field = array('tid');
        $where = "`tid`='{$tid}'";
        $list = $this->field($field, true)->where($where)->select();
        if (empty($list)) {
            return array();
        }
        return $list;
    }

    //获取单条数据
    public function getRow($tid, $position)
    {
        $where['tid'] = $tid;
        $where['position'] = $position;
        return $this->getRowCondition($where);
    }

    //增加星位
    public function cData($tid, $position)
    {
        $add['tid'] = $tid;
        $add['position'] = $position;
        $add['attr1'] = D('Static')->access('star', $position, 'percent_attribute_1_value');
        $add['attr2'] = D('Static')->access('star', $position, 'percent_attribute_2_value');
        if (!$this->CreateData($add)) {
            return false;
        }
        return true;
    }

    //卸下伙伴
    public function unload($tid, $position)
    {
        $where['tid'] = $tid;
        $where['position'] = $position;
        $data['partner'] = 0;
        return $this->UpdateData($data, $where);
    }

    //卸下伙伴
    public function unloadPartner($tid, $partner)
    {
        $where['tid'] = $tid;
        $where['partner'] = $partner;
        $data['partner'] = 0;
        return $this->UpdateData($data, $where);
    }

    //装备伙伴
    public function equip($tid, $position, $partner)
    {
        $where['tid'] = $tid;
        $where['position'] = $position;
        $data['partner'] = $partner;
        return $this->UpdateData($data, $where);
    }

    //升级星位
    public function levelup($tid, $position)
    {
        $row = $this->getRow($tid, $position);
        $where['tid'] = $tid;
        $where['position'] = $position;
        if (false === $this->IncreaseData($where, 'level')) {
            return false;
        }
        D('LStar')->cLog($tid, $position, $row['level'], $row['level'] + 1);
        return true;
    }

    //重置星位
    public function reset($tid)
    {
        //查询星位情况
        $select = $this->getAll($tid);
        if (empty($select)) {
            return true;
        }
        //清零
        $where['tid'] = $tid;
        $data['level'] = 0;
        if (false === $this->UpdateData($data, $where)) {
            return false;
        }
        //遍历
        $now = time();
        foreach ($select as $value) {
            $add['tid'] = $tid;
            $add['position'] = $value['position'];
            $add['level'] = $value['level'];
            $add['after'] = 0;
            $add['ctime'] = $now;
            $all[] = $add;
        }
        D('LStar')->CreateAllData($all);
        return true;
    }

    //判断伙伴是否已经装备
    public function isEquip($tid, $partner)
    {
        $where['tid'] = $tid;
        $where['partner'] = $partner;
        $count = $this->where($where)->count();
        if ($count >= 1) {
            C('G_ERROR', 'star_partner_equip_already');
            return true;
        }
        return false;
    }

}