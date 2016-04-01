<?php
namespace Home\Model;

use Think\Model;

class GLeagueArenaTeamModel extends BaseModel
{

    protected $_auto = array(
        array('opponent', 0),
        array('status', 2),
        array('ctime', 'time', 1, 'function'),
    );

    //获取信息
    public function getRow($id){
        $where['id'] = $id;
        return $this->where($where)->find();
    }

    //获取信息
    public function getList(){
        return $this->order("`league_id` ASC,`tid` ASC,`ctime` ASC")->select();
    }

    //获取信息
    public function getTids(){
        return $this->getField('distinct(`tid`)', true);
    }

    //获取玩家已经上阵的伙伴
    public function getPartners($tid, $id = 0)
    {
        $where['tid'] = $tid;
        if ($id > 0) {
            $where['id'] = array('neq', $id);
        }
        $partnerList = $this->where($where)->getField('partner', true);
        $list = array();
        if (!empty($partnerList)) {
            foreach ($partnerList as $value) {
                $list = array_merge($list, json_decode($value, true));
            }
        }
        return $list;
    }

    //更改战斗状态
    public function changeStatus($battleId, $status)
    {
        $where['id'] = $battleId;
        $data['status'] = $status;
        return $this->UpdateData($data, $where);
    }

}