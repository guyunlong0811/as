<?php
namespace Home\Model;

use Think\Model;

class GInstanceModel extends BaseModel
{

    protected $autoCheckFields = false;

    //拆表参数
    const TABLE_NAME = 'g_instance_';
    const TABLE_NUM = 10;

    //获取已经完成的副本ID
    public function getInstances($tid)
    {

        $field = array('instance',);
        $where['tid'] = $tid;
        $where['count'] = array('gt', 0);
        $select = $this->table($this->getName($tid, self::TABLE_NAME))->field($field)->where($where)->select();
        if (empty($select))
            return array();
        foreach ($select as $value)
            $data[] = $value['instance'];
        return $data;
    }

    //查询副本是否已经完成
    public function isComplete($tid, $instance)
    {
        $where['tid'] = $tid;
        $where['instance'] = $instance;
        $count = $this->table($this->getName($tid, self::TABLE_NAME))->where($where)->getField('count');
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    //获取已经完成的副本ID
    public function getList($tid)
    {
        $field = array('instance', 'star', 'combo', 'combo_time');
        $where['tid'] = $tid;
        $where['count'] = array('gt', 0);
        $select = $this->table($this->getName($tid, self::TABLE_NAME))->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        foreach ($select as $value) {
            $list[$value['instance']] = $value;
            unset($list[$value['instance']]['instance']);
        }
        return $list;
    }

    //获取已经完成的副本ID:次数
    public function getRow($tid, $instance)
    {
        $where['tid'] = $tid;
        $where['instance'] = $instance;
        $info = $this->table($this->getName($tid, self::TABLE_NAME))->where($where)->find();
        return $info;
    }

    //获取已经完成的副本ID:次数
    public function getCount($tid, $instance)
    {
        $where['tid'] = $tid;
        $where['instance'] = $instance;
        $count = $this->table($this->getName($tid, self::TABLE_NAME))->where($where)->getField('count');
        $count = $count ? $count : 0;
        return $count;
    }

    //获取已经完成的副本组ID:次数
    public function getGroupCount($tid, $group)
    {
        $where['tid'] = $tid;
        $where['group'] = $group;
        $count = $this->table($this->getName($tid, self::TABLE_NAME))->where($where)->sum('count');
        $count = $count ? $count : 0;
        return $count;
    }

    //完成副本
    public function complete($tid, $instance, $count, $star, $combo)
    {
        $group = D('Static')->access('instance_info', $instance, 'group');

        //查询有没有记录
        $where['tid'] = $tid;
        $where['instance'] = $instance;
        $row = $this->table($this->getName($tid, self::TABLE_NAME))->where($where)->find();

        //星数
        $starBefore = $row['star'] ? $row['star'] : 0;//当前星数
        $starNew = $starBefore | $star;//最新星数

        //没有记录则增加记录
        if (empty($row)) {
            $data = $where;
            $data['group'] = $group;
            $data['count'] = $count;
            $data['star'] = $star ? $star : 0;
            $data['combo'] = $combo;
            $data['combo_time'] = time();
            if (false === $this->table($this->getName($tid, self::TABLE_NAME))->CreateData($data)) {
                return false;
            }
        } else {
            //修改情况
            $save = array();

            //完成次数
            if ($count > 0) {
                $save['count'] = $row['count'] + $count;
            }

            //连击数
            if ($row['combo'] < $combo) {
                $save['combo'] = $combo;
                $save['combo_time'] = time();
            }

            //星数
            if ($starNew != $starBefore) {
                $save['star'] = $starNew;
            }

            //保存
            if (!empty($save)) {
                if (false === $this->table($this->getName($tid, self::TABLE_NAME))->UpdateData($save, $where)) {
                    return false;
                }
            }
        }

        //星数
        if ($starBefore != $starNew) {
            //增加总星数
            $starBeforeBit = sum_bit($starBefore);//计算原来获得星数
            $starNewBit = sum_bit($starNew);//计算新获得星数
            $starCountAdd = $starNewBit - $starBeforeBit;//本次获得星数
            if (!D('GCount')->star($tid, $starCountAdd)) {
                return false;
            }
        }

        return true;

    }

    //获取主线副本最高combo数获得者
    public function maxComboList()
    {
        //获取地图配置
        $mapConfig = D('Static')->access('map');

        //获取所有副本ID
        $arrAllInstance = array();
        foreach ($mapConfig as $config) {
            $arrInstance = explode('#', $config['instance']);
            $arrAllInstance = array_merge($arrAllInstance, $arrInstance);
        }
        $in = sql_in_condition($arrAllInstance);

        $list = array();
        for ($i = 0; $i < self::TABLE_NUM; ++$i) {

            //查询数据
            $sql = "select `gi`.`tid`,`gt`.`nickname`,`gi`.`instance`,`gi`.`combo`,`gi`.`combo_time` from `g_team` as `gt`,(select `tid`,`instance`,`combo`,`combo_time` from `g_instance_{$i}` order by `combo` DESC,`combo_time` ASC,`tid` ASC) as `gi` where `gt`.`tid`=`gi`.`tid` && `gi`.`instance`{$in} group by `gi`.`instance` order by `gi`.`instance` ASC;";
            $select = $this->query($sql);
            if (!empty($select)) {
                foreach ($select as $value) {
                    if (!isset($list[$value['instance']]) || $value['combo'] > $list[$value['instance']]['combo'] || ($value['combo'] == $list[$value['instance']]['combo'] && $value['combo_time'] < $list[$value['instance']]['combo_time']) || ($value['combo'] == $list[$value['instance']]['combo'] && $value['combo_time'] == $list[$value['instance']]['combo_time'] && $value['tid'] < $list[$value['instance']]['tid'])) {
                        $list[$value['instance']] = $value;
                        unset($list[$value['instance']]['instance']);
                    }
                }
            }

        }

        //返回
        return $list;
    }

    //获取某章节星数
    public function getStarCount($tid, $instance)
    {
        $field = 'star';
        $in = sql_in_condition($instance);
        $where = "`tid`='{$tid}' && `instance`{$in}";
        $select = $this->table($this->getName($tid, self::TABLE_NAME))->field($field)->where($where)->select();
        $star = 0;
        if (!empty($select)) {
            foreach ($select as $value) {
                $star += sum_bit($value['star']);
            }
        }
        return $star;
    }

}