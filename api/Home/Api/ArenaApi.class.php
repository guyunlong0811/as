<?php
namespace Home\Api;

use Think\Controller;

class ArenaApi extends BEventApi
{

    const OPEN = 2001;
    const GROUP = 1;
    private $mArenaInfo;

    const ALL = 3;//需要的总人数

    const AFTER = 1;//排名靠后人数
    const AFTER_RANGE = 10;//排名靠后范围

    const BEFORE_DEFAULT = 0;//默认
    static $before = array(50 => 1, 30 => 1,);//排名靠前配置
    static $beforeTop = array(70 => 1, 0 => 1,);//排名靠前配置

    public function _initialize()
    {
        parent::_initialize();
        if (!$this->event(self::GROUP)) {
            exit;
        }
        //查询玩家竞技场信息
        $this->mArenaInfo = D('GArena')->getRow($this->mTid);
        if (empty($this->mArenaInfo)) {
            if (!D('SOpenProcess')->checkOpen($this->mTid, self::OPEN)) {
                exit;
            } else {
                D('GArena')->open($this->mTid);
                if (!$this->mArenaInfo = D('GArena')->getRow($this->mTid)) {
                    exit;
                }
            }
        }
        $this->mArenaInfo['rand_list'] = json_decode($this->mArenaInfo['rand_list'], true);
    }

    //获取玩家信息
    public function getInfo()
    {
        //排名
        $info['rank'] = $this->mArenaInfo['rank'];

        //剩余挑战次数
        $info['remain'] = $this->mEventRemainCount;

        //购买次数
        $info['buy'] = $this->mEventBuyCount;

        //防御阵容
        $info['defense']['partner'] = json_decode($this->mArenaInfo['partner'], true);

        //返回
        return $info;
    }

    //获取玩家可以挑战的对手列表
    public function getList()
    {

        //随机对手
        $needRefresh = false;
        if (count($this->mArenaInfo['rand_list']) < 3) {
            $needRefresh = true;
        } else if (max($this->mArenaInfo['rand_list']) <= $this->mArenaInfo['rank']) {
            $needRefresh = true;
        } else if (in_array($this->mArenaInfo['rank'], $this->mArenaInfo['rand_list'])) {
            $needRefresh = true;
        }
        if ($needRefresh) {
            $this->refresh(true);//刷新对手
            $this->mArenaInfo = D('GArena')->getRow($this->mTid);
            $this->mArenaInfo['rand_list'] = json_decode($this->mArenaInfo['rand_list'], true);
        }

        //获取对手数据
        $rankList = $this->mArenaInfo['rand_list'];

        //查看自己在不在列表中
        if (empty($rankList) || in_array($this->mArenaInfo['rank'], $rankList)) {
            $rankList = $this->getRandRank();
            $where['tid'] = $this->mTid;
            $data['rand_list'] = json_encode($rankList);
            if (false === D('GArena')->UpdateData($data, $where)) {
                return false;
            }
        }

        //查询
        $fieldArena = array('tid', 'rank', 'partner',);
        $whereRank = '';
        foreach ($rankList as $value) {
            $whereRank .= "`rank`='{$value}' || ";
        }
        $whereRank = substr($whereRank, 0, -4);
        $select = M('GArena')->field($fieldArena)->where($whereRank)->select();
        foreach ($select as $value) {
            $defense[$value['rank']] = $value;
        }
//        dump($defense);

        //查询对手的基本信息
        $fieldTeam = array('tid', 'nickname', 'level', 'icon',);
        $whereTid = '';
        foreach ($defense as $value) {
            $whereTid .= "`tid`='{$value['tid']}' || ";
        }
        $whereTid = substr($whereTid, 0, -4);
        $select = M('GTeam')->field($fieldTeam)->where($whereTid)->select();
        $team = array();
        foreach ($select as $value) {
            $team[$value['tid']] = $value;
        }
//        dump($team);

        //查询对手的伙伴信息
        $opponent = array();
        foreach ($defense as $key => $value) {
            $partners = json_decode($value['partner'], true);
            $partnerList = D('GPartner')->getDisplayInfo($value['tid'], $partners);
            foreach ($partners as $val) {
                $opponent[$value['tid']][] = $val != 0 ? $partnerList[(int)$val] : array('group' => 0);
            }
        }
//        dump($opponent);

        //整理数据
        $list = array();
        foreach ($rankList as $key => $value) {
            $arr = array();
            //基础数值
            $arr['rank'] = $value;
            $tid = $defense[$value]['tid'];
            if (empty($tid)) continue;
            $arr['tid'] = $tid;
            $arr['nickname'] = $team[$tid]['nickname'];
            $arr['level'] = $team[$tid]['level'];
            $arr['icon'] = $team[$tid]['icon'];
            $arr['partner'] = $opponent[$tid];
            $list[] = $arr;
        }

        //返回
        return $list;

    }

