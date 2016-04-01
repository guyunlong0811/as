<?php
return array(

    'PROTOCOL' => array(

        /*************************************************基础模块*************************************************/

        'User' => array(

            'fast' => array(
                'isToken' => false,
                'params' => array(
                    'base_version' => array('type' => 'string',),
                    'mac' => array('type' => 'string', 'regex' => '/^\w|-{15,64}$/',),
                    'udid' => array('type' => 'string', 'regex' => '/^\w|-{15,64}$/',),
                    'channel_id' => array('type' => 'number',),
                    'channel_uid' => array('type' => 'string',),
                    'channel_token' => array('type' => 'string',),
                    'channel_type' => array('type' => 'string',),
                    'adid' => array('type' => 'string',),
                    'tid' => array('type' => 'number',),
                    'pf' => array('type' => 'string',),
                    'pfkey' => array('type' => 'string',),
                    'paytoken' => array('type' => 'string',),
                ),
            ),

            'register' => array(
                'isToken' => false,
                'params' => array(
                    'username' => array('type' => 'string', 'regex' => '/^\w{6,16}$/',),
                    'password' => array('type' => 'string', 'regex' => '/^\w{32}$/',),
                    'udid' => array('type' => 'string', 'regex' => '/^\w{15,64}$/',),
                    'channel_id' => array('type' => 'number',),
                    'channel_uid' => array('type' => 'string',),
                ),
            ),

            'login' => array(
                'isToken' => false,
                'params' => array(
                    'base_version' => array('type' => 'string',),
                    'username' => array('type' => 'string', 'regex' => '/^\w{6,16}$/',),
                    'password' => array('type' => 'string', 'regex' => '/^\w{32}$/',),
                    'channel_id' => array('type' => 'number',),
                    'udid' => array('type' => 'string', 'regex' => '/^\w{15,64}$/',),
                    'pts' => array('type' => 'number',),
                    'tid' => array('type' => 'number',),
                ),
            ),

            'newGame' => array(
                'behave' => 'new_game',
                'params' => array(
                    'mac' => array('type' => 'string', 'regex' => '/^\w|-{15,64}$/',),
                    'udid' => array('type' => 'string', 'regex' => '/^\w|-{15,64}$/',),
                    'channel_id' => array('type' => 'number',),
                    'nickname' => array('type' => 'string',),
                ),
            ),

            'device' => array(
                'params' => array(
                    'name' => array('type' => 'string',),
                    'system' => array('type' => 'number',),
                    'version' => array('type' => 'string',),
                    'token' => array('type' => 'string',),
                ),
            ),

            'usernameCheck' => array(
                'isToken' => false,
                'params' => array(
                    'username' => array('type' => 'string', 'regex' => '/^\w{6,16}$/',),
                ),
            ),

            'email' => array(
                'params' => array(
                    'email' => array('type' => 'string', 'regex' => '/^(\w)+(\.\w+)*@(\w)+((\.\w+)+)$/',),
                ),
            ),

            'phone' => array(
                'params' => array(
                    'phone' => array('type' => 'number', 'regex' => '/^1\d{10}$/',),
                ),
            ),

            'changePwd' => array(
                'params' => array(
                    'password' => array('type' => 'string', 'regex' => '/^\w{32}$/',),
                    'newPassword' => array('type' => 'string', 'regex' => '/^\w{32}$/',),
                    'pts' => array('type' => 'number',),
                ),
            ),

            'binding' => array(
                'params' => array(
                    'username' => array('type' => 'string', 'regex' => '/^\w{6,16}$/',),
                    'password' => array('type' => 'string', 'regex' => '/^\w{32}$/',),
                    'udid' => array('type' => 'string', 'regex' => '/^\w{15,64}$/',),
                ),
            ),

            'complete' => array(
                'params' => array(
                    'username' => array('type' => 'string', 'regex' => '/^\w{6,16}$/',),
                    'password' => array('type' => 'string', 'regex' => '/^\w{32}$/',),
                    'udid' => array('type' => 'string', 'regex' => '/^\w{15,64}$/',),
                ),
            ),


            'ident' => array(
                'params' => array(
                    'realname' => array('type' => 'string', 'regex' => '/^[\x{4e00}-\x{9fa5}]{2,4}$/',),
                    'ident' => array('type' => 'number', 'regex' => '/^[1-9]\d{16}(\d|x)$/',),
                ),
            ),

            'stay' => array(),

            'refresh' => array(
                'params' => array(
                    'pf' => array('type' => 'string',),
                    'pfkey' => array('type' => 'string',),
                    'paytoken' => array('type' => 'string',),
                ),
            ),

        ),

        'Pay' => array(

            'getList' => array(),

            'launch' => array(
                'params' => array(
                    'cash_id' => array('type' => 'number',),
                ),
            ),

            'cancel' => array(
                'params' => array(
                    'order_id' => array('type' => 'string', 'regex' => '/^[0-9_]{12,21}$/',),
                ),
            ),

            'successIOS' => array(
                'key' => 'status',
                'behave' => 'pay',
                'params' => array(
                    'order_id' => array('type' => 'string', 'regex' => '/^[0-9_]{12,21}$/',),
                    'receipt' => array('type' => 'string',),
                    'is_sandbox' => array('type' => 'number',),
                ),
            ),

            'confirm' => array(
                'key' => 'status',
                'params' => array(
                    'order_id' => array('type' => 'string', 'regex' => '/^[0-9_]{12,21}$/',),
                    'pf' => array('type' => 'string',),
                    'pfkey' => array('type' => 'string',),
                    'paytoken' => array('type' => 'string',),
                ),
            ),

            'fail' => array(
                'params' => array(
                    'order_id' => array('type' => 'string', 'regex' => '/^[0-9_]{12,21}$/',),
                    'comment' => array('type' => 'string',),
                ),
            ),

        ),

        'Pray' => array(

            'getUtime' => array(
                'key' => 'list',
            ),

            'drawFree' => array(
                'key' => 'list',
                'behave' => 'pray_free',
                'params' => array(
                    'pray_id' => array('type' => 'number',),
                ),
            ),

            'drawNow' => array(
                'key' => 'list',
                'behave' => 'pray_pay',
                'params' => array(
                    'pray_id' => array('type' => 'number',),
                ),
            ),

        ),

        'PrayTimed' => array(

            'getInfo' => array(),

            'drawFree' => array(
                'behave' => 'pray_timed_free',
                'params' => array(
                    'pray_id' => array('type' => 'number',),
                ),
            ),

            'drawNow' => array(
                'behave' => 'pray_timed_pay',
                'params' => array(
                    'pray_id' => array('type' => 'number',),
                ),
            ),

        ),

        'Team' => array(

            'getInfo' => array(),

            'getCurrencyInfo' => array(),

            'setIcon' => array(
                'params' => array(
                    'icon' => array('type' => 'string',),
                ),
            ),

            'autoAdd' => array(
                'behave' => 'auto_add',
            ),

            'buyGold' => array(
                'behave' => 'buy_gold',
                'params' => array(
                    'count' => array('type' => 'number', 'gt' => '0',),
                ),
            ),

            'buyVality' => array(
                'behave' => 'buy_vality',
            ),

            'buySkillPoint' => array(
                'behave' => 'buy_skill_point',
            ),

            'share' => array(),

            'mini' => array(
                'params' => array(
                    'kill' => array('type' => 'number',),
                ),
            ),

            'update' => array(
                'behave' => 'auto_add',
                'params' => array(
                    'notice_id' => array('type' => 'number',),
                ),
            ),

        ),

        'Member' => array(

            'getList' => array(
                'key' => 'list',
            ),

            'receive' => array(
                'behave' => 'member_bonus',
                'params' => array(
                    'member_id' => array('type' => 'number',),
                ),
            ),

        ),

        'Vip' => array(

            'getBonusList' => array(
                'key' => 'list',
            ),

            'receive' => array(
                'behave' => 'vip_bonus',
                'params' => array(
                    'vip_id' => array('type' => 'number',),
                ),
            ),

        ),

        'Shop' => array(

            'getList' => array(
                'params' => array(
                    'shop_type' => array('type' => 'number',),
                ),
            ),

            'buy' => array(
                'behave' => 'shop_buy',
                'params' => array(
                    'shop_type' => array('type' => 'number',),
                    'goods_no' => array('type' => 'number',),
                ),
            ),

            'refreshNow' => array(
                'behave' => 'shop_refresh',
                'params' => array(
                    'shop_type' => array('type' => 'number',),
                ),
            ),

        ),

        'Operate' => array(

            'notice' => array(
                'key' => 'list',
            ),

            'service' => array(
                'key' => 'content',
            ),

            'exchange' => array(
                'params' => array(
                    'code' => array('type' => 'string',),
                ),
            ),

            'activation' => array(
                'params' => array(
                    'code' => array('type' => 'string',),
                ),
            ),

            'cheat' => array(
                'params' => array(
                    'type' => array('type' => 'number',),
                    'value' => array('type' => 'string',),
                    'normal' => array('type' => 'string',),
                ),
            ),

            'quit' => array(
                'params' => array(
                    'scene' => array('type' => 'number',),
                ),
            ),

            'getActivityList' => array(
                'key' => 'list',
            ),

        ),

        'Mail' => array(

            'getAll' => array(
                'key' => 'list',
            ),

            'getAnnex' => array(
                'params' => array(
                    'mail_id' => array('type' => 'number',),
                ),
            ),

            'getAnnexAll' => array(
                'key' => 'list',
            ),

            'read' => array(
                'params' => array(
                    'mail_id' => array('type' => 'number',),
                ),
            ),

            'getNewCount' => array(
                'key' => 'count',
            ),

        ),

        'Guide' => array(

            'getList' => array(
                'key' => 'list',
            ),

            'complete' => array(
                'params' => array(
                    'step1' => array('type' => 'number',),
                    'step2' => array('type' => 'number',),
                ),
            ),

            'skip' => array(),

        ),

        /*************************************************基础模块*************************************************/

        /*************************************************养成模块*************************************************/

        'Partner' => array(

            'getList' => array(
                'key' => 'list',
            ),

            'call' => array(
                'behave' => 'partner_call',
                'params' => array(
                    'group' => array('type' => 'number',),
                ),
            ),

            'upgrade' => array(
                'behave' => 'partner_upgrade',
                'params' => array(
                    'group' => array('type' => 'number',),
                ),
            ),

            'skillLevelup' => array(
                'behave' => 'partner_skill_levelup',
                'params' => array(
                    'group' => array('type' => 'number',),
                    'skill' => array('type' => 'json',),
                ),
            ),

            'awake' => array(
                'behave' => 'partner_awake',
                'params' => array(
                    'group' => array('type' => 'number',),
                ),
            ),

            'setForce' => array(
                'behave' => 'partner_set_force',
                'params' => array(
                    'list' => array('type' => 'json',),
                ),
            ),

        ),

        'Equip' => array(

            'strengthenAll' => array(
                'behave' => 'equip_strengthen',
                'params' => array(
                    'group' => array('type' => 'number',),
                ),
            ),

            'strengthen' => array(
                'behave' => 'equip_strengthen',
                'params' => array(
                    'group' => array('type' => 'number',),
                    'level' => array('type' => 'number', 'gt' => '0',),
                ),
            ),

            'upgrade' => array(
                'behave' => 'equip_upgrade',
                'params' => array(
                    'group' => array('type' => 'number',),
                ),
            ),

            'enchantLock' => array(
                'behave' => 'equip_enchant_lock',
                'params' => array(
                    'group' => array('type' => 'number',),
                    'extra' => array('type' => 'number',),
                ),
            ),

            'enchantUnlock' => array(
                'params' => array(
                    'group' => array('type' => 'number',),
                    'extra' => array('type' => 'number',),
                ),
            ),

            'enchantNormal' => array(
                'behave' => 'equip_enchant',
                'params' => array(
                    'group' => array('type' => 'number',),
                ),
            ),

            'enchantDiamond' => array(
                'behave' => 'equip_enchant_diamond',
                'params' => array(
                    'group' => array('type' => 'number',),
                ),
            ),

            'enchantCover' => array(
                'params' => array(
                    'group' => array('type' => 'number',),
                ),
            ),

            'enchantOffer' => array(
                'behave' => 'equip_enchant_offer',
                'params' => array(
                    'material_id' => array('type' => 'number',),
                    'count' => array('type' => 'number', 'gt' => '0',),
                ),
            ),

        ),

        'Emblem' => array(

            'getList' => array(
                'key' => 'list',
            ),

            'equip' => array(
                'params' => array(
                    'emblem_id' => array('type' => 'number',),
                    'partner' => array('type' => 'number',),
                    'slot' => array('type' => 'number',),
                ),
            ),

            'unload' => array(
                'params' => array(
                    'partner' => array('type' => 'number',),
                    'slot' => array('type' => 'number',),
                ),
            ),

            'decompose' => array(
                'key' => 'list',
                'behave' => 'emblem_decompose',
                'params' => array(
                    'emblem_id' => array('type' => 'number',),
                ),
            ),

            'sell' => array(
                'key' => 'list',
                'behave' => 'emblem_sell',
                'params' => array(
                    'emblem_id' => array('type' => 'number',),
                ),
            ),

            'combine' => array(
                'key' => 'list',
                'behave' => 'emblem_combine',
                'params' => array(
                    'emblem_combine_id' => array('type' => 'number',),
                ),
            ),

        ),

        'Star' => array(

            'getList' => array(),

            'levelup' => array(
                'behave' => 'star_levelup',
                'params' => array(
                    'position' => array('type' => 'number',),
                ),
            ),

            'equip' => array(
                'params' => array(
                    'position' => array('type' => 'number',),
                    'partner' => array('type' => 'number',),
                ),
            ),

            'unload' => array(
                'params' => array(
                    'position' => array('type' => 'number',),
                ),
            ),

            'baptizeGold' => array(
                'behave' => 'star_baptize_gold',
                'params' => array(
                    'position' => array('type' => 'number',),
                ),
            ),

            'baptizeDiamond' => array(
                'behave' => 'star_baptize_diamond',
                'params' => array(
                    'position' => array('type' => 'number',),
                ),
            ),

            'baptizeCover' => array(
                'params' => array(
                    'position' => array('type' => 'number',),
                ),
            ),

//            'reset' => array(
//                'behave' => 'star_reset',
//            ),

        ),

        'Item' => array(

            'getList' => array(
                'key' => 'list',
            ),

            'sell' => array(
                'behave' => 'item_sell',
                'params' => array(
                    'item' => array('type' => 'number',),
                    'count' => array('type' => 'number', 'gt' => '0',),
                ),
            ),

            'sellAll' => array(
                'behave' => 'item_sell',
                'params' => array(
                    'list' => array('type' => 'json',),
                ),
            ),

            'toUse' => array(
                'key' => 'list',
                'behave' => 'item_use',
                'params' => array(
                    'item' => array('type' => 'number',),
                    'count' => array('type' => 'number', 'gt' => '0',),
                    'partner' => array('type' => 'number',),
//                    'params' => array('type' => 'string',),
                ),
            ),

        ),

        /*************************************************养成模块*************************************************/

        /*************************************************任务模块*************************************************/

        'Quest' => array(

            'getList' => array(
                'key' => 'list',
            ),

            'complete' => array(
                'behave' => 'quest_complete',
                'params' => array(
                    'quest_id' => array('type' => 'number',),
                    'quest_type' => array('type' => 'number',),
                ),
            ),

        ),

        'QuestDaily' => array(

            'getList' => array(
                'key' => 'list',
            ),

            'complete' => array(
                'behave' => 'quest_daily_complete',
                'params' => array(
                    'quest_id' => array('type' => 'number',),
                ),
            ),

        ),

        'QuestPartner' => array(

            'getList' => array(
                'key' => 'list',
            ),

            'complete' => array(
                'behave' => 'partner_quest_complete',
                'params' => array(
                    'quest_id' => array('type' => 'number',),
                ),
            ),

            'fight' => array(
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'win' => array(
                'key' => 'drop',
                'behave' => 'quest_partner_win',
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                    'star' => array('type' => 'number',),
                    'combo' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                ),
            ),

            'lose' => array(
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                ),
            ),

        ),

        'Achievement' => array(

            'getInfo' => array(),

            'complete' => array(
                'behave' => 'achievement_complete',
                'params' => array(
                    'achieve_id' => array('type' => 'number',),
                ),
            ),

        ),

        'Fund' => array(

            'getInfo' => array(),

            'buy' => array(
                'behave' => 'fund_buy',
            ),

            'receive' => array(
                'behave' => 'fund_receive',
                'params' => array(
                    'level' => array('type' => 'number',),
                ),
            ),

        ),

        'Activity' => array(

            'getInfo' => array(),

            'receive' => array(
                'behave' => 'activity_receive',
                'params' => array(
                    'bonus_id' => array('type' => 'number',),
                ),
            ),

        ),


        /*************************************************任务模块*************************************************/

        /*************************************************社交模块*************************************************/
        'League' => array(

            'setup' => array(
                'behave' => 'league_setup',
                'params' => array(
                    'league_name' => array('type' => 'string',),
                ),
            ),

            'leave' => array(),

            'agreeApply' => array(
                'params' => array(
                    'applier_id' => array('type' => 'number',),
                ),
            ),

            'declineApply' => array(
                'params' => array(
                    'applier_id' => array('type' => 'number',),
                ),
            ),

            'fire' => array(
                'params' => array(
                    'target_tid' => array('type' => 'number',),
                ),
            ),

            'apply' => array(
                'key' => 'league_name',
                'params' => array(
                    'league_id' => array('type' => 'number',),
                ),
            ),

            'changePresident' => array(
                'behave' => 'league_change_president',
                'params' => array(
                    'target_tid' => array('type' => 'number',),
                ),
            ),

            'donate' => array(
                'behave' => 'league_donate',
                'params' => array(
                    'donate_type' => array('type' => 'number',),
                ),
            ),

            'getLeagueMemberList' => array(
                'key' => 'list',
            ),

            'getApplyingList' => array(
                'key' => 'list',
            ),

            'upgradeLeagueBuilding' => array(
                'behave' => 'league_upgrade',
                'params' => array(
                    'building_type' => array('type' => 'number',),
                ),
            ),

            'eat' => array(
                'behave' => 'league_eat',
            ),

            'setNotice' => array(
                'params' => array(
                    'notice' => array('type' => 'string',),
                ),
            ),

            'recommend' => array(
                'behave' => 'league_recommend',
            ),

            'getInfo' => array(),

            'getList' => array(
                'params' => array(
                    'league_id' => array('type' => 'number',),
                    'page' => array('type' => 'number',),
                ),
            ),

            'getFeed' => array(),

            'getDefenseInfo' => array(
                'params' => array(
                    'target_tid' => array('type' => 'number',),
                ),
            ),

            'appoint' => array(
                'behave' => 'league_appoint',
                'params' => array(
                    'target_tid' => array('type' => 'number',),
                    'position' => array('type' => 'number',),
                ),
            ),

        ),

        'Friend' => array(

            'getList' => array(
                'key' => 'list',
            ),

            'getApplyList' => array(
                'key' => 'list',
            ),

            'apply' => array(
                'params' => array(
                    'friend_tid' => array('type' => 'number',),
                ),
            ),

            'agree' => array(
                'params' => array(
                    'friend_tid' => array('type' => 'number',),
                ),
            ),

            'refuse' => array(
                'params' => array(
                    'friend_tid' => array('type' => 'number',),
                ),
            ),

            'remove' => array(
                'params' => array(
                    'friend_tid' => array('type' => 'number',),
                ),
            ),

            'sendVality' => array(
                'behave' => 'friend_sendVality',
                'params' => array(
                    'friend_tid' => array('type' => 'number',),
                ),
            ),

            'getVality' => array(
                'behave' => 'friend_getVality',
                'params' => array(
                    'friend_tid' => array('type' => 'number',),
                ),
            ),

        ),

        'Chat' => array(

            'sendPrivateMsg' => array(
                'params' => array(
                    'target_tid' => array('type' => 'number',),
                    'msg' => array('type' => 'string',),
                ),
            ),

            'getPrivateList' => array(
                'key' => 'list',
            ),

            'getPrivateMsg' => array(
                'key' => 'list',
                'params' => array(
                    'target_tid' => array('type' => 'number',),
                ),
            ),

            'sendWorldMsg' => array(
                'params' => array(
                    'msg' => array('type' => 'string',),
                ),
            ),

            'getWorldMsg' => array(
                'key' => 'list',
                'params' => array(
                    'last' => array('type' => 'number',),
                ),
            ),

            'sendLeagueMsg' => array(
                'params' => array(
                    'msg' => array('type' => 'string',),
                ),
            ),

            'getLeagueMsg' => array(
                'key' => 'list',
                'params' => array(
                    'last' => array('type' => 'number',),
                ),
            ),

            'getNoticeMsg' => array(
                'params' => array(
                    'last' => array('type' => 'number',),
                ),
            ),

        ),

        'Rank' => array(

            'getList' => array(
                'params' => array(
                    'type' => array('type' => 'number',),
                ),
            ),

            'getDefenseInfo' => array(
                'key' => 'list',
                'params' => array(
                    'target_tid' => array('type' => 'number',),
                ),
            ),

        ),
        /*************************************************社交模块*************************************************/

        /*************************************************活动模块*************************************************/

        'NoviceLogin' => array(

            'getInfo' => array(),

            'receive' => array(
                'behave' => 'novice_login_receive',
                'params' => array(
                    'day' => array('type' => 'number',),
                ),
            ),

        ),

        'LoginContinuous' => array(

            'getInfo' => array(),

            'receive' => array(
                'behave' => 'login_continuous_receive',
            ),

        ),

        'DailyRegister' => array(

            'getList' => array(),

            'receive' => array(
                'behave' => 'daily_register_receive',
            ),

            'receiveDouble' => array(
                'behave' => 'daily_register_receive',
                'params' => array(
                    'day' => array('type' => 'number',),
                ),
            ),

            'receiveNow' => array(
                'behave' => 'daily_register_receive_now',
                'params' => array(
                    'day' => array('type' => 'number',),
                ),
            ),

        ),

        'ValityGrant' => array(

            'getInfo' => array(
                'key' => 'count',
            ),

            'receive' => array(
                'behave' => 'vality_grant_receive',
            ),

        ),

        'MiracleLake' => array(

            'drop' => array(
                'key' => 'list',
                'behave' => 'miracle_lake_drop',
                'params' => array(
                    'drop_type' => array('type' => 'number',),
                    'drop_id' => array('type' => 'number',),
                    'drop_count' => array('type' => 'number', 'gt' => '0',),
                ),
            ),

        ),

        'OnlineBonus' => array(

            'getInfo' => array(
                'params' => array(
                    'second' => array('type' => 'number',),
                ),
            ),

            'setTime' => array(
                'params' => array(
                    'second' => array('type' => 'number',),
                ),
            ),

            'receive' => array(
                'behave' => 'online_bonus_receive',
                'params' => array(
                    'bonus_id' => array('type' => 'number',),
                    'second' => array('type' => 'number',),
                ),
            ),

        ),

        'LevelBonus' => array(

            'getList' => array(
                'key' => 'list',
            ),

            'receive' => array(
                'behave' => 'level_bonus_receive',
                'params' => array(
                    'bonus_id' => array('type' => 'number',),
                ),
            ),

        ),

        'Fate' => array(

            'getInfo' => array(),

            'round' => array(
                'behave' => 'fate_round',
            ),

        ),

        'NewServerBonus' => array(

            'getInfo' => array(),

            'receive' => array(
                'behave' => 'new_server_bonus_receive',
                'key' => 'list',
            ),

        ),

        /*************************************************活动模块*************************************************/

        /*************************************************战斗模块*************************************************/
        'Instance' => array(

            'getAllList' => array(),

            'getComboInfo' => array(
                'key' => 'list',
                'params' => array(
                    'instance' => array('type' => 'json',),
                ),
            ),

            'fight' => array(
                'behave' => 'instance_fight',
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'win' => array(
                'behave' => 'instance_win',
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                    'star' => array('type' => 'number',),
                    'combo' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                ),
            ),

            'lose' => array(
                'behave' => 'instance_lose',
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                ),
            ),

            'resetCount' => array(
                'behave' => 'instance_reset',
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                ),
            ),

            'sweep' => array(
                'key' => 'list',
                'behave' => 'instance_sweep',
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                    'times' => array('type' => 'number', 'gt' => '0',),
                ),
            ),

            'receiveMapBonus' => array(
                'behave' => 'instance_receive_map_bonus',
                'params' => array(
                    'bonus_id' => array('type' => 'number',),
                ),
            ),

        ),

        'LeagueQuest' => array(

            'getList' => array(
                'key' => 'list',
            ),

            'fight' => array(
                'behave' => 'league_quest_fight',
                'params' => array(
                    'quest_id' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'win' => array(
                'behave' => 'league_quest_win',
                'params' => array(
                    'quest_id' => array('type' => 'number',),
                    'combo' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                    'verify_target_partner' => array('type' => 'json',),
                ),
            ),

            'lose' => array(
                'params' => array(
                    'quest_id' => array('type' => 'number',),
                ),
            ),

        ),


        /*'LeagueBattle' =>  array(

            'getList' => array(),

            'getListSchedule' => array(
                'key' => 'list',
            ),

            'formation' => array(
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                ),
            ),

            'quit' => array(
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                ),
            ),

            'fight' => array(
                'behave' => 'league_battle_fight',
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'win' => array(
                'key' => 'drop',
                'behave' => 'league_battle_win',
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                    'current_partner' => array('type' => 'json',),
                ),
            ),

            'lose' => array(
                'behave' => 'league_battle_lose',
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                    'battle' => array('type' => 'number',),
                    'monster' => array('type' => 'json',),
                ),
            ),

            'getBuyCount' => array(),

            'buy' => array(
                'behave' => 'league_battle_buy_challenges',
                'params' => array(
                    'target_tid' => array('type' => 'number',),
                ),
            ),

            'getIdol' => array(),

            'activationIdol' => array(
                'behave' => 'league_battle_activation_idol',
            ),

            'worship' => array(
                'behave' => 'league_battle_worship',
            ),

        ),*/

        'Arena' => array(

            'getInfo' => array(),

            'getList' => array(
                'key' => 'list',
            ),

            'getBattleList' => array(
                'key' => 'list',
            ),

            'refresh' => array(),

            'setDefense' => array(
                'params' => array(
                    'defense_partner' => array('type' => 'json',),
                ),
            ),

            'fight' => array(
                'behave' => 'arena_fight',
                'params' => array(
                    'target_rank' => array('type' => 'number',),
                    'target_tid' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'win' => array(
                'behave' => 'arena_win',
                'params' => array(
                    'verify_partner' => array('type' => 'json',),
                    'verify_target_partner' => array('type' => 'json',),
                    'combo' => array('type' => 'number',),
                    'vcr' => array('type' => 'json',),
                ),
            ),

            'lose' => array(
                'behave' => 'arena_lose',
                'params' => array(
                    'vcr' => array('type' => 'json',),
                ),
            ),

            'buy' => array(
                'behave' => 'arena_buy_challenges',
            ),

        ),

        'Babel' => array(

            'getInfo' => array(),

            'fight' => array(
                'behave' => 'babel_fight',
                'params' => array(
                    'partner' => array('type' => 'json',),
                ),
            ),

            'win' => array(
                'key' => 'drop',
                'behave' => 'babel_win',
                'params' => array(
                    'star' => array('type' => 'number',),
                    'combo' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                    'partner_dead' => array('type' => 'json',),
                ),
            ),

            'lose' => array(
                'params' => array(
                    'partner_dead' => array('type' => 'json',),
                ),
            ),

            'refresh' => array(),

            'refreshNow' => array(
                'behave' => 'babel_refresh_now',
            ),

            'reward' => array(
                'behave' => 'babel_reward',
            ),

            'sweepStart' => array(),

            'sweepComplete' => array(
                'behave' => 'babel_sweep_complete',
            ),

            'sweepCompleteNow' => array(
                'behave' => 'babel_sweep_complete_now',
            ),

        ),

        'GodBattle' => array(

            'getCount' => array(
                'params' => array(
                    'event_group' => array('type' => 'number',),
                ),
            ),

            'fight' => array(
                'behave' => 'god_battle_fight',
                'params' => array(
                    'event_group' => array('type' => 'number',),
                    'battle_id' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'win' => array(
                'key' => 'drop',
                'behave' => 'god_battle_win',
                'params' => array(
                    'event_group' => array('type' => 'number',),
                    'battle_id' => array('type' => 'number',),
                    'combo' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                ),
            ),

            'lose' => array(
                'behave' => 'god_battle_lose',
                'params' => array(
                    'event_group' => array('type' => 'number',),
                    'battle_id' => array('type' => 'number',),
                ),
            ),

            'buy' => array(
                'behave' => 'god_battle_buy',
                'params' => array(
                    'event_group' => array('type' => 'number',),
                ),
            ),

        ),

        'AbyssBattle' => array(

            'getList' => array(),

            'clearCd' => array(
                'behave' => 'abyss_battle_clear',
            ),

            'fight' => array(
                'behave' => 'abyss_battle_fight',
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'end' => array(
                'behave' => 'abyss_battle_end',
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                    'damage' => array('type' => 'number',),
                    'combo' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                ),
            ),

        ),

        'LifeDeathBattle' => array(

            'getInfo' => array(),

            'fight' => array(
                'params' => array(
                    'partner' => array('type' => 'json',),
                ),
            ),

            'win' => array(
                'behave' => 'life_death_win',
                'params' => array(
                    'combo' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                    'verify_target_partner' => array('type' => 'json',),
                ),
            ),

            'lose' => array(),

            'giveUp' => array(
                'behave' => 'life_death_give_up',
            ),

            'buy' => array(
                'behave' => 'life_death_buy',
            ),

            'clear' => array(
                'behave' => 'life_death_clear',
            ),

            'restart' => array(),


        ),

        'LeagueFight' => array(

            'getTargetInfo' => array(),

            'getBattleInfo' => array(
                'params' => array(
                    'last' => array('type' => 'number',),
                ),
            ),

            'getSituation' => array(
                'key' => 'list',
            ),

            'useKit' => array(
                'key' => 'hold_id',
                'params' => array(
                    'kit_id' => array('type' => 'number',),
                ),
            ),

            'buyAssault' => array(
                'behave' => 'league_fight_buy_assault',
            ),

            'buyCount' => array(
                'behave' => 'league_fight_buy_challenges',
            ),

            'sendChatNotice' => array(
                'params' => array(
                    'msg' => array('type' => 'string',),
                ),
            ),

            'fight' => array(
                'behave' => 'league_fight_start',
                'params' => array(
                    'hold_id' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'end' => array(
                'key' => 'drop',
                'behave' => 'league_fight_end',
                'params' => array(
                    'hold_id' => array('type' => 'number',),
                    'damage' => array('type' => 'number',),
                    'combo' => array('type' => 'number',),
                    'duration' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                ),
            ),

        ),

        'LeagueArenaReg' => array(

            'getInfo' => array(),

            'register' => array(
                'behave' => 'league_arena_register',
                'params' => array(
                    'area' => array('type' => 'number',),
                ),
            ),

        ),

        'LeagueArena' => array(

            'getInfo' => array(
                'params' => array(
                    'area' => array('type' => 'number',),
                ),
            ),

            'getTeamList' => array(),

            'register' => array(
                'key' => 'battle_id',
                'params' => array(
                    'partner' => array('type' => 'json',),
                ),
            ),

            'ban' => array(
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                ),
            ),

            'change' => array(
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'fight' => array(
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                ),
            ),

            'win' => array(
                'behave' => 'league_arena_win',
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                ),
            ),

            'lose' => array(
                'behave' => 'league_arena_lose',
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                ),
            ),

        ),

        'LuckyCat' => array(

            'getCount' => array(
                'params' => array(
                    'event_group' => array('type' => 'number',),
                ),
            ),

            'fight' => array(
                'behave' => 'lucky_cat_fight',
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'end' => array(
                'behave' => 'lucky_cat_end',
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                    'damage' => array('type' => 'number',),
                    'combo' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                ),
            ),

            'buy' => array(
                'behave' => 'lucky_cat_buy',
                'params' => array(
                    'event_group' => array('type' => 'number',),
                ),
            ),

        ),

        'LeagueBoss' => array(

            'getList' => array(),

            'call' => array(
                'behave' => 'league_boss_call',
                'params' => array(
                    'site' => array('type' => 'number',),
                ),
            ),

            'callForce' => array(
                'behave' => 'league_boss_call_force',
                'params' => array(
                    'site' => array('type' => 'number',),
                ),
            ),

            'buff' => array(
                'behave' => 'league_boss_buff',
            ),

            'buffAll' => array(
                'behave' => 'league_boss_buff_all',
            ),

            'fight' => array(
                'behave' => 'league_boss_fight',
                'params' => array(
                    'site' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'end' => array(
                'behave' => 'league_boss_end',
                'params' => array(
                    'site' => array('type' => 'number',),
                    'damage' => array('type' => 'number',),
                    'combo' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                ),
            ),

        ),

        'RandomBattle' => array(

            'fight' => array(
                'behave' => 'random_battle_fight',
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'win' => array(
                'key' => 'drop',
                'behave' => 'random_battle_win',
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                    'star' => array('type' => 'number',),
                    'combo' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                ),
            ),

            'lose' => array(
                'behave' => 'random_battle_lose',
                'params' => array(
                    'instance_id' => array('type' => 'number',),
                ),
            ),

        ),

        'ExpireBattle' => array(

            'getList' => array(
                'key' => 'list',
            ),

            'fight' => array(
                'behave' => 'expire_battle_fight',
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                    'partner' => array('type' => 'json',),
                ),
            ),

            'win' => array(
                'key' => 'drop',
                'behave' => 'expire_battle_win',
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                    'combo' => array('type' => 'number',),
                    'verify_partner' => array('type' => 'json',),
                ),
            ),

            'lose' => array(
                'behave' => 'expire_battle_lose',
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                ),
            ),

            'sweep' => array(
                'key' => 'list',
                'behave' => 'expire_battle_sweep',
                'params' => array(
                    'battle_id' => array('type' => 'number',),
                    'times' => array('type' => 'number', 'gt' => '0',),
                ),
            ),

        ),
        /*************************************************战斗模块*************************************************/


    ),

);