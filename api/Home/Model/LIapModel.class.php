<?php
namespace Home\Model;

use Think\Model;

class LIapModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
    );

    //查询凭证有没有处理过
    public function isExist($transaction_id)
    {
        $where['transaction_id'] = $transaction_id;
        $count = $this->where($where)->count();
        if ($count == '0') {
            return false;
        } else {
            return true;
        }
    }

    //创建日志
    public function cLog($transaction_id)
    {
        $data['transaction_id'] = $transaction_id;
        return $this->CreateData($data);
    }

}