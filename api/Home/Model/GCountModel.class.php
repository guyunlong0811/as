<?php
namespace Home\Model;

use Think\Model;

class GCountModel extends BaseModel
{

    //创建数据
    public function cData($tid)
    {
        $data['tid'] = $tid;
        return $this->CreateData($data);
    }

    //获取数据
    public function getRow($tid, $field = null)
    {

        $where['tid'] = $tid;
        $data = $this->getRowCondition($where, $field);
        if (empty($data)) {

            if ($this->cData($tid)) {
                return $this->getRow($tid, $field);
            } else {
                return false;
            }

        } else {
            return $data;
        }

    }

    //增加属性
    public function incAttr($tid, $attr, $value = 1)
    {
        $where['tid'] = $tid;
        return $this->IncreaseData($where, $attr, $value);
    }

    //获取属性
    public function getAttr($tid, $attr)
    {
        $where['tid'] = $tid;
        return $this->where($where)->getField($attr);
    }

    //获取属性
    public function setAttr($tid, $attr, $value)
    {
        $where['tid'] = $tid;
        return $this->where($where)->setField($attr, $value);
    }

    //记录连击数
    public function mini($tid, $ver)
    {
        //获取当前值
        $now = $this->getAttr($tid, 'mini_game');
        //超越则更新
        if ($ver > $now) {
            return $this->setAttr($tid, 'mini_game', $ver);
        }
        return true;
    }

    //增加总星数
    public function star($tid, $star)
    {
        $where['tid'] = $tid;
        return $this->IncreaseData($where, 'star', $star);
    }

    //记录连击数
    public function combo($tid, $combo)
    {
        //获取当前值
        $now = $this->getAttr($tid, 'combo');
        //超越则更新
        if ($combo > $now) {
            return $this->setAttr($tid, 'combo', $combo);
        }
        return true;
    }

    //更新竞技场连胜记录
    public function arenaWinContinuous($tid, $win)
    {
        $now = $this->getAttr($tid, 'arena_win_continuous');
        if ($now < $win) {
            return $this->setAttr($tid, 'arena_win_continuous', $win);
        }
        return true;
    }

    //记录深渊之战排名
    public function abyss($tid, $rank)
    {
        //获取当前排名
        $now = $this->getAttr($tid, 'abyss_rank');
        //超越则更新
        if ($now == 0 || $rank < $now) {
            return $this->setAttr($tid, 'abyss_rank', $rank);
        }
        return true;
    }

    //记录水晶捐献次数
    public function donate($tid)
    {
        $where['tid'] = $tid;
        return $this->IncreaseData($where, 'league_donate', 1);
    }

    //记录分享次数
    public function share($tid)
    {
        $where['tid'] = $tid;
        return $this->IncreaseData($where, 'share', 1);
    }

    //记录登录天数
    public function login($tid)
    {
        $lastLoginTime = D('GTeam')->getAttr($tid, 'last_login_time');
        $today = get_daily_utime();
        if ($lastLoginTime < $today) {
            $where['tid'] = $tid;
            return $this->IncreaseData($where, 'login', 1);
        }
        return true;
    }

    //记录连续登录天数
    public function loginContinuous($tid, $day)
    {
        $now = $this->getAttr($tid, 'login_continuous');
        if ($now < $day) {
            return $this->setAttr($tid, 'login_continuous', $day);
        }
        return true;
    }

    //更新总战力
    public function force($tid)
    {
        $force = D('GPartner')->getForce($tid);
        return $this->setAttr($tid, 'force', $force);
    }

    //更新总战力
    public function forceTop($tid)
    {
        $force = D('GPartner')->getForceTop($tid);
        return $this->setAttr($tid, 'force_top', $force);
    }

    //获取星数排名
    public function getStarRankList()
    {
        $sql = "select `gt`.`tid`,`gt`.`nickname`,`gt`.`icon`,`gt`.`level`,`gc`.`star` as `data` from `g_team` as `gt`,`g_count` as `gc` where `gt`.`tid`=`gc`.`tid` && `gt`.`ctime` > 0 order by `gc`.`star` DESC, `gc`.`tid` ASC limit " . C('RANK_MAX') . ";";
        $list = $this->query($sql);
        $list = $this->getLeagueName($list);
        return $list;
    }

    //获取竞技场连胜排名
    public function getArenaWinContinuousRankList()
    {
        $sql = "select `gt`.`tid`,`gt`.`nickname`,`gt`.`icon`,`gt`.`level`,`gc`.`arena_win_continuous` as `data` from `g_team` as `gt`,`g_count` as `gc` where `gt`.`tid`=`gc`.`tid` && `gt`.`ctime` > 0 order by `gc`.`arena_win_continuous` DESC, `gc`.`tid` ASC limit " . C('RANK_MAX') . ";";
        $list = $this->query($sql);
        $list = $this->getLeagueName($list);
        return $list;
    }

