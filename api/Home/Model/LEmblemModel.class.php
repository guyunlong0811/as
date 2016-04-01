<?php
namespace Home\Model;

use Think\Model;

class LEmblemModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //道具增减日志
    public function cLog($tid, $emblem, $count, $behave = false)
    {
        if ($emblem == '0') {
            return false;
        }
        $data['tid'] = $tid;
        $data['emblem'] = $emblem;
        $behave = empty($behave) ? C('G_BEHAVE') : $behave;
        $data['behave'] = $behave > 0 ? $behave : get_config('behave', array($behave, 'code',));
        $data['count'] = $count;
        return $this->CreateData($data);
    }

}