<?php
namespace Home\Api;

use Think\Controller;

class BaseApi extends Controller
{

    protected $mToken = '';//用户token信息
    protected $mSessionKey = '';//用户session键值
    protected $mSessionInfo = array();//用户session信息
    protected $mTid = 0;//玩家战队ID
    protected $mUid = 0;//玩家UID
    protected $mSilence = 0;//玩家ID
    protected $mServer;//服务器列表
    protected $mMoneyType = array(
        '1' => 'gold',
        '2' => 'diamond',
        '3' => 'contribution',
        '4' => 'honour',
        '5' => 'fund',
    );
    protected $mBonusType = array(
        '1' => 'item',
        '2' => 'attr',
        '3' => 'partner',
        '4' => 'favour',
        '5' => 'soul',
        '6' => 'dailyAttr',
        '7' => 'partnerExp',
        '8' => 'box',
        '9' => 'emblem',
    );
    private $openModule = array('Test', 'PayNotify',);

    public function _initialize()
    {

        //回掉放行
        if (in_array(CONTROLLER_NAME, $this->openModule)) {
            return;
        }


        if(C('G_LOOP') != 1){

            C('G_LOOP', 1);
            //获取角色ID(mTid)
            $arr = explode('.', C('G_METHOD'));
            $protocol = get_config('protocol', $arr);

            if (!isset($protocol['isToken'])) {

                $this->mToken = trim(C('G_TOKEN'));
                $this->mTid = D('Predis')->cli('game')->get('t:' . $this->mToken);
                if($this->mTid == 0){
                    $this->mSessionKey = 's:' . $this->mToken;
                }else{
                    $this->mSessionKey = 's:' . $this->mTid;
                }
                $this->mSessionInfo = D('Predis')->cli('game')->hgetall($this->mSessionKey);
                if (empty($this->mSessionInfo)) {
                    C('G_ERROR', 'login_timeout');
                    exit;
                }
                if (!$this->token2data()) {
                    exit;
                }
                C('G_TID', $this->mTid);
                C('G_SK', $this->mSessionKey);
                C('G_SI', $this->mSessionInfo);
                C('G_SLI', $this->mSilence);
                C('G_UID', $this->mUid);

            }

        }else{
            $this->mToken = trim(C('G_TOKEN'));
            $this->mTid = C('G_TID');
            $this->mSessionKey = C('G_SK');
            $this->mSessionInfo = C('G_SI');
            $this->mSilence = C('G_SLI');
            $this->mUid = C('G_UID');
        }

        ini_set('memory_limit', '256M');
        $this->mServer = get_server_list();
        return;
    }

    //检测版本
    protected function checkBaseVersion($cbv)
    {
        //获取当前服务器基座版本
        $content = D('StaticDyn')->access('params', 'VERLIST');
        $content = trim($content);
        $aes = new \Org\Util\CryptAES();
        $aes->set_key(C('AES_KEY'));
        $aes->require_pkcs5();
        $content = $aes->decrypt($content);
        $content = json_decode($content, true);
        $sbv = $content['currentbase'];

        //查看版本是否一样
        if ($cbv != $sbv) {
            //比较版本
            $arrCBV = explode('.', $cbv);
            $arrSBV = explode('.', $sbv);
            for ($i = 0; $i < 2; ++$i) {
                //比较大小
                if ($arrCBV[$i] != $arrSBV[$i]) {
                    if ($arrCBV[$i] > $arrSBV[$i]) {
                        return true;
                    } else {
                        C('G_ERROR', 'version_low');
                        return false;
                    }
                }
            }
        }
        return true;
    }

    //获取玩家角色ID
    protected function token2data()
    {
        //获取uid
        if (!$this->isLogin()) {
            return false;
        }
        //数据
        if (!isset($this->mSessionInfo['silence'])) {
            C('G_ERROR', 'login_timeout');
            return false;
        }
        //赋值
        $this->mSilence = $this->mSessionInfo['silence'];
        return true;
    }