    //获取战斗记录
    public function getBattleList()
    {
        D('GArena')->setAttr($this->mTid, 'rank_change', 0);
        return D('LArenaBattle')->getList($this->mTid);
    }

    //手动刷新排名
    public function refresh($status = false)
    {
        if (!$status) {
            //是否刷新过快
            $now = time();
            $utime = $this->mArenaInfo['last_refresh_time'];
            $utimeConfig = D('Static')->access('params', 'ARENA_REFRESH_PLAYER');
            if ($utime + $utimeConfig > $now) {
                C('G_ERROR', 'refresh_too_fast');
                return false;
            }
            //获取刷新时间
            $data['last_refresh_time'] = time();
        }

        //随机
        $rankList = $this->getRandRank();

        //更新
        $where['tid'] = $this->mTid;
        $data['rand_list'] = json_encode((array)$rankList);
        D('GArena')->UpdateData($data, $where);
        return true;
    }

    //返回对手列表ID
    private function getRandRank()
    {

        $rankRandList = array();

        //当前排名
        $rank = $this->mArenaInfo['rank'];

        //查询最低排名
        $last = M('GArena')->max('rank');

        //排名靠后个数
        $afterNum = $last - $rank;

        //排名在后面的对手个数
        if ($afterNum < self::AFTER) {
            $after = $afterNum;
        } else {
            $after = self::AFTER;
        }

        //排名在前面的对手个数
        $before = self::ALL - $after;

        //重新计算排名靠后的对手个数
        if ($rank <= $before) {
            $before = $rank - 1;
            $afterNew = self::ALL - $before;
            if ($afterNew > $afterNum - $after) {
                $after = $afterNum;
            } else {
                $after = $afterNew;
            }
        }

        //随机排名在后的对手
        if ($after > 0) {

            //开始排名
            $start = $rank + 1;
            $afterEnd = ceil($rank * self::AFTER_RANGE / 100);
            if ($afterNum >= $afterEnd) {
                $end = $start + $afterEnd - 1;
            } else {
                $end = $start + $afterNum - 1;
            }
            if ($end - $start < $after) {
                $end = $start + $after - 1;
            }
            for ($i = 1; $i <= $after; ++$i) {
                $rankRandList[] = $this->randRank($start, $end, $rankRandList);
            }

        }

        //随机排名在前的对手
        if ($before > 0) {

            //首先查看前面有没有足够的人
            if ($before >= $rank - 1) {
                for ($i = 1; $i <= $rank - 1; ++$i) {
                    $rankRandList[] = $i;
                }
            } else {

                //最后排名
                $lastEnd = 0;
                $arrBefore = $rank <= 10 && $rank >= 4 ? self::$beforeTop : self::$before;
                foreach ($arrBefore as $rankBeforeRate => $count) {

                    $rankBefore = $rank - ceil($rank * $rankBeforeRate / 100);

                    //有多余是默认范围
                    if (!isset($init)) {
                        $init = $rankBefore;
                    }

                    //检查在此之前是否有那么多排名
                    if ($rankBefore >= $rank - 1) {
                        for ($i = 1; $i <= $before; ++$i) {
                            $rankRandList[] = $this->randRank(1, $rank - 1, $rankRandList);
                        }
                        $before = 0;
                        break;
                    } else {
                        for ($i = 1; $i <= $count; ++$i) {
                            $rankRandList[] = $this->randRank($rank - $rankBefore, $rank - $lastEnd - 1, $rankRandList);
                        }
                        $before -= $count;
                    }

                    $lastEnd = $rankBefore;
                }

                //如果
                if ($before > 0) {

                    for ($i = 1; $i <= $before; ++$i) {

                        //分配10000机器人给他
                        if ($i == 1) {
                            $robTid = substr($this->mTid, -3) + 9001;
                            $robRank = M('GArena')->where("`tid`='{$robTid}'")->getField('rank');
                            if (in_array($robRank, $rankRandList)) {
                                $rankRandList[] = $this->randRank($rank - $init, $rank - 1, $rankRandList);
                            } else {
                                $rankRandList[] = (int)$robRank;
                            }
                        } else {
                            $rankRandList[] = $this->randRank($rank - $init, $rank - 1, $rankRandList);
                        }

                    }

                }

            }

        }

        //返回
        sort($rankRandList);//排序
        return $rankRandList;

    }

