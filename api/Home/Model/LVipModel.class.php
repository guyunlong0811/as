<?php
namespace Home\Model;

use Think\Model;

class LVipModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //战队创建日志
    public function cLog($tid, $attr, $value, $before)
    {
        if (!$attr) {
            return true;
        }
        $data['tid'] = $tid;
        $data['attr'] = $attr;
        $data['value'] = $value;
        $data['before'] = $before;
        $data['after'] = D('GVip')->getAttr($tid, $attr);
        $data['behave'] = C('G_BEHAVE') > 0 ? C('G_BEHAVE') : get_config('behave', array(C('G_BEHAVE'), 'code',));//获取改变原因
        return $this->CreateData($data);
    }

}