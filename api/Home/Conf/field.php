<?php
return array(

    //游戏字段
    'FIELD' => array(

        //基础属性表
        1001 => 'SPartner.name',//名称
        1002 => 'SPartner.race',//种族
        1003 => 'SPartner.gender',//性别
        1004 => 'SPartner.age',//年龄
        1005 => 'SPartner.astro',//星座
        1006 => 'SPartner.blood',//血型
        1007 => 'SPartner.personality',//性格
        1008 => 'SPartner.icon',//头像
        1009 => 'SPartner.half_body',//半身像
        1010 => 'GPartner.level',//等级
        1011 => 'GPartner.exp',//经验
        1012 => 'GTeam.vality',//体力
        1013 => 'STeamLevel.vality_recover',//体力恢复
        1014 => 'STeamLevel.max_vality',//体力上限
        1015 => 'GTeam.energy',//气力
        1016 => 'STeamLevel.energy_recover',//气力恢复
        1017 => 'STeamLevel.max_energy',//气力上限
        1018 => 'SPartner.nickname',//昵称
        1019 => 'GTeam.brand',//品级
        1020 => 'STeam.grown',//成长
        1021 => 'SPartner.quality',//品质
        1022 => 'SPartner.tag',//标签
        1023 => 'SPartner.attribute',//系别
        1025 => 'SPartner.full_body',//全身像
        1026 => 'SPartner.union',//连携条件
        1027 => 'SPartner.battle_body',//战场形象
        1028 => 'SPartner.cost',//怪物出战COST
        1029 => 'SPartner.cost_return',//怪物回收COST
        1030 => 'SPartner.attack_tag',//攻击距离类型
        1031 => 'GTeam.nickname',//队伍名称
        1032 => 'GTeam.level',//队伍等级
        1033 => 'GTeam.exp',//队伍经验
        1034 => 'GPartner.soul',//神力
        1035 => 'GTeam.skill_point',//技能点
        1036 => 'SPartner.profession',//职业
        1037 => 'SPartner.type',//类型
        1038 => 'GCount.achievement',//类型

        //数值属性表
        2001 => 'phy',//主角/伙伴当前体质
        2002 => 'str',//主角/伙伴当前力量
        2003 => 'agi',//主角/伙伴当前敏捷
        2004 => 'int',//主角/伙伴当前智力

        2101 => 'curr_hp',//当前生命
        2102 => 'curr_hp_rat',//当前生命%

        2111 => 'basic_hp_regen_add',//基础生命回复加成
        2112 => 'basic_hp_regen_rat',//基础生命回复加成%
        2113 => 'basic_hp_regen',//当前生命回复

        2121 => 'basic_hp_limit',//当前生命上限
        2122 => 'basic_hp_limit_add',//基础生命上限加成
        2123 => 'basic_hp_limit_rat',//基础生命上限加成%

        2201 => 'curr_mp',//当前魔法
        2202 => 'curr_mp_rat',//当前魔法%

        2211 => 'basic_mp_regen_add',//基础魔法回复加成
        2212 => 'basic_mp_regen_rat',//基础魔法回复加成%
        2213 => 'basic_mp_regen',//当前魔法回复

        2221 => 'basic_mp_limit',//当前魔法上限
        2222 => 'basic_mp_limit_add',//基础魔法上限加成
        2223 => 'basic_mp_limit_rat',//基础魔法上限加成%

        2301 => 'curr_xp',//当前怒气
        2302 => 'curr_xp_rat',//当前怒气%

        2311 => 'basic_xp_limit',//当前怒气上限
        2312 => 'basic_xp_limit_add',//基础怒气上限加成
        2313 => 'basic_xp_limit_rat',//基础怒气上限加成%

        2321 => 'xp_time_grow_add',//基础自然成长怒气加成
        2322 => 'xp_time_grow_rat',//基础自然成长怒气加成%

        2331 => 'xp_atk_grow_add',//基础攻击获取怒气加成
        2332 => 'xp_atk_grow_rat',//基础攻击获取怒气加成%

        2341 => 'xp_hit_grow_add',//基础受到攻击时的怒气加成
        2342 => 'xp_hit_grow_rat',//基础受到攻击时的怒气加成%

        2401 => 'curr_atk',//当前攻击
        2402 => 'curr_atk_add',//当前攻击加成
        2403 => 'basic_atk_rat',//基础攻击加成%
        2404 => 'curr_atk_rat',//当前攻击加成%

        2411 => 'curr_mag',//当前法术攻击
        2412 => 'curr_mag_add',//当前法术攻击加成
        2413 => 'basic_mag_rat',//基础法术攻击加成%
        2414 => 'curr_mag_rat',//当前法术攻击加成%

        2421 => 'curr_def',//当前防御
        2422 => 'curr_def_add',//当前防御加成
        2423 => 'basic_def_rat',//基础防御加成%
        2424 => 'curr_def_rat',//当前防御加成%

        2431 => 'curr_res',//当前法术抗性
        2432 => 'curr_res_add',//当前法术抗性加成
        2433 => 'basic_res_rat',//基础法术抗性加成%
        2434 => 'curr_res_rat',//当前法术抗性加成%

        2441 => 'curr_as',//当前攻击速度
        2442 => 'curr_as_add',//当前攻击速度加成
        2443 => 'basic_as_rat',//基础攻击速度加成%
        2444 => 'curr_as_rat',//当前攻击速度加成%

        2451 => 'curr_ms',//当前移动速度
        2452 => 'curr_ms_add',//当前移动速度加成
        2453 => 'basic_ms_rat',//基础移动速度加成%
        2454 => 'curr_ms_rat',//当前移动速度加成%

        2461 => 'curr_hit',//当前命中
        2462 => 'curr_hit_add',//当前命中加成
        2463 => 'basic_hit_rat',//基础命中加成%
        2464 => 'curr_hit_rat',//当前命中加成%

        2471 => 'curr_dodge',//当前闪避
        2472 => 'curr_dodge_add',//当前闪避加成
        2473 => 'basic_dodge_rat',//基础闪避加成%
        2474 => 'curr_dodge_rat',//当前闪避加成%

        2481 => 'curr_cri',//当前暴击
        2482 => 'curr_cri_add',//当前暴击加成
        2483 => 'basic_cri_rat',//基础暴击加成%
        2484 => 'curr_cri_rat',//当前暴击加成%

        2491 => 'curr_block',//当前招架
        2492 => 'curr_block_add',//当前招架加成
        2493 => 'basic_block_rat',//基础招架加成%
        2494 => 'curr_block_rat',//当前招架加成%

        2501 => 'curr_luck',//当前幸运
        2502 => 'curr_luck_add',//当前幸运加成
        2503 => 'basic_luck_rat',//基础幸运加成%
        2504 => 'curr_luck_rat',//当前幸运加成%

        2511 => 'curr_curse',//当前诅咒
        2512 => 'curr_curse_add',//当前诅咒加成
        2513 => 'basic_curse_rat',//基础诅咒加成%
        2514 => 'curr_curse_rat',//当前诅咒加成%

        2521 => 'curr_god',//当前神圣
        2522 => 'curr_god_add',//当前神圣加成
        2523 => 'basic_god_rat',//基础神圣加成%
        2524 => 'curr_god_rat',//当前神圣加成%

        2531 => 'curr_poison',//当前毒抗
        2532 => 'curr_poison_add',//当前毒抗加成
        2533 => 'basic_poison_rat',//基础毒抗加成%
        2534 => 'curr_poison_rat',//当前毒抗加成%

        2541 => 'curr_tough',//当前韧性
        2542 => 'curr_tough_add',//当前韧性加成
        2543 => 'basic_tough_rat',//基础韧性加成%
        2544 => 'curr_tough_rat',//当前韧性加成%

        2551 => 'curr_hs',//当前生命偷取
        2552 => 'curr_hs_add',//当前生命偷取加成
        2553 => 'basic_hs_rat',//基础生命偷取加成%
        2554 => 'curr_hs_rat',//当前生命偷取加成%

        2561 => 'curr_sv',//当前法术吸血
        2562 => 'curr_sv_add',//当前法术吸血加成
        2563 => 'basic_sv_rat',//基础法术吸血加成%
        2564 => 'curr_sv_rat',//当前法术吸血加成%

        2571 => 'curr_armor_pen',//当前护甲穿透
        2572 => 'curr_armor_pen_add',//当前护甲穿透加成
        2573 => 'basic_armor_pen_rat',//基础护甲穿透加成%
        2574 => 'curr_armor_pen_rat',//当前护甲穿透加成%

        2581 => 'curr_magic_pen',//当前法术穿透
        2582 => 'curr_magic_pen_add',//当前法术穿透加成
        2583 => 'basic_magic_pen_rat',//基础法术穿透加成%
        2584 => 'curr_magic_pen_rat',//当前法术穿透加成%

        2601 => 'curr_power',//当前战斗力

        //经济属性表
        3001 => 'GTeam.diamond',//宝石/点券
        3002 => 'GTeam.gold',//金币
        3003 => 'GArena.honour',//荣誉值
        3004 => 'GLeagueTeam.contribution',//贡献度
        3005 => 'GTeam.material_score',//贡献度

        //社会属性表
        4003 => 'GPartner.favour',//好感

        //状态属性表
        5001 => 'move',//可移动
        5002 => 'hit_down',//可被击倒
        5003 => 'normal_attack',//可使用普攻
        5004 => 'skill',//可使用技能
        5005 => 'attacked',//可被攻击
        5006 => 'healed',//可被治疗
        5007 => 'activated',//可被打醒

        //活动属性表
        6001 => 'fight.kit_1',//锦囊1
        6002 => 'fight.kit_1',//锦囊2
        6003 => 'fight.kit_1',//锦囊3

        //每日属性
        7001 => 'TDailyCount.7',

    ),

);

