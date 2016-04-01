<?php
namespace Home\Model;

use Think\Model;

class GPartnerModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
        array('utime', 'time', 3, 'function'), //任何时候把utime字段设置为当前时间
    );

    //查询全部伙伴信息
    public function getAll($tid, $field = array(), $partner = array())
    {
        if (empty($field)) {
            $field = array('group', 'index', 'level', 'exp', 'favour', 'soul', 'force', 'skill_1_level', 'skill_2_level', 'skill_3_level', 'skill_4_level', 'skill_5_level', 'skill_6_level',);
        }
        $where['tid'] = $tid;
        if (!empty($partner)) {
            $where['group'] = array('in', $partner);
        }
        $list = $this->field($field)->where($where)->select();
        if (empty($list))
            return array();
        return $list;
    }

    //获得伙伴
    public function cPartner($tid, $group)
    {

        //获取伙伴组的初始伙伴
        $config = D('Static')->access('partner_group', $group);
        if (empty($config)) {
            return false;
        }

        foreach ($config as $value) {
            if ($value['is_init'] == 1) {
                $partnerConfig = $value;
                break;
            }
        }

        //查是否有数据
        $where['tid'] = $tid;
        $where['group'] = $group;
        $partner = $this->getRowCondition($where);

        //没有数据
        if (empty($partner)) {
            //创建伙伴(当没有数据时)
            return $this->cData($partnerConfig, $tid, 1, 0, 0);
        }

        //有数据没级数
        if ($partner['level'] == 0) {
            return $this->incAttr($tid, $group, 'level', 1, 0);//升一级
        }

        //有数据有级数
        $soul = D('Static')->access('partner_group', $group, array($partnerConfig['index'], 'ex_soul',));//获取神力数
        return $this->incAttr($tid, $group, 'soul', $soul, $partner['soul']);//加神力

    }

    //创建神力
    public function addSoul($tid, $group, $soul)
    {

        //数据保护
        if (!($group > 0) || !($soul > 0)) {
            return true;
        }

        //查是否有数据
        $where['tid'] = $tid;
        $where['group'] = $group;
        $partner = $this->getRowCondition($where);

        //没有数据
        if (empty($partner)) {

            //获取伙伴组的初始伙伴
            $config = D('Static')->access('partner_group', $group);
            if (empty($config)) {
                return false;
            }

            foreach ($config as $value) {
                if ($value['is_init'] == 1) {
                    $partnerConfig = $value;
                    break;
                }
            }

            //创建伙伴(神力)
            return $this->cData($partnerConfig, $tid, 0, $soul);

        }

        //有数据则加神力
        return $this->incAttr($tid, $group, 'soul', $soul, $partner['soul']);//加神力

    }

    //创建伙伴
    private function cData($partnerConfig, $tid, $level, $soul = false)
    {

        //创建伙伴
        $data['tid'] = $tid;
        $data['group'] = $partnerConfig['group'];
        $data['index'] = $partnerConfig['index'];
        $data['level'] = $level ? $partnerConfig['init_level'] : 0;
        $data['exp'] = $partnerConfig['init_exp'];
        $data['soul'] = $soul ? $soul : 0;
        $data['favour'] = $partnerConfig['init_favour'];
        for ($i = 1; $i <= 6; ++$i) {
            $data['skill_' . $i . '_level'] = $partnerConfig['skill_' . $i . '_activate_type'] == 1 && $partnerConfig['skill_' . $i . '_activate_value'] == 1 ? 1 : 0;
        }
        if (!$this->CreateData($data)) {
            return false;
        }

        //基础属性
//        D('LPartner')->cLog($tid,$data['group'],'index',$data['index'],0);
//        if($data['level']>0)D('LPartner')->cLog($tid,$data['group'],'level',$data['level'],0);
//        if($data['exp']>0)D('LPartner')->cLog($tid,$data['group'],'exp',$data['exp'],0);
//        if($data['soul']>0)D('LPartner')->cLog($tid,$data['group'],'soul',$data['soul'],0);
//        if($data['favour']>0)D('LPartner')->cLog($tid,$data['group'],'favour',$data['favour'],0);

        //技能
//        for($i=1;$i<=6;++$i)
//            if($data['skill_'.$i.'_level']>0)
//                D('LPartner')->cLog($tid,$data['group'],'skill_'.$i.'_level',$data['skill_'.$i.'_level'],0);

        //创建伙伴装备
        if (!D('GEquip')->cData($tid, $partnerConfig['init_equipment_weapon'], $partnerConfig['group'])) {
            return false;
        }
        if (!D('GEquip')->cData($tid, $partnerConfig['init_equipment_armor'], $partnerConfig['group'])) {
            return false;
        }
        if (!D('GEquip')->cData($tid, $partnerConfig['init_equipment_accessory'], $partnerConfig['group'])) {
            return false;
        }

        //返回
        return true;

    }

    //伙伴获得经验(返回实际增加经验)
    public function incExp($tid, $group, $exp)
    {

        if (!($group > 0) || !($exp > 0)) {
            return true;
        }

        //获取当前等级和经验
        $teamLevel = D('GTeam')->getAttr($tid, 'level');

        //获取伙伴当前经验等级
        $partnerInfo = $this->getRow($tid, $group);
        if (empty($partnerInfo)) {
            C('G_ERROR', 'partner_not_exist');
            return false;
        }

        //如果等级为0则报错
        if ($partnerInfo['level'] == '0') {
            C('G_ERROR', 'partner_not_exist');
            return false;
        }

        //加经验
        $luaFunc = D('Static')->access('partner_group', $group, array($partnerInfo['index'], 'level_type',));
        $rs = $this->expLogic($tid, $group, $luaFunc, $teamLevel, (int)$partnerInfo['level'], (int)$partnerInfo['exp'], (int)$exp, 0, 0);

        //技能解锁
        if ($rs['level'] > 0) {//如果升级了
            $levelNow = $partnerInfo['level'] + $rs['level'];//最新等级
//            $addSkillPoint = D('Static')->access('params', 'SKILL_POINT_LEVEL_GET');
//            if (false === $this->incAttr($tid, $group, 'skill_point', $rs['level'] * $addSkillPoint)) {//加技能点
//                return false;
//            }
            $partnerConfig = D('Static')->access('partner_group', $group, $partnerInfo['index']);//查询伙伴配置
            //检查6个技能
            for ($i = 1; $i <= 6; ++$i) {
                if ($partnerInfo['skill_' . $i . '_level'] == '0' && $partnerConfig['skill_' . $i . '_activate_type'] == '1' && $partnerConfig['skill_' . $i . '_activate_value'] <= $levelNow) {//达到升级条件
                    if (false === $this->skillUnlock($tid, $group, $i)) {//解锁技能
                        return false;
                    }
                }
            }
        }

        //返回增加经验结果
        return $rs;

    }

    /* 加经验逻辑(
     * 战队ID，伙伴ID，
     * 当前战队等级，初始等级，初始经验，需要增加的经验，
     * 当前已增加等级，当前已增加经验
     * )
     */
    private function expLogic($tid, $group, $luaFunc, $teamLevel, $levelBefore, $expBefore, $exp, $levelAdded, $expAdded)
    {

        //获取升级所需经验
        $needExp = lua('partner_levelup', 'partner_levelup_' . $luaFunc, array($levelBefore + $levelAdded,));
        $where['tid'] = $tid;
        $where['group'] = $group;

        //查询当前升级还需要的经验
        if ($levelAdded == 0)
            $levelupNeedExp = $needExp - $expBefore;
        else
            $levelupNeedExp = $needExp;

        //加经验
        if ($levelupNeedExp > ($exp - $expAdded)) {//经验不够升级
            //增加经验
            if (!$this->IncreaseData($where, 'exp', $exp - $expAdded))
                return false;
            //记录日志
            if ($levelAdded > 0) {
                D('LPartner')->cLog($tid, $group, 'level', $levelAdded, $levelBefore);
            }
            D('LPartner')->cLog($tid, $group, 'exp', $exp, $expBefore);
            //返回升级的等级经验
            $rs['level'] = $levelAdded;
            $rs['exp'] = $exp;
            return $rs;
        } else {//需要升级
            if ($teamLevel <= ($levelBefore + $levelAdded)) {//如果等级已经等于战队等级则不能升级
                //增加经验
                if (!$this->IncreaseData($where, 'exp', $levelupNeedExp - 1)) {
                    return false;
                }
                //记录日志
                $expAdded = $expAdded + $levelupNeedExp - 1;
                if ($levelAdded > 0) {
                    D('LPartner')->cLog($tid, $group, 'level', $levelAdded, $levelBefore);
                }
                D('LPartner')->cLog($tid, $group, 'exp', $expAdded, $expBefore);
                //返回升级的等级经验
                $rs['level'] = $levelAdded;
                $rs['exp'] = $expAdded;
                return $rs;
            } else {
                //升一级
                if (!$this->IncreaseData($where, 'level', 1)) {
                    return false;
                }
                //经验清0
                $data['exp'] = 0;
                if (false === $this->UpdateData($data, $where)) {
                    return false;
                }
                //修改已增加的经验和等级
                $expAdded += $levelupNeedExp;
                ++$levelAdded;
                return $this->expLogic($tid, $group, $luaFunc, $teamLevel, $levelBefore, $expBefore, $exp, $levelAdded, $expAdded);//递归
            }
        }

    }

    //技能解锁
    public function skillUnlock($tid, $group, $skill_no)
    {
        $attr = 'skill_' . $skill_no . '_level';
        return $this->incAttr($tid, $group, $attr, 1, 0);
    }

    //增加属性
    public function incAttr($tid, $group, $attr, $value, $before = null)
    {
        if ($value == 0) {
            return true;
        }
        if ($attr == 'exp') {
            return $this->incExp($tid, $group, $value);
        }//加经验
        if ($before === null) {
            $before = $this->getAttr($tid, $group, $attr);
        }//如果没有before
        $where['tid'] = $tid;
        $where['group'] = $group;
        if (!$this->IncreaseData($where, $attr, $value)) {
            return false;
        }
        //如果是favour检查是否有技能解锁
        if ($attr == 'favour') {
            $partnerInfo = $this->getRow($tid, $group);
            $partnerConfig = D('Static')->access('partner_group', $group, $partnerInfo['index']);//查询伙伴配置
            //检查6个技能
            for ($i = 1; $i <= 6; ++$i) {
                if ($partnerInfo['skill_' . $i . '_level'] == 0 && $partnerConfig['skill_' . $i . '_activate_type'] == '2' && $partnerConfig['skill_' . $i . '_activate_value'] <= $value + $before) {//达到升级条件
                    $this->skillUnlock($tid, $group, $i);//解锁技能
                }
            }
        }
        D('LPartner')->cLog($tid, $group, $attr, $value, $before);//日志
        return true;
    }

    //减少属性
    public function decAttr($tid, $group, $attr, $value, $before = null)
    {
        if ($value == 0) {
            return true;
        }
        if ($before === null) {
            $before = $this->getAttr($tid, $group, $attr);
        }
        $where['tid'] = $tid;
        $where['group'] = $group;
        if (!$this->DecreaseData($where, $attr, $value)) {
            return false;
        }
        D('LPartner')->cLog($tid, $group, $attr, -$value, $before);//日志
        return true;
    }

    //改变属性
    public function updateAttr($tid, $group, $attr, $value, $before = null)
    {
        //如果没有before
        if ($before === null) {
            $before = $this->getAttr($tid, $group, $attr);
        }
        $where['tid'] = $tid;
        $where['group'] = $group;
        $data[$attr] = $value;
        if (false === $this->UpdateData($data, $where)) {
            return false;
        }
        D('LPartner')->cLog($tid, $group, $attr, $value, $before);//日志
        return true;
    }

    //获取单条数据某个字段
    public function getAttr($tid, $group, $attr)
    {
        $where['tid'] = $tid;
        $where['group'] = $group;
        return $this->where($where)->getField($attr);
    }

    //获取单条数据
    public function getRow($tid, $group, $field = null)
    {
        $where['tid'] = $tid;
        $where['group'] = $group;
        $data = $this->getRowCondition($where, $field);
        if (empty($data)) {
            C('G_ERROR', 'partner_not_exist');
            return false;
        }
        return $data;
    }

    //升阶
    public function upgrade($tid, $group, $target, $index)
    {
        return $this->updateAttr($tid, $group, 'index', $target, $index);
    }

    //查询胖班是否存在
    public function isExist($tid, $group)
    {
        $where['tid'] = $tid;
        $where['group'] = $group;
        $count = $this->where($where)->count();
        if ($count > 0) {
            return true;
        } else {
            C('G_ERROR', 'partner_not_exist');
            return false;
        }
    }

    //获取当前玩家拥有伙伴组信息
    public function getGroups($tid)
    {
        $field = array('group',);
        $where['tid'] = $tid;
        $where['level'] = array('gt', 0);
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        foreach ($select as $value)
            $list[] = $value['group'];
        return $list;
    }

    //获取玩家当前拥有的所有伙伴ID
    public function getPartnerIds($tid)
    {
        $field = array('index',);
        $where['tid'] = $tid;
        $where['level'] = array('gt', 0);
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        foreach ($select as $value)
            $list[] = $value['index'];
        return $list;
    }

    //查询玩家是否拥有伙伴组
    public function group2partner($tid, $group)
    {
        $where['tid'] = $tid;
        $where['group'] = $group;
        $where['level'] = array('gt', 0);
        $partner = $this->where($where)->getField('index');
        if (empty($partner)) {
            return false;
        }
        return $partner;
    }

    //查询玩家总战力
    public function getForce($tid)
    {
        $where['tid'] = $tid;
        return $this->where($where)->sum('`force`');
    }

    //查询玩家最强小队战力
    public function getForceTop($tid)
    {
        $sql = "select sum(`force`) as `force_top` from (select `force` from `g_partner` where `tid`='{$tid}' order by `force` DESC limit 5) as `gp` limit 1";
        $data = $this->query($sql);
        return $data[0]['force_top'];
    }

    //获取伙伴显示信息
    public function getDisplayInfo($tid, $partner)
    {
        $field = array('group', 'index', 'level', 'favour', 'force',);
        $where['tid'] = $tid;
        $where['group'] = array('in', $partner);
        return $this->field($field)->where($where)->select();
    }

    //获取战力最高的5伙伴group
    public function getTop5Group($tid)
    {
        $field = 'group';
        $where['tid'] = $tid;
        $where['level'] = array('gt', 0);
        $select = $this->field($field)->where($where)->order('`force` DESC')->limit(5)->select();
        $list = array();
        foreach ($select as $value) {
            $list[] = (int)$value['group'];
        }
        return $list;
    }

}