    //检查玩家token是否存在或者过期
    protected function isLogin()
    {
        //token是否过期
        $tokenNow = D('Predis')->cli('game')->get('u:' . $this->mSessionInfo['uid']);
        if (!$tokenNow) {
            C('G_ERROR', 'login_timeout');
            return false;
        }

        //别处登录
        if ($this->mToken != $tokenNow) {
            C('G_ERROR', 'login_timeout');
            return false;
        }

        //服务器是否正确
        $sessionSid = $this->mSessionInfo['sid'];
        if ($sessionSid != C('G_SID')) {
            C('G_ERROR', 'login_timeout');
            return false;
        }

        $this->mUid = $this->mSessionInfo['uid'];
        return true;
    }

    //更新数据
    protected function setSessionAttr($tid, $attr, $value)
    {
        if(D('Predis')->cli('game')->exists('s:' . $tid)){
            D('Predis')->cli('game')->hset('s:' . $tid, $attr, $value);
        }
    }

    //开始事务
    protected function transBegin()
    {
        C('G_TRANS', true);//事务标示
        C('G_TRANS_FLAG', false);//事务标示
//        change_db_config(C('G_SID'), 'master');
        M()->startTrans();
    }

    //结束事务
    protected function transEnd()
    {

        if (!C('G_TRANS_FLAG')) {
            M()->rollback();
            if (C('G_ERROR') != 'db_error')//如果不是数据库有问题
                C('G_TRANS', false);//结束事务
        } else {
            M()->commit();
            C('G_TRANS', false);//结束事务
        }
//        change_db_config(C('G_SID'), 'all');
        return C('G_TRANS_FLAG');

    }

    //验证玩家操作是否符合条件
    protected function verify($need, $type, $params = null)
    {

        if ($need == 0) {
            return true;
        }

        if (!($need > 0)) {
            C('G_ERROR', 'params_error');
            return false;
        }

        $now = $this->schedule($type, $params);
        if ($need <= $now) {
            return $now;
        }

        switch ($type) {
            case 'gold':
                C('G_ERROR', 'not_enough_gold');
                break;

            case 'vality':
                C('G_ERROR', 'not_enough_vality');
                break;

            case 'diamond':
                C('G_ERROR', 'not_enough_diamond');
                break;

            case 'level':
                C('G_ERROR', 'team_level_low');
                break;

            case 'contribution':
                C('G_ERROR', 'league_contribution_not_enough');
                break;

            case 'attr':
            case 'dailyAttr':
                C('G_ERROR', 'not_enough_attr');
                break;

            case 'material_score':
                C('G_ERROR', 'material_score_not_enough');
                break;

            case 'item':
                C('G_ERROR', 'not_enough_item');
                break;

            case 'equip':
                C('G_ERROR', 'not_enough_equip');
                break;

            case 'soul':
                C('G_ERROR', 'not_enough_partner_soul');
                break;

            case 'vip':
                C('G_ERROR', 'vip_level_low');
                break;

            case 'partnerId':
                C('G_ERROR', 'partner_not_exist');
                break;

            case 'partnerGroup':
                C('G_ERROR', 'partner_not_exist');
                break;

            case 'partnerLevel':
                C('G_ERROR', 'partner_level_low');
                break;

            case 'partnerQuality':
                C('G_ERROR', 'partner_quality_low');
                break;

            case 'partnerQualityCount':
                C('G_ERROR', 'partner_quality_count_not_enough');
                break;

            case 'favour':
                C('G_ERROR', 'partner_favour_not_enough');
                break;

            case 'honour':
                C('G_ERROR', 'arena_honour_not_enough');
                break;

            case 'instance':
                C('G_ERROR', 'instance_complete_count_not_enough');
                break;

            case 'instanceGroup':
                C('G_ERROR', 'instance_group_complete_count_not_enough');
                break;

            case 'quest':
                C('G_ERROR', 'quest_not_complete');
                break;

            case 'skillPoint':
                C('G_ERROR', 'not_enough_skill_point');
                break;

            case 'emblem':
                C('G_ERROR', 'emblem_not_enough');
                break;

            case 'fund':
                C('G_ERROR', 'league_fund_not_enough');
                break;
        }
        return false;

    }

