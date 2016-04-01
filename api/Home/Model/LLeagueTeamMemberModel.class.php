<?php
namespace Home\Model;

use Think\Model;

class LLeagueTeamMemberModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //用户的团队状态日志 action_type 1:申请 2：加入 3：离开
    public function cLog($league_id, $tid, $action_type)
    {
        $data['league_id'] = $league_id;
        $data['tid'] = $tid;
        $data['action_type'] = $action_type;
        return $this->CreateData($data);
    }

    //获取用户最后一次离开公会的时间
    public function getLastLeftTime($tid)
    {
        $where['tid'] = $tid;
        $where['action_type'] = 3;
        $order['ctime'] = 'desc';
        $ctime = $this->where($where)->order($order)->getField('ctime');
        $ctime = $ctime ? $ctime : 0;
        return $ctime;
    }

}