<?php
namespace Home\Model;

use Think\Model;

class LEmblemModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    public function cLog($data, $behave = false)
    {
        $log['index'] = $data['index'];
        $log['tid'] = $data['tid'];
        $behave = empty($behave) ? C('G_BEHAVE') : $behave;
        $log['behave'] = $behave > 0 ? $behave : get_config('behave', array($behave, 'code',));
        return $this->CreateData($log);
    }

}