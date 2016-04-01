<?php
namespace Home\Model;

use Think\Model;

class TDailyLeagueModel extends BaseModel
{

    //获取推荐公会个数
    public function getCount()
    {
        return $this->count();
    }

    public function getList($start, $row)
    {
        return $this->field("`league_id`,`league_name`,`president_tid`,`president_nickname`,`center_level`,`count`,`center_level` as `data`")->order("`center_level` DESC,`count` DESC,`league_id` ASC")->limit($start, $row)->select();
    }

    public function getRank($tid)
    {

        //查询公会ID
        $leagueId = D('GLeagueTeam')->getAttr($tid, 'league_id');
        if (empty($leagueId)) {
            return 0;
        } else {

            //查询公会信息
            $where = "`league_id`='{$leagueId}'";
            $row = $this->where($where)->find();

            //公会未上榜
            if (empty($row)) {
                return 0;
            }

            //查询最新排名
            $where = "`center_level`>'{$row['center_level']}' || (`center_level`='{$row['center_level']}' && `count`>'{$row['count']}') || (`center_level`='{$row['center_level']}' && `count`='{$row['count']}' && `league_id`<='{$leagueId}')";
            $count = $this->where($where)->count();
            return $count;
        }
    }

}