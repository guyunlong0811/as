<?php
namespace Home\Model;

use Think\Model;

class LLeagueDonateModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //用户的团队状态日志 donate_type 2：金币 3：水晶
    public function cLog($leagueId, $tid, $type)
    {
        $data['league_id'] = $leagueId;
        $data['tid'] = $tid;
        $data['donate_type'] = $type;
        return $this->CreateData($data);
    }

    //获取用户当天捐献次数
    public function getTodayCount($tid, $type)
    {
        $where['tid'] = $tid;
        $where['donate_type'] = $type;
        $where['ctime'] = array('egt', get_daily_utime());
        return $this->where($where)->count();
    }

    //获取最后20条捐赠信息
    public function getList($leagueId)
    {
        $list = $this->field('`g_team`.`tid`,`g_team`.`nickname`,`g_team`.`icon`,`g_team`.`level`,`l_league_donate`.`donate_type`,`l_league_donate`.`ctime`')->join('`g_team` ON `g_team`.`tid` = `l_league_donate`.`tid`')->where("`l_league_donate`.`league_id` = '{$leagueId}'")->order("`l_league_donate`.`ctime` DESC")->limit(20)->select();
        if (empty($list)) {
            return array();
        }
        return $list;
    }

    //获取用户当天捐献情况
    public function getTodayCountList($tid)
    {
        $where['tid'] = $tid;
        $where['ctime'] = array('egt', get_daily_utime());
        $list = $this->field('`donate_type`, count(`id`) as `count`')->where($where)->group('`donate_type`')->select();
        if(empty($list)){
            $list = array();
        }
        return $list;
    }

}