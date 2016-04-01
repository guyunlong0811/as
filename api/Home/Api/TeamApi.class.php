<?php
namespace Home\Api;

use Think\Controller;

class TeamApi extends BaseApi
{

    const EXCHANGE_GOLD_TYPE = 1;
    const EXCHANGE_VALITY_TYPE = 2;
    const EXCHANGE_SKILL_POINT_TYPE = 12;
    const WORLD_CHAT_COUNT = 1;
    const BUY_GOLD_COUNT = 2;
    const BUY_VALITY_COUNT = 3;
    const BUY_SKILL_POINT_COUNT = 10;

    //获取玩家在主城需要的全部信息
    public function getMainInfo($tid = null, $silence = null)
    {
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }
        if (!is_null($silence)) {
            $this->mSilence = $silence;
        }
        $this->autoAdd();
//        $data['novice'] = A('NoviceLogin','Api')->getInfo($this->mTid);
        $data['server_utime'] = get_daily_utime() + 86400;
        $data['team'] = D('GTeam')->getAllInfo($this->mTid, $this->mSilence);
        $data['partner'] = A('Partner', 'Api')->getList($this->mTid);
        $data['item'] = A('Item', 'Api')->getList($this->mTid);
        $data['quest'] = A('Quest', 'Api')->getList($this->mTid);
        $data['quest_daily'] = A('QuestDaily', 'Api')->getList($this->mTid);
        $data['quest_partner'] = A('QuestPartner', 'Api')->getList($this->mTid);
        $data['instance'] = A('Instance', 'Api')->getAllList($this->mTid);
        $data['max_level'] = D('GParams')->getValue('TEAM_MAX_LEVEL');
        $data['guide'] = A('Guide', 'Api')->getList($this->mTid);
        $data['emblem'] = A('Emblem', 'Api')->getList($this->mTid);
        $data['vip_bonus'] = A('Vip', 'Api')->getBonusList($this->mTid);
        $data['new_mail'] = A('Mail', 'Api')->getNewCount($this->mTid);
        $data['star'] = A('Star', 'Api')->getList($this->mTid);
        $data['online_bonus'] = A('OnlineBonus', 'Api')->getInfo($this->mTid);
        $data['daily_register'] = D('GDailyRegister')->isReceiveFree($this->mTid) ? 1 : 0;//查看今天是否已经领取过;
        $data['announce'] = A('Operate', 'Api')->notice();
        $data['pray'] = A('Pray', 'Api')->getUtime($this->mTid);
        $data['babel'] = D('GBabel')->getSweepInfo($this->mTid);
        $data['arena'] = D('GArena')->getAttr($this->mTid, 'rank_change');
        $data['activity'] = A('Activity', 'Api')->getInfo($this->mTid);
        $data['activity_pay_consume'] = A('Operate', 'Api')->getActivityCount();
        $data['pray_timed'] = A('PrayTimed', 'Api')->getInfo($this->mTid);
        $data['achievement'] = A('Achievement', 'Api')->getInfo($this->mTid);

