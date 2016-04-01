<?php
namespace Home\Model;

use Think\Model;

class LLeagueModel extends BaseModel
{
    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //公会日志
    public function cLog($leagueId, $attr, $value, $before)
    {
        if (!$attr) {
            return true;
        }
        $data['league_id'] = $leagueId;
        $data['attr'] = $attr;
        $data['value'] = $value;
        $data['before'] = $before;
        $data['after'] = D('GLeague')->getAttr($leagueId, $attr);
        $data['behave'] = C('G_BEHAVE') > 0 ? C('G_BEHAVE') : get_config('behave', array(C('G_BEHAVE'), 'code',));//获取改变原因
        return $this->CreateData($data);
    }

    //获取当日获得的活跃度值
    public function getTodayCount($leagueId, $attr)
    {
        $where['league_id'] = $leagueId;
        $where['attr'] = $attr;
        $where['value'] = array('gt', 0);
        $where['ctime'] = array('egt', get_daily_utime());
        $count = $this->where($where)->sum('value');
        $count = $count ? $count : 0;
        return $count;
    }

    //公会战资金动态
    public function getLeagueFightFund($leagueId)
    {
        $where['league_id'] = $leagueId;
        $where['attr'] = 'fund';
        $where['behave'] = get_config('BEHAVE', array('league_fight_rank', 'code'));
        $where['ctime'] = array('egt', get_daily_utime());
        $select = $this->where($where)->getField('value');
        $select = $select ? $select : 0;
        return $select;
    }

}