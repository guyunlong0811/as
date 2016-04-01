<?php
namespace Home\Model;

use Think\Model;

class GLeagueArenaRankModel extends BaseModel
{

    //获取公会前三情况
    public function getList($area)
    {
        $field = array('league_id', 'league_name', 'point' => 'league_point');
        $where['area'] = $area;
        $list = $this->field($field)->where($where)->order('`point` DESC')->select();
        if (!empty($select)) {
            return array();
        }
        return $list;
    }

}