    //游戏所需进度(接取条件&完成条件都可用)查询(任务类型,条件参数,记录开始时间)
    protected function schedule($type, $params = null)
    {
        switch ($type) {
            case 'attr'://玩家属性
                $field = get_config('field', $params);//获取属性
                $arr = explode('.', $field);//分解
                return D($arr[0])->getAttr($this->mTid, $arr[1]);
            case 'dailyAttr'://玩家属性
                $field = get_config('field', $params);//获取属性
                $arr = explode('.', $field);//分解
                return D($arr[0])->getCount($this->mTid, $arr[1]);
            case 'gold'://战队当前金钱
                return D('GTeam')->getAttr($this->mTid, 'gold');
            case 'diamond'://战队当前水晶
                return D('GTeam')->getAttr($this->mTid, 'diamond');
            case 'honour'://竞技场荣誉值
                return D('GArena')->getAttr($this->mTid, 'honour');
            case 'level'://战队等级
                return D('GTeam')->getAttr($this->mTid, 'level');
            case 'vality'://战队等级提升
                return D('GTeam')->getAttr($this->mTid, 'vality');
            case 'skillPoint'://战队技能点
                return D('GTeam')->getAttr($this->mTid, 'skill_point');
            case 'material_score'://战队当前献祭积分
                return D('GTeam')->getAttr($this->mTid, 'material_score');
            case 'contribution'://战队公会贡献度
                return D('GLeagueTeam')->getAttr($this->mTid, 'contribution');
            case 'partnerId'://指定伙伴(更高品质亦可)
                //获取指定伙伴配置
                $partnerConfig = D('Static')->access('partner', $params);
                //查询玩家是否有指定伙伴所在组的伙伴
                $where['tid'] = $this->mTid;
                $where['group'] = $partnerConfig['group'];
                $where['level'] = array('gt', 0);
                $partner = M('GPartner')->where($where)->getField('index');//获取玩家拥有的该组伙伴ID
                //没有改伙伴
                if (empty($partner))
                    return 0;
                //伙伴品质不够
                if ($partner < $params)
                    return 0;
                //满足条件
                return 1;
            case 'partnerQuality':
                $where['tid'] = $this->mTid;
                $where['group'] = $params;
                $index = M('GPartner')->where($where)->getField('index');//获取玩家拥有的该组伙伴等级
                $index = D('Static')->access('partner', $index, 'base_quality');
                $index = $index ? $index : 0;
                return $index;
            case 'partnerQualityCount'://指定品质伙伴总数(更高品质亦可)
                $partnerIds = D('GPartner')->getPartnerIds($this->mTid);//获取玩家所有拥有的伙伴ID
                if ($params == '0') {
                    return count($partnerIds);
                }
                $count = 0;
                foreach ($partnerIds as $value) {
                    $quality = D('Static')->access('partner', $value, 'base_quality');
                    if ($quality >= $params)
                        ++$count;
                }
                return $count;
            case 'partnerClassCount'://指定级别伙伴总数
                $partnerIds = D('GPartner')->getPartnerIds($this->mTid);//获取玩家所有拥有的伙伴ID
                if ($params == '0') {
                    return count($partnerIds);
                }
                $count = 0;
                foreach ($partnerIds as $value) {
                    $class = D('Static')->access('partner', $value, 'partner_class');
                    if ($class == $params)
                        ++$count;
                }
                return $count;
            case 'item'://道具
                return D('GItem')->getCount($this->mTid, $params);//查询玩家当前拥有道具数量
            case 'equip'://装备
                return D('GEquip')->getCount($this->mTid, $params);//查询玩家当前拥有道具数量
            case 'favour'://伙伴好感
                $where['tid'] = $this->mTid;
                if ($params != 0) {
                    $where['group'] = $params;
                    $favour = M('GPartner')->where($where)->getField('favour');//查询某个伙伴当前好感度
                } else {
                    $favour = M('GPartner')->where($where)->max('favour');//查询查询玩家伙伴最高好感度
                }
                return $favour;
            case 'partnerGroup'://获得伙伴组
                $where['tid'] = $this->mTid;
                $where['group'] = $params;
                $where['level'] = array('gt', 0);
                $partner = D('GPartner')->getRowCondition($where);//获取玩家拥有的该组伙伴ID
                //没有改伙伴
                if (empty($partner))
                    return 0;
                //满足条件
                return 1;
            case 'partnerLevel'://获得伙伴等级
                $where['tid'] = $this->mTid;
                $where['group'] = $params;
                $level = M('GPartner')->where($where)->getField('level');//获取玩家拥有的该组伙伴等级
                $level = $level ? $level : 0;
                return $level;
            case 'partnerLevelCount'://获得伙伴等级
                $where['tid'] = $this->mTid;
                $where['level'] = array('egt', $params);
                return M('GPartner')->where($where)->count();
            case 'soul'://获得伙伴神力数量
                return D('GPartner')->getAttr($this->mTid, $params, 'soul');
            case 'vip'://玩家VIP等级
                return D('GVip')->getLevel($this->mTid);
            case 'instance'://副本完成次数
                return D('GInstance')->getCount($this->mTid, $params);
            case 'instanceGroup'://副本组完成次数
                return D('GInstance')->getGroupCount($this->mTid, $params);
            case 'quest'://副本完成次数
                return D('GQuest')->isFinish($this->mTid, $params);
            case 'emblem'://当前纹章
                return D('GEmblem')->getCount($this->mTid, $params);
            case 'fund':
                return D('GLeague')->getAttr($params, 'fund');
        }
    }

