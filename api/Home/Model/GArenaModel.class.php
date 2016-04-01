<?php
namespace Home\Model;

use Think\Model;

class GArenaModel extends BaseModel
{
    const GROUP = 1;

    protected $_auto = array(
        array('honour', 0), //荣誉值
        array('rand_list', '[]'), //随机对手
        array('win', '0'), //当前连胜奖励
        array('rank_change', '0'), //排名变化
        array('last_refresh_time', 0), //更新时间
        array('ctime', 'time', 1, 'function'), //创建时间
    );

    protected $_validate = array(//自动验证
        array('rank', '', 'rank_repeat', 0, 'unique', 1), //在新增的时候验证name字段是否唯一
    );

    //获取单条数据
    public function getRow($tid, $field = null)
    {
        $where['tid'] = $tid;
        return $this->getRowCondition($where, $field);
    }

    //创建数据
    public function open($tid)
    {
        $data['tid'] = $tid;
        $data['rank'] = $this->getMaxRank();
        $list = D('GPartner')->getTop5Group($tid);
        $data['partner'] = json_encode($list);
        if ($this->CreateData($data)) {
            return true;
        } else {
            return false;
        }
    }

    //交换名次
    public function exchange($tid1, $tid2)
    {
        //交换名次
        $sql = "update `g_arena` as `a`,`g_arena` as `b` set `a`.`rank` = `b`.`rank`, `b`.`rank` = `a`.`rank` where `a`.`tid`='{$tid1}' && `b`.`tid`='{$tid2}' && `b`.`rank` < `a`.`rank`;";
        if (false === $row = $this->ExecuteData($sql)) {//发生改变
            return false;
        }
        //如果交换
        if ($row > 0) {
            //如果替换第一名则全服公告
            $rank = $this->getAttr($tid1, 'rank');//获取最新排名
            $rank2 = $this->getAttr($tid2, 'rank');//获取最新排名
            $change = $rank2 - $rank;
            if ($rank == '1') {
                $params['nickname'] = D('GTeam')->getAttr($tid1, 'nickname');
                $params['lose_nickname'] = D('GTeam')->getAttr($tid2, 'nickname');
                $noticeConfig = D('SEventString')->getConfig('ARENA_CHAMPION', $params);
                D('GChat')->sendNoticeMsg($tid1, $noticeConfig['des'], $noticeConfig['show_level']);
            }
            //被挑战者设置排名变化
            $this->setAttr($tid2, 'rank_change', 1);
            //如果50名内发生变化则清除redis
            if ($rank <= C('RANK_MAX')) {
                D('Predis')->cli('game')->hdel('rank', 'arena');
            }
            return abs($change);
        } else {//如果不需要交换
            return 0;
        }
    }

    //获取排名
    public function rank($max)
    {
        $list = $this->query("SELECT `ga`.`rank`,`ga`.`tid`,`gt`.`nickname` FROM `g_team` `gt`,`g_arena` `ga` WHERE ( `ga`.`tid`=`gt`.`tid` && `gt`.`ctime`>0 ) ORDER BY `ga`.`rank` ASC LIMIT {$max}");
        return $list;
    }

    //获取属性值
    public function getAttr($tid, $attr)
    {
        $where['tid'] = $tid;
        return $this->where($where)->getField($attr);
    }

    //设置属性(无日志)
    public function setAttr($tid, $attr, $value)
    {
        $where['tid'] = $tid;
        return $this->where($where)->setField($attr, $value);
    }

    //增加属性
    public function incAttr($tid, $attr, $value = 1, $before = null, $behave = null)
    {
        if ($value == 0) {
            return true;
        }
        //没有改变前参数
        if ($before === null) {
            $before = $this->getAttr($tid, $attr);
        }
        $where['tid'] = $tid;
        if (!$this->IncreaseData($where, $attr, $value)) {
            return false;
        }
        D('LArena')->cLog($tid, $attr, $value, $before, $behave);//日志
        return true;
    }

    //减少属性
    public function decAttr($tid, $attr, $value = 1, $before = null)
    {
        $where['tid'] = $tid;
        if ($value == 0) {
            return true;
        }
        if ($before === null) {
            $before = $this->getAttr($tid, $attr);
        }
        if (!$this->DecreaseData($where, $attr, $value)) {
            return false;
        }
        D('LArena')->cLog($tid, $attr, -$value, $before);//日志
        return true;
    }

    //改变属性
    public function updateAttr($tid, $attr, $value, $before = null)
    {
        if ($before === null) {
            $before = $this->getAttr($tid, $attr);
        }//如果没有before
        $where['tid'] = $tid;
        $data[$attr] = $value;
        if (false === $this->UpdateData($data, $where)) {
            return false;
        }
        D('LArena')->cLog($tid, $attr, $value, $before);//日志
        return true;
    }

    //竞技场排名
    public function getRankList()
    {
        //查询竞技场排名前50数据
        $field = array('`g_team`.`tid`', '`g_team`.`nickname`', '`g_team`.`icon`', '`g_team`.`level`');
        $order = "`g_arena`.`rank` ASC";
        $list = $this->field($field)->join('`g_team` ON `g_team`.`tid`=`g_arena`.`tid`')->order($order)->limit(C('RANK_MAX'))->select();
        $list = $this->getLeagueName($list);
        return $list;
    }

    //获取竞技场最大排名
    public function getMaxRank()
    {
        if (D('Predis')->cli('game')->exists('arena_max_rank')) {
            return D('Predis')->cli('game')->incr('arena_max_rank');
        } else {
            $maxNow = $this->max('rank');
            $maxNow = $maxNow ? $maxNow : 0;
            D('Predis')->cli('game')->set('arena_max_rank', $maxNow);
            return $this->getMaxRank();
        }
    }

}