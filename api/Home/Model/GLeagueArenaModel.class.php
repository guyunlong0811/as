<?php
namespace Home\Model;

use Think\Model;

class GLeagueArenaModel extends BaseModel
{

    protected $_auto = array(
        array('count', 1),
        array('point', 0),
        array('ctime', 'time', 1, 'function'),
        array('utime', 'time', 3, 'function'),
    );

    //插入数据
    public function record($leagueId, $areaId, $tid)
    {
        $where['league_id'] = $leagueId;
        $row = $this->where($where)->find();
        if (empty($row)) {
            $add['league_id'] = $leagueId;
            $add['area'] = $areaId;
            $add['reg_tid'] = $tid;
            if (!$this->CreateData($add)) {
                return false;
            }
        } else {
            if ($row['area'] != $areaId) {
                $data['area'] = $areaId;
                $data['reg_tid'] = $tid;
                $data['count'] = $row['count'] + 1;
                if (!$this->UpdateData($data, $where)) {
                    return false;
                }
            }
        }
        return true;
    }

    //获取公会报名的地区
    public function getAreaId($leagueId)
    {
        $where['league_id'] = $leagueId;
        $areaId = $this->where($where)->getField('area');
        $areaId = empty($areaId) ? 0 : $areaId;
        return $areaId;
    }

    //获取公会信息
    public function getRow($leagueId)
    {
        $where['league_id'] = $leagueId;
        $row = $this->where($where)->find();
        if (empty($row)) {
            $row['area'] = 0;
            $row['count'] = 0;
        }
        return $row;
    }

    //查询是否报名
    public function isReg($leagueId)
    {
        $where['league_id'] = $leagueId;
        return $this->where($where)->count();
    }

    //区域公会报名数量
    public function getRegCount($areaId)
    {
        $where['area'] = $areaId;
        return $this->where($where)->count();
    }

    //获取公会报名情况
    public function getAreaList()
    {
        $select = $this->field(array('area', 'league_id', 'point'))->order('`utime` ASC')->select();
        $list = array();
        if (!empty($select)) {
            foreach ($select as $value) {
                $arr['league_id'] = $value['league_id'];
                $arr['point'] = $value['point'];
                $list[$value['area']][] = $arr;
            }
        }
        return $list;
    }

    //获取公会排名
    public function getRank($area, $point, $utime)
    {
        $where = "`area`='{$area}' && (`point` > {$point} || (`point` = {$point} && `utime` < {$utime}))";
        $count = $this->where($where)->count();
        return $count + 1;
    }

}