<?php
namespace Home\Api;

use Think\Controller;

class AchievementApi extends BaseApi
{

    static private $compare = array(15, 34);//特殊比较

    //获取成就情况
    public function getInfo($tid)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }
        //查询累计登录天数
        $field = array('achievement', 'diamond_total', 'gold_total', 'combo', 'arena', 'arena_win', 'arena_win_continuous', 'arena_rank', 'abyss', 'abyss_kill', 'abyss_rank', 'league', 'league_donate', 'league_boss', 'league_boss_kill', 'league_fight', 'league_fight_win', 'league_arena', 'league_arena_win', 'login', 'login_continuous', 'star', 'quest_daily', 'gold_box_use', 'silver_box_use', 'emblem_combine',);
        $info = D('GCount')->getRow($this->mTid, $field);
        $info['life_death'] = D('GLifeDeathBattle')->getAttr($this->mTid, 'max');
        $info['babel'] = D('GBabel')->getAttr($this->mTid, 'max');
        $info['complete'] = D('GAchievement')->getList($this->mTid);
        return $info;
    }

    //完成成就
    public function complete()
    {

        //查询是否已经完成成就
        if (D('GAchievement')->isCompleted($this->mTid, $_POST['achieve_id'])) {
            C('G_ERROR', 'achieve_completed');
            return false;
        }

        //获取配置
        $config = D('Static')->access('achievement', $_POST['achieve_id']);

        //查询条件是否达成
        $count = 0;
        switch ($config['target_type']) {
            case '1'://指定级别伙伴数量
                $count = $this->schedule('partnerClassCount', $config['target_value_1']);
                break;
            case '2'://指定级别伙伴数量
                $count = $this->schedule('partnerQualityCount', $config['target_value_1']);
                break;
            case '3'://单个伙伴战力
                $where['tid'] = $this->mTid;
                $count = D('GPartner')->where($where)->max('`force`');
                break;
            case '4'://所有伙伴战力
                $where['tid'] = $this->mTid;
                $count = D('GPartner')->where($where)->sum('`force`');
                break;
            case '5'://指定觉醒伙伴数量
                $where['tid'] = $this->mTid;
                $where['favour'] = array('egt', floor($config['target_value_1'] / 1000));
                $count = D('GPartner')->where($where)->count();
                break;
            case '6'://指定等级伙伴数量
                $count = $this->schedule('partnerLevelCount', $config['target_value_1']);
                break;
            case '7'://加入公会
                $count = D('GCount')->getAttr($this->mTid, 'league');
                break;
            case '8'://公会捐献次数
                $count = D('GCount')->getAttr($this->mTid, 'league_donate');
                break;
            case '9'://PVE公会战参与次数
                $count = D('GCount')->getAttr($this->mTid, 'league_fight');
                break;
            case '10'://PVP公会战参与次数
                $count = D('GCount')->getAttr($this->mTid, 'league_arena');
                break;
            case '11'://公会BOSS参与次数
                $count = D('GCount')->getAttr($this->mTid, 'league_boss');
                break;
            case '12'://公会BOSS击杀次数
                $count = D('GCount')->getAttr($this->mTid, 'league_boss_kill');
                break;
            case '13'://竞技场参与次数
                $count = D('GCount')->getAttr($this->mTid, 'arena');
                break;
            case '14'://竞技场获胜次数
                $count = D('GCount')->getAttr($this->mTid, 'arena_win');
                break;
            case '15'://竞技场排名
                $count = D('GCount')->getAttr($this->mTid, 'arena_rank');
                break;
            case '16'://通天塔层数
                $count = D('GBabel')->getAttr($this->mTid, 'max');
                break;
            case '17'://累计登陆天数
                $count = D('GCount')->getAttr($this->mTid, 'login');
                break;
            case '18'://连续登录天数
                $count = D('GCount')->getAttr($this->mTid, 'login_continuous');
                break;
            case '19'://深渊之战挑战次数
                $count = D('GCount')->getAttr($this->mTid, 'abyss');
                break;
            case '20'://深渊之战击杀次数
                $count = D('GCount')->getAttr($this->mTid, 'abyss_kill');
                break;
            case '21'://累计完成每日任务次数
                $count = D('GCount')->getAttr($this->mTid, 'quest_daily');
                break;
            case '22'://伙伴装备纹章数
                $where['tid'] = $this->mTid;
                $where['partner'] = array('gt', 0);
                $select = D('GEmblem')->where($where)->getField('index', true);
                foreach($select as $value){
                    $quality = D('Static')->access('emblem', $value, 'quality');
                    if($quality >= $config['target_value_1']){
                        ++$count;
                    }
                }
                break;
            case '23'://合成纹章的数量
                $count = D('GCount')->getAttr($this->mTid, 'emblem_combine');
                break;
            case '24'://累计获得水晶数
                $count = D('GCount')->getAttr($this->mTid, 'diamond_total');
                break;
            case '25'://累计获得金币数
                $count = D('GCount')->getAttr($this->mTid, 'gold_total');
                break;
            case '26'://累计获得成就点
                $count = D('GCount')->getAttr($this->mTid, 'achievement');
                break;
            case '27'://金色宝箱使用
                $count = D('GCount')->getAttr($this->mTid, 'gold_box_use');
                break;
            case '28'://银色宝箱使用
                $count = D('GCount')->getAttr($this->mTid, 'silver_box_use');
                break;
            case '29'://指定品质装备
                $where['tid'] = $this->mTid;
                $select = D('GEquip')->field(array('group', 'index'))->where($where)->select();
                foreach($select as $value){
                    $quality = D('Static')->access('equipment', $value['group'], array($value['index'], 'quality'));
                    if($quality >= $config['target_value_1']){
                        ++$count;
                    }
                }
                break;
            case '30'://指定品质套装
                $where['tid'] = $this->mTid;
                $select = D('GEquip')->field(array('group', 'index', 'partner_group'))->where($where)->order('`partner_group` ASC')->select();
                $partner = 0;
                $flag = true;
                foreach($select as $value){
                    if($partner != $value['partner_group']){
                        if($flag === true && $partner != 0){
                            ++$count;
                        }
                        $partner = $value['partner_group'];
                        $flag = true;
                    }

                    $quality = D('Static')->access('equipment', $value['group'], array($value['index'], 'quality'));
                    if($quality < $config['target_value_1']){
                        $flag = false;
                    }
                }
                if($flag === true){
                    ++$count;
                }
                break;
            case '31'://最高Combo数
                $count = D('GCount')->getAttr($this->mTid, 'combo');
                break;
            case '32'://生死门层数
                $count = D('GLifeDeathBattle')->getAttr($this->mTid, 'max');
                break;
            case '33'://副本总星数
                $count = D('GCount')->getAttr($this->mTid, 'star');
                break;
            case '34'://深渊之战排名
                $count = D('GCount')->getAttr($this->mTid, 'abyss_rank');
                break;
            case '35'://PVP公会战胜利次数
                $count = D('GCount')->getAttr($this->mTid, 'league_arena_win');
                break;
            case '36'://PVE公会战胜利次数
                $count = D('GCount')->getAttr($this->mTid, 'league_fight_win');
                break;
            case '37'://竞技场连胜次数
                $count = D('GCount')->getAttr($this->mTid, 'arena_win_continuous');
                break;
            case '38'://单个伙伴觉醒
                $count = D('GPartner')->getAttr($this->mTid, $config['target_value_1'], 'favour');
                $count = floor($count / 1000);
                break;

        }

        //比较目标
        if(in_array($config['target_type'], self::$compare)){
            if ($count == '0' || $count > $config['target_value_2']) {
                C('G_ERROR', 'achieve_not_complete');
                return false;
            }
        }else{
            if ($count < $config['target_value_2']) {
                C('G_ERROR', 'achieve_not_complete');
                return false;
            }
        }

        //开始事务
        $this->transBegin();

        //记录成就达成
        if (false === D('GAchievement')->complete($this->mTid, $_POST['achieve_id'])) {
            goto end;
        }

        //获得奖励
        if (false === $this->bonus($config)) {
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