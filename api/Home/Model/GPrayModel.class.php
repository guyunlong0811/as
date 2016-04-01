<?php
namespace Home\Model;

use Think\Model;

class GPrayModel extends BaseModel
{

    protected $_auto = array(
        array('count', 1),//创建时间
        array('ctime', 'time', 1, 'function'),//创建时间
        array('utime', 'time', 3, 'function'),//更新时间
    );

    //创建数据
    public function cData($tid, $pray_id, $isFree)
    {
        $where['tid'] = $tid;
        $where['pray_id'] = $pray_id;
        $where['is_free'] = $isFree;
        $count = $this->where($where)->count();
        if ($count == 0) {
            return $this->CreateData($where);
        } else {
            return $this->IncreaseData($where, 'count');
        }
    }

    //获取某玩家的全部数据
    public function getAll($tid)
    {
        $where['tid'] = $tid;
        $list = $this->where($where)->select();
        if (empty($list)) {
            return array();
        }
        return $list;
    }

    //获取单条数据
    public function getUtime($tid, $pray_id, $isFree = 1)
    {
        $where['tid'] = $tid;
        $where['pray_id'] = $pray_id;
        $where['is_free'] = $isFree;
        $utime = $this->where($where)->getField('utime');
        $utime = $utime ? $utime : 0;
        return $utime;
    }

    //获取单条数据
    public function isFirst($tid, $pray_id, $isFree = 1)
    {
        $utime = $this->getUtime($tid, $pray_id, $isFree);
        if ($utime == 0) {
            return 1;
        } else {
            return 0;
        }
    }

    //获取祈愿历史记录
    public function getList($tid, $pray_id)
    {
        $field = array('is_free', 'count',);
        $where['tid'] = $tid;
        $where['pray_id'] = $pray_id;
        $select = $this->field($field)->where($where)->select();
        $data[0] = 0;
        $data[1] = 0;
        if (!empty($select)) {
            foreach ($select as $value) {
                if ($value['is_free'] == '1') {
                    $data[1] = $value['count'];
                } else if ($value['is_free'] == '0') {
                    $data[0] = $value['count'];
                }
            }
        }
        return $data;
    }

}