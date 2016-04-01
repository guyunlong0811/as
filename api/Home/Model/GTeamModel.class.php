<?php
namespace Home\Model;

use Think\Model;

class GTeamModel extends BaseModel
{

    protected $_validate = array(//自动验证
        array('uid', 'require', 'user_not_exist'),//新增时必须要有uid
        array('nickname', '', 'nickname_existed', 0, 'unique', 3), //在新增的时候验证name字段是否唯一
    );

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
        array('vality_utime', 'time', 1, 'function'), //新增的时候把vality_utime字段设置为当前时间
        array('energy_utime', 'time', 1, 'function'), //新增的时候把energy_utime字段设置为当前时间
        array('diamond_pay', 0),
        array('diamond_free', 0),
        array('material_score', 0),
        array('star', 0),
        array('fund', 0),
        array('last_login_time', 0),
        array('guide_skip', 0),
    );

    const WORLD_CHAT_COUNT = 1;
    const BUY_GOLD_COUNT = 2;
    const BUY_VALITY_COUNT = 3;
    const BUY_SKILL_POINT_COUNT = 10;

    //获取帐号角色数量
    public function getTeamIds($uid, $channelId)
    {
        $where['uid'] = $uid;
        $where['channel_id'] = $channelId;
        $list = $this->where($where)->getField('tid', true);
        return $list;
    }

    //创建角色
    public function cData($uid, $nickname, $channelId, $teamConfig, $roleId = null)
    {
        $team['uid'] = $uid;
        $team['role_id'] = $roleId;
        $team['nickname'] = $nickname;
        $team['channel_id'] = $channelId;
        $team['last_login_time'] = time();
        $team['level'] = $teamConfig['init_level'];
        $team['exp'] = $teamConfig['init_exp'];
        $team['gold'] = $teamConfig['init_gold'];
        $team['vality'] = $teamConfig['init_vality'];
        $team['energy'] = $teamConfig['init_energy'];
        $team['icon'] = $teamConfig['init_icon'];
        $team['skill_point'] = $teamConfig['init_skill_point'];
        return $this->CreateData($team);
    }

    //生成用户登陆密钥
    public function createLoginToken($uid, $token, $silence, $tid, $sid, $team = array())
    {
        //获取当前服务器上的UID的token
        $tokenOld = D('Predis')->cli('game')->get('u:' . $uid);

        //删除老token
        if (!empty($tokenOld)) {
            D('Predis')->cli('game')->del('t:' . $tokenOld);
        }

        //写入redis-u
        D('Predis')->cli('game')->setex('u:' . $uid, get_config('REDIS_TOKEN_TIME'), $token);

        //写入redis-t
        D('Predis')->cli('game')->setex('t:' . $token, get_config('REDIS_TOKEN_TIME'), $tid);

        //写入redis-s
        $arr['uid'] = $uid;
        $arr['silence'] = $silence;
        $arr['tid'] = $tid;
        $arr['sid'] = $sid;
        $arr['league_id'] = $team['league_id'] > 0 ? $team['league_id'] : 0;
        $arr['channel_id'] = $team['channel_id'] > 0 ? $team['channel_id'] : $_POST['channel_id'];
        $arr['channel_uid'] = $_POST['channel_uid'];
        if($tid == 0){
            $tid = $token;
        }
        D('Predis')->cli('game')->hmset('s:' . $tid, $arr);
        D('Predis')->cli('game')->expire('s:' . $tid, get_config('REDIS_TOKEN_TIME'));
        return true;
    }

    //获取角色所有属性
    public function getRow($tid, $field = null)
    {
        $where['tid'] = $tid;
        return $this->getRowCondition($where, $field);
    }

    //获取角色所有属性
    public function isExist($tid)
    {
        $where['tid'] = $tid;
        $count = $this->where($where)->count();
        if ($count > 0)
            return true;
        return false;
    }

    //获取角色基本属性
    public function getAttr($tid, $attr)
    {
        $where['tid'] = $tid;
        if ($attr == 'diamond') {
            $attr = "`diamond_free`+`diamond_pay`";
        }
        return $this->where($where)->getField($attr);
    }

    //获取昵称
    public function tid2nick($tid)
    {
        $where['tid'] = $tid;
        return $nick = $this->where($where)->getField('nickname');
    }

    //查询用户是否已经创建角色
    public function isUidExist($uid, $channelId)
    {
        $where['uid'] = $uid;
        $where['channel_id'] = $channelId;
        $count = $this->where($where)->count();
        if ($count == '0'){
            return false;
        }
        return true;
    }

    //获得战队经验(返回实际增加经验)
    public function incExp($tid, $exp)
    {

        //获取战队当前经验等级
        $field = array('tid', 'level', 'exp', 'vality',);
        $team = $this->getRow($tid, $field);
        $maxLevel = D('GParams')->getValue('TEAM_MAX_LEVEL');

        //加经验
        $return = $this->expLogic($tid, $maxLevel, $team['level'], $team['exp'], $exp, 0, 0, $maxLevel);

        //如果有升级
        if ($return['level'] > 0) {
            //加体力
            $addVality = 0;
            for ($i = 0; $i < $return['level']; ++$i) {
                $level = $team['level'] + $i;
                $addVality += D('Static')->access('team_level', $level, 'bonus_vality');
            }
            if (false === $this->incAttr($tid, 'vality', $addVality, $team['vality'])) {
                return false;
            }
            //查看有没有需要开放的功能
            D('SOpenProcess')->checkNewOpen($tid, 1, $return['level']);
        }

        //返回
        return $return;

    }

    /* 加经验逻辑(
     * 战队ID，当前战队等级，初始等级，初始经验，需要增加的经验，
     * 当前已增加等级，当前已增加经验，最大等级
     * )
     */
    private function expLogic($tid, $maxLevel, $levelBefore, $expBefore, $exp, $levelAdded, $expAdded, $maxLevel)
    {
        //如果到了最大等级则不操作
        if ($levelBefore + $levelAdded >= $maxLevel) {
            $rs['level'] = $levelAdded;
            $rs['exp'] = $expAdded;
            return $rs;
        }

        //获取升级所需经验
        $levelNow = $levelBefore + $levelAdded;
        $needExp = D('Static')->access('team_level', $levelNow, 'exp');
        $where['tid'] = $tid;

        //查询当前升级还需要的经验
        if ($levelAdded == 0) {
            $levelupNeedExp = $needExp - $expBefore;
        } else {
            $levelupNeedExp = $needExp;
        }

        //加经验
        if ($levelupNeedExp > ($exp - $expAdded)) {//经验不够升级
            //增加经验
            if (!$this->IncreaseData($where, 'exp', $exp - $expAdded)) {
                return false;
            }
            //记录日志
            if ($levelAdded > 0) {
                D('LTeam')->cLog($tid, 'level', $levelAdded, $levelBefore);
            }
            D('LTeam')->cLog($tid, 'exp', $exp, $expBefore);
            //返回升级的等级经验
            $rs['level'] = $levelAdded;
            $rs['exp'] = $exp;
            return $rs;
        } else {//需要升级
            if ($maxLevel <= ($levelBefore + $levelAdded)) {//如果等级已经等于战队等级则不能升级
                //增加经验
                if (!$this->IncreaseData($where, 'exp', $levelupNeedExp - 1)) {
                    return false;
                }
                //记录日志
                $expAdded = $expAdded + $levelupNeedExp - 1;
                if ($levelAdded > 0) {
                    D('LTeam')->cLog($tid, 'level', $levelAdded, $levelBefore);
                }
                D('LTeam')->cLog($tid, 'exp', $exp, $expBefore);
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
                return $this->expLogic($tid, $maxLevel, $levelBefore, $expBefore, $exp, $levelAdded, $expAdded, $maxLevel);//递归
            }
        }

    }

    //增加属性
    public function incAttr($tid, $attr, $value, $before = null)
    {
        if ($value <= 0) {
            return true;
        }

        //加经验
        if ($attr == 'exp') {
            return $this->incExp($tid, $value);
        }

        //没有改变前参数
        if ($before === null) {
            $before = $this->getAttr($tid, $attr);
        }

        //体力上限保护
        if ($attr == 'vality') {
            $max = D('Static')->access('params', 'VALITY_PROTECT');
            //如果超过上限
            if ($value + $before > $max) {
                $value = $max - $before;
                if ($value <= 0) {
                    return true;
                }
            }
        } else if ($attr == 'skill_point') {
            //获取VIP对应配置
            $vipIndex = D('GVip')->getAttr($tid, 'index');
            $skillPointMax = D('Static')->access('vip', $vipIndex, 'skill_point_max');
            $lack = $skillPointMax - $before;
            $value = $lack >= $value ? $value : $lack;
            if ($value <= 0) {
                return true;
            }
        } else if ($attr == 'gold') {
            D('GCount')->incAttr($tid, 'gold_total', $value);
        } else if ($attr == 'diamond_free') {
            D('GCount')->incAttr($tid, 'diamond_total', $value);
        } else if ($attr == 'diamond_pay') {
            D('GCount')->incAttr($tid, 'diamond_total', $value);
            //付费活动逻辑
            if (false === $this->activityPayConsume($tid, 1, $value)) {
                return false;
            }
        }

        //加属性
        $where['tid'] = $tid;
        if (!$this->IncreaseData($where, $attr, $value)) {
            return false;
        }

        //记录日志
        D('LTeam')->cLog($tid, $attr, $value, $before);//日志
        return true;
    }

    //减少属性
    public function decAttr($tid, $attr, $value, $before = null)
    {

        $where['tid'] = $tid;

        //不扣
        if ($value <= 0) {
            return true;
        }

        //体力&气力扣除特殊处理
        switch ($attr) {
            case 'vality':
            case 'energy':
                //使用体力增加公会活跃度
                if ($value > 0 && false === D('GLeague')->incAttrActivity($tid, $value)) {
                    return false;
                }
                //修改更新时间
                $row = $this->getRow($tid, array($attr, 'level'));
                $max = D('Static')->access('team_level', $row['level'], 'max_' . $attr);
                if ($row[$attr] >= $max && $max > $row[$attr] - $value) {
                    $update[$attr . '_utime'] = time();
                    if (false === $this->UpdateData($update, $where)) {
                        return false;
                    }
                }
                break;
            case 'diamond':
                //付费活动逻辑
                if (false === $this->activityPayConsume($tid, 2, $value)) {
                    return false;
                }
                //扣除水晶
                $row = $this->getRow($tid, array('diamond_pay', 'diamond_free'));
                if ($row['diamond_free'] >= $value) {//免费水晶足够
                    $attr = 'diamond_free';
                    $before = $row['diamond_free'];
                } else if ($row['diamond_free'] > 0 && $row['diamond_free'] < $value) {//有免费水晶，但是不够
                    if (false === $this->decAttr($tid, 'diamond_free', $row['diamond_free'], $row['diamond_free'])) {//扣除免费水晶
                        return false;
                    }
                    if (false === $this->decAttr($tid, 'diamond_pay', $value - $row['diamond_free'], $row['diamond_pay'])) {//扣除付费水晶
                        return false;
                    }
                    return true;
                } else {//只有付费水晶
                    $attr = 'diamond_pay';
                    $before = $row['diamond_pay'];
                }
                break;
        }


        if ($before === null) {
            $before = $this->getAttr($tid, $attr);
        }

        if (!$this->DecreaseData($where, $attr, $value)) {
            return false;
        }
        //记录日志
        D('LTeam')->cLog($tid, $attr, -$value, $before);//日志
        return true;
    }

    //查询好友信息
    public function getFriendList($tidList)
    {
        $field = array('tid', 'nickname', 'level', 'icon',);
        $in = sql_in_condition($tidList);
        $where = "`tid` {$in}";
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        return $select;
    }

    //获取玩家战斗信息
    public function getFightInfo($tid)
    {
        //获取玩家公会ID
        $league_id = D('GLeagueTeam')->getAttr($tid, 'league_id');

        //获取公会神像等级
        if (!$league_id || $league_id == '0') {//没有公会则等级为0
            $attribute_level = 0;
        } else {
            $attribute_level = D('Predis')->cli('game')->hget('lg:' . $league_id, 'attr_lv');
            if (!$attribute_level) {
                $attribute_level = D('GLeague')->getCenterLevel($tid);//获取当前公会等级
                D('Predis')->cli('game')->hset('lg:' . $league_id, 'attr_lv', $attribute_level);
            }
        }

        $info['attribute_level'] = $attribute_level;
        return $info;
    }

    //获取用户所有登录所需信息
    public function getAllInfo($tid, $silence)
    {

        //查询战队信息
        $field = array('tid', 'role_id', 'nickname', 'icon', 'level', 'exp', '`diamond_pay`+`diamond_free`' => 'diamond', 'gold', 'star', 'material_score', 'vality', 'vality_utime', 'skill_point', 'skill_point_utime', 'fund', 'guide_skip',);
        $teamInfo = $this->getRow($tid, $field);

        //查询vip信息
        $field = array('index' => 'vip_id', 'score',);
        $vipInfo = D('GVip')->getRow($tid, $field);

        //查询公会情况
        $field = array('league_id', 'position', 'contribution',);
        $leagueInfo = D('GLeagueTeam')->getRow($tid, $field);
        if (empty($leagueInfo)) {
            $leagueInfo['league_id'] = 0;
            $leagueInfo['position'] = 0;
            $leagueInfo['contribution'] = 0;
            $leagueInfo['league_name'] = '';
            $leagueInfo['attribute_level'] = 0;
        } else {
            $leagueRow = D('GLeague')->getRow($leagueInfo['league_id'], array('name', 'attribute_level'));
            $leagueInfo['league_name'] = $leagueRow['name'];//公会名称
            $leagueInfo['attribute_level'] = $leagueRow['attribute_level'];//公会神像等级
        }

        //竞技场
        $field = array('honour',);
        $arenaInfo = D('GArena')->getRow($tid, $field);
        if (empty($arenaInfo)) {
            $arenaInfo['honour'] = 0;
        }

        //聊天信息
        if ($silence == 1) {
            $chat['silence'] = -1;
        } else {
            $chat['silence'] = D('TDailyCount')->getCount($tid, self::WORLD_CHAT_COUNT);
        }

        //购买信息
        $buy['buy_gold_count'] = D('TDailyCount')->getCount($tid, self::BUY_GOLD_COUNT);
        $buy['buy_vality_count'] = D('TDailyCount')->getCount($tid, self::BUY_VALITY_COUNT);
        $buy['buy_skill_point_count'] = D('TDailyCount')->getCount($tid, self::BUY_SKILL_POINT_COUNT);

        //返回
        $info = $teamInfo + $vipInfo + $leagueInfo + $arenaInfo + $chat + $buy;
        return $info;

        /*$field = array('`g_team`.`tid`','`g_team`.`nickname`','`g_team`.`icon`','`g_team`.`level`','`g_team`.`exp`','`g_team`.`diamond`','`g_team`.`gold`','`g_team`.`vality`','`g_team`.`vality_utime`','`g_vip`.`index` as `vip_id`','`g_vip`.`score`','`g_league_team`.`league_id`','`g_league_team`.`position`','`g_league_team`.`contribution`',);
        $where = "`g_team`.`tid` = '{$tid}'";
        $data = $this->field($field)->table('g_team')->join('LEFT JOIN `g_vip` ON `g_team`.`tid`=`g_vip`.`tid`')->join('LEFT JOIN `g_league_team` ON `g_team`.`tid`=`g_league_team`.`tid`')->where($where)->find();
        $data['vip_id'] = $data['vip_id'] ? $data['vip_id'] : 0;
        $data['score'] = $data['score'] ? $data['score'] : 0;
        $data['league_id'] = $data['league_id'] ? $data['league_id'] : 0;
        $data['position'] = $data['position'] ? $data['position'] : 0;
        $data['contribution'] = $data['contribution'] ? $data['contribution'] : 0;
        return $data;*/
    }

    //使用预创建帐号
    public function usePreCreate($tid, $uid, $nickname, $channelId)
    {
        $where['tid'] = $tid;
        $where['uid'] = array('eq', 0);
        $data['uid'] = $uid;
        $data['nickname'] = $nickname;
        $data['channel_id'] = $channelId;
        $data['ctime'] = time();
        return $this->UpdateData($data, $where);
    }

    //获取战队等级排名
    public function getRankList()
    {
        //战队等级最高的前50名
        $field = array('tid', 'nickname', 'icon', 'level', 'level' => 'data',);
        $where['ctime'] = array('gt', 0);
        $order = array('level' => 'desc', 'exp' => 'desc', 'tid' => 'asc');
        $list = $this->field($field)->where($where)->order($order)->limit(C('RANK_MAX'))->select();
        $list = $this->getLeagueName($list);
        return $list;
    }

    //计算实时排名
    public function getCurrentLevelRank($tid)
    {

        //如果没有传排名
        $row = $this->getRow($tid, array('level', 'exp'));
        $data['current'] = $row['level'];

        //查询最新排名
        $where = "`ctime` > 0 && (`level`>'{$row['level']}' || (`level`='{$row['level']}' && `exp`>'{$row['exp']}') || (`level`='{$row['level']}' && `exp`='{$row['exp']}' && `tid`<='{$tid}'))";
        $count = $this->where($where)->count();
        $data['rank'] = $count;

        //返回
        return $data;

    }

    //查询昵称是否已经存在
    public function isNicknameExist($nick)
    {
        $where['nickname'] = $nick;
        $count = $this->where($where)->count();
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    //检查运营消费活动情况
    public function activityPayConsume($tid, $type, $diamond)
    {
        //当前时间
        $now = time();

        //获取活动情况
        $config = D('StaticDyn')->access('event');
        if(empty($config)){
            return true;
        }

        //获取玩家活动完成
        $complete = D('LActivityComplete')->getList($tid);

        //获取玩家活动完成
        $completeToday = D('LActivityComplete')->getTodayList($tid);

        //获取当前服务器ID
        $serverId = C('G_SID');

        //获取当前渠道ID
        $channelId = $this->getAttr($tid, 'channel_id');

        //遍历活动
        $mailConfig = array();
        $dailyPayConsume = null;
        foreach ($config as $value) {

            //检查活动状态
            if ($value['status'] != '1') {
                continue;
            }

            //消费活动
            if ($value['type1'] != $type){
                continue;
            }

            //检查活动时间
            if ($value['starttime'] > $now || $now > $value['endtime']) {
                continue;
            }

            //检查服务器
            if ($value['server'] != 0 && $value['server'] != $serverId) {
                $serverList = explode('#', $value['server']);
                if (!in_array($serverId, $serverList)) {
                    continue;
                }
            }

            //检查渠道
            if ($value['channel'] != 0 && $value['channel'] != $channelId) {
                $channelList = explode('#', $value['channel']);
                if (!in_array($channelId, $channelList)) {
                    continue;
                }
            }

            $where = '1=1';
            switch ($value['type2']) {//充值活动

                //累计充值
                case '1':

                    //领取次数
                    if($complete[$value['index']] < $value['receive_max']) {

                        //获取活动时间内情况
                        $key = 'diamond' . $value['group'];

                        if (!isset($$key)) {
                            if($type == '1') {
                                $where = "`tid`='{$tid}' && `attr`='diamond_pay' && `value`>0 && `ctime` between '{$value['starttime']}' and '{$value['endtime']}'";
                            }else if($type == '2') {
                                $where = "`tid`='{$tid}' && (`attr`='diamond_pay' || `attr`='diamond_free') && `value`<0 && `ctime` between '{$value['starttime']}' and '{$value['endtime']}'";
                            }
                            $$key = D('LTeam')->where($where)->sum('value');
                            $$key = $$key ? abs($$key) : 0;
                        }

                        //计算当前总共可领取次数
                        $timesTotal = floor(($$key + $diamond) / $value['value']);
                        $timesTotal = $timesTotal > $value['receive_max'] ? $value['receive_max'] : $timesTotal;

                        //计算本次消费过后新领取的次数
                        $times = $timesTotal - $complete[$value['index']];
                        if ($times > 0) {
                            for ($i = 1; $i <= $times; ++$i) {
                                $mailConfig[] = $value;//发奖励
                            }
                        }

                    }

                    break;

                //每日消费
                case '2':

                    //领取次数
                    if($completeToday[$value['index']] < $value['receive_max']) {

                        //获取活动时间内消费情况
                        if (is_null($dailyPayConsume)) {
                            $starttime = get_daily_utime();
                            $endtime = $starttime + 86399;
                            if($type == '1') {
                                $where = "`tid`='{$tid}' && `attr`='diamond_pay' && `value`>0 && `ctime` between '{$starttime}' and '{$endtime}'";
                            }else if($type == '2') {
                                $where = "`tid`='{$tid}' && (`attr`='diamond_pay' || `attr`='diamond_free') && `value`<0 && `ctime` between '{$value['starttime']}' and '{$value['endtime']}'";
                            }
                            $dailyPayConsume = D('LTeam')->where($where)->sum('value');
                            $dailyPayConsume = $dailyPayConsume ? abs($dailyPayConsume) : 0;
                        }

                        //计算当前总共可领取次数
                        $timesTotal = floor(($dailyPayConsume + $diamond) / $value['value']);
                        $timesTotal = $timesTotal > $value['receive_max'] ? $value['receive_max'] : $timesTotal;

                        //计算本次消费过后新领取的次数
                        $times = $timesTotal - $completeToday[$value['index']];
                        if ($times > 0) {
                            for ($i = 1; $i <= $times; ++$i) {
                                $mailConfig[] = $value;//发奖励
                            }
                        }

                    }

                    break;
            }

        }

        //发奖励
        if (!empty($mailConfig) && false === $this->mailReward($tid, $mailConfig)) {
            return false;
        }

        //返回
        return true;
    }

    //邮件奖励
    protected function mailReward($tid, $configList)
    {
        //当前时间
        $now = time();

        //邮件
        $mailAll = array();

        //生成邮件
        $mail['tid'] = $tid;
        $mail['type'] = 2;
        $mail['from'] = 'GM';
        $mail['open_script'] = '';
        $mail['behave'] = get_config('behave', array('operation_activity', 'code',));
        $mail['ctime'] = $now;
        $mail['dtime'] = $now + (7 * 86400);
        $mail['status'] = 0;

        //遍历活动
        $activityList = array();
        foreach ($configList as $config) {

            //邮件标题
            $mail['title'] = $config['name'];

            //邮件描述
            $mail['des'] = $config['des'];

            //邮件附件
            $k = 1;
            for ($i = 1; $i <= 8; ++$i) {

                //发现奖励为空则跳出循环
                if ($config['bonus_' . $i . '_type'] == 0) {
                    break;
                }

                //如果k是第1个
                if ($k == 1) {
                    //生成附件基本信息
                    for ($j = 1; $j <= 4; ++$j) {
                        $mail['item_' . $j . '_type'] = 0;
                        $mail['item_' . $j . '_value_1'] = 0;
                        $mail['item_' . $j . '_value_2'] = 0;
                    }
                }

                //邮件奖励
                $mail['item_' . $k . '_type'] = $config['bonus_' . $i . '_type'];
                $mail['item_' . $k . '_value_1'] = $config['bonus_' . $i . '_value_1'];
                $mail['item_' . $k . '_value_2'] = $config['bonus_' . $i . '_value_2'];

                //如果满了4个奖品则重新计算则
                if ($k == 4) {
                    $mailAll[] = $mail;
                    $k = 1;
                } else {
                    ++$k;
                }

            }

            //如果有剩余的道具没发则发送
            if ($k > 1) {
                $mailAll[] = $mail;
            }

            //记录活动完成ID
            $activityList[] = $config['index'];

        }

        //发送邮件
        if (false === D('GMail')->CreateAllData($mailAll)) {
            return false;
        }

        //完成活动
        if (!empty($activityList)) {
            foreach ($activityList as $activity) {
                if (false === D('LActivityComplete')->cLog($tid, $activity)) {
                    return false;
                }
            }
        }

        return true;
    }

}