<?php
namespace Home\Api;

use Think\Controller;

class PartnerApi extends BaseApi
{

    private $upgradeType = array(
        1 => 'attr',
        2 => 'item',
        3 => 'soul',
    );

    //获取伙伴列表
    public function getList($tid = null)
    {

        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        //获取所有伙伴信息
        return D('GPartner')->getAll($this->mTid);
    }

    //伙伴召唤
    public function call()
    {

        //查询伙伴信息
        $partnerInfo = D('GPartner')->getRow($this->mTid, $_POST['group']);
        if (empty($partnerInfo)) {
            C('G_ERROR', 'not_enough_partner_soul');
            return false;
        }

        //查看玩家是否已经获得伙伴
        if ($partnerInfo['level'] > 0) {
            C('G_ERROR', 'partner_already_get');
            return false;
        }

        //检查神力是否足够
        $partnerConfig = D('Static')->access('partner', $partnerInfo['index']);
        $needSoul = $partnerConfig['re_soul'];
        if ($needSoul > $partnerInfo['soul']) {
            C('G_ERROR', 'not_enough_partner_soul');
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣除神力
        if (!$this->recover('soul', $_POST['group'], $needSoul)) {
            goto end;
        }

        //升一级
        if (!D('GPartner')->incAttr($this->mTid, $_POST['group'], 'level', 1, 0)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //如果召唤的伙伴是ss级则发送全服公告
        if ($partnerConfig['partner_class'] == '9') {
            $params['nickname'] = D('GTeam')->getAttr($this->mTid, 'nickname');
            $params['partner_name'] = $partnerConfig['name'];
            $noticeConfig = D('SEventString')->getConfig('CALL_PARTNER', $params);
            D('GChat')->sendNoticeMsg($this->mTid, $noticeConfig['des'], $noticeConfig['show_level']);
        }

        //返回
        return true;

    }

    //伙伴升阶
    public function upgrade()
    {

        //查看是否达到了开放条件
//        if(!D('SOpenProcess')->checkOpen($this->mTid,1003)){return false;}

        //查询伙伴信息
        $partnerInfo = D('GPartner')->getRow($this->mTid, $_POST['group']);
        if (empty($partnerInfo)) {//错误伙伴
            C('G_ERROR', 'partner_not_exist');
            return false;
        }

        //查询升阶条件
        $upgradeConfig = D('Static')->access('partner_upgrade');
        if (!isset($upgradeConfig[$partnerInfo['index']])) {//不能升阶
            C('G_ERROR', 'partner_quality_max');
            return false;
        }

        //配置信息
        $upgrade = $upgradeConfig[$partnerInfo['index']];

        //逐个验证升阶条件
        for ($i = 1; $i <= 3; ++$i) {
            if ($upgrade['upgrade_' . $i . '_type'] != '0' && $upgrade['upgrade_' . $i . '_condition'] != '0' && $upgrade['upgrade_' . $i . '_value'] != '0') {
                if (!$verify[$i] = $this->verify($upgrade['upgrade_' . $i . '_value'], $this->upgradeType[$upgrade['upgrade_' . $i . '_type']], $upgrade['upgrade_' . $i . '_condition'])) {
                    return false;
                }
            }
        }

        //开始事务
        $this->transBegin();

        //伙伴升阶
        if (!D('GPartner')->upgrade($this->mTid, $_POST['group'], $upgrade['target_index'], $partnerInfo['index'])) {
            goto end;
        }

        //逐个扣除升阶属性和物品
        for ($i = 1; $i <= 3; ++$i) {
            if ($upgrade['upgrade_' . $i . '_type'] != '0' && $upgrade['upgrade_' . $i . '_condition'] != '0' && $upgrade['upgrade_' . $i . '_value'] != '0') {
                if (!$this->recover($this->upgradeType[$upgrade['upgrade_' . $i . '_type']], $upgrade['upgrade_' . $i . '_condition'], $upgrade['upgrade_' . $i . '_value'], $verify[$i])) {
                    goto end;
                }
            }
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

    //技能升级
    public function skillLevelup()
    {

        //查询伙伴信息
        if (!$partnerInfo = D('GPartner')->getRow($this->mTid, $_POST['group'])) {
            C('G_ERROR', 'partner_not_exist');
            return false;
        }

        //获取配置
        $skillLevelConfig = D('Static')->access('skill_levelup');

        //需求
        $needGold = 0;
        $needPoint = 0;

        //遍历升级技能
        foreach ($_POST['skill'] as $key => $value) {

            //技能未解锁
            if ($partnerInfo['skill_' . $key . '_level'] == '0') {//技能未解锁
                C('G_ERROR', 'skill_lock');
                return false;
            }

            //获取升级所需条件
            if (!isset($skillLevelConfig[($value - 1)])) {
                C('G_ERROR', 'skill_level_max');
                return false;
            }

            //检查伙伴是否到等级
            if ($skillLevelConfig[($value - 1)]['need_partner_level'] > $partnerInfo['level']) {
                C('G_ERROR', 'partner_level_low');
                return false;
            }

            //等级是否正确
            if ($value <= $partnerInfo['skill_' . $key . '_level']) {
                continue;
            }

            //升的级数
            $up = $value - $partnerInfo['skill_' . $key . '_level'];

            //计算所需金币和技能点
            for ($i = 0; $i < $up; ++$i) {
                $level = $partnerInfo['skill_' . $key . '_level'] + $i;
                $needGold += $skillLevelConfig[$level]['need_gold'];
                $needPoint += $skillLevelConfig[$level]['need_skill_point'];
            }

        }

        //检查玩家是否有足够的金币
        if (!$gold = $this->verify($needGold, 'gold')) {
            return false;
        }

        //是否有足够的技能点
        if (!$skillPoint = $this->verify($needPoint, 'skillPoint')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣钱
        if (!$this->recover('gold', $needGold, null, $gold)) {
            goto end;
        }

        //扣技能点
        if (!$this->recover('skillPoint', $needPoint, null, $skillPoint)) {
            goto end;
        }

        //技能升级
        foreach ($_POST['skill'] as $key => $value) {
            $up = $value - $partnerInfo['skill_' . $key . '_level'];//升的级数
            if ($up > 0) {
                if (!D('GPartner')->incAttr($this->mTid, $_POST['group'], 'skill_' . $key . '_level', $up, $partnerInfo['skill_' . $key . '_level'])) {
                    goto end;
                }
            }
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

    //觉醒
    public function awake()
    {

        //获取当前装备情况
        if (!$partnerInfo = D('GPartner')->getRow($this->mTid, $_POST['group'])) {
            C('G_ERROR', 'partner_not_exist');
            return false;
        }

        //查询装备进阶数据
        $favourNow = floor($partnerInfo['favour'] / 1000) * 1000;
        $awakeConfig = D('Static')->access('partner_awake', $_POST['group'], $favourNow);
        if (empty($awakeConfig)) {//是否可以觉醒
            C('G_ERROR', 'partner_awake_max');//觉醒到达顶级
            return false;
        }

        //查询等级是否足够
        if($partnerInfo['level'] < $awakeConfig['level']){
            C('G_ERROR', 'partner_level_low');
            return false;
        }

        //是否有足够的材料
        for ($i = 1; $i <= 6; $i++) {

            //材料是否足够
            if (false === $this->verify($awakeConfig['awake_' . $i . '_value_2'], $this->mBonusType[$awakeConfig['awake_' . $i . '_type']], $awakeConfig['awake_' . $i . '_value_1'])) {
                return false;
            }

        }

        //开始事务
        $this->transBegin();

        //扣除合成材料
        for ($i = 1; $i <= 6; $i++) {
            if (!$this->recover($this->mBonusType[$awakeConfig['awake_' . $i . '_type']], $awakeConfig['awake_' . $i . '_value_1'], $awakeConfig['awake_' . $i . '_value_2'])) {
                goto end;
            }
        }

        //觉醒
        if (!D('GPartner')->IncAttr($this->mTid, $_POST['group'], 'favour', $awakeConfig['favour_up'])) {
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

    //检查是否有技能开启
    public function checkSkillUnlock($group, $partnerInfo = null)
    {

        if (empty($partnerInfo)) {
            $partnerInfo = D('GPartner')->getRow($this->mTid, $group);
        }

        $partnerConfig = D('Static')->access('partner_group', $group, $partnerInfo['index']);

        for ($i = 1; $i <= 6; ++$i) {
            if ($partnerInfo['skill_' . $i . '_level'] == '0') {
                switch ($partnerConfig['skill_' . $i . '_activate_type']) {
                    case '1':
                        if ($partnerInfo['level'] >= $partnerConfig['skill_' . $i . '_activate_value'])
                            if (!D('GPartner')->skillUnlock($this->mTid, $group, $i)) {
                                return false;
                            }
                        break;
                    case '2':
                        if ($partnerInfo['favour'] >= $partnerConfig['skill_' . $i . '_activate_value'])
                            if (!D('GPartner')->skillUnlock($this->mTid, $group, $i)) {
                                return false;
                            }
                        break;
                    case '3':
                        if ($partnerInfo['index'] >= $partnerConfig['skill_' . $i . '_activate_value'])
                            if (!D('GPartner')->skillUnlock($this->mTid, $group, $i)) {
                                return false;
                            }
                        break;
                }
            }
        }

        return true;

    }

    //设置伙伴战斗力
    public function setForce()
    {

        $flag = true;

        //更新伙伴战斗力
        $field = array('index', 'level', 'favour');
        foreach ($_POST['list'] as $partner => $force) {
            $arrPartner[] = $partner;
            //验证战力最大值
            $partnerInfo = D('GPartner')->getRow($this->mTid, $partner, $field);
            $class = D('Static')->access('partner', $partnerInfo['index'], 'partner_class');
            $maxForce = lua('cal_ability', 'verify_ability', array($partnerInfo['index'], $class, $partnerInfo['level'], $partnerInfo['favour'],));
            if ($maxForce < $force) {
                $flag = false;
            } else {
                D('GPartner')->updateAttr($this->mTid, $partner, 'force', $force);
            }
        }

        //更新战力里排行榜数据
        D('GCount')->force($this->mTid);
        D('GCount')->forceTop($this->mTid);

        if (!$flag) {
            C('G_ERROR', 'partner_force_abnormal');
            return false;
        }

        return true;
    }

    public function getForce($defense)
    {
        $in = sql_in_condition($defense);
        $where = "`tid`='{$this->mTid}' && `group`" . $in;
        return D('GPartner')->where($where)->sum('`force`');
    }

}