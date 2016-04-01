<?php
namespace Home\Model;

use Think\Model;

class GLeagueRankModel extends BaseModel
{

    //获取某个公会的排名信息
    public function getRow($league_id, $field = null)
    {
        $where['league_id'] = $league_id;
        return $this->getRowCondition($where, $field);
    }

    //获取排名信息
    public function getList($start, $row)
    {
        $field = "`league_id`,`league_name`,`president_tid`,`president_nickname`,`center_level`,`count`,`point` as `data`";
        $order['point'] = 'desc';
        $order['center_level'] = 'desc';
        $order['count'] = 'desc';
        $order['league_id'] = 'asc';
        $list = $this->field($field)->order($order)->limit($start, $row)->select();
        if (empty($list)) {
            return array();
        }
        return $list;
    }

    public function getRank($tid)
    {

        $rs['point'] = 0;
        $rs['rank'] = 0;

        //查询公会ID
        $leagueId = D('GLeagueTeam')->getAttr($tid, 'league_id');
        if (empty($leagueId)) {
            return $rs;
        } else {

            //查询公会信息
            $where = "`league_id`='{$leagueId}'";
            $row = $this->where($where)->find();

            //公会未上榜
            if (empty($row)) {
                return $rs;
            }

            //查询最新排名
            $where = "`point`>'{$row['point']}' || (`point`='{$row['point']}' && `center_level`>'{$row['center_level']}') || (`point`='{$row['point']}' && `center_level`='{$row['center_level']}' && `count`>'{$row['count']}') || (`point`='{$row['point']}' && `center_level`='{$row['center_level']}' && `count`='{$row['count']}' && `league_id`<='{$leagueId}')";
            $count = $this->where($where)->count();

            //返回
            $rs['point'] = $row['point'];
            $rs['rank'] = $count;
            return $rs;
        }
    }

    public function getRankLeague($leagueId)
    {
        //查询公会信息
        $where = "`league_id`='{$leagueId}'";
        $row = $this->where($where)->find();

        //公会未上榜
        if (empty($row)) {
            return 0;
        }

        //查询最新排名
        $where = "`point`>'{$row['point']}' || (`point`='{$row['point']}' && `center_level`>'{$row['center_level']}') || (`point`='{$row['point']}' && `center_level`='{$row['center_level']}' && `count`>'{$row['count']}') || (`point`='{$row['point']}' && `center_level`='{$row['center_level']}' && `count`='{$row['count']}' && `league_id`<='{$leagueId}')";
        return $this->where($where)->count();
    }

}