<?php
namespace Home\Model;

use Think\Model;

class LArenaModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //竞技场日志
    public function cLog($tid, $attr, $value, $before, $behave = null)
    {
        if (!$attr) {
            return true;
        }
        $data['tid'] = $tid;
        $data['attr'] = $attr;
        $data['value'] = $value;
        $data['before'] = $before;
        $data['after'] = D('GArena')->getAttr($tid, $attr);
        $behave = empty($behave) ? C('G_BEHAVE') : $behave;
        $data['behave'] = $behave > 0 ? $behave : get_config('behave', array($behave, 'code',));//获取改变原因
        return $this->CreateData($data);
    }

}