    //随机名次
    private function randRank($start, $end, $arr)
    {
        $num = rand($start, $end);//列成一个数组
        if (in_array($num, $arr)) {
            return $this->randRank($start, $end, $arr);
        } else {
            return $num;
        }
    }

    //配置防御阵型
    public function setDefense()
    {
        $where['tid'] = $this->mTid;
        $data['partner'] = json_encode($_POST['defense_partner']);
        if (false === D('GArena')->UpdateData($data, $where)) {
            return false;
        }
        return true;
    }

    //发起挑战
    public function fight()
    {

        //不能和自己对战
        if ($_POST['target_tid'] == $this->mTid) {
            C('G_ERROR', 'fight_to_self');
            return false;
        }

        //检查挑战次数是否足够
        if ($this->mEventRemainCount < 1) {
            C('G_ERROR', 'challenges_not_enough');
            return false;
        }

        //对手是否在随机列表中
        $opponentList = $this->mArenaInfo['rand_list'];
        if (!in_array($_POST['target_rank'], $opponentList)) {
            C('G_ERROR', 'arena_opponent_not_in_list');
            return false;
        }

        //实例化PVP
        $dynId = D('Static')->access('params', 'ARENA_BATTLE_MAP');
        $target['tid'] = $_POST['target_tid'];
        $target['rank'] = $_POST['target_rank'];
        if (!$return = $this->dynamicFight($dynId, 'Arena', $_POST['partner'], $target)) {
            return false;
        }

        //记录购买
        if (!$this->useEventCount()) {
            return false;
        }

        //返回
        return $return;

    }

