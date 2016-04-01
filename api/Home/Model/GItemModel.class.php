<?php
namespace Home\Model;

use Think\Model;

class GItemModel extends BaseModel
{

    //拆表参数
    const TABLE_NAME = 'g_item_';
    const TABLE_NUM = 10;
    protected $autoCheckFields = false;

    //查询当前背包情况
    public function getAll($tid)
    {
        $field = array('item', 'count',);
        $where['tid'] = $tid;
        $where['count'] = array('gt', 0);
        $order['item'] = 'asc';
        $list = $this->table($this->getName($tid, self::TABLE_NAME))->field($field)->where($where)->order($order)->select();
        if (empty($list)) {
            return array();
        }
        return $list;
    }

    //获取道具情况
    public function getList($tid)
    {
        $select = $this->getAll($tid);
        $list = array();
        if (!empty($select)) {
            foreach ($select as $value) {
                $list[$value['item']] = $value['count'];
            }
        }
        return $list;
    }

    //获取某种道具的值
    public function getCount($tid, $item)
    {
        $where['tid'] = $tid;
        $where['item'] = $item;
        $count = $this->table($this->getName($tid, self::TABLE_NAME))->where($where)->getField('count');
        $count = is_null($count) ? false : $count;
        return $count;
    }

    //获取某些道具的值
    public function getCounts($tid, $itemIds)
    {
        $where['tid'] = $tid;
        $where['item'] = array('in', $itemIds);
        $select = $this->table($this->getName($tid, self::TABLE_NAME))->field('`item`,`count`')->where($where)->select();
        if (empty($select)) {
            return array();
        }
        $list = array();
        foreach ($select as $value) {
            $list[$value['item']] = $value['count'];
        }
        return $list;
    }

    //添加单个道具
    public function inc($tid, $item, $count = 1)
    {

        //id为0或数量为0
        if (!($item > 0)) {
            return true;
        }

        //查看背包理由没有该道具
        $countAll = $this->getCount($tid, $item);

        //计算应该增加的值
        $max = D('Static')->access('item', $item, 'stacking');

        //如果没有
        if (false === $countAll) {

            //计算增加数量
            $count = $count > $max ? $max : $count;

            //查询道具的基本属性
            $itemConfig = D('Static')->access('item', $item);

//            //限制时间
//            $dtime = 0;
//            if ($itemConfig['need_time_type'] == '2') {//道具时间限制
//                $dtime = strtotime($itemConfig['need_time_value']);//销毁时间
//            }

            //增加
            $add['tid'] = $tid;
            $add['item'] = $item;
            $add['count'] = $count;
//            $add['dtime'] = $dtime;
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

            //增加
            $where['tid'] = $tid;
            $where['item'] = $item;
            if (false === $this->table($this->getName($tid, self::TABLE_NAME))->IncreaseData($where, 'count', $count)) {
                return false;
            }

        }

        //记录日志
        D('LItem')->cLog($tid, $item, $count);

        //返回
        return true;

    }

    //减少有效道具
    public function dec($tid, $item, $count = 1)
    {

        //id为0或数量为0
        if (!($item > 0)) {
            return true;
        }

        //减少
        $where['tid'] = $tid;
        $where['item'] = $item;
        if (false === $this->table($this->getName($tid, self::TABLE_NAME))->DecreaseData($where, 'count', $count)) {
            return false;
        }

        //记录日志
        D('LItem')->cLog($tid, $item, -$count);

        //返回
        return true;

    }

    //获取过期道具
    /*public function getExpire($now = null)
    {
        //查询所有过期物品
        $now = is_null($now) ? time() : $now;
        $where = "`dtime`>0 && `dtime`<{$now}";
        $select = $this->where($where)->select();
        if (empty($select)) {
            return array();
        }
        return $select;
    }*/

    //开宝箱
    public function openBox($tid, $box, $count = 1, $type = 1)
    {

        //宝箱逻辑
        if ($count == 1 && $type == 1) {
            $open = D('SBox')->open1($box);
            $total = array();
            foreach ($open as $value) {
                if (isset($total[$value['type']][$value['id']])) {
                    $total[$value['type']][$value['id']] += $value['count'];
                } else {
                    $total[$value['type']][$value['id']] = $value['count'];
                }
            }
            $list = array();
            foreach ($total as $type => $value) {
                foreach ($value as $id => $count) {
                    $arr = array();
                    $arr['type'] = $type;
                    $arr['id'] = $id;
                    $arr['count'] = $count;
                    $list[] = $arr;
                }
            }
        } else {
            $open = D('SBox')->open($box, $count);
            $total = $open['total'];
            $list = $open['list'];
        }

        //增加物品
        foreach ($total as $type => $value) {
            foreach ($value as $id => $count) {
                if (false === $this->addBoxBonus($tid, $type, $id, $count)) {
                    return false;
                }
            }
        }

        //返回
        return $list;

    }

    public function addBoxBonus($tid, $type, $value1, $value2)
    {

        switch ($type) {
            case 1://加道具
                if (!$this->inc($tid, $value1, $value2)) {
                    return false;
                }
                break;
            case 2://加属性
                $field = get_config('field', $value1);//获取属性
                $arr = explode('.', $field);//分解
                if ($arr[1] == 'diamond') {//免费水晶
                    $arr[1] = 'diamond_free';
                }
                if (!D($arr[0])->incAttr($tid, $arr[1], $value2)) {
                    return false;
                }
                break;
            case 3://加伙伴
                for ($i = 1; $i <= $value2; ++$i)
                    if (!D('GPartner')->cPartner($tid, $value1)) {
                        return false;
                    }
                break;
            case 4:
                break;
            case 5://加神力
                if (!D('GPartner')->addSoul($tid, $value1, $value2)) {
                    return false;
                }
                break;
            case 6:
                break;
            case 7:
                break;
            case 8:
                break;
            case 9:
                if (!D('GEmblem')->cData($tid, $value1, $value2)) {
                    return false;
                }
                break;
        }

        return true;
    }

}