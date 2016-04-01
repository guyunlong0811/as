<?php
namespace Home\Model;

use Think\Model;

class LPrayModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'),
    );

    //获取今天抽卡次数
    public function getTodayCount($tid, $pray)
    {
        $where['tid'] = $tid;
        $where['ctime'] = array('egt', get_daily_utime());
        if ($pray > '0') {
            $where['pray_id'] = $pray;
        }
        $select = $this->where($where)->select();
        $prayConfig = D('Static')->access('pray');
        $time = 0;
        foreach ($select as $value) {
            $time += $prayConfig[$value['pray_id']]['times'];
        }
        return $time;
    }

    //获取今天免费收卡次数
    public function getTodayFreeCount($tid, $pray)
    {
        $where['tid'] = $tid;
        $where['pray_id'] = $pray;
        $where['is_free'] = 1;
        $where['ctime'] = array('egt', get_daily_utime());
        return $this->where($where)->count();
    }

}
