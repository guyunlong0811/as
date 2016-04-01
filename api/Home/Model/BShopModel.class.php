<?php
namespace Home\Model;

use Think\Model;

class BShopModel extends BaseModel
{

    protected $_auto = array(
        array('refresh_time', 'time', 3, 'function'),
    );

    //获取单条数据
    public function getRow($tid, $field = null)
    {
        $where['tid'] = $tid;
        return $this->getRowCondition($where, $field);
    }

    //获取单条数据
    public function buy($tid, $goodsNo, $goods)
    {
        $goods = substr($goods, 0, -1) . '1';
        $where['tid'] = $tid;
        $data['goods_' . $goodsNo] = $goods;
        return $this->UpdateData($data, $where);
    }

}