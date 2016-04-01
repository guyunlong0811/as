<?php
namespace Home\Model;

use Think\Model;

class GVipModel extends BaseModel
{

    const defaultVipIndex = 1000;

    protected $_auto = array(
        array('index', self::defaultVipIndex),
        array('score', 0),
        array('pay_valid', 0),
        array('pay', 0),
        array('pay_count', 0),
        array('first_pay', 0),
        array('first_pay_level', 0),
        array('first_pay_time', 0),
        array('utime', 'time', 2, 'function'),
        array('dtime', 0),
    );

    //创建信息
    public function cData($tid)
    {
        $add['tid'] = $tid;
        $add['utime'] = 0;
        return $this->CreateData($add);
    }

    //获取角色所有属性
    public function getRow($tid, $field = null)
    {
        $where['tid'] = $tid;
        return $this->getRowCondition($where, $field);
    }

    //获取VIP等级
    public function getLevel($tid)
    {
        $index = $this->getAttr($tid, 'index');
        $level = D('static')->access('vip', $index, 'level');
        return $level;
    }

    //获取角色基本属性
    public function getAttr($tid, $attr)
    {
        $where['tid'] = $tid;
        return $this->where($where)->getField($attr);
    }

    //增加属性
    public function incAttr($tid, $attr, $value, $before = null)
    {
        if ($value == 0) {
            return true;
        }
        if ($before === null) {
            $before = $this->getAttr($tid, $attr);
        }//没有改变前参数
        $where['tid'] = $tid;
        if (!$this->IncreaseData($where, $attr, $value)) {
            return false;
        }
        D('LVip')->cLog($tid, $attr, $value, $before);//日志
        return true;
    }

    //减少属性
    public function decAttr($tid, $attr, $value, $before = null)
    {
        if ($value == 0) {
            return true;
        }
        if ($before === null) {
            $before = $this->getAttr($tid, $attr);
        }//没有改变前参数
        $where['tid'] = $tid;
        if (!$this->DecreaseData($where, $attr, $value)) {
            return false;
        }
        D('LVip')->cLog($tid, $attr, -$value, $before);//日志
        return true;
    }

    //改变属性
    public function updateAttr($tid, $attr, $value, $before = null)
    {
        //如果没有before
        if ($before === null)
            $before = $this->getAttr($tid, $attr);
        $where['tid'] = $tid;
        $data[$attr] = $value;
        if (false === $this->UpdateData($data, $where))
            return false;
        D('LVip')->cLog($tid, $attr, $value, $before);//日志
        return true;
    }

    //增加VIP数据
    public function pay($tid, $pay, $isValid, $isCount = true)
    {
        //获取VIP配置
        $vipConfig = D('static')->access('vip');
        //获取VIP信息
        $vipInfo = $this->getRow($tid);
        $vipInfo['level'] = $vipConfig[$vipInfo['index']]['level'];
        //加总充值
        if (!$this->incAttr($tid, 'pay', $pay, $vipInfo['pay'])) {
            return false;
        }
        //加充值次数
        if ($isCount === true) {
            if (!$this->incAttr($tid, 'pay_count', 1, $vipInfo['pay_count'])) {
                return false;
            }
        }
        //是否是有效充值
        if ($isValid == 1) {
            //加有效充值
            if (!$this->incAttr($tid, 'pay_valid', $pay, $vipInfo['pay_valid'])) {
                return false;
            }
            //加VIP积分
            $rate = D('Static')->access('params', 'CASH_TRAN');
            $score = $rate * $pay;
            if (!$this->incAttr($tid, 'score', $score, $vipInfo['score'])) {
                return false;
            }
            //检查是否有升级情况
            $scoreAll = $score + $vipInfo['score'];
            $currentIndex = self::defaultVipIndex;
            foreach ($vipConfig as $key => $value) {
                if ($scoreAll >= $value['score']) {
                    $currentIndex = $key;
                } else {
                    break;
                }
            }
            if ($currentIndex != self::defaultVipIndex && $currentIndex != $vipInfo['index']) {
                if (!$this->updateAttr($tid, 'index', $currentIndex, $vipInfo['index'])) {
                    return false;
                }
                //发送补偿邮件
                $mailList = array();
                //获取用户昵称
                $nickname = D('GTeam')->getAttr($tid, 'nickname');
                //创建邮件
                for ($i = 1; $i <= $currentIndex - $vipInfo['index']; ++$i) {
                    $mail = array();
                    $mail['mail_id'] = $vipInfo['index'] + $i - 1000 + 6000;
                    $mail['tid'] = 0;
                    $mail['target_tid'] = $tid;
                    $mail['params']['target_nickname'] = $nickname;
                    $mailList[] = $mail;
                }
                //返回
                if (!empty($mailList)) {
                    if (false === D('GMail')->sendAll($mailList)) {
                        return false;
                    }
                }
                D('SOpenProcess')->checkNewOpen($tid, 2, $vipInfo['index']);//查看有没有需要开放的功能
            }
        }

        //返回
        return true;

    }

    //检查是否已经达到最大次数
    public function checkCount($tid, $field, $count, $max = 0)
    {
        $vip_id = $this->where(array('tid' => $tid,))->getField('index');
        $extra = D('Static')->access('vip', $vip_id, $field);
        $max = $extra + $max;
        if ($count >= $max) {
            C('G_ERROR', 'buy_count_max');
            return false;
        }
        return true;
    }

    //获取VIP等级排名
    public function getRankList()
    {
        $sql = "select `gt`.`tid`,`gt`.`nickname`,`gt`.`icon`,`gt`.`level`,`gv`.`index` as `data` from `g_team` as `gt`,`g_vip` as `gv` where `gt`.`tid`=`gv`.`tid` && `gv`.`index` >= 1000 && `gv`.`index` < 2000 order by `gv`.`index` DESC, `gv`.`score` DESC, `gt`.`tid` ASC limit " . C('RANK_MAX') . ";";
        $list = $this->query($sql);
        $list = $this->getLeagueName($list);
        return $list;
    }

    //计算实时排名
    public function getCurrentVipLevelRank($tid, $vip = null)
    {

        //如果没有传排名
        if (is_null($vip)) {
            $vip = $this->getAttr($tid, 'index');
        }
        $data['current'] = $vip;

        //排名
        if ($vip >= 2000 || $vip < 1000) {
            $data['rank'] = 0;
        } else {
            //查询最新排名
            $where = "`index`>'{$vip}' || (`index`='{$vip}' && `tid`<='{$tid}')";
            $count = $this->where($where)->count();
            $data['rank'] = $count;
        }

        //返回
        return $data;

    }

}