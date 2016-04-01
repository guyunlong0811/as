<?php
namespace Home\Model;

use Think\Model;

class GPayModel extends BaseModel
{

    protected $_auto = array(
        array('count', 1),
        array('ctime', 'time', 1, 'function'),
        array('utime', 'time', 3, 'function'),
    );

    //创建数据
    private function cData($tid, $cash)
    {
        $data['tid'] = $tid;
        $data['cash_id'] = $cash;
        return $this->CreateData($data);
    }

    //获取数据
    public function getRow($tid, $cash)
    {
        $where['tid'] = $tid;
        $where['cash_id'] = $cash;
        return $this->getRowCondition($where);
    }

    //获取数据
    public function getCount($tid, $cash)
    {
        $where['tid'] = $tid;
        $where['cash_id'] = $cash;
        $count = $this->where($where)->getField('count');
        $count = $count ? $count : 0;
        return $count;
    }

    //完成充值
    public function complete($tid, $cash)
    {
        $count = $this->getCount($tid, $cash);
        if ($count > 0) {
            $where['tid'] = $tid;
            $where['cash_id'] = $cash;
            return $this->IncreaseData($where, 'count');
        } else {
            return $this->cData($tid, $cash);
        }
    }

    //获取已购列表
    public function getBoughtList($tid)
    {
        //获取首冲重置时间
        $payResetTime = D('GParams')->getValue('FIRST_PAY_RESET_TIME');
        $payResetTime = $payResetTime == '0' ? 0 : strtotime($payResetTime);

        //查询
        $where['tid'] = $tid;
        $where['count'] = array('egt', 1);
        $where['utime'] = array('egt', $payResetTime);
        $list = $this->where($where)->getField('cash_id', true);
        if (empty($list)) {
            return array();
        }

        //返回
        return $list;
    }

}