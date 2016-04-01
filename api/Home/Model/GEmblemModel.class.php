<?php
namespace Home\Model;

use Think\Model;

class GEmblemModel extends BaseModel
{

    //拆表参数
    const TABLE_NAME = 'g_emblem_';
    const TABLE_NUM = 10;
    protected $autoCheckFields = false;

    //查询当前背包情况
    public function getAll($tid)
    {
        $field = array('emblem', 'count',);
        $where['tid'] = $tid;
        $where['count'] = array('gt', 0);
        $order['emblem'] = 'asc';
        $list = $this->table($this->getName($tid, self::TABLE_NAME))->field($field)->where($where)->order($order)->select();
        if (empty($list)) {
            return array();
        }
        return $list;
    }

    //获取纹章情况
    public function getList($tid)
    {
        $select = $this->getAll($tid);
        $list = array();
        if (!empty($select)) {
            foreach ($select as $value) {
                $list[$value['emblem']] = $value['count'];
            }
        }
        return $list;
    }

    //获取某种纹章的值
    public function getCount($tid, $emblem)
    {
        $where['tid'] = $tid;
        $where['emblem'] = $emblem;
        $count = $this->table($this->getName($tid, self::TABLE_NAME))->where($where)->getField('count');
        $count = is_null($count) ? false : $count;
        return $count;
    }

    //获取某些纹章的值
    public function getCounts($tid, $emblemIds)
    {
        $where['tid'] = $tid;
        $where['emblem'] = array('in', $emblemIds);
        $select = $this->table($this->getName($tid, self::TABLE_NAME))->field('`emblem`,`count`')->where($where)->select();
        if (empty($select)) {
            return array();
        }
        $list = array();
        foreach ($select as $value) {
            $list[$value['emblem']] = $value['count'];
        }
        return $list;
    }

    //添加纹章
    public function cData($tid, $emblem, $count = 1)
    {

        //id为0或数量为0
        if (!($emblem > 0)) {
            return true;
        }

        //查看背包理由没有该纹章
        $countAll = $this->getCount($tid, $emblem);

        //计算应该增加的值
        $max = D('Static')->access('emblem', $emblem, 'stacking');

        //如果没有
        if (false === $countAll) {

            //计算增加数量
            $count = $count > $max ? $max : $count;

            //增加
            $add['tid'] = $tid;
            $add['emblem'] = $emblem;
            $add['count'] = $count;
            $add['total'] = $count;
            if (false === $this->table($this->getName($tid, self::TABLE_NAME))->CreateData($add)) {
                return false;
            }

        } else {

            //已达最大值
            if ($countAll == $max) {
                return true;
            }

            //计算应该增加的值
            if ($countAll + $count > $max) {
                $count = $max - $countAll;
            }

            //sql
            $table = $this->getName($tid, self::TABLE_NAME);
            $sql = "update `{$table}` set `count` = `count` + {$count},`total` = `total` + {$count} where `tid`='{$tid}' && `emblem`='{$emblem}';";
            if (false === $this->execute($sql)) {
                return false;
            }

        }

        //记录日志
        D('LEmblem')->cLog($tid, $emblem, $count);

        //返回
        return true;

    }

    //销毁纹章
    public function dData($tid, $emblem, $count = 1)
    {

        //id为0或数量为0
        if (!($emblem > 0)) {
            return true;
        }

        //sql
        $table = $this->getName($tid, self::TABLE_NAME);
        $sql = "update `{$table}` set `count` = `count` - {$count},`total` = `total` - {$count} where `tid`='{$tid}' && `emblem`='{$emblem}';";
        if (false === $this->execute($sql)) {
            return false;
        }

        //记录日志
        D('LEmblem')->cLog($tid, $emblem, -$count);

        //返回
        return true;

    }

    //添加纹章(装备)
    public function inc($tid, $emblem, $count = 1)
    {

        //id为0或数量为0
        if (!($emblem > 0)) {
            return true;
        }

        //查看背包理由没有该纹章
        $countAll = $this->getCount($tid, $emblem);

        //计算应该增加的值
        $max = D('Static')->access('emblem', $emblem, 'stacking');

        //已达最大值
        if ($countAll == $max) {
            return true;
        }

        //计算应该增加的值
        if ($countAll + $count > $max) {
            $count = $max - $countAll;
        }

        //增加
        $where['tid'] = $tid;
        $where['emblem'] = $emblem;
        if (false === $this->table($this->getName($tid, self::TABLE_NAME))->IncreaseData($where, 'count', $count)) {
            return false;
        }

        //返回
        return true;

    }

    //减少纹章(装备)
    public function dec($tid, $emblem, $count = 1)
    {

        //id为0或数量为0
        if (!($emblem > 0)) {
            return true;
        }

        //减少
        $where['tid'] = $tid;
        $where['emblem'] = $emblem;
        if (false === $this->table($this->getName($tid, self::TABLE_NAME))->DecreaseData($where, 'count', $count)) {
            return false;
        }

        //返回
        return true;

    }

}