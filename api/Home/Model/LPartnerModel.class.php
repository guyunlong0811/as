<?php
namespace Home\Model;

use Think\Model;

class LPartnerModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //装备获取日志
    public function cLog($tid, $group, $attr, $value, $before)
    {
        if (!$tid || !$group || !$attr || $value == 0) return true;
        $data['tid'] = $tid;
        $data['group'] = $group;
        $data['attr'] = $attr;
        $data['value'] = $value;
        $data['before'] = $before;
        $data['after'] = D('GPartner')->getAttr($tid, $group, $attr);
        $data['behave'] = C('G_BEHAVE') > 0 ? C('G_BEHAVE') : get_config('behave', array(C('G_BEHAVE'), 'code',));
        return $this->CreateData($data);
    }

    //获取今天升级技能次数
    public function getTodaySkillLevelupCount($tid)
    {
        $today = get_daily_utime();
        $where = "`tid`='{$tid}' && `ctime`>='{$today}' && (`attr`='skill_1_level' || `attr`='skill_2_level' || `attr`='skill_3_level' || `attr`='skill_4_level' || `attr`='skill_5_level' || `attr`='skill_6_level')";
        $count = $this->where($where)->sum('value');
        $count = $count ? $count : 0;
        return $count;
    }

}