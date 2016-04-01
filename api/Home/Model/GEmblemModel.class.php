<?php
namespace Home\Model;

use Think\Model;

class GEmblemModel extends BaseModel
{

    protected $_auto = array(
        array('partner', 0),
        array('slot', 0),
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

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

    //查询当前情况
    public function getList($tid)
    {
        $field = array('index', 'count(`index`)' => 'count');
        $where['tid'] = $tid;
        $where['partner'] = 0;
        $where['slot'] = 0;
        $select = $this->field($field)->where($where)->group('`index`')->select();
        $list = array();
        if (!empty($select)) {
            foreach($select as $value){
                $list[$value['index']] = $value['count'];
            }
        }
        return $list;
    }

    //获取多伙伴装备情况
    public function getPartnersList($tid, $partner)
    {
        $field = array('index', 'partner');
        $where['tid'] = $tid;
        $where['partner'] = array('in', $partner);
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        $list = array();
        foreach ($select as $value) {
            $list[$value['partner']][] = $value['index'];
        }
        return $list;
    }

    //获取装备情况
    public function getEquipList($tid, $partner)
    {
        $field = array('index');
        $where['tid'] = $tid;
        $where['partner'] = $partner;
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        $list = array();
        foreach ($select as $value) {
            $list[] = $value['index'];
        }
        return $list;
    }

    //获取单条数据
    public function getRow($id)
    {
        $where['id'] = $id;
        return $this->getRowCondition($where);
    }

    //增加纹章
    public function cData($tid, $index, $count)
    {
        $add['tid'] = $tid;
        $add['index'] = $index;
        for ($i = 1; $i <= $count; ++$i) {
            if (!$this->CreateData($add)) {
                return false;
            }
        }
        return true;
    }

    //卸下装备
    public function unload($tid, $partner, $slot)
    {
        $where['tid'] = $tid;
        $where['partner'] = $partner;
        $where['slot'] = $slot;
        $data['partner'] = 0;
        $data['slot'] = 0;
        return $this->UpdateData($data, $where);
    }

    //装备纹章
    public function equip($id, $partner, $slot)
    {
        $where['id'] = $id;
        $data['partner'] = $partner;
        $data['slot'] = $slot;
        return $this->UpdateData($data, $where);
    }

    //销毁纹章
    public function destroy($data)
    {

        //销毁数据
        $where['id'] = $data['id'];
        if (false === $this->DeleteData($where)) {
            return false;
        }

        //记录
        D('LEmblem')->cLog($data);
        return true;

    }

    //减少纹章
    public function dec($tid, $index, $count)
    {
        $where['tid'] = $tid;
        $where['index'] = $index;
        $where['slot'] = 0;
        $select = $this->where($where)->order('`id` ASC')->limit($count)->select();
        foreach ($select as $value) {
            if (false === $this->destroy($value)) {
                return false;
            }
        }
        return true;
    }

    //获取未装备的纹章个数
    public function getCount($tid, $index)
    {
        $where['tid'] = $tid;
        $where['index'] = $index;
        $where['slot'] = 0;
        return $this->where($where)->count();
    }

}