    //获得物品，增加属性，奖励等
    protected function produce($type, $params1, $params2 = null, $before = null)
    {
        switch ($type) {
            case 'attr'://属性
                $field = get_config('field', $params1);//获取属性
                $arr = explode('.', $field);//分解
                if ($arr[1] == 'diamond') {//免费水晶
                    $arr[1] = 'diamond_free';
                }
                if ($arr[0] == 'GCount') {
                    return D($arr[0])->incAttr($this->mTid, $arr[1], $params2);
                } else {
                    return D($arr[0])->incAttr($this->mTid, $arr[1], $params2, $before);
                }
            case 'dailyAttr'://每日属性
                $field = get_config('field', $params1);//获取属性
                $arr = explode('.', $field);//分解
                return D($arr[0])->record($this->mTid, $arr[1], $params2);
            case 'teamExp'://战队经验
                return D('GTeam')->incExp($this->mTid, $params1);
            case 'gold'://金币
                return D('GTeam')->incAttr($this->mTid, 'gold', $params1, $before);
            case 'diamond'://免费水晶
            case 'diamondFree'://免费水晶
                return D('GTeam')->incAttr($this->mTid, 'diamond_free', $params1, $before);
            case 'diamondPay'://付费水晶
                return D('GTeam')->incAttr($this->mTid, 'diamond_pay', $params1, $before);
            case 'vality'://体力
                return D('GTeam')->incAttr($this->mTid, 'vality', $params1, $before);
//            case 'energy'://气力
//                return D('GTeam')->incAttr($this->mTid,'energy',$params1,$before);
            case 'material_score'://献祭积分
                return D('GTeam')->incAttr($this->mTid, 'material_score', $params1, $before);
            case 'contribution'://贡献度
                return D('GLeagueTeam')->incAttr($this->mTid, 'contribution', $params1, $before, $params2);
            case 'item'://道具
                return D('GItem')->inc($this->mTid, $params1, $params2);
            case 'partner'://伙伴
                for ($i = 1; $i <= $params2; ++$i) {
                    if (!D('GPartner')->cPartner($this->mTid, $params1)) {
                        return false;
                    }
                }
                return true;
            case 'partnerExp'://伙伴经验
                return D('GPartner')->incExp($this->mTid, $params1, $params2);
            case 'favour'://伙伴好感度
                //判断有没有该伙伴
                if (!D('GPartner')->group2partner($this->mTid, $params1)) {
                    return true;
                }
                //有的话则加好感度
                return D('GPartner')->incAttr($this->mTid, $params1, 'favour', $params2, $before);
            case 'soul'://增加神力
                return D('GPartner')->addSoul($this->mTid, $params1, $params2);
            case 'box'://开宝箱
                return D('GItem')->openBox($this->mTid, $params1, $params2);
            case 'skillPoint':
                return D('GTeam')->incAttr($this->mTid, 'skill_point', $params1, $before);
            case 'emblem':
                return D('GEmblem')->cData($this->mTid, $params1, $params2);
            case 'fund':
                return D('GLeague')->incAttr($params2, 'fund', $params1, $before);
        }
    }