        //活动图标顺序
        $json = D('GParams')->getValue('EVENT_ICON');
        $data['event_icon'] = json_decode($json, true);
        return $data;
    }

    //获取玩家当前属性
    public function getInfo()
    {
        $data['server_utime'] = get_daily_utime() + 86400;
        $data['activity_pay_consume'] = A('Operate', 'Api')->getActivityCount();

        //活动图标顺序
        $json = D('GParams')->getValue('EVENT_ICON');
        $data['event_icon'] = json_decode($json, true);

        $teamInfo = D('GTeam')->getAllInfo($this->mTid, $this->mSilence);
        $return = array_merge($data, $teamInfo);
        return $return;
    }

    //获取游戏币数
    public function getCurrencyInfo()
    {
        $field = array('diamond', 'gold',);
        $info = D('GTeam')->getRow($this->mTid, $field);
        return $info;
    }

    //修改战队头像
    public function setIcon()
    {
        $where['tid'] = $this->mTid;
        $data['icon'] = $_POST['icon'];
        if (false === D('GTeam')->UpdateData($data, $where)) {
            return false;
        }
        return true;
    }

    //购买金币
    public function buyGold()
    {

        //查询今天购买了几次
        $countNow = D('TDailyCount')->getCount($this->mTid, self::BUY_GOLD_COUNT);
        $countNew = $countNow + $_POST['count'] - 1;

        //检查是否达到最大次数
        if (!D('GVip')->checkCount($this->mTid, 'gold_times', $countNew)) {
            return false;
        }

        //获取购买信息
        $level = D('GTeam')->getAttr($this->mTid, 'level');//获取战队等级
        $luaRet = lua('gold', 'buy_gold', array((int)$level, (int)$countNow, (int)$_POST['count']));
        $addGold = $luaRet[1];
        $needDiamond = $luaRet[0];

        //检查玩家当前水晶是否足够
        if (!$diamondNow = $this->verify($needDiamond, 'diamond')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //加属性
        if (!$this->produce('gold', $addGold)) {
            goto end;
        }

        //扣钱
        if (!$this->recover('diamond', $needDiamond, null, $diamondNow)) {
            goto end;
        }

        //记录
        if (false === D('TDailyCount')->record($this->mTid, self::BUY_GOLD_COUNT, $_POST['count'])) {
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

    //购买体力
    public function buyVality()
    {

        //检查体力是否已经到达上限
        $valityNow = D('GTeam')->getAttr($this->mTid, 'vality');
        $max = D('Static')->access('params', 'VALITY_PROTECT');
        if ($valityNow >= $max) {
            C('G_ERROR', 'team_vality_max');
            return false;
        }

        //查询今天购买了几次
        $count = D('TDailyCount')->getCount($this->mTid, self::BUY_VALITY_COUNT);
        //检查是否达到最大次数
        if (!D('GVip')->checkCount($this->mTid, 'vality_times', $count)) {
            return false;
        }
        ++$count;

        //查询购买需要的货币
        $exchange = $this->exchangeMoney(self::EXCHANGE_VALITY_TYPE, $count);
        $needType = $exchange['needType'];
        $needValue = $exchange['needValue'];

        //检查玩家当前货币是否足够
        if (!$now = $this->verify($needValue, $this->mMoneyType[$needType])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //加属性
        $vality = D('Static')->access('params', 'BUY_VALITY_REW');
        if (!$this->produce('vality', $vality, null, $valityNow)) {
            goto end;
        }

        //扣钱
        if (!$this->recover($this->mMoneyType[$needType], $needValue, null, $now)) {
            goto end;
        }

        //记录
        if (false === D('TDailyCount')->record($this->mTid, self::BUY_VALITY_COUNT)) {
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

    //购买技能点
    public function buySkillPoint()
    {

        //检查体力是否已经到达上限
        $skillPointNow = D('GTeam')->getAttr($this->mTid, 'skill_point');
        if ($skillPointNow != 0) {
            C('G_ERROR', 'team_skill_point_not_used');
            return false;
        }

        //查询今天购买了几次
        $count = D('TDailyCount')->getCount($this->mTid, self::BUY_SKILL_POINT_COUNT);
        ++$count;

        //查询购买需要的货币
        $exchange = $this->exchangeMoney(self::EXCHANGE_SKILL_POINT_TYPE, $count);
        $needType = $exchange['needType'];
        $needValue = $exchange['needValue'];

        //检查玩家当前水晶是否足够
        if (!$now = $this->verify($needValue, $this->mMoneyType[$needType])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //加属性
        $skillPoint = D('Static')->access('params', 'BUY_SKILL_REW');
        if (!$this->produce('skillPoint', $skillPoint, null, $skillPointNow)) {
            goto end;
        }

        //扣钱
        if (!$this->recover($this->mMoneyType[$needType], $needValue, null, $now)) {
            goto end;
        }

        //记录
        if (false === D('TDailyCount')->record($this->mTid, self::BUY_SKILL_POINT_COUNT)) {
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

    //心跳协议
    public function update()
    {
        //修改session保存时间
        D('Predis')->cli('game')->expire('u:' . $this->mUid, get_config('REDIS_TOKEN_TIME'));
        D('Predis')->cli('game')->expire('t:' . $this->mToken, get_config('REDIS_TOKEN_TIME'));
        D('Predis')->cli('game')->expire($this->mSessionKey, get_config('REDIS_TOKEN_TIME'));

        //自动增加体力&技能点
        $return = $this->autoAdd();

        //获取当前世界ID
        $keys = D('Predis')->cli('social')->keys('w:*');
        $return['world']['id'] = D('Predis')->cli('social')->get('w:id');
        $return['world']['count'] = count($keys) - 1;

        //获取当前公会ID
        $return['league']['id'] = $this->mSessionInfo['league_id'] == 0 ? 0 : D('Predis')->cli('social')->get('l:' . $this->mSessionInfo['league_id'] . ':id');
        $keys = D('Predis')->cli('social')->keys('l:' . $this->mSessionInfo['league_id'] . ':*');
        $return['league']['count'] = count($keys) - 1;

        //查询消息
        $return['list'] = D('GChat')->getNoticeMsg($_POST['notice_id']);
        return $return;
    }

    //自动加体力和气力
    public function autoAdd()
    {

        //延长凭证时间
        C('G_BEHAVE', 'auto_add');

        //获取当前时间
        $now = time();

        //获取战队信息
        $team = D('GTeam')->getRow($this->mTid);

        //获取当前等级对应配置
        $teamLevelConfig = D('Static')->access('team_level', $team['level']);

        //获取当前等级&VIP对应配置
        $vipIndex = D('GVip')->getAttr($this->mTid, 'index');
        $skillPointMax = D('Static')->access('vip', $vipIndex, 'skill_point_max');

        /*********************************** 体  力 ***********************************/

        //获取体力自动增加时间
        $addValityTime = D('Static')->access('params', 'VALITY_RECOVERY_PERIOD');

        //判断是否达到最大体力
        if ($team['vality'] < $teamLevelConfig['max_vality']) {

            //如果体力小于当前值
            $pastVality = $now - $team['vality_utime'];//距离上次自动加体力时间
            if ($pastVality >= $addValityTime) {//检查时间是否满足

                //当前缺的体力值
                $lack = $teamLevelConfig['max_vality'] - $team['vality'];

                //计算需要加的体力值
                $vality = floor($pastVality / $addValityTime);

                //检查是否大于最大值
                if ($vality < $lack) {
                    if (!D('GTeam')->incAttr($this->mTid, 'vality', $vality)) {
                        return false;
                    }//加体力
                    $update['vality_utime'] = $team['vality_utime'] + ($vality * $addValityTime);//自动加体力时间
                } else {
                    if (!D('GTeam')->incAttr($this->mTid, 'vality', $lack)) {
                        return false;
                    }//加体力

                }

                if (!isset($update['vality_utime'])) {
                    $update['vality_utime'] = $now;//自动加体力时间
                }

            }

        } else {
            $update['vality_utime'] = $now;//自动加体力时间
        }

        /*********************************** 体  力 ***********************************/

        /*********************************** 技能点 ***********************************/

        //获取技能点自动增加时间
        $addSkillPointTime = D('Static')->access('params', 'SKILL_RECOVERY_PERIOD');

        //判断是否达到最大体力
        if ($team['skill_point'] < $skillPointMax) {

            //如果体力小于当前值
            $pastSkillPoint = $now - $team['skill_point_utime'];//距离上次自动加体力时间
            if ($pastSkillPoint >= $addSkillPointTime) {//检查时间是否满足

                //当前缺的体力值
                $lack = $skillPointMax - $team['skill_point'];

                //计算需要加的体力值
                $skillPoint = floor($pastSkillPoint / $addSkillPointTime);

                //检查是否大于最大值
                if ($skillPoint < $lack) {
                    if (!D('GTeam')->incAttr($this->mTid, 'skill_point', $skillPoint)) {
                        return false;
                    }//加体力
                    $update['skill_point_utime'] = $team['skill_point_utime'] + ($skillPoint * $addSkillPointTime);//自动加体力时间
                } else {
                    if (!D('GTeam')->incAttr($this->mTid, 'skill_point', $lack)) {
                        return false;
                    }//加体力

                }

                if (!isset($update['skill_point_utime'])) {
                    $update['skill_point_utime'] = $now;//自动加体力时间
                }

            }

        } else {
            $update['skill_point_utime'] = $now;//自动加体力时间
        }

        /*********************************** 技能点 ***********************************/

        //修改
        if (!empty($update)) {
            $where['tid'] = $this->mTid;
            if (false === D('GTeam')->UpdateData($update, $where)) {
                return false;
            }
        }

        //返回
        return D('GTeam')->getRow($this->mTid, array('`diamond_pay`+`diamond_free`' => 'diamond', 'gold', 'vality', 'vality_utime', 'skill_point', 'skill_point_utime'));

    }

    //分享
    public function share()
    {

        //开始事务
        $this->transBegin();

        //记录分享日志
        if (false === D('LShare')->cLog($this->mTid)) {
            goto end;
        }

        //记录分享总次数
        if (false === D('GCount')->share($this->mTid)) {
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

    //小游戏奖励
    public function mini()
    {

        //获取当前版本
        $verNew = D('StaticDyn')->access('params', 'MINI_GAME_COUNT');

        //获取用户版本
        $verNow = D('GCount')->getAttr($this->mTid, 'mini_game');

        //检查是否要送奖励
        if ($verNow >= $verNew) {
            return true;
        }

        //计算要送多少东西
        $count = floor($_POST['kill'] / 100);
        $count = $count >= 10 ? 10 : $count;

        //开始事务
        $this->transBegin();

        //记录小游戏完成版本号
        if (false === D('GCount')->mini($this->mTid, $verNew)) {
            goto end;
        }

        //发送邮件
        $mailID = 100 + $count;
        if (false === D('GMail')->send($mailID, 0, $this->mTid)) {
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