<?php
return array(

    'ERROR' => array(

        /********************用户中心********************/
        //UC系统
        'uc_curl_error' => array('code' => 100, 'message' => 'UC连接失败',),
        'uc_db_error' => array('code' => 101, 'message' => 'UC服务器忙',),
        'uc_sign_error' => array('code' => 102, 'message' => 'UC签名验证失败',),

        //用户名
        'username_existed' => array('code' => 201, 'message' => '已存在相同的账号',),
        'username_not_exist' => array('code' => 202, 'message' => '用户尚未注册',),
        'username_format' => array('code' => 203, 'message' => '用户名输入格式不正确',),

        //密码
        'password_wrong' => array('code' => 301, 'message' => '用户密码输入错误',),
        'password_format_error' => array('code' => 302, 'message' => '密码格式错误',),
        'password_not_changed' => array('code' => 303, 'message' => '新密码与旧密码相同',),

        //绑定
        'phone_existed' => array('code' => 401, 'message' => '手机号码已被绑定',),
        'phone_format' => array('code' => 402, 'message' => '手机号码格式错误',),
        'phone_binding_already' => array('code' => 403, 'message' => '账号已绑定手机号码',),
        'ident_binding_already' => array('code' => 404, 'message' => '账号已绑定身份信息',),
        'email_binding_already' => array('code' => 405, 'message' => '账号已绑定电子邮箱',),

        //状态
        'user_banned' => array('code' => 501, 'message' => '账户已经被封禁',),
        'user_silence' => array('code' => 502, 'message' => '账户已经被禁言',),

        //兑换码
        'exchange_not_exist' => array('code' => 601, 'message' => '兑换码不存在',),
        'exchange_used' => array('code' => 602, 'message' => '兑换码已被使用',),
        'exchange_not_start' => array('code' => 603, 'message' => '兑换尚未开始',),
        'exchange_over' => array('code' => 604, 'message' => '兑换已经结束',),
        'exchange_expire' => array('code' => 605, 'message' => '兑换码已经过期',),
        'exchange_wrong_game' => array('code' => 606, 'message' => '兑换码不属于本游戏',),
        'exchange_wrong_server' => array('code' => 607, 'message' => '兑换码不能在本服务器使用',),
        'exchange_type_use_max' => array('code' => 608, 'message' => '此类兑换码已达最大兑换次数',),
        'exchange_use_fail' => array('code' => 609, 'message' => '兑换失败请稍后再试',),
        'exchange_level_low' => array('code' => 610, 'message' => '尚未达到最低兑换等级',),
        'exchange_wrong_channel' => array('code' => 611, 'message' => '兑换码不能在本渠道使用',),

        //激活码
        'activation_not_exist' => array('code' => 651, 'message' => '激活码不存在',),
        'activation_used' => array('code' => 652, 'message' => '激活码已被使用',),
        'activation_not_start' => array('code' => 653, 'message' => '激活尚未开始',),
        'activation_over' => array('code' => 654, 'message' => '激活已经结束',),
        'activation_expire' => array('code' => 655, 'message' => '激活码已经过期',),
        'activation_wrong_game' => array('code' => 656, 'message' => '激活码不属于本游戏',),
        'activation_wrong_server' => array('code' => 657, 'message' => '激活码不能在本服务器使用',),
        'activation_type_use_max' => array('code' => 658, 'message' => '此类兑换码已达最大兑换次数',),
        'activation_wrong_channel' => array('code' => 659, 'message' => '激活码不能在本渠道使用',),

        //第三方平台错误
        'platform_login_error' => array('code' => 901, 'message' => '平台登录失败',),
        'channel_not_exist' => array('code' => 902, 'message' => '渠道不存在',),
        'linekong_error' => array('code' => 903, 'message' => '蓝港eRating返回错误',),

        /********************用户中心********************/

        /******************** 游 戏 ********************/

        /******************** 系 统 ********************/
        //系统错误或未知错误
        'unknown' => array('code' => 1001, 'message' => '未知错误',),
        'illegal' => array('code' => 1002, 'message' => '非法操作',),
        'protocol_error' => array('code' => 1003, 'message' => '协议不存在',),
        'battle_anomaly' => array('code' => 1004, 'message' => '战斗数据异常',),
        'refresh_too_fast' => array('code' => 1005, 'message' => '刷新频率太快了',),
        'open_require_not_meet' => array('code' => 1006, 'message' => '尚未达到功能开放条件',),
        'buy_count_max' => array('code' => 1007, 'message' => '已达到最大购买次数',),

        //服务器错误
        'db_error' => array('code' => 1101, 'message' => '服务器忙',),
        'redis_error' => array('code' => 1102, 'message' => '服务器忙',),
        'config_error' => array('code' => 1103, 'message' => '服务器忙',),
        'lua_error' => array('code' => 1104, 'message' => '服务器忙',),
        'sid_not_exist' => array('code' => 1105, 'message' => '服务器不存在',),
        'server_maintenance' => array('code' => 1106, 'message' => '服务器维护中',),
        'db_field_max' => array('code' => 1107, 'message' => '数据已经达到最大值',),
        'server_close_reg' => array('code' => 1108, 'message' => '暂时不开放创建新帐号',),
        'version_low' => array('code' => 1109, 'message' => '客户端版本过低',),
        'config_dynamic_error' => array('code' => 1110, 'message' => '服务器忙',),

        //非法操作
        'id_not_exist' => array('code' => 1201, 'message' => '协议缺少通讯ID',),
        'login_timeout' => array('code' => 1202, 'message' => '登录状态过期',),
        'params_error' => array('code' => 1203, 'message' => '参数错误',),
        'no_update' => array('code' => 1204, 'message' => '数据与原来的相同',),
        'sign_error' => array('code' => 1205, 'message' => 'Sign验证失败',),
        'sign_repeat' => array('code' => 1206, 'message' => '协议重复发送',),
        'login_elsewhere' => array('code' => 1207, 'message' => '帐号在别处登录',),
        'client_reboot' => array('code' => 1208, 'message' => '客户端强制重启',),

        //充值&会员卡&VIP
        'pay_goods_not_enable' => array('code' => 1301, 'message' => '商品尚未开放购买',),
        'pay_launch_too_fast' => array('code' => 1302, 'message' => '充值订单提交太快',),
        'order_not_available' => array('code' => 1303, 'message' => '订单不存在',),
        'order_id_error' => array('code' => 1304, 'message' => '订单号错误',),
        'receipt_handle_already' => array('code' => 1305, 'message' => '凭证已经处理过',),
        'pay_goods_cannot_buy' => array('code' => 1306, 'message' => '商品暂时不能购买',),
        'not_period_member' => array('code' => 1307, 'message' => '非周期领取奖励',),
        'member_bonus_received_already' => array('code' => 1308, 'message' => '本时段奖励已经领取',),
        'member_expire' => array('code' => 1309, 'message' => '会员卡已经过期',),
        'pray_not_open' => array('code' => 1310, 'message' => '祈愿类型未开放',),
        'pray_not_in_free_time' => array('code' => 1311, 'message' => '免费祈愿时间尚未达到',),
        'pray_type_no_free' => array('code' => 1312, 'message' => '该祈愿类型没有免费机会',),
        'vip_bonus_received' => array('code' => 1313, 'message' => 'VIP奖励已经领取',),
        'vip_level_low' => array('code' => 1314, 'message' => 'VIP等级不够',),
        'pray_free_max_today' => array('code' => 1315, 'message' => '今天的免费祈愿次数已经用完',),
        'member_buy_not_allow' => array('code' => 1316, 'message' => '会员卡暂时不能购买',),

        /******************** 系 统 ********************/

        /******************** 战 队 ********************/
        //创建
        'team_not_create' => array('code' => 2001, 'message' => '还未创建战队',),
        'user_existed' => array('code' => 2002, 'message' => '账号已经创建战队',),
        'user_not_exist' => array('code' => 2003, 'message' => '账号不存在',),
        'team_existed' => array('code' => 2004, 'message' => '战队ID已存在',),
        'nickname_existed' => array('code' => 2005, 'message' => '角色昵称已被使用',),
        'error_team' => array('code' => 2006, 'message' => '对方不存在',),
        'team_level_low' => array('code' => 2007, 'message' => '战队等级不够',),
        'item_pack_max' => array('code' => 2008, 'message' => '背包格子已经扩展到最大',),
        'team_vality_max' => array('code' => 2009, 'message' => '体力值达到上限',),
        'level_bonus_received' => array('code' => 2010, 'message' => '等级奖励已经领取',),
        'nickname_shield' => array('code' => 2011, 'message' => '该名称无法使用',),
        'team_skill_point_not_used' => array('code' => 2012, 'message' => '技能点还没用完',),

        //属性
        'not_enough_attr' => array('code' => 2101, 'message' => '战队属性不足',),
        'not_enough_gold' => array('code' => 2102, 'message' => '金币不足',),
        'not_enough_diamond' => array('code' => 2103, 'message' => '水晶不足',),
        'not_enough_vality' => array('code' => 2104, 'message' => '体力不足',),
        'not_enough_energy' => array('code' => 2105, 'message' => '气力不足',),
        'not_enough_skill_point' => array('code' => 2106, 'message' => '技能点不足',),
        'error_exchange' => array('code' => 2107, 'message' => '不能兑换',),
        'material_score_not_enough' => array('code' => 2108, 'message' => '献祭积分不足',),
        'fund_not_buy' => array('code' => 2109, 'message' => '尚未购买基金',),
        'fund_buy_already' => array('code' => 2110, 'message' => '基金已经购买',),
        'fund_close' => array('code' => 2111, 'message' => '基金已经关闭购买',),
        'fund_not_exist' => array('code' => 2112, 'message' => '基金奖励不存在',),

        /******************** 战 队 ********************/

        /******************** 伙 伴 ********************/
        //伙伴
        'partner_level_low' => array('code' => 3001, 'message' => '伙伴等级不足',),
        'not_enough_partner_soul' => array('code' => 3002, 'message' => '伙伴神力不足',),
        'partner_upgrade_condition_not_reach' => array('code' => 3003, 'message' => '伙伴升阶条件未达成',),
        'partner_quality_max' => array('code' => 3004, 'message' => '伙伴已到最高品质',),
        'partner_not_exist' => array('code' => 3005, 'message' => '尚未获得该伙伴',),
        'partner_favour_not_enough' => array('code' => 3006, 'message' => '伙伴好感度不足',),
        'partner_quality_count_not_enough' => array('code' => 3007, 'message' => '没有足够品质的伙伴',),
        'partner_quality_not_enough' => array('code' => 3008, 'message' => '伙伴品质太低',),
        'partner_already_get' => array('code' => 3009, 'message' => '伙伴已经获得',),
        'partner_not_allow_to_fight' => array('code' => 3010, 'message' => '所选伙伴暂时不能上场',),
        'partner_exp_max' => array('code' => 3011, 'message' => '伙伴经验已满',),
        'partner_quality_low' => array('code' => 3012, 'message' => '伙伴品质不足',),
        'partner_force_abnormal' => array('code' => 3013, 'message' => '伙伴战力异常',),
        'partner_awake_max' => array('code' => 3014, 'message' => '伙伴已经觉醒到最高等级',),
        'partner_empty' => array('code' => 3015, 'message' => '至少需要上阵一个伙伴',),

        //技能
        'skill_lock' => array('code' => 3101, 'message' => '技能尚未解锁',),
        'skill_level_max' => array('code' => 3102, 'message' => '技能已到最高等级',),
        'skill_already_levelup' => array('code' => 3103, 'message' => '技能已经升级',),

        /******************** 伙 伴 ********************/

        /******************** 装备&纹章&星灵 ********************/
        'equip_not_exist' => array('code' => 4001, 'message' => '尚未获得该装备',),
        'equip_no_upgrade' => array('code' => 4002, 'message' => '装备已经精炼至最大品质',),
        'equip_level_max' => array('code' => 4003, 'message' => '装备已经强化至最大等级',),
        'equip_level_low' => array('code' => 4004, 'message' => '装备强化等级不足',),
        'equip_upgrade_low' => array('code' => 4005, 'message' => '装备精炼品质不足',),
        'equip_enchant_locked' => array('code' => 4006, 'message' => '装备属性已被锁定',),
        'equip_enchant_unlocked' => array('code' => 4007, 'message' => '装备属性未被锁定',),
        'equip_enchant_lock_max' => array('code' => 4008, 'message' => '装备属性锁定达到上限',),
        'equip_enchant_expired' => array('code' => 4009, 'message' => '装备附魔已过期',),

        'emblem_not_exist' => array('code' => 4101, 'message' => '纹章不存在',),
        'emblem_equip_already' => array('code' => 4102, 'message' => '纹章已经被装备',),
        'emblem_slot_max' => array('code' => 4103, 'message' => '纹章槽位已满',),
        'emblem_not_enough' => array('code' => 4104, 'message' => '纹章不足',),
        'emblem_material_not_enough' => array('code' => 4105, 'message' => '纹章合成材料不足',),

        'star_not_enough' => array('code' => 4201, 'message' => '星数不足',),
        'star_partner_equip_already' => array('code' => 4202, 'message' => '伙伴已经在星位上',),
        'star_level_max' => array('code' => 4203, 'message' => '星位已达到最高等级',),
        'star_not_open' => array('code' => 4204, 'message' => '星位尚未开启',),
        'star_not_baptize' => array('code' => 4205, 'message' => '星位尚未洗炼',),
        /******************** 装备&纹章&星灵 ********************/

        /******************** 道 具 ********************/
        'not_enough_item' => array('code' => 5001, 'message' => '道具不足',),
        'item_cannot_split' => array('code' => 5002, 'message' => '道具不能分解',),
        'item_cannot_use' => array('code' => 5003, 'message' => '道具不能使用',),
        'item_cannot_sell' => array('code' => 5004, 'message' => '道具不能出售',),
        /******************** 道 具 ********************/

        /******************** 好 友 ********************/
        'friend_not_exist' => array('code' => 6001, 'message' => '对方不存在',),
        'friend_already' => array('code' => 6002, 'message' => '已经是好友关系',),
        'friend_not_yet' => array('code' => 6003, 'message' => '不是好友关系',),
        'friend_max' => array('code' => 6004, 'message' => '好友数量已达上限',),
        'friend_friend_max' => array('code' => 6005, 'message' => '对方的好友数量已达上限',),
        'friend_send_already' => array('code' => 6006, 'message' => '好友请求已发送',),
        'friend_vality_send' => array('code' => 6007, 'message' => '体力已经送出',),
        'friend_vality_get' => array('code' => 6008, 'message' => '体力已经收到',),
        'friend_vality_not_send' => array('code' => 6009, 'message' => '好友没有送你体力',),
        'friend_add_fail' => array('code' => 6010, 'message' => '好友添加失败',),
        /******************** 好 友 ********************/

        /******************** 商 店 ********************/
        'shop_not_exist' => array('code' => 7001, 'message' => '商店不存在',),
        'shop_need_refresh' => array('code' => 7002, 'message' => '商店需要刷新',),
        'shop_goods_bought_already' => array('code' => 7003, 'message' => '商品已经被购买',),
        'shop_lock' => array('code' => 7004, 'message' => '商店尚未解锁',),
        'shop_goods_not_exist' => array('code' => 7005, 'message' => '商品不存在',),
        'shop_not_allow_refresh' => array('code' => 7006, 'message' => '该商店不能刷新',),
        /******************** 商 店 ********************/

        /******************** 聊 天 ********************/
        'chat_with_self' => array('code' => 8001, 'message' => '不能和自己发消息',),
        'chat_count_not_enough' => array('code' => 8002, 'message' => '今天世界聊天发送次数已经用完',),
        'chat_msg_no_empty' => array('code' => 8003, 'message' => '不能发送空消息',),
        /******************** 聊 天 ********************/

        /******************** 联 盟 ********************/
        'league_already_attended' => array('code' => 9001, 'message' => '已经拥有公会',),
        'league_name_existed' => array('code' => 9002, 'message' => '公会名字已存在',),
        'league_not_attended' => array('code' => 9003, 'message' => '尚未参加任何公会',),
        'president_can_not_leave' => array('code' => 9004, 'message' => '会长不能退出公会',),
        'league_id_required' => array('code' => 9006, 'message' => '需要公会ID',),
        'league_froze_time_required' => array('code' => 9007, 'message' => '未达到离开公会的冻结时间',),
        'league_is_full' => array('code' => 9008, 'message' => '公会已满员',),
        'league_permission_low' => array('code' => 9009, 'message' => '公会权限不足',),
        'target_tid_required' => array('code' => 9010, 'message' => '需要转让人的id',),
        'not_same_league' => array('code' => 9011, 'message' => '不是相同公会的伙伴',),
        'league_already_donated' => array('code' => 9012, 'message' => '今天已经捐献过',),
        'League_donate_type_required' => array('code' => 9013, 'message' => '需要捐献类型',),
        'league_param_building_type_not_received' => array('code' => 9014, 'message' => '需要捐献建筑类型',),
        'league_update_center_require' => array('code' => 9015, 'message' => '需要先升级公会大厅',),
        'league_fund_not_enough' => array('code' => 9016, 'message' => '公会资金不足',),
        'league_contribution_not_enough' => array('code' => 9017, 'message' => '公会贡献度不足',),
        'league_center_level_low' => array('code' => 9018, 'message' => '公会大厅等级不足',),
        'league_food_eat_already' => array('code' => 9019, 'message' => '已经去过公会食堂',),
        'league_quest_not_in_list' => array('code' => 9020, 'message' => '任务不在列表中',),
        'league_quest_error' => array('code' => 9021, 'message' => '任务尚未接取',),
        'target_not_in_league' => array('code' => 9022, 'message' => '对方不是公会成员',),
        'league_president_not' => array('code' => 9023, 'message' => '不是公会会长',),
        'league_not_exist' => array('code' => 9024, 'message' => '公会不存在',),
        'league_already_apply' => array('code' => 9025, 'message' => '申请正等待会长回复',),
        'league_quest_accept' => array('code' => 9026, 'message' => '公会任务已经开始',),
        'league_not_be_applied' => array('code' => 9027, 'message' => '玩家没有申请加入本公会',),
        'cannot_fire_self' => array('code' => 9028, 'message' => '不能将自己踢出公会',),
        'league_not_allow_to_dismiss' => array('code' => 9029, 'message' => '公会战期间不能解散公会',),
        'league_shop_level_low' => array('code' => 9030, 'message' => '公会商店等级不足',),
        'league_appoint_error' => array('code' => 9031, 'message' => '不能任命该公会职务',),
        'league_position_max' => array('code' => 9032, 'message' => '公会职务达到人数上限',),
        'league_activity_not_enough' => array('code' => 9033, 'message' => '公会活跃度不足',),
        'league_fight_not_allow_to_join' => array('code' => 9034, 'message' => '公会战期间不能增加成员',),

        'league_boss_lock' => array('code' => 9101, 'message' => '公会BOSS尚未被召唤',),
        'league_boss_fight_max_today' => array('code' => 9102, 'message' => '该公会BOSS今甜的挑战次数已经用完',),
        'league_boss_alive' => array('code' => 9103, 'message' => '公会BOSS尚未被打败',),
        'league_boss_site_lock' => array('code' => 9104, 'message' => '公会BOSS槽位尚未解锁',),
        'league_boss_site_unlock' => array('code' => 9105, 'message' => '公会BOSS槽位已解锁，不能强制召唤',),
        'league_boss_buff_max' => array('code' => 9106, 'message' => '公会BOSS的BUFF次数已达到最大值',),

        /******************** 联 盟 ********************/

        /******************** 任 务 ********************/
        'quest_not_accept' => array('code' => 10001, 'message' => '任务尚未领取',),
        'quest_complete_max' => array('code' => 10002, 'message' => '任务完成次数已达上限',),
        'quest_not_complete' => array('code' => 10003, 'message' => '任务条件未完成',),
        'quest_complete_already' => array('code' => 10004, 'message' => '任务已经完成',),
        'quest_not_exist' => array('code' => 10005, 'message' => '任务不存在',),
        'quest_count_max' => array('code' => 10006, 'message' => '任务次数已达上限',),
        'achieve_completed' => array('code' => 11001, 'message' => '成就奖励已经领取',),
        'achieve_not_complete' => array('code' => 11002, 'message' => '成就目标尚未达成',),
        'activity_not_enough' => array('code' => 12001, 'message' => '活跃度不足',),
        'activity_bonus_received' => array('code' => 12002, 'message' => '活跃奖励已经领取',),
        /******************** 任 务 ********************/

        /******************** 邮 件 ********************/
        'mail_expired' => array('code' => 11001, 'message' => '邮件已过期',),
        'mail_no_annex' => array('code' => 11002, 'message' => '邮件没有附件',),
        'mail_not_belong' => array('code' => 11003, 'message' => '邮件所属错误',),
        'not_notice_mail' => array('code' => 11004, 'message' => '不是公告邮件',),
        'mail_already_read' => array('code' => 11005, 'message' => '邮件已经读取',),
        /******************** 邮 件 ********************/

        /******************** 活 动 ********************/
        'novice_login_not_enough' => array('code' => 12001, 'message' => '尚未达到登录次数',),
        'event_bonus_received' => array('code' => 12002, 'message' => '活动奖励已经领取',),
        'activity_not_in_open_time' => array('code' => 12003, 'message' => '活动未开始',),
        'offering_not_enough' => array('code' => 12004, 'message' => '祭品不足',),
        'event_not_exist' => array('code' => 12005, 'message' => '活动不存在',),
        'event_count_max' => array('code' => 12006, 'message' => '参加活动次数已达上限',),
        'challenges_not_enough' => array('code' => 12007, 'message' => '挑战次数不足',),
        'arena_data_need_to_update' => array('code' => 12008, 'message' => '竞技场数据需要更新',),
        'fight_to_self' => array('code' => 12009, 'message' => '不能与自己对战',),
        'arena_battle_error' => array('code' => 12010, 'message' => '尚未开始挑战',),
        'arena_challenges_max' => array('code' => 12011, 'message' => '请先使用剩余挑战次数后再购买',),
        'arena_honour_not_enough' => array('code' => 12012, 'message' => '荣誉值不够',),
        'league_battle_formation_timeout' => array('code' => 12013, 'message' => '编组超时',),
        'league_battle_start' => array('code' => 12014, 'message' => '已经有公会成员在攻打该据点',),
        'league_battle_hold' => array('code' => 12015, 'message' => '据点已经被占领',),
        'league_battle_challenges_not_enough' => array('code' => 12016, 'message' => '挑战次数不够',),
        'league_battle_error' => array('code' => 12017, 'message' => '还没有开始攻打',),
        'league_battle_timeout' => array('code' => 12018, 'message' => '战斗超时',),
        'league_battle_fight_error' => array('code' => 12019, 'message' => '战队数据异常',),
        'league_battle_challenges_max' => array('code' => 12020, 'message' => '请先使用剩余挑战次数后再购买',),
        'league_battle_lose' => array('code' => 12021, 'message' => '没有在公会活动中获胜',),
        'league_battle_idol_activation' => array('code' => 12022, 'message' => '神像已经开启',),
        'league_battle_idol_not_activation' => array('code' => 12023, 'message' => '神像尚未激活',),
        'league_battle_already_worship_today' => array('code' => 12024, 'message' => '今天已经参拜过',),
        'activity_bonus_not_received' => array('code' => 12025, 'message' => '尚未领取单倍奖品',),
        'activity_bonus_free_first' => array('code' => 12026, 'message' => '还没有领取免费奖励',),
        'activity_bonus_end_this_month' => array('code' => 12027, 'message' => '本月奖励已经全部领完',),
        'arena_opponent_not_in_list' => array('code' => 12028, 'message' => '对手不在列表中',),
        'babel_completed' => array('code' => 12029, 'message' => '通天塔已经全部完成',),
        'babel_reward_first' => array('code' => 12030, 'message' => '请先领取通关奖励',),
        'babel_not_win' => array('code' => 12031, 'message' => '通天塔战役尚未胜利',),
        'babel_refresh_count_not_enough' => array('code' => 12032, 'message' => '通天塔刷新次数不足',),
        'babel_free_refresh_first' => array('code' => 12033, 'message' => '请先使用通天塔免费刷新次数',),
        'vality_grant_received_today' => array('code' => 12034, 'message' => '今天体力已经领取',),
        'god_battle_count_not_enough' => array('code' => 12035, 'message' => '神之试炼挑战次数不足',),
        'god_battle_not_comply_rule' => array('code' => 12036, 'message' => '伙伴条件不符合规则',),
        'abyss_battle_lock' => array('code' => 12037, 'message' => 'BOSS暂时不能攻打',),
        'abyss_battle_cd' => array('code' => 12038, 'message' => '还在冷却时间中',),
        'abyss_battle_cd_over' => array('code' => 12039, 'message' => '冷却时间已经结束',),
        'abyss_battle_boss_dead' => array('code' => 12040, 'message' => 'BOSS已经被击杀',),
        'abyss_battle_not_start' => array('code' => 12041, 'message' => '尚未开始战斗',),
        'life_death_no_bonus' => array('code' => 12042, 'message' => '没有掉落任何奖品',),
        'life_death_reset_today' => array('code' => 12043, 'message' => '今天已经重置过',),
        'life_death_battle_not_start' => array('code' => 12044, 'message' => '尚未开始战斗',),
        'life_death_not_clear' => array('code' => 12045, 'message' => '尚未通关所有关卡',),
        'life_death_not_fail_to_restart' => array('code' => 12046, 'message' => '尚未挑战失败,不能重新开始',),
        'life_death_not_fail_to_buy' => array('code' => 12047, 'message' => '尚未挑战失败,不能购买奖励',),
        'life_death_cannot_give_up' => array('code' => 12048, 'message' => '尚不能放弃挑战',),
        'life_death_cannot_fight' => array('code' => 12049, 'message' => '尚不能开始战斗',),
        'arena_lock' => array('code' => 12050, 'message' => '尚未解锁竞技场',),
        'novice_login_bonus_received' => array('code' => 12052, 'message' => '今天的新手奖励已经领取',),
        'novice_login_finished' => array('code' => 12053, 'message' => '所有的新手奖励已经全部领完',),
        'daily_register_wrong_day' => array('code' => 12054, 'message' => '每日签到天数错误',),
        'league_not_join_fight' => array('code' => 12055, 'message' => '公会没有参加本次公会战',),
        'league_fight_kit_not_enough' => array('code' => 12056, 'message' => '锦囊数量不足',),
        'league_fight_buy_count_max' => array('code' => 12057, 'message' => '公会战挑战购买次数已达最大',),
        'league_fight_buy_assault_max' => array('code' => 12058, 'message' => '公会战突击购买次数已达最大',),
        'league_fight_count_not_enough' => array('code' => 12059, 'message' => '公会战挑战次数不足',),
        'league_fight_hold_already_occupied' => array('code' => 12060, 'message' => '公会战据点已被占领',),
        'league_fight_win' => array('code' => 12061, 'message' => '你的公会已经在公会战中取得胜利',),
        'league_fight_lose' => array('code' => 12062, 'message' => '你的公会已经在公会战中被打败',),
        'league_fight_battle_over' => array('code' => 12063, 'message' => '本场战斗已经结束',),
        'league_fight_hold_hp_max' => array('code' => 12064, 'message' => '未被占领据点耐久度已最大',),
        'babel_sweeping' => array('code' => 12065, 'message' => '正在扫荡通天塔',),
        'daily_register_double_over' => array('code' => 12066, 'message' => '补领时间已经结束',),
        'rank_repeat' => array('code' => 12067, 'message' => '竞技场排名重复',),
        'online_bonus_received' => array('code' => 12068, 'message' => '本时段在线奖励已经领取过',),
        'online_bonus_not_come' => array('code' => 12069, 'message' => '在线时间尚未达成',),
        'lucky_cat_not_enough' => array('code' => 12070, 'message' => '猫男爵次数不足',),
        'lucky_cat_remain_count' => array('code' => 12071, 'message' => '猫男爵还有挑战次数没有使用',),
        'god_battle_remain_count' => array('code' => 12072, 'message' => '神之试炼还有挑战次数没有使用',),
        'babel_partner_dead' => array('code' => 12073, 'message' => '已经阵亡的伙伴不能继续战斗',),
        'babel_overstep_sweep_max' => array('code' => 12074, 'message' => '当前楼层已经超出通天塔可扫荡的最大层数',),
        'babel_not_sweeping' => array('code' => 12074, 'message' => '通天塔尚未处于扫荡状态',),
        'babel_sweep_completed' => array('code' => 12075, 'message' => '通天塔扫荡已经完成',),
        'babel_not_start' => array('code' => 12076, 'message' => '尚未进行任何战斗',),
        'login_continuous_bonus_received' => array('code' => 12077, 'message' => '连续登录奖励已领取',),
        'new_server_bonus_received' => array('code' => 12078, 'message' => '新服红包已领取',),
        'fate_over' => array('code' => 12079, 'message' => '命运之轮已经全部转完',),
        'league_boss_reborn' => array('code' => 12080, 'message' => '公会boss已经重新复活',),
        'league_arena_area_not_exist' => array('code' => 12081, 'message' => '区域不存在',),
        'league_arena_partner_repeat' => array('code' => 12082, 'message' => '伙伴已经在其他上阵队伍中',),
        'league_arena_battle_error' => array('code' => 12083, 'message' => '战斗信息错误',),
        'league_arena_no_battle' => array('code' => 12084, 'message' => '队伍没有上榜作战',),
        'league_arena_battle_end' => array('code' => 12085, 'message' => '战斗已经结束',),
        'league_arena_not_allow_change' => array('code' => 12086, 'message' => '当前不允许调整队伍',),
        'league_not_reg' => array('code' => 12087, 'message' => '所属公会尚未报名参战',),
        'daily_register_complete' => array('code' => 12088, 'message' => '本月奖励已领取完毕',),

        /******************** 活 动 ********************/

        /******************** 副 本 ********************/
        'instance_error' => array('code' => 13001, 'message' => '副本不存在',),
        'pre_instance_not_complete' => array('code' => 13002, 'message' => '前置副本尚未完成',),
        'instance_count_max_today' => array('code' => 13003, 'message' => '副本完成次数超过上限',),
        'instance_complete_count_not_enough' => array('code' => 13004, 'message' => '指定副本完成次数不够',),
        'instance_group_complete_count_not_enough' => array('code' => 13005, 'message' => '指定副本组完成次数不够',),
        'instance_difficulty_complete_count_not_enough' => array('code' => 13005, 'message' => '指定副本难度完成次数不够',),
        'instance_not_complete' => array('code' => 13006, 'message' => '副本尚未完成',),
        'instance_create_time_no_limit' => array('code' => 13007, 'message' => '副本创建次数没有限制',),
        'instance_create_time_remain' => array('code' => 13008, 'message' => '副本创建次数没有用完',),
        'instance_reset_count_max' => array('code' => 13009, 'message' => '副本重置次数已经用完',),
        'instance_lock' => array('code' => 13010, 'message' => '副本尚未解锁',),
        'instance_map_bonus_received' => array('code' => 13011, 'message' => '副本星数奖励已经领取',),
        'instance_not_allow_sweep' => array('code' => 13012, 'message' => '副本不能扫荡',),
        'instance_completed' => array('code' => 13013, 'message' => '副本已经完成',),
        'instance_reset_not_allow' => array('code' => 13014, 'message' => '副本不能重置',),
        'instance_not_open' => array('code' => 13015, 'message' => '副本暂不开放',),
        'dynamic_error' => array('code' => 14101, 'message' => '对战副本不存在',),

        /******************** 副 本 ********************/


    ),

);