    //失去物品，减少属性等
    protected function recover($type, $params1, $params2 = null, $before = null)
    {
        if ($params1 == '0' || $params2 == '0') {
            return true;
        }
        switch ($type) {
            case 'attr'://扣金币
                $field = get_config('field', $params1);//获取属性
                $arr = explode('.', $field);//分解
                return D($arr[0])->decAttr($this->mTid, $arr[1], $params2, $before);
            case 'gold'://扣金币
                return D('GTeam')->decAttr($this->mTid, 'gold', $params1, $before);
            case 'vality'://扣体力
                return D('GTeam')->decAttr($this->mTid, 'vality', $params1, $before);
            case 'diamond'://扣水晶
                return D('GTeam')->decAttr($this->mTid, 'diamond', $params1, $before);
            case 'skillPoint':
                return D('GTeam')->decAttr($this->mTid, 'skill_point', $params1, $before);
            case 'material_score'://献祭积分
                return D('GTeam')->decAttr($this->mTid, 'material_score', $params1, $before);
            case 'honour'://扣水晶
                return D('GArena')->decAttr($this->mTid, 'honour', $params1, $before);
            case 'item'://道具
                return D('GItem')->dec($this->mTid, $params1, $params2);
            case 'equip'://装备(暂无)
                return true;
            case 'soul'://扣除魂石
                return D('GPartner')->decAttr($this->mTid, $params1, 'soul', $params2, $before);
            case 'contribution'://公会贡献值
                return D('GLeagueTeam')->decAttr($this->mTid, 'contribution', $params1, $before, $params2);
            case 'emblem'://纹章
                return D('GEmblem')->dec($this->mTid, $params1, $params2);
            case 'fund':
                return D('GLeague')->decAttr($params2, 'fund', $params1, $before);
        }
    }

