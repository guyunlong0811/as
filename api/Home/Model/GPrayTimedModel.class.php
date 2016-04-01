<?php
namespace Home\Model;

use Think\Model;

class GPrayTimedModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),//创建时间
        array('utime', 'time', 3, 'function'),//更新时间
    );

    //获取玩家数据
    public function getRow($tid, $index, $field = null)
    {
        $where['tid'] = $tid;
        $where['dyn_id'] = $index;
        return $this->getRowCondition($where, $field);
    }

    //获取玩家当前排名
    public function getRank($tid, $index, $row)
    {
        if (empty($row)) {
            $where = "`dyn_id` = '{$index}'";
        } else {
            $where = "`dyn_id` = '{$index}' && (`point` > {$row['point']} || (`point` = {$row['point']} && `utime` < {$row['utime']}) || (`point` = {$row['point']} && `utime` = {$row['utime']} && `tid`< '{$tid}'))";
        }
        $count = $this->where($where)->count('tid');
        ++$count;
        return $count;
    }

    //排名
    public function rank($index, $max)
    {
        $field = array('`g_pray_timed`.`tid`', '`g_team`.`nickname`', '`g_pray_timed`.`point`');
        $where = "`g_pray_timed`.`dyn_id` = '{$index}'";
        $order = "`g_pray_timed`.`point` desc,`g_pray_timed`.`utime` asc";
        $list = $this->field($field)->join("`g_team` on `g_team`.`tid` = `g_pray_timed`.`tid`")->where($where)->order($order)->limit($max)->select();
        if(empty($list)){
            return array();
        }
        return $list;
    }

    //记录
    public function record($tid, $index, $point, $isFree, $times)
    {

        //获取玩家当前数据
        $row = $this->getRow($tid, $index);

        //拼key
        $key = $isFree == 1 ? 'free_' : 'pay_';
        $key .= $times;

        //判断是否有数据
        if (empty($row)) {
            //创建数据
            $add['tid'] = $tid;
            $add['dyn_id'] = $index;
            $add['point'] = $point;
            $add['free_1'] = 0;
            $add['free_10'] = 0;
            $add['pay_1'] = 0;
            $add['pay_10'] = 0;
            $add[$key] = 1;
            if (false === $this->CreateData($add)) {
                return false;
            }
        } else {
            //创建数据
            $where['tid'] = $tid;
            $where['dyn_id'] = $index;
            $save['point'] = $row['point'] + $point;
            $save[$key] = $row[$key] + 1;
            if (false === $this->UpdateData($save, $where)) {
                return false;
            }
        }

        //返回
        return true;

    }

}