    //获取连击次数排名
    public function getComboRankList()
    {
        $sql = "select `gt`.`tid`,`gt`.`nickname`,`gt`.`icon`,`gt`.`level`,`gc`.`combo` as `data` from `g_team` as `gt`,`g_count` as `gc` where `gt`.`tid`=`gc`.`tid` && `gt`.`ctime` > 0 order by `gc`.`combo` DESC, `gc`.`tid` ASC limit " . C('RANK_MAX') . ";";
        $list = $this->query($sql);
        $list = $this->getLeagueName($list);
        return $list;
    }

    //获取战力排名
    public function getForceRankList()
    {
        $sql = "select `gt`.`tid`,`gt`.`nickname`,`gt`.`icon`,`gt`.`level`,`gc`.`force` as `data` from `g_team` as `gt`,`g_count` as `gc` where `gt`.`tid`=`gc`.`tid` && `gt`.`ctime` > 0 && `gc`.`force` > 0 order by `gc`.`force` DESC, `gc`.`tid` ASC limit " . C('RANK_MAX') . ";";
        $list = $this->query($sql);
        $list = $this->getLeagueName($list);
        return $list;
    }

    //获取最强小队战力排名
    public function getForceTopRankList()
    {
        $sql = "select `gt`.`tid`,`gt`.`nickname`,`gt`.`icon`,`gt`.`level`,`gc`.`force_top` as `data` from `g_team` as `gt`,`g_count` as `gc` where `gt`.`tid`=`gc`.`tid` && `gt`.`ctime` > 0 && `gc`.`force_top` > 0 order by `gc`.`force_top` DESC, `gc`.`tid` ASC limit " . C('RANK_MAX') . ";";
        $list = $this->query($sql);
        $list = $this->getLeagueName($list);
        return $list;
    }

    //获取成就点排名
    public function getAchievementRankList()
    {
        $sql = "select `gt`.`tid`,`gt`.`nickname`,`gt`.`icon`,`gt`.`level`,`gc`.`achievement` as `data` from `g_team` as `gt`,`g_count` as `gc` where `gt`.`tid`=`gc`.`tid` && `gt`.`ctime` > 0 && `gc`.`achievement` > 0 order by `gc`.`achievement` DESC, `gc`.`tid` ASC limit " . C('RANK_MAX') . ";";
        $list = $this->query($sql);
        $list = $this->getLeagueName($list);
        return $list;
    }

    //获取玩家实时星数排名
    public function getCurrentStarRank($tid, $star = null)
    {

        //如果没有传排名
        if (is_null($star)) {
            $star = $this->getAttr($tid, 'star');
        }
        $data['current'] = $star;

        //查询最新排名
        $where = "`star`>'{$star}' || (`star`='{$star}' && `tid`<='{$tid}')";
        $count = $this->where($where)->count();
        $data['rank'] = $count;

        //返回
        return $data;

    }

    //获取玩家实时连击数排名
    public function getCurrentComboRank($tid, $combo = null)
    {

        //如果没有传排名
        if (is_null($combo)) {
            $combo = $this->getAttr($tid, 'combo');
        }
        $data['current'] = $combo;

        //查询最新排名
        $where = "`combo`>'{$combo}' || (`combo`='{$combo}' && `tid`<='{$tid}')";
        $count = $this->where($where)->count();
        $data['rank'] = $count;

        //返回
        return $data;

    }

    //获取玩家实时竞技场连胜数排名
    public function getCurrentArenaWinContinuousRank($tid, $count = null)
    {

        //如果没有传排名
        if (is_null($count)) {
            $count = $this->getAttr($tid, 'arena_win_continuous');
        }
        $data['current'] = $count;

        //查询最新排名
        $where = "`arena_win_continuous`>'{$count}' || (`arena_win_continuous`='{$count}' && `tid`<='{$tid}')";
        $count = $this->where($where)->count();
        $data['rank'] = $count;

        //返回
        return $data;

    }

    //获取玩家实时战力排名
    public function getCurrentForceRank($tid, $force = null)
    {

        //如果没有传排名
        if (is_null($force)) {
            $force = $this->getAttr($tid, 'force');
        }
        $data['current'] = $force;

        //查询最新排名
        $where = "`force`>'{$force}' || (`force`='{$force}' && `tid`<='{$tid}')";
        $count = $this->where($where)->count();
        $data['rank'] = $count;

        //返回
        return $data;

    }

    //获取玩家实时最强小队战力排名
    public function getCurrentForceTopRank($tid, $force = null)
    {

        //如果没有传排名
        if (is_null($force)) {
            $force = $this->getAttr($tid, 'force_top');
        }
        $data['current'] = $force;

        //查询最新排名
        $where = "`force_top`>'{$force}' || (`force_top`='{$force}' && `tid`<='{$tid}')";
        $count = $this->where($where)->count();
        $data['rank'] = $count;

        //返回
        return $data;

    }

    //获取玩家实时成就点排名
    public function getCurrentAchievementRank($tid, $value = null)
    {

        //如果没有传排名
        if (is_null($value)) {
            $value = $this->getAttr($tid, 'achievement');
        }
        $data['current'] = $value;

        //查询最新排名
        $where = "`achievement`>'{$value}' || (`achievement`='{$value}' && `tid`<='{$tid}')";
        $count = $this->where($where)->count();
        $data['rank'] = $count;

        //返回
        return $data;

    }

}