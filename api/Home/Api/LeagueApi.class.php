<?php
namespace Home\Api;

use Think\Controller;

class LeagueApi extends BaseApi
{

    const LEAGUE_FIGHT_GROUP = 15;//公会战活动组ID

    //创建公会
    public function setup()
    {

        //查看是否已经加入公会
        if ($this->mSessionInfo['league_id'] > 0) {
            C('G_ERROR', 'league_already_attended');
            return false;
        }

        $league['name'] = $_POST['league_name'];
        $league['president_tid'] = $this->mTid;
        $league['activity'] = D('Static')->access('params', 'INIT_GUILD_ACTIVITY');
        $league['point'] = D('Static')->access('params', 'LEAGUE_DEFAULT_POINTS');

        //$isExitingMember = M('GLeagueTeam')->where('tid = '.$this->mTid)->find();
        //判断是否已经加入公会
        $where['tid'] = $this->mTid;
        $isExitingMember = D('GLeagueTeam')->getRowCondition($where);
        if (!empty($isExitingMember)) {
            C('G_ERROR', 'league_already_attended');
            return false;
        }

        //查询公会名是否已存在
        $where_league_name['name'] = $league['name'];
        $league_row = M('GLeague')->where($where_league_name)->getField('id');
        if (!empty($league_row)) {
            C('G_ERROR', 'league_name_existed');
            return false;
        }

        //判断等级
        $min_Create_Level = D('Static')->access('params', 'LEAGUE_CREATE_LEVEL');
        if (!$levelNow = $this->verify($min_Create_Level, 'level')) {
            return false;
        }

        //判断水晶
        $require_diamond = D('Static')->access('params', 'LEAGUE_CREATE_DIAMOND');
        if (!$diamondNow = $this->verify($require_diamond, 'diamond')) {
            return false;
        }

        //判断金钱
        $require_gold = D('Static')->access('params', 'LEAGUE_CREATE_GOLD');
        if (!$goldNow = $this->verify($require_gold, 'gold')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //创建公会
        if (!$leagueId = D('GLeague')->CreateData($league)) {
            goto end;
        }

        //把自己加入公会
        $league_team['league_id'] = $leagueId;
        $league_team['tid'] = $this->mTid;
        $league_team['position'] = 1;
        if (!D('GLeagueTeam')->CreateData($league_team)) {
            goto end;
        }

        //在每日公会列表中加入自己
        $add['league_id'] = $leagueId;
        $add['league_name'] = $_POST['league_name'];
        $add['president_tid'] = $this->mTid;
        $add['president_nickname'] = D('GTeam')->getAttr($this->mTid, 'nickname');
        $add['center_level'] = 1;
        $add['count'] = 1;
        D('TDailyLeague')->CreateData($add);

        //扣水晶
        if ($require_diamond > 0) {
            if (!$this->recover('diamond', $require_diamond, null, $diamondNow)) {
                goto end;
            }
        }

        //扣金币
        if ($require_gold > 0) {
            if (!$this->recover('gold', $require_gold, null, $goldNow)) {
                goto end;
            }
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //记录加入公会次数
        D('GCount')->incAttr($this->mTid, 'league');

        //删除applying记录
        $this->removeApply($this->mTid);

        //更新公会
        $this->setSessionAttr($this->mTid, 'league_id', $leagueId);

        //返回
        return true;
    }

    //离开公会
    public function leave()
    {

        //判断是否已存在公会，不存在则报错
        $leagueTeamInfo = D('GLeagueTeam')->getRow($this->mTid);
        if (empty($leagueTeamInfo)) {
            C('G_ERROR', 'league_not_attended');
            return false;
        }

        //判断是否是公会会长
        if ($leagueTeamInfo['position'] == 1) {

            //判断公会是否只剩一个人
            $where['league_id'] = $leagueTeamInfo['league_id'];
            $count = M('GLeagueTeam')->where($where)->count();
            if ($count > 1) { //不止一个人时
                C('G_ERROR', 'president_can_not_leave');
                return false;
            }

            //公会战匹配后任何公会不能解散
            if (D('Predis')->cli('game')->get('league_dismiss_status') != '1') {
                C('G_ERROR', 'league_not_allow_to_dismiss');
                return false;
            }

            //查询公会信息
            $leagueInfo = D('GLeague')->getRow($leagueTeamInfo['league_id']);

        }

        //开始事务
        $this->transBegin();

        //如果是公会会长
        if ($leagueTeamInfo['position'] == 1) {
            //加删除公会记录
            if (false === D('LLeagueDismiss')->CreateData($leagueInfo)) {
                goto end;
            }
            //删除公会
            if (false === D('GLeague')->DeleteData($leagueTeamInfo['league_id'])) {
                goto end;
            }
            //删除PVP公会战报名
            if (false === D('GLeagueArena')->DeleteData($leagueTeamInfo['league_id'])) {
                goto end;
            }
            //删除PVP公会战排名
            if (false === D('GLeagueArenaRank')->DeleteData($leagueTeamInfo['league_id'])) {
                goto end;
            }
            //删除工会排名
            D('TDailyLeague')->DeleteData($leagueTeamInfo['league_id']);
            //删除公会redis
            D('Predis')->cli('game')->del('lg:' . $leagueTeamInfo['league_id']);
        }

        //退出公会逻辑
        if (!$this->quit($this->mTid)) {
            goto end;
        }

        //记录log，3为离开公会
        D('LLeagueTeamMember')->cLog($leagueTeamInfo['league_id'], $this->mTid, 3);

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return true;
    }

    //申请加入公会
    public function apply()
    {

        //查看申请的公会是否存在
        $count = M('GLeague')->where("`id`='{$_POST['league_id']}'")->count();
        if ($count == 0) {
            C('G_ERROR', 'league_not_exist');
            return false;
        }

        //查看是否已经加入公会
        if ($this->mSessionInfo['league_id'] > 0) {
            C('G_ERROR', 'league_already_attended');
            return false;
        }

        //判断等级
        $min_Join_Level = D('Static')->access('params', 'LEAGUE_JOIN_LEVEL');
        if (!$levelNow = $this->verify($min_Join_Level, 'level')) {
            return false;
        }

        //查询是否在退出公会保护期
        $froze = D('Static')->access('params', 'LEAGUE_INTERVAL_TIME');//分钟
        $last_left_time = D('LLeagueTeamMember')->getLastLeftTime($this->mTid);
        if (($last_left_time + ($froze * 60)) >= time()) {
            C('G_ERROR', 'league_froze_time_required');
            return false;
        }

        //查询是否已经申请过
        if (D('Predis')->cli('social')->exists('la:' . $_POST['league_id'] . ':' . $this->mTid)) {
            C('G_ERROR', 'league_already_apply');
            return false;
        }

        //提交申请
        $add['l'] = $_POST['league_id'];
        $add['t'] = $this->mTid;
        $info = D('GTeam')->getRow($this->mTid, array('nickname', 'level', 'icon'));
        $add['nn'] = $info['nickname'];
        $add['lv'] = $info['level'];
        $add['i'] = $info['icon'];
        $add['ts'] = time();
        $time = D('Static')->access('params', 'LEAGUE_APPLY_EXPIRE_TIME');
        $time = $time * 3600;
        D('Predis')->cli('social')->hmset('la:' . $_POST['league_id'] . ':' . $this->mTid, $add);
        D('Predis')->cli('social')->expire('la:' . $_POST['league_id'] . ':' . $this->mTid, $time);

        //记录log，1为申请加入公会
        D('LLeagueTeamMember')->cLog($_POST['league_id'], $this->mTid, 1);

        //返回
        return true;

    }

    //批准加入公会
    public function agreeApply()
    {

        //检查权限
        if (false == $leagueId = $this->leaguePermission($this->mTid, 2)) {
            return false;
        }

        //检查对方有没有申请加入
        if (!D('Predis')->cli('social')->exists('la:' . $leagueId . ':' . $_POST['applier_id'])) {
            C('G_ERROR', 'league_not_be_applied');
            return false;
        }

        //公会战期间不允许加人
        if(A('BEvent', 'Api')->isOpen(self::LEAGUE_FIGHT_GROUP)){
            C('G_ERROR', 'league_fight_not_allow_to_join');
            return false;
        }

        //获取公会信息
        $leagueInfo = D('GLeague')->getRow($leagueId, array('name', 'center_level'));

        //检查公会人数是否已满
        $where_league['league_id'] = $leagueId;
        $member_count = M('GLeagueTeam')->where($where_league)->count();
        $max_count = D('Static')->access('league', $leagueInfo['center_level'], 'people_num_max');
        if ($member_count >= $max_count) {
            C('G_ERROR', 'league_is_full');
            return false;
        }

        //检查申请人是否已有公会，有的话删掉申请人申请中的所有公会
        $rowExitingMember = D('GLeagueTeam')->getRow($_POST['applier_id']);
        if (!empty($rowExitingMember)) {
            C('G_ERROR', 'league_already_attended');
            return false;
        }

        //没有的话允许其加入公会
        $league_team['league_id'] = $leagueId;
        $league_team['tid'] = $_POST['applier_id'];
        $league_team['position'] = 0;
        if (!D('GLeagueTeam')->CreateData($league_team)) {
            return false;
        }

        //删除applying记录
        $keys = D('Predis')->cli('social')->keys('la:*:' . $_POST['applier_id']);
        if(!empty($keys)){
            D('Predis')->cli('social')->del($keys);
        }

        //记录log
        D('LLeagueTeamMember')->cLog($leagueId, $_POST['applier_id'], 2);

        //发送邮件
        $mailId = D('Static')->access('params', 'LEAGUE_ADD');
        $params['leaguename'] = $leagueInfo['name'];//获取公会名称
        D('GMail')->send($mailId, $this->mTid, $_POST['applier_id'], $params);

        //删除applying记录
        $this->removeApply($this->mTid);

        //更新公会
        $this->setSessionAttr($_POST['applier_id'], 'league_id', $leagueId);

        //记录加入公会次数
        D('GCount')->incAttr($_POST['applier_id'], 'league');

        return true;

    }

    //拒绝申请加入
    public function declineApply()
    {
        //检查权限
        if (false == $leagueId = $this->leaguePermission($this->mTid, 2)) {
            return false;
        }

        //检查对方有没有申请加入
        if (!D('Predis')->cli('social')->exists('la:' . $leagueId . ':' . $_POST['applier_id'])) {
            C('G_ERROR', 'league_not_be_applied');
            return false;
        }

        //删除申请中的公会
        D('Predis')->cli('social')->del('la:' . $leagueId . ':' . $_POST['applier_id']);

        //记录log
        D('LLeagueTeamMember')->cLog($leagueId, $_POST['applier_id'], 4);
        return true;
    }

    //转交公会会长
    public function changePresident()
    {

        //检查权限
        if (false == $leagueId = $this->leaguePermission($this->mTid)) {
            return false;
        }

        //检查对象是否为本公会成员
        $whereTeam['league_id'] = $leagueId;
        $whereTeam['tid'] = $_POST['target_tid'];
        $count = M('GLeagueTeam')->where($whereTeam)->count();
        if ($count == '0') {
            C('G_ERROR', 'not_same_league');
            return false;
        }

        //开始事务
        $this->transBegin();

        //更改公会表会长
        if (!D('GLeague')->updateAttr($leagueId, 'president_tid', $_POST['target_tid'], $this->mTid)) {
            goto end;
        };

        //更改会员信息
        if (!D('GLeagueTeam')->updateAttr($_POST['target_tid'], 'position', 1, null, $leagueId)) {
            goto end;
        }//更改普通会员为会长
        if (!D('GLeagueTeam')->updateAttr($this->mTid, 'position', 0, 1, $leagueId)) {
            goto end;
        }//更改会长为普通会员

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //发送邮件
        $mailId = D('Static')->access('params', 'LEAGUE_TRANSFER');
        D('GMail')->send($mailId, $this->mTid, $_POST['target_tid']);

        //返回
        return true;
    }

    //踢人
    public function fire()
    {

        //不能踢自己
        if ($_POST['target_tid'] == $this->mTid) {
            C('G_ERROR', 'cannot_fire_self');
            return false;
        }

        //检查权限
        if (false == $leagueId = $this->leaguePermission($this->mTid, 2)) {
            return false;
        }

        //检查对象是否为本公会成员
        $whereTeam['league_id'] = $leagueId;
        $whereTeam['tid'] = $_POST['target_tid'];
        $count = M('GLeagueTeam')->where($whereTeam)->count();
        if ($count == '0') {
            C('G_ERROR', 'not_same_league');
            return false;
        }

        //开始事务
        $this->transBegin();

        //删除会员
        if (!$this->quit($_POST['target_tid'])) {
            goto end;
        }

        //记录log
        D('LLeagueTeamMember')->cLog($leagueId, $_POST['target_tid'], 5);

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //发送退出邮件
        $mailId = D('Static')->access('params', 'LEAGUE_EXIT');
        $params['leaguename'] = D('GLeague')->getAttr($leagueId, 'name');//获取公会名称
        D('GMail')->send($mailId, $this->mTid, $_POST['target_tid'], $params);

        //返回
        return true;

    }

    private function quit($tid)
    {
        //删除公会成员
        $where['tid'] = $tid;
        if (false === D('GLeagueTeam')->DeleteData($where)) {
            return false;
        }
        //清除公会战信息
        if (false === D('GLeagueArenaTeam')->DeleteData($where)) {
            return false;
        }
        //更新个人公会信息
        $this->setSessionAttr($tid, 'league_id', 0);
        return true;
    }

    //捐献
    public function donate()
    {

        //获取donate_type
        $donate_type = $_POST['donate_type'];
        if (empty($donate_type) || !is_numeric($donate_type)) {
            C('G_ERROR', 'League_donate_type_required');
            return false;
        }

        //判断是否是公会会员并取得公会id
        if ($this->mSessionInfo['league_id'] == 0) {
            C('G_ERROR', 'league_not_attended');
            return false;
        }

        //判断玩家是哪种类型捐献
        $donateConfig = D('Static')->access('league_donate', $donate_type);

        //获取玩家当日此类型已捐献次数
        $count = D('LLeagueDonate')->getTodayCount($this->mTid, $donate_type);
        ++$count;

        //查询购买需要的货币
        $exchange = $this->exchangeMoney($donateConfig['consume_type'], $count);
        $needType = $exchange['needType'];
        $needValue = $exchange['needValue'];

        //检查玩家当前货币是否足够
        if (!$now = $this->verify($needValue, $this->mMoneyType[$needType])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣钱
        if (!$this->recover($this->mMoneyType[$needType], $needValue, null, $now)) {
            goto end;
        }

        //成就
        if($this->mMoneyType[$needType] == 'diamond') {
            D('GCount')->donate($this->mTid);
        }

        //加贡献值
        if (!$this->produce('contribution', $donateConfig['bonus_contribution'], $this->mSessionInfo['league_id'])) {
            goto end;
        }

        //加公会资金
        if (!$this->produce('fund', $donateConfig['bonus_exp'], $this->mSessionInfo['league_id'])) {
            goto end;
        }

        //写log
        D('LLeagueDonate')->cLog($this->mSessionInfo['league_id'], $this->mTid, $donate_type);

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return true;

    }

    //获得成员一览
    public function getLeagueMemberList()
    {
        //判断是否是公会会员并取得公会id
        if ($this->mSessionInfo['league_id'] == 0) {
            C('G_ERROR', 'league_not_attended');
            return false;
        }

        //获取当前属性
        $field = array('`g_league_team`.`tid`', '`g_league_team`.`position`', '`g_league_team`.`contribution`', '`g_league_team`.`ctime`', '`g_team`.`nickname`', '`g_team`.`level`', '`g_team`.`icon`', '`g_team`.`last_login_time`',);
        $where = '`g_league_team`.`league_id`=' . $this->mSessionInfo['league_id'];
        $list = M('GLeagueTeam')->field($field)->join('`g_team` ON `g_team`.`tid` = `g_league_team`.`tid`')->where($where)->select();

        //获取成员TID列表
        $tidList = array();
        foreach ($list as $value) {
            $tidList[] = $value['tid'];
        }

        //获取成员历史贡献度
        $end = time();
        $start = $end - (7 * 86400);
        $where = array();
        $where['tid'] = array('in', $tidList);
        $where['league_id'] = $this->mSessionInfo['league_id'];
        $where['attr'] = 'contribution';
        $where['value'] = array('gt', 0);
        $where['behave'] = 8002;
        $where['ctime'] = array('between', array($start, $end));
        $select = D('LLeagueTeam')->field("`tid`,sum(`value`) as `count`")->where($where)->group('`tid`')->select();
        $contributionList = array();
        foreach ($select as $value) {
            $contributionList[$value['tid']] = $value['count'];
        }

        //遍历
        foreach ($list as $key => $value) {
            $list[$key]['contribution_day7'] = $contributionList[$value['tid']] ? $contributionList[$value['tid']] : 0;
        }

        //返回
        return $list;

    }

    //获得申请中的玩家一览
    public function getApplyingList()
    {
        //检查权限
        if (false == $leagueId = $this->leaguePermission($this->mTid, 2)) {
            return false;
        }

        //获取申请中的玩家一览
        $keyList = D('Predis')->cli('social')->keys('la:' . $leagueId . ':*');
        if (empty($keyList)) {
            return array();
        }

        $list = array();
        foreach ($keyList as $value) {
            $redis = D('Predis')->cli('social')->hgetall($value);
            $arr['tid'] = $redis['t'];
            $arr['nickname'] = $redis['nn'];
            $arr['level'] = $redis['lv'];
            $arr['icon'] = $redis['i'];
            $arr['ctime'] = $redis['ts'];
            $rank = D('GArena')->getAttr($arr['tid'], 'rank');
            $arr['rank'] = empty($rank) ? 0 : $rank;
            $list[] = $arr;
        }
        return $list;

    }

    //升级公会建筑
    public function upgradeLeagueBuilding()
    {

        //检查权限
        if (false == $leagueId = $this->leaguePermission($this->mTid, 2)) {
            return false;
        }

        switch ($_POST['building_type']) {
            case 1:
                $building_name = 'center';
                break;
            case 2:
                $building_name = 'shop';
                break;
            case 3:
                $building_name = 'food';
                break;
            case 4:
                $building_name = 'boss';
                break;
            case 5:
                $building_name = 'attribute';
                break;
            default:
                C('G_ERROR', 'league_param_building_type_not_received');
                return false;
                break;
        }

        //获取当前建筑情况
        $leagueInfo = D('GLeague')->getRow($leagueId);

        //检查建筑等级是否超过公会大厅
        if (($building_name != 'center') && $leagueInfo[$building_name . '_level'] >= $leagueInfo['center_level']) {
            C('G_ERROR', 'league_update_center_require');
            return false;

        }

        //检查公会资金是否足够
        //获取升级需要的公会资金
        $level_up_cost = D('Static')->access('league_building', $leagueInfo[$building_name . '_level'], 'currency_' . $building_name . '_gold');

        if ($leagueInfo['fund'] < $level_up_cost) {
            C('G_ERROR', 'league_fund_not_enough');
            return false;
        }

        //开始事务
        $this->transBegin();

        //升级建筑
        if (!D('GLeague')->incAttr($leagueId, $building_name . '_level', 1, $leagueInfo[$building_name . '_level'])) {
            goto end;
        }

        //扣公会资金
        if (!D('GLeague')->decAttr($leagueId, 'fund', $level_up_cost, $leagueInfo['fund'])) {
            goto end;
        }

        if ($building_name == 'attribute') {
            D('Predis')->cli('game')->hset('lg:' . $leagueId, 'attr_lv', $leagueInfo[$building_name . '_level'] + 1);
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

    //公会食堂吃饭
    public function eat()
    {

        //查看今天有没有来过食堂
        $count = D('LLeagueFood')->getCount($this->mTid);
        if ($count > 0) {
            C('G_ERROR', 'league_food_eat_already');
            return false;
        }

        //获取公会ID
        if ($this->mSessionInfo['league_id'] == 0) {
            C('G_ERROR', 'league_not_attended');
            return false;
        }

        //获取公会食堂等级
        $foodLevel = D('GLeague')->getAttr($this->mSessionInfo['league_id'], 'food_level');

        //获取食堂配置
        $foodConfig = D('Static')->access('league_food', $foodLevel);

        //查看贡献度够不够
        if (!$now = $this->verify($foodConfig['currency_contribution'], 'contribution')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣除贡献度
        if (!$this->recover('contribution', $foodConfig['currency_contribution'], null, $now)) {
            goto end;
        }

        //加体力
        if (!$this->produce('vality', $foodConfig['bonus_pow'])) {
            goto end;
        }

        //加金币
        if (!$this->produce('gold', $foodConfig['bonus_gold'])) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //记录食堂记录
        D('LLeagueFood')->cLog($this->mTid, $this->mSessionInfo['league_id']);

        //返回
        return true;

    }

    //修改工会公告
    public function setNotice()
    {
        //检查权限
        if (false == $leagueId = $this->leaguePermission($this->mTid, 2)) {
            return false;
        }
        //修改公告
        $saveWhere['id'] = $leagueId;
        $data['notice'] = $_POST['notice'];
        if (false === D('GLeague')->UpdateData($data, $saveWhere)) {
            return false;
        }
        //返回
        return true;
    }

    //推荐自己的公会
    public function recommend()
    {

        //判断是否已存在公会，不存在则报错
        $where['tid'] = $this->mTid;
        $leagueId = M('GLeagueTeam')->where($where)->getField('league_id');
        if (empty($leagueId)) {
            C('G_ERROR', 'league_not_attended');
            return false;
        }

        //需要的水晶
        $needDiamond = D('Static')->access('params', 'LEAGUE_RECOMMENDED');
        if (!$now = $this->verify($needDiamond, 'diamond')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣钱
        if (!$this->recover('diamond', $needDiamond, null, $now)) {
            goto end;
        }

        //增加时间
        unset($where);
        $where['id'] = $leagueId;
        $recommend = M('GLeague')->where($where)->getField('recommend');
        if ($recommend >= time()) {
            $recommendNew = $recommend + 86400;
        } else {
            $recommendNew = time() + 86400;
        }

        if (!D('GLeague')->updateAttr($leagueId, 'recommend', $recommendNew, $recommend)) {
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

    //获取自己公会基本信息
    public function getInfo()
    {

        //判断是否已存在公会，不存在则报错
        $where['tid'] = $this->mTid;
        $leagueId = M('GLeagueTeam')->where($where)->getField('league_id');
        if (empty($leagueId)) {
            C('G_ERROR', 'league_not_attended');
            return false;
        }

        //查询信息
        $info = D('GLeague')->getInfo($leagueId);

        //查询公会人数
        $info['count'] = D('GLeagueTeam')->getMemberCount($leagueId);

        //公会捐献
        $info['donate'] = D('LLeagueDonate')->getTodayCountList($this->mTid);

        //公会食堂
        $info['eat'] = D('LLeagueFood')->getCount($this->mTid);

        //公会申请人数
        $info['apply'] = 0;
        if ($info['president_tid'] == $this->mTid) {
            //获取申请中的玩家一览
            $keyList = D('Predis')->cli('social')->keys('la:' . $info['id'] . ':*');
            if (!empty($keyList)) {
                $info['apply'] = count($keyList);
            }
        }

        //返回
        return $info;
    }

    //获取服务器公会列表
    public function getList()
    {
        if ($_POST['league_id'] > 0) {
            $list = D('GLeague')->search($_POST['league_id']);
            if (!empty($list)) {
                foreach ($list as $key => $value) {
                    $list[$key]['recommend'] = 0;
                    $list[$key]['apply'] = D('Predis')->cli('social')->exists('la:' . $value['league_id'] . ':' . $this->mTid) ? 1 : 0;
                }
            }
            $return['total'] = 1;
        } else {
            $list1 = array();
            $list2 = array();
            $page = $_POST['page'] > 0 ? $_POST['page'] : 1;//分页数
            $row = 5;//单页行数
            $start = ($page - 1) * $row + 1;//启始条数
            $end = $page * $row;//结束条数
            //查询推荐情况
            $recommendCount = D('GLeague')->getRecommendCount();//推荐数量
            $allCount = D('TDailyLeague')->getCount();//总数量
            if ($recommendCount >= $end) {
                $startRow = ($page - 1) * $row;
                $list1 = D('GLeague')->getRecommendList($startRow, $row);
            } else if ($start <= $recommendCount && $recommendCount < $end) {
                $recommendStartRow = ($page - 1) * $row;
                $recommendRow = $recommendCount - $start + 1;
                $list1 = D('GLeague')->getRecommendList($recommendStartRow, $recommendRow);
                $list2 = D('TDailyLeague')->getList(0, ($end - $recommendCount));
            } else if ($recommendCount < $start) {
                $start = ($page - 1) * $row - $recommendCount;
                $list2 = D('TDailyLeague')->getList($start, $row);
            }

            //加上推荐标识
            if (!empty($list1)) {
                foreach ($list1 as $key => $value) {
                    $list1[$key]['recommend'] = 1;
                    $list1[$key]['apply'] = D('Predis')->cli('social')->exists('la:' . $value['league_id'] . ':' . $this->mTid) ? 1 : 0;
                }
            } else {
                $list1 = array();
            }

            if (!empty($list2)) {
                foreach ($list2 as $key => $value) {
                    $list2[$key]['recommend'] = 0;
                    $list2[$key]['apply'] = D('Predis')->cli('social')->exists('la:' . $value['league_id'] . ':' . $this->mTid) ? 1 : 0;
                }
            } else {
                $list2 = array();
            }

            $list = array_merge_recursive($list1, $list2);
            if (empty($list)) {
                $list = array();
            }
            $return['total'] = $recommendCount + $allCount;
        }
        //返回
        $return['list'] = $list;
        return $return;
    }

    //获取动态信息(捐献)
    public function getFeed()
    {
        //判断是否已存在公会，不存在则报错
        $where['tid'] = $this->mTid;
        $leagueId = M('GLeagueTeam')->where($where)->getField('league_id');
        if (empty($leagueId)) {
            C('G_ERROR', 'league_not_attended');
            return false;
        }

        //获取联盟战资金动态
        $fund = D('LLeague')->getLeagueFightFund($leagueId);
        if ($fund > 0) {
            $sql = "select `gt`.`tid`,`gt`.`nickname`,`gt`.`icon`,`gt`.`level` from `g_team` as `gt`,`g_league` as `gl` where `gt`.`tid`=`gl`.`president_tid` && `gl`.`id`='{$leagueId}' limit 1";
            $select = M()->query($sql);
            $return['fight'] = $select;
            $return['fight'][0]['fund'] = $fund;
            $return['fight'][0]['ctime'] = get_daily_utime();
        } else {
            $return['fight'] = array();
        }

        //获取捐献动态
        $return['list'] = D('LLeagueDonate')->getList($leagueId);
        return $return;
    }

    //查询玩家竞技场防御阵容基本信息
    public function getDefenseInfo()
    {
        //获取玩家排名&总战力&vip等级
        $rank = D('GArena')->getAttr($_POST['target_tid'], 'rank');
        $return['rank'] = $rank ? $rank : 0;
        $return['force'] = D('GCount')->getAttr($_POST['target_tid'], 'force');
        $return['vip'] = D('GVip')->getLevel($_POST['target_tid']);

        //获取玩家战力前5小队
        $field = array('group', 'index', 'level', 'favour', 'force',);
        $where['tid'] = $_POST['target_tid'];
        $order['force'] = 'desc';
        $return['list'] = M('GPartner')->field($field)->where($where)->order($order)->limit(5)->select();

        //返回
        return $return;
    }

    //任命公会职位
    public function appoint()
    {
        //检查权限
        if (false == $leagueId = $this->leaguePermission($this->mTid)) {
            return false;
        }

        //不能设置公会会长
        if ($_POST['position'] == '1') {
            C('G_ERROR', 'target_not_in_league');
            return false;
        }

        //查询对方在公会中的情况
        $row = D('GLeagueTeam')->getRow($_POST['target_tid'], array('league_id', 'position'));
        if (empty($row) || $row['league_id'] != $leagueId) {
            C('G_ERROR', 'target_not_in_league');
            return false;
        }

        //查看是否达到职务人数上限
        if ($_POST['position'] == '2') {
            $max = D('Static')->access('params', 'LEAGUE_VICEATTRIBUTE_MAX');
            $where['league_id'] = $leagueId;
            $where['position'] = $_POST['position'];
            $now = D('GLeagueTeam')->where($where)->count();
            if ($max <= $now) {
                C('G_ERROR', 'league_position_max');
                return false;
            }
        }

        //查看对方职位
        if ($row['position'] != $_POST['position']) {
            if (false === D('GLeagueTeam')->updateAttr($_POST['target_tid'], 'position', $_POST['position'], $row['position'], $leagueId)) {
                return false;
            }
        }

        //返回
        return true;
    }

    //删除玩家申请信息
    public function removeApply($tid)
    {
        //删除applying记录
        $keys = D('Predis')->cli('social')->keys('la:*:' . $tid);
        if (!empty($keys)) {
            D('Predis')->cli('social')->del($keys);
        }
        return true;
    }

}