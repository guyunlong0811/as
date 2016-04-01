<?php
namespace Home\Model;

use Think\Model;

class TDailyOnlineBonusModel extends BaseModel
{

    //获取数据
    public function getRow($tid, $field = null)
    {
        $where['tid'] = $tid;
        return $this->getRowCondition($where, $field);
    }

    //设置在线时间
    public function setOnlineTime($tid, $second, $isRow = null)
    {
        //检查是否已经有数据
        if (is_null($isRow)) {
            $where['tid'] = $tid;
            $row = $this->where($where)->find();
            if (!empty($row)) {
                $isRow = true;
            } else {
                $isRow = false;
            }
        }

        //更新数据
        if ($isRow) {
            $sql = "update `t_daily_online_bonus` set `second` = `second` + '{$second}',`cache`='0' where `tid`='{$tid}'";
            return $this->ExecuteData($sql);
        } else {
            $add['tid'] = $tid;
            $add['last_receive_bonus'] = 0;
            $add['last_receive_time'] = get_daily_utime();
            $add['second'] = $second;
            $add['cache'] = 0;
            return $this->CreateData($add);
        }
    }

    //设置在线时间
    public function setCacheTime($tid, $second, $isRow = null)
    {
        //检查是否已经有数据
        if (is_null($isRow)) {
            $where['tid'] = $tid;
            $row = $this->where($where)->find();
            if (!empty($row)) {
                $isRow = true;
            } else {
                $isRow = false;
            }
        }

        //更新数据
        if ($isRow) {
            if ($row['cache'] == 0) {
                $data['cache'] = $second;
                $where['tid'] = $tid;
                return $this->UpdateData($data, $where);
            }
        } else {
            $add['tid'] = $tid;
            $add['last_receive_bonus'] = 0;
            $add['last_receive_time'] = get_daily_utime();
            $add['second'] = 0;
            $add['cache'] = $second;
            return $this->CreateData($add);
        }

        return true;
    }

    //将缓存时间覆盖
    public function setCache2Online($tid)
    {
        $sql = "update `t_daily_online_bonus` set `second` = `second` + `cache`,`cache`='0' where `tid`='{$tid}'";
        return $this->ExecuteData($sql);
    }

    //领取奖励
    public function receive($tid, $bonus_id, $isRow = null)
    {

        //更新数据
        if ($isRow) {
            $data['last_receive_bonus'] = $bonus_id;
            $data['last_receive_time'] = time();
            $data['second'] = 0;
            $where['tid'] = $tid;
            return $this->UpdateData($data, $where);
        } else {
            $add['last_receive_bonus'] = $bonus_id;
            $add['last_receive_time'] = time();
            $add['second'] = 0;
            $add['tid'] = $tid;
            return $this->CreateData($add);
        }

    }

}