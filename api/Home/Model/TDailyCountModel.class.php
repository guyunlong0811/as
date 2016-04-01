<?php
namespace Home\Model;

use Think\Model;

class TDailyCountModel extends BaseModel
{

    /*
     * TYPE
     * 1:世界聊天发送次数;
     * 2:购买金币次数;
     * 3:购买体力次数;
     * 4:参拜神像;
     * 5:公会任务;
     * 6:最高连击次数;
     * 7:活跃度;
     * 8:连续登录奖励领取次数;
     * 9:新服礼包领取次数;
     * 10:购买技能点次数;
     *
     * 101-200:公会BOSS战斗次数
     *
     *
     */

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
        array('utime', 'time', 3, 'function'),
    );

    //插入数据
    public function record($tid, $type, $count = 1)
    {
        $where['tid'] = $tid;
        $where['type'] = $type;
        $countNow = $this->where($where)->getField('count');
        if (is_null($countNow)) {
            $data = $where;
            $data['count'] = $count;
            if (!$this->CreateData($data)) {
                return false;
            }
            return true;
        } else {
            if ($type == 6) {
                if ($countNow < $count) {
                    $data['count'] = $count;
                    if (!$this->UpdateData($data, $where)) {
                        return false;
                    }
                }
            } else {
                if (!$this->IncreaseData($where, 'count', $count)) {
                    return false;
                }
            }
            return true;
        }
    }

    //获取次数
    public function getCount($tid, $type)
    {
        $where['tid'] = $tid;
        $where['type'] = $type;
        $count = $this->where($where)->getField('count');
        $count = empty($count) ? 0 : $count;
        return $count;
    }

    //获取当日连击排行榜
    public function getComboRankList($limit = false)
    {
        $limit = $limit ? $limit : C('RANK_MAX');
        $sql = "select `gt`.`tid`,`gt`.`nickname`,`gt`.`icon`,`gt`.`level`,`tdc`.`count` as `data` from `g_team` as `gt`,`t_daily_count` as `tdc` where `gt`.`tid`=`tdc`.`tid` && `tdc`.`type`='6' order by `tdc`.`count` DESC, `tdc`.`tid` ASC limit {$limit};";
        $list = $this->query($sql);
        $list = $this->getLeagueName($list);
        return $list;
    }

    //获取玩家实时当日连击数排名
    public function getCurrentTodayComboRank($tid, $combo = null)
    {

        //如果没有传排名
        if (is_null($combo)) {
            $combo = $this->getCount($tid, 6);
        }
        $data['current'] = $combo;

        if ($combo == 0) {
            $data['rank'] = 0;
        } else {
            //查询最新排名
            $where = "`type`='6' && (`count`>'{$combo}' || (`count`='{$combo}' && `tid`<='{$tid}'))";
            $count = $this->where($where)->count();
            $data['rank'] = $count;
        }


        //返回
        return $data;

    }

    //获取公会BOSS挑战情况
    public function getLeagueBossCountList($tid)
    {
        $field = array('type', 'count');
        $where['tid'] = $tid;
        $where['type'] = array('between', array(101, 199));
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        $list = array();
        foreach ($select as $value) {
            $site = $value['type'] % 100;
            $list[$site] = $value['count'];
        }
        return $list;
    }

}