    //战斗胜利
    public function win()
    {

        //副本胜利
        $dynId = D('Static')->access('params', 'ARENA_BATTLE_MAP');
        $ret = $this->dynamicWin($dynId);
        if (!$ret) {
            return false;
        }
        $result = $ret['result'];
        $target_tid = $ret['target'];

        //交换排名
        $change = 0;
        if ($result == 1) {

            //交换排名
            if (false === $change = D('GArena')->exchange($this->mTid, $target_tid)) {
                goto end;
            }

            //连胜记录
            if (!D('GArena')->incAttr($this->mTid, 'win', 1, $this->mArenaInfo['win'])) {
                goto end;
            }

            //重新获取数据
            $this->mArenaInfo = D('GArena')->getRow($this->mTid);

            //连胜成就
            if (!D('GCount')->arenaWinContinuous($this->mTid, $this->mArenaInfo['win'])) {
                goto end;
            }

            //属性
            $this->refresh();


        } else {
            //连胜记录
            if (!D('GArena')->updateAttr($this->mTid, 'win', 0, $this->mArenaInfo['win'])) {
                goto end;
            }

            C('G_BEHAVE', 'arena_lose');
            C('G_ERROR', 'battle_anomaly');
        }

        //战斗结束
        if (!$this->end($result, $target_tid, $change)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        if ($result == 1) {
            return true;
        } else {
            return false;
        }

    }


    //战斗失败
    public function lose()
    {

        //战斗失败
        $dynId = D('Static')->access('params', 'ARENA_BATTLE_MAP');
        if (false === $ret = $this->dynamicLose($dynId)) {
            return false;
        }

        //返回信息
        $result = $ret['result'];
        $target_tid = $ret['target'];

        //开始事务
        $this->transBegin();

        //连胜记录
        if (!D('GArena')->updateAttr($this->mTid, 'win', 0, $this->mArenaInfo['win'])) {
            goto end;
        }

        //战斗结束
        if (!$this->end($result, $target_tid)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return true;

    }

    //战斗结束
    private function end($result, $target_tid, $change = 0)
    {

        //获取当前成就值
        $count = D('GCount')->getRow($this->mTid, array('arena', 'arena_win', 'arena_rank'));

        //战斗次数
        $count['arena'] += 1;

        //查看排名是否需要更新
        if ($count['arena_rank'] == 0 || $this->mArenaInfo['rank'] < $count['arena_rank']) {
            $count['arena_rank'] = $this->mArenaInfo['rank'];
        }

        //发放奖励
        switch ($result) {
            case '-1':
            case '0':
                $bonus = D('Static')->access('params', 'ARENA_BATTLE_LOSE_BONUS');//获得奖励
                break;
            case '1':
                $count['arena_win'] += 1;
                $bonus = D('Static')->access('params', 'ARENA_BATTLE_WIN_BONUS');//获得奖励
                break;
        }

        //增加荣誉值
        if (!D('GArena')->incAttr($this->mTid, 'honour', $bonus)) {
            return false;
        }

        //记录成就值
        $where['tid'] = $this->mTid;
        D('GCount')->UpdateData($count, $where);

        //战斗记录
        $where = array();
        $field = array('tid', 'nickname', 'icon', 'level',);
        $where['tid'] = array('in', array($this->mTid, $target_tid,));
        $select = M('GTeam')->field($field)->where($where)->limit(2)->select();
        foreach ($select as $value) {
            if ($value['tid'] == $this->mTid) {
                $add['tid1'] = $value['tid'];
                $add['nickname1'] = $value['nickname'];
                $add['icon1'] = $value['icon'];
                $add['level1'] = $value['level'];
            } else {
                $add['tid2'] = $value['tid'];
                $add['nickname2'] = $value['nickname'];
                $add['icon2'] = $value['icon'];
                $add['level2'] = $value['level'];
            }
        }
        if ($result >= 0) {
            $add['result'] = $result;
        } else {
            $add['result'] = 0;
        }
        $add['change'] = $change;
        D('LArenaBattle')->CreateData($add);

        //返回
        return true;
    }

    //购买挑战次数
    public function buy()
    {

        //买一次获得几次挑战次数
        $addCount = D('Static')->access('params', 'ARENA_BATTLE_COUNT_ADD');

        //查看次数是否超过上限
        if ($this->mEventConfig['count'] < ($addCount + $this->mEventRemainCount)) {
            C('G_ERROR', 'arena_challenges_max');
            return false;
        }

        //检查玩家是否还有购买资格
        if (!D('GVip')->checkCount($this->mTid, 'arena_count', $this->mEventBuyCount)) {
            return false;
        }
        $count = $this->mEventBuyCount + 1;

        //查询购买需要的货币
        $exchange = $this->exchangeMoney($this->mEventConfig['exchange'], $count);
        $needType = $exchange['needType'];
        $needValue = $exchange['needValue'];

        //检查玩家当前货币是否足够
        if (!$now = $this->verify($needValue, $this->mMoneyType[$needType])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣除货币
        if (!$this->recover($this->mMoneyType[$needType], $needValue, null, $now)) {
            goto end;
        }

        //记录购买
        if (!$this->buyEventCount()) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return true;

    }

}