    //发放奖励
    protected function bonus($config, $prefix = '')
    {
        //单一奖励情况
        if (isset($config[$prefix . 'bonus_type'])) {
            if ($config[$prefix . 'bonus_type'] > 0) {
                if (!$this->produce($this->mBonusType[$config[$prefix . 'bonus_type']], $config[$prefix . 'bonus_value_1'], $config[$prefix . 'bonus_value_2'])) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        } else {

            //多奖励情况
            $flag = true;//是否全部成功

            //发放奖励
            for ($i = 1; $i <= 10; ++$i) {

                //如果没有则结束
                if (!isset($config[$prefix . 'bonus_' . $i . '_type'])) {
                    break;
                }

                //发奖励
                if ($config[$prefix . 'bonus_' . $i . '_type'] > 0 && isset($this->mBonusType[$config[$prefix . 'bonus_' . $i . '_type']])) {//查看奖励是否配置
                    if (!$this->produce($this->mBonusType[$config[$prefix . 'bonus_' . $i . '_type']], $config[$prefix . 'bonus_' . $i . '_value_1'], $config[$prefix . 'bonus_' . $i . '_value_2'])) {
                        $flag = false;
                        break;
                    }
                }

            }

            return $flag;

        }

    }

    //计算exchange金额
    protected function exchangeMoney($exchange, $count)
    {
        //查询购买需要的货币
        $buyConfig = D('Static')->access('exchange', $exchange);
        if (empty($buyConfig)) {
            return false;
        }
        if (isset($buyConfig[$count])) {
            $data['needType'] = $buyConfig[$count]['consume_currency'];
            $data['needValue'] = $buyConfig[$count]['consume_value'];
        } else {
            $config = end($buyConfig);
            $data['needType'] = $config['consume_currency'];
            $data['needValue'] = $config['consume_value'];
        }
        return $data;
    }

    //公会权限判定
    protected function leaguePermission($tid, $position = 1)
    {
        //如果是0直接报错
        if ($position == 0) {
            C('G_ERROR', 'league_permission_low');
            return false;
        }

        //检查本人是否是会长并获取公会id
        $row = D('GLeagueTeam')->getRow($tid, array('league_id', 'position'));
        if (empty($row)) {
            C('G_ERROR', 'league_not_attended');
            return false;
        }

        if ($row['position'] == 0 || $row['position'] > $position) {
            C('G_ERROR', 'league_permission_low');
            return false;
        }

        return $row['league_id'];
    }

    //交货
    protected function delivery($tid, $cash)
    {

        //查询充值配置
        $cashConfig = D('Static')->access('cash', $cash);

        //查看是否是第一次充值
        $payFirst = D('GVip')->getAttr($tid, 'first_pay');

        //赠送首冲奖励
        if ($payFirst == '0') {
            $updateWhere['tid'] = $tid;
            $updateWhere['first_pay'] = 0;
            $update['first_pay'] = $cash;
            $update['first_pay_level'] = D('GTeam')->getAttr($tid, 'level');
            $update['first_pay_time'] = time();
            D('GVip')->UpdateData($update, $updateWhere);//首冲商品
            D('GMail')->send(7003, $tid, $tid);
        }

        //查看用户商品购买情况
        $isFirst = false;
        $payInfo = D('GPay')->getRow($tid, $cash);
        if (empty($payInfo) || $payInfo['count'] == '0') {
            $isFirst = true;
        } else {
            //获取首冲重置情况
            $payResetTime = D('GParams')->getValue('FIRST_PAY_RESET_TIME');
            if ($payResetTime != '0') {
                $payResetTime = strtotime($payResetTime);
                if ($payInfo['utime'] < $payResetTime) {
                    $isFirst = true;
                }
            }
        }

        //增加商品
        if (!$isFirst) {
            $payValue = $cashConfig['value'];
            $freeValue = $cashConfig['normal_bonus'];
        } else {
            $payValue = $cashConfig['value'];
            $freeValue = $cashConfig['first_bonus'] + $cashConfig['normal_bonus'];
        }

        //加属性
        switch ($cashConfig['type']) {
            case '1'://水晶
                if (!D('GTeam')->incAttr($tid, 'diamond_pay', $payValue)) {
                    return false;
                }
                if (!D('GTeam')->incAttr($tid, 'diamond_free', $freeValue)) {
                    return false;
                }
                //发送邮件
                $params['bonus_diamond_count'] = $payValue + $freeValue;
                D('GMail')->send(7001, $tid, $tid, $params);
                break;
            case '2'://会员卡
                if (!D('GMember')->pay($tid, $cashConfig['member_id'], $payValue + $freeValue)) {
                    return false;
                }
                //发送邮件
                $params['member_name'] = D('Static')->access('member', $cashConfig['member_id'], 'name');
                D('GMail')->send(7002, $tid, $tid, $params);
                break;
        }

        //加VIP
        if (!D('GVip')->pay($tid, $cashConfig['price'], $cashConfig['is_valid'])) {
            return false;
        }

        //记录充值
        if (false === D('GPay')->complete($tid, $cash)) {
            return false;
        }

        //返回
        return true;

    }

    //异常订单处理
    protected function abnormalDelivery($tid, $diamondPay, $isCount)
    {
        if (!D('GTeam')->incAttr($tid, 'diamond_pay', $diamondPay)) {
            return false;
        }

        //添加VIP积分
        if (!D('GVip')->pay($tid, $diamondPay / C('MONEY_RATE') * 100, 1, $isCount)) {
            return false;
        }

        return true;
    }

}