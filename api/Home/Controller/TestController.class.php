<?php
namespace Home\Controller;

use Think\Controller;

class TestController extends Controller
{

    private $controller = 'User';//控制器名称
    private $action = 'login';//方法名称
    private $test;//测试数据
    private $host;//请求地址
    private $json = array();

    //测试数据
    const DEFAULT_UID = 10000;
    private $TestData = array(

        10000 => array(

            /*************************************************基础模块*************************************************/

            'User' => array(

                'fast' => array(
                    'adid' => '1',
                    'base_version' => '1.0.0.0',
                    'udid' => 'e05e175d4878543f79781bbc5a1d587b',
                    'mac' => 'e05e175d4878543f79781bbc5a1d587b',
                    'channel_id' => 1002,
                    'channel_uid' => '',
                    'channel_token' => '',
                    'tid' => 0,
                ),

                'login' => array(
                    'base_version' => '1.0.0.0',
                    'username' => 'guyunlong',
                    'password' => '123456',
                    'channel_id' => 1002,
                    'udid' => '1q2w3e4r5t6y7u8i9o0p',
                    'pts' => 0,
                    'tid' => 0,
                ),

                'usernameCheck' => array(
                    'username' => 'guyunlong123',
                ),

                'register' => array(
                    'username' => 'guyunlong',
                    'password' => '123456',
                    'udid' => '1q2w3e4r5t6y7u8i9o0p',
                    'channel_id' => 1001,
                    'channel_uid' => '76574534536',
                ),

                'changePwd' => array(
                    'password' => '654321',
                    'newPassword' => '123456',
                    'pts' => 0,
                ),

                'email' => array(
                    'email' => 'guyunlong_0811@126.com',
                ),

                'phone' => array(
                    'phone' => 13764426340,
                ),

                'binding' => array(
                    'username' => 'guyunlong',
                    'password' => '123456',
                    'udid' => '1q2w3e4r5t6y7u8i9o0p',
                ),

                'complete' => array(
                    'username' => 'guyunlong',
                    'password' => '123456',
                    'udid' => '1q2w3e4r5t6y7u8i9o0p',
                ),


                'ident' => array(
                    'realname' => '顾云龙',
                    'ident' => '310115198808112216',
                ),

                'newGame' => array(
                    'nickname' => '暴暴',
                    'channel_id' => 1002,
                    'udid' => '1q2w3e4r5t6y7u8i9o0p',
                    'mac' => 'e05e175d4878543f79781bbc5a1d587b',
                ),

                'device' => array(
                    'name' => 'iPhone4S',
                    'system' => 1,
                    'version' => '6.1.1',
                    'token' => 'd2hgd72dg6328tdg28hod2738dg267e323idg2637',
                ),

            ),

            'Pay' => array(

                'getList' => array(
                    'channel_id' => 1001,
                ),

                'launch' => array(
                    'cash_id' => 1,
                ),

                'cancel' => array(
                    'order_id' => '1_1407573773',
                ),

                'successIOS' => array(
                    'order_id' => '1_1408103850',
                    'receipt' => 'ewoJInNpZ25hdHVyZSIgPSAiQWg3VFFQNDA0eU1DVnlCOG42T0dCRGdtVVB0TnhLOWxyQU92cHVnZUU0dmJxcURSdkNHdGJ1bHFBOTBpMmtaS3JEY1d3YjRkYk5GL3pRcU9TVzNVdWRSTmFrdFlmYS92QjcvdWI5elRSbjNVL3owa2kvRkVZbjRhc2FXVFdmUUx6QjNDNFlLQk9CZmNxc1NCK25YTlBTZm5WTUR3NCt4ZHNrYXlHZ3BudGg3UkFBQURWekNDQTFNd2dnSTdvQU1DQVFJQ0NCdXA0K1BBaG0vTE1BMEdDU3FHU0liM0RRRUJCUVVBTUg4eEN6QUpCZ05WQkFZVEFsVlRNUk13RVFZRFZRUUtEQXBCY0hCc1pTQkpibU11TVNZd0pBWURWUVFMREIxQmNIQnNaU0JEWlhKMGFXWnBZMkYwYVc5dUlFRjFkR2h2Y21sMGVURXpNREVHQTFVRUF3d3FRWEJ3YkdVZ2FWUjFibVZ6SUZOMGIzSmxJRU5sY25ScFptbGpZWFJwYjI0Z1FYVjBhRzl5YVhSNU1CNFhEVEUwTURZd056QXdNREl5TVZvWERURTJNRFV4T0RFNE16RXpNRm93WkRFak1DRUdBMVVFQXd3YVVIVnlZMmhoYzJWU1pXTmxhWEIwUTJWeWRHbG1hV05oZEdVeEd6QVpCZ05WQkFzTUVrRndjR3hsSUdsVWRXNWxjeUJUZEc5eVpURVRNQkVHQTFVRUNnd0tRWEJ3YkdVZ1NXNWpMakVMTUFrR0ExVUVCaE1DVlZNd2daOHdEUVlKS29aSWh2Y05BUUVCQlFBRGdZMEFNSUdKQW9HQkFNbVRFdUxnamltTHdSSnh5MW9FZjBlc1VORFZFSWU2d0Rzbm5hbDE0aE5CdDF2MTk1WDZuOTNZTzdnaTNvclBTdXg5RDU1NFNrTXArU2F5Zzg0bFRjMzYyVXRtWUxwV25iMzRucXlHeDlLQlZUeTVPR1Y0bGpFMU93QytvVG5STStRTFJDbWVOeE1iUFpoUzQ3VCtlWnRERWhWQjl1c2szK0pNMkNvZ2Z3bzdBZ01CQUFHamNqQndNQjBHQTFVZERnUVdCQlNKYUVlTnVxOURmNlpmTjY4RmUrSTJ1MjJzc0RBTUJnTlZIUk1CQWY4RUFqQUFNQjhHQTFVZEl3UVlNQmFBRkRZZDZPS2RndElCR0xVeWF3N1hRd3VSV0VNNk1BNEdBMVVkRHdFQi93UUVBd0lIZ0RBUUJnb3Foa2lHOTJOa0JnVUJCQUlGQURBTkJna3Foa2lHOXcwQkFRVUZBQU9DQVFFQWVhSlYyVTUxcnhmY3FBQWU1QzIvZkVXOEtVbDRpTzRsTXV0YTdONlh6UDFwWkl6MU5ra0N0SUl3ZXlOajVVUllISytIalJLU1U5UkxndU5sMG5rZnhxT2JpTWNrd1J1ZEtTcTY5Tkluclp5Q0Q2NlI0Szc3bmI5bE1UQUJTU1lsc0t0OG9OdGxoZ1IvMWtqU1NSUWNIa3RzRGNTaVFHS01ka1NscDRBeVhmN3ZuSFBCZTR5Q3dZVjJQcFNOMDRrYm9pSjNwQmx4c0d3Vi9abEwyNk0ydWVZSEtZQ3VYaGRxRnd4VmdtNTJoM29lSk9PdC92WTRFY1FxN2VxSG02bTAzWjliN1BSellNMktHWEhEbU9Nazd2RHBlTVZsTERQU0dZejErVTNzRHhKemViU3BiYUptVDdpbXpVS2ZnZ0VZN3h4ZjRjemZIMHlqNXdOelNHVE92UT09IjsKCSJwdXJjaGFzZS1pbmZvIiA9ICJld29KSW05eWFXZHBibUZzTFhCMWNtTm9ZWE5sTFdSaGRHVXRjSE4wSWlBOUlDSXlNREUwTFRBNExUQTRJREl6T2pBMU9qTTJJRUZ0WlhKcFkyRXZURzl6WDBGdVoyVnNaWE1pT3dvSkluVnVhWEYxWlMxcFpHVnVkR2xtYVdWeUlpQTlJQ0prWmpaaFpURm1NalJrTURKaFpqSXlPVFJsTURaaVpHRm1aREJsTURobE9URXpOVE13Wm1JNUlqc0tDU0p2Y21sbmFXNWhiQzEwY21GdWMyRmpkR2x2YmkxcFpDSWdQU0FpTVRBd01EQXdNREV4T1RZM016YzVNU0k3Q2draVluWnljeUlnUFNBaU1TNHdJanNLQ1NKMGNtRnVjMkZqZEdsdmJpMXBaQ0lnUFNBaU1UQXdNREF3TURFeE9UWTNNemM1TVNJN0Nna2ljWFZoYm5ScGRIa2lJRDBnSWpFaU93b0pJbTl5YVdkcGJtRnNMWEIxY21Ob1lYTmxMV1JoZEdVdGJYTWlJRDBnSWpFME1EYzFOalF6TXpZNE9EVWlPd29KSW5WdWFYRjFaUzEyWlc1a2IzSXRhV1JsYm5ScFptbGxjaUlnUFNBaU9UY3lNRFV6TURVdE1ESTNSUzAwUXpWRUxUaENRMEl0UXpnM01qUXhNelJDTlVJMUlqc0tDU0p3Y205a2RXTjBMV2xrSWlBOUlDSmpiMjB1Wm05eVpYWmxjbWRoYldVdWVtVjBkR0ZwWkdWdGJ5NXpaWFF4SWpzS0NTSnBkR1Z0TFdsa0lpQTlJQ0k1TURjek1qTTJNakFpT3dvSkltSnBaQ0lnUFNBaVkyOXRMbVp2Y21WMlpYSm5ZVzFsTG1KaGRIUnNaV1JsYlc4aU93b0pJbkIxY21Ob1lYTmxMV1JoZEdVdGJYTWlJRDBnSWpFME1EYzFOalF6TXpZNE9EVWlPd29KSW5CMWNtTm9ZWE5sTFdSaGRHVWlJRDBnSWpJd01UUXRNRGd0TURrZ01EWTZNRFU2TXpZZ1JYUmpMMGROVkNJN0Nna2ljSFZ5WTJoaGMyVXRaR0YwWlMxd2MzUWlJRDBnSWpJd01UUXRNRGd0TURnZ01qTTZNRFU2TXpZZ1FXMWxjbWxqWVM5TWIzTmZRVzVuWld4bGN5STdDZ2tpYjNKcFoybHVZV3d0Y0hWeVkyaGhjMlV0WkdGMFpTSWdQU0FpTWpBeE5DMHdPQzB3T1NBd05qb3dOVG96TmlCRmRHTXZSMDFVSWpzS2ZRPT0iOwoJImVudmlyb25tZW50IiA9ICJTYW5kYm94IjsKCSJwb2QiID0gIjEwMCI7Cgkic2lnbmluZy1zdGF0dXMiID0gIjAiOwp9',
                    'is_sandbox' => 1,
                ),

                'confirm' => array(
                    'order_id' => '102000047_1436195357',
                ),

                'fail' => array(
                    'order_id' => '1_1408103665',
                    'comment' => '失败备注',
                ),

            ),

            'Pray' => array(

                'getUtime' => array(),

                'drawFree' => array(
                    'pray_id' => 1001,
                ),

                'drawNow' => array(
                    'pray_id' => 2010,
                ),

            ),

            'PrayTimed' => array(

                'getInfo' => array(),

                'drawFree' => array(
                    'pray_id' => 10001,
                ),

                'drawNow' => array(
                    'pray_id' => 10010,
                ),

            ),

            'Team' => array(

                'getInfo' => array(),

                'getCurrencyInfo' => array(),

                'setIcon' => array(
                    'icon' => 'partnerhead_1002',
                ),

                'update' => array(
                    'notice_id' => 0,
                ),

                'autoAdd' => array(),

                'buyGold' => array(
                    'count' => 10,
                ),

                'buyVality' => array(),

                'buySkillPoint' => array(),

                'share' => array(),

                'mini' => array(
                    'kill' => 1234,
                ),

            ),

            'Member' => array(

                'getList' => array(),

                'receive' => array(
                    'member_id' => 3001,
                ),

            ),

            'Vip' => array(

                'getBonusList' => array(),

                'receive' => array(
                    'vip_id' => 1001,
                ),

            ),

            'Shop' => array(

                'getList' => array(
                    'shop_type' => 401,
                ),

                'buy' => array(
                    'shop_type' => 401,
                    'goods_no' => 1,
                ),

                'refreshNow' => array(
                    'shop_type' => 401,
                ),

            ),

            'Operate' => array(

                'notice' => array(
                    'channel_id' => '1001',
                ),

                'service' => array(),

                'exchange' => array(
                    'code' => 'xz5gwvf',
                ),

                'activation' => array(
                    'code' => 'wfbzt54',
                ),

                'cheat' => array(
                    'type' => '1',
                    'value' => 'abc',
                    'normal' => 'aaa',
                ),

                'quit' => array(
                    'scene' => 1,
                ),

            ),

            'Mail' => array(

                'getAll' => array(),

                'getAnnex' => array(
                    'mail_id' => 1,
                ),

                'read' => array(
                    'mail_id' => 1,
                ),

                'getNewCount' => array(),
            ),

            'Guide' => array(

                'getList' => array(),

                'complete' => array(
                    'step1' => 3,
                    'step2' => 3,
                ),

                'skip' => array(
                    'step1' => 3001,
                ),

            ),


            /*************************************************基础模块*************************************************/

            /*************************************************养成模块*************************************************/

            'Partner' => array(

                'getList' => array(),

                'call' => array(
                    'group' => 1002,
                ),

                'upgrade' => array(
                    'group' => 1000,
                ),

                'skillLevelup' => array(
                    'group' => 1000,
                    'skill' => '{"1":10,"3":20,"5":30}',
                ),

                'awake' => array(
                    'group' => 1001,
                ),

                'setForce' => array(
                    'list' => '{"1001":2141,"1002":2031,"1007":1031}',
                ),

            ),

            'Equip' => array(

                'strengthenAll' => array(
                    'group' => 1004,
                ),

                'strengthen' => array(
                    'group' => 100101,
                    'level' => 10,
                ),

                'upgrade' => array(
                    'group' => 100101,
                ),

                'enchantLock' => array(
                    'group' => 100101,
                    'extra' => 4,
                ),

                'enchantUnlock' => array(
                    'group' => 100101,
                    'extra' => 1,
                ),

                'enchantNormal' => array(
                    'group' => 100101,
                ),

                'enchantDiamond' => array(
                    'group' => 100101,
                ),

                'enchantCover' => array(
                    'group' => 100101,
                ),

                'enchantOffer' => array(
                    'material_id' => 1,
                    'count' => 1,
                ),

            ),

            'Emblem' => array(

                'getList' => array(),

                'combine' => array(
                    'emblem_combine_id' => 1,
                ),

                'equip' => array(
                    'emblem_id' => 2,
                    'partner' => 1001,
                    'slot' => 1,
                ),

                'unload' => array(
                    'partner' => 1001,
                    'slot' => 1,
                ),

                'decompose' => array(
                    'emblem_id' => 6,
                ),

                'sell' => array(
                    'emblem_id' => 5,
                ),

            ),

            'Star' => array(

                'getList' => array(),

                'levelup' => array(
                    'position' => 1,
                ),

                'equip' => array(
                    'position' => 4,
                    'partner' => 1001,
                ),

                'unload' => array(
                    'position' => 1,
                ),

                'baptizeGold' => array(
                    'position' => 1,
                ),

                'baptizeDiamond' => array(
                    'position' => 1,
                ),

                'baptizeCover' => array(
                    'position' => 1,
                ),

//                'reset' => array(),

            ),

            'Item' => array(

                'getList' => array(),

                'sell' => array(
                    'item' => 1011001,
                    'count' => 1,
                ),

                'sellAll' => array(
                    'list' => '',
                ),

                'toUse' => array(
                    'item' => 1011001,
                    'count' => -1,
                    'partner' => 0,
                ),

            ),

            /*************************************************养成模块*************************************************/

            /*************************************************任务模块*************************************************/

            'Quest' => array(

                'getList' => array(),

                'complete' => array(
                    'quest_type' => 1,
                    'quest_id' => 11001,
                ),

            ),

            'QuestDaily' => array(

                'getList' => array(),

                'complete' => array(
                    'quest_id' => 2,
                ),

            ),

            'QuestPartner' => array(

                'getList' => array(),

                'complete' => array(
                    'quest_id' => 1000003,
                ),

                'fight' => array(
                    'instance_id' => 4010042,
                    'partner' => '[1001]',
                ),

                'win' => array(
                    'instance_id' => 4010041,
                    'star' => 0,
                    'combo' => 123,
                    'verify_partner' => '[]',
                ),

                'lose' => array(
                    'instance_id' => 4010042,
                    'combo' => 167,
                ),

            ),

            'Achievement' => array(

                'getInfo' => array(),

                'complete' => array(
                    'achieve_id' => 1,
                ),

            ),

            'Fund' => array(

                'getInfo' => array(),

                'buy' => array(),

                'receive' => array(
                    'level' => 20,
                ),

            ),

            'Activity' => array(

                'getInfo' => array(),

                'receive' => array(
                    'bonus_id' => 30,
                ),

            ),

            /*************************************************任务模块*************************************************/

            /*************************************************社交模块*************************************************/

            'Friend' => array(

                'getList' => array(),

                'getApplyList' => array(),

                'apply' => array(
                    'friend_tid' => 2,
                ),

                'agree' => array(
                    'friend_tid' => 2,
                ),

                'refuse' => array(
                    'friend_tid' => 2,
                ),

                'remove' => array(
                    'friend_tid' => 2,
                ),

                'sendVality' => array(
                    'friend_tid' => 2,
                ),

                'getVality' => array(
                    'friend_tid' => 2,
                ),

            ),


            'League' => array(

                'setup' => array(
                    'league_name' => '小小公会',
                ),

                'leave' => array(),

                'agreeApply' => array(
                    'applier_id' => 1,
                ),

                'declineApply' => array(
                    'applier_id' => 104,
                ),

                'fire' => array(
                    'target_tid' => 2,
                ),

                'apply' => array(
                    'league_id' => 1,
                ),

                'changePresident' => array(
                    'target_tid' => 2,
                ),

                'donate' => array(
                    'donate_type' => 3,
                ),

                'getLeagueMemberList' => array(),

                'getApplyingList' => array(),

                'upgradeLeagueBuilding' => array(
                    'building_type' => 1,
                ),

                'eat' => array(),

                'setNotice' => array(
                    'notice' => '公会公告公会公告公会公告公会公告公会公告公会公告公会公告公会公告',
                ),

                'recommend' => array(),

                'getInfo' => array(),

                'getList' => array(
                    'league_id' => 0,
                    'page' => 1,
                ),

                'getFeed' => array(),

                'appoint' => array(
                    'target_tid' => 101000001,
                    'position' => 2,
                ),

            ),

            'Chat' => array(

                'sendPrivateMsg' => array(
                    'target_tid' => 2,
                    'msg' => '大师你好！',
                ),

                'getPrivateList' => array(),

                'getPrivateMsg' => array(
                    'target_tid' => 2,
                ),

                'sendWorldMsg' => array(
                    'msg' => '我是暴暴！',
                ),

                'getWorldMsg' => array(
                    'last' => 1,
                ),

                'sendLeagueMsg' => array(
                    'msg' => '我是暴暴！',
                ),

                'getLeagueMsg' => array(
                    'last' => 1,
                ),

                'getNoticeMsg' => array(
                    'last' => 0,
                ),

            ),

            'Rank' => array(

                'getList' => array(
                    'type' => 102,
                ),

                'getDefenseInfo' => array(
                    'target_tid' => 101000003,
                ),

            ),

            /*************************************************社交模块*************************************************/

            /*************************************************活动模块*************************************************/

            'NoviceLogin' => array(

                'getInfo' => array(),

                'receive' => array(
                    'day' => 2,
                ),

            ),

            'LoginContinuous' => array(

                'getInfo' => array(),

                'receive' => array(),

            ),

            'NewServerBonus' => array(

                'getInfo' => array(),

                'receive' => array(),

            ),

            'DailyRegister' => array(

                'getList' => array(),

                'receive' => array(),

                'receiveDouble' => array(
                    'day' => 2,
                ),

                'receiveNow' => array(
                    'day' => 2,
                ),

            ),

            'ValityGrant' => array(
                'getInfo' => array(),
                'receive' => array(),
            ),

            'MiracleLake' => array(

                'drop' => array(
                    'drop_type' => 1,
                    'drop_id' => 10401001,
                    'drop_count' => 2,
                ),

            ),

            'OnlineBonus' => array(

                'getInfo' => array(
                    'second' => 0,
                ),

                'setTime' => array(
                    'second' => 300,
                ),

                'receive' => array(
                    'bonus_id' => 5,
                    'second' => 300,
                ),

            ),

            'LevelBonus' => array(

                'getList' => array(),

                'receive' => array(
                    'bonus_id' => 10,
                ),

            ),

            'Fate' => array(

                'getInfo' => array(),

                'round' => array(),

            ),

            /*************************************************活动模块*************************************************/

            /*************************************************战斗模块*************************************************/

            'Instance' => array(

                'getAllList' => array(),

                'getComboInfo' => array(
                    'instance' => '[10101,20101]',
                ),

                'fight' => array(
                    'instance_id' => 10101,
                    'partner' => '[1001]',
                ),

                'win' => array(
                    'instance_id' => 10101,
                    'star' => 7,
                    'combo' => 20101,
                    'verify_partner' => '[]',
                ),

                'lose' => array(
                    'instance_id' => 10101,
                ),

                'sweep' => array(
                    'instance_id' => 10101,
                    'times' => 10,
                ),

                'resetCount' => array(
                    'instance_id' => 20101,
                ),

                'receiveMapBonus' => array(
                    'bonus_id' => 1,
                ),

            ),

            'LeagueQuest' => array(

                'getList' => array(),

                'fight' => array(
                    'quest_id' => 3,
                    'partner' => '[1001,1002,1003,1004,1005]',
                ),

                'win' => array(
                    'quest_id' => 3,
                    'verify_partner' => '[]',
                    'verify_target_partner' => '[]',
                ),

                'lose' => array(
                    'quest_id' => 3,
                ),

            ),

            'LeagueBattle' => array(

                'getList' => array(),

                'getListSchedule' => array(),

                'getBuyCount' => array(),

                'buy' => array(
                    'target_tid' => 1,
                ),

                'formation' => array(
                    'instance_id' => 10101,
                ),

                'quit' => array(
                    'instance_id' => 10101,
                ),

                'fight' => array(
                    'instance_id' => 10101,
                    'partner' => '[1001,1002,1003,1004,1005]',
                ),

                'win' => array(
                    'instance_id' => 10101,
                    'verify_partner' => '[]',
                    'current_partner' => '[]',
                ),

                'lose' => array(
                    'instance_id' => 10101,
                    'verify_partner' => '[]',
                    'battle' => 2,
                    'monster' => '[{"id":10001,"hp":0},{"id":10002,"hp":0},{"id":10003,"hp":1000},{"id":10004,"hp":2000},{"id":10005,"hp":2300},{"id":10001,"hp":4000}]',
                ),

                'getIdol' => array(),

                'activationIdol' => array(),

                'worship' => array(),

            ),

            'Arena' => array(

                'getInfo' => array(),

                'getList' => array(
                    'force' => 10000,
                ),

                'getBattleList' => array(),

                'refresh' => array(),

                'setDefense' => array(
                    'defense_partner' => '[1000,1001,1002,1003,1006]',
                ),

                'fight' => array(
                    'target_rank' => 78,
                    'target_tid' => 78,
                    'partner' => '[1001]',
                ),

                'win' => array(
                    'verify_partner' => '[]',
                    'verify_target_partner' => '[]',
                    'vcr' => '[]',
                    'combo' => 300,
                ),

                'lose' => array(
                    'vcr' => '[]',
                ),

                'autoAdd' => array(),

                'buy' => array(),

            ),

            'Babel' => array(

                'getInfo' => array(),


                'fight' => array(
                    'partner' => '[1001,1002,1004,1005,1007]',
                ),

                'win' => array(
                    'star' => 7,
                    'combo' => 20101,
                    'verify_partner' => '[]',
                    'partner_dead' => '[]',
                ),

                'lose' => array(
                    'partner_dead' => '[1002]',
                ),

                'refresh' => array(),

                'refreshNow' => array(),

                'reward' => array(),

                'sweepStart' => array(),

                'sweepComplete' => array(),

                'sweepCompleteNow' => array(),

            ),

            'GodBattle' => array(

                'getCount' => array(
                    'event_group' => 18,
                ),

                'fight' => array(
                    'event_group' => 18,
                    'battle_id' => 5011801,
                    'partner' => '[1001]',
                ),

                'win' => array(
                    'event_group' => 18,
                    'battle_id' => 5011801,
                    'combo' => 123,
                    'verify_partner' => '[]',
                ),

                'lose' => array(
                    'event_group' => 18,
                    'battle_id' => 5011801,
                    'combo' => 123,
                ),

            ),

            'AbyssBattle' => array(

                'getList' => array(),

                'clearCd' => array(),

                'fight' => array(
                    'battle_id' => 2,
                    'partner' => '[1001]',
                ),

                'end' => array(
                    'battle_id' => 2,
                    'damage' => 1000,
                    'combo' => 500,
                    'verify_partner' => '[]',
                ),

            ),

            'LifeDeathBattle' => array(

                'getInfo' => array(),

                'fight' => array(
                    'partner' => '[1001]',
                ),

                'win' => array(
                    'verify_partner' => '[]',
                    'verify_target_partner' => '[]',
                    'combo' => 500,
                ),

                'lose' => array(
                    'combo' => 500,
                ),

                'giveUp' => array(),

                'buy' => array(),

            ),

            'LeagueFight' => array(

                'getTargetInfo' => array(),

                'getRankList' => array(
                    'last' => 0,
                    'row' => 5,
                ),

                'getBattleInfo' => array(
                    'last' => 0,
                ),

                'getSituation' => array(),

                'useKit' => array(
                    'kit_id' => 3,
                ),

                'buyAssault' => array(),

                'buyCount' => array(),

                'sendChatNotice' => array(
                    'msg' => '我是大师！',
                ),

                'fight' => array(
                    'hold_id' => 3,
                    'partner' => '[1001,1002,1003,1004,1005]',
                ),

                'end' => array(
                    'hold_id' => 3,
                    'damage' => 10000000,
                    'combo' => 500,
                    'verify_partner' => '[]',
                ),

            ),

            'LeagueArenaReg' => array(

                'getInfo' => array(),

                'register' => array(
                    'area' => 1,
                ),

            ),

            'LeagueArena' => array(

                'getInfo' => array(
                    'area' => 5,
                ),

                'getTeamList' => array(),

                'register' => array(
                    'partner' => '[1001]',
                ),

                'ban' => array(
                    'battle_id' => 1,
                ),

                'change' => array(
                    'battle_id' => 1,
                    'partner' => '[1001]',
                ),

                'fight' => array(
                    'battle_id' => 1,
                ),

                'win' => array(
                    'battle_id' => 1,
                ),

                'lose' => array(
                    'battle_id' => 1,
                ),

            ),

            'LuckyCat' => array(

                'getCount' => array(
                    'event_group' => 19,
                ),

                'fight' => array(
                    'battle_id' => 42001,
                    'partner' => '[1001]',
                ),

                'end' => array(
                    'battle_id' => 42001,
                    'damage' => 10000000,
                    'combo' => 1500,
                    'verify_partner' => '[]',
                ),

            ),

            'LeagueBoss' => array(

                'getList' => array(),

                'call' => array(
                    'site' => 1,
                ),

                'callForce' => array(
                    'site' => 3,
                ),

                'buff' => array(),

                'buffAll' => array(),

                'fight' => array(
                    'site' => 1,
                    'partner' => '[1001]',
                ),

                'end' => array(
                    'site' => 1,
                    'damage' => 1000,
                    'combo' => 500,
                    'verify_partner' => '[]',
                ),

            ),

            'RandomBattle' => array(

                'fight' => array(
                    'instance_id' => 10101,
                    'partner' => '[1001]',
                ),

                'win' => array(
                    'instance_id' => 10101,
                    'combo' => 123,
                    'verify_partner' => '[]',
                ),

                'lose' => array(
                    'instance_id' => 10101,
                    'combo' => 123,
                ),

            ),

            'ExpireBattle' => array(

                'getList' => array(),

                'fight' => array(
                    'battle_id' => 1,
                    'partner' => '[1001]',
                ),

                'win' => array(
                    'battle_id' => 1,
                    'combo' => 123,
                    'star' => 7,
                    'verify_partner' => '[]',
                ),

                'lose' => array(
                    'battle_id' => 1,
                ),

                'sweep' => array(
                    'battle_id' => 1,
                    'times' => 1,
                ),

            ),

            /*************************************************战斗模块*************************************************/

        ),

        1000031 => array(

            'User' => array(

                'login' => array(
                    'base_version' => '0.7.0.0',
                    'username' => 'a123456',
                    'password' => '123456',
                    'channel_id' => 2001,
                    'udid' => '49ea3a010ec2015b03193d9722abbefe',
                    'pts' => 0,
                    'tid' => 0,
                ),

            ),

        ),

        1000039 => array(

            'User' => array(

                'fast' => array(
                    'adid' => '1',
                    'base_version' => '0.9.0.0',
                    'udid' => 'aa9f4a3aab90f9a664589fe152972834',
                    'mac' => 'BD2AFD09-8D68-4578-9B1A-BBD6AA540A4D',
                    'channel_id' => 1002,
                    'channel_uid' => '',
                    'channel_token' => '',
                ),

                'login' => array(
                    'base_version' => '0.9.0.0',
                    'username' => 'zl00001',
                    'password' => '111111',
                    'channel_id' => 1002,
                    'udid' => 'aa9f4a3aab90f9a664589fe152972834',
                    'pts' => 0,
                ),

            ),

        ),

    );

    public function _initialize()
    {

        //header信息
        header_info();

        //测试帐号UID
        $this->test = isset($_GET['test']) ? $_GET['test'] : self::DEFAULT_UID;

        //测试服务器
        $sid = isset($_GET['sid']) ? $_GET['sid'] : 104;
        C('G_SID', $sid);
        change_db_config(C('G_SID'), 'all');

        //测试模块
        if (isset($_GET['controller']))
            $this->controller = $_GET['controller'];

        if (isset($_GET['action']))
            $this->action = $_GET['action'];

        //测试地址
        $this->host = WEB_URL;

        //测试数据
        $this->json['id'] = rand(1000, 9999);
        $this->json['sid'] = C('G_SID');
        $this->json['ver'] = '1.0';
        $this->json['method'] = $this->controller . '.' . $this->action;
    }

    public function index()
    {
        $token = D('Predis')->cli('game')->get('u:' . $this->test);
//        $token = '20dae784b96895eaba9cdab5a29f8e6c';
        if (isset($_GET['params'])) {
            $params = json_decode($_GET['params'], true);
        } else if (isset($this->TestData[$this->test][$this->controller][$this->action])) {
            $params = $this->TestData[$this->test][$this->controller][$this->action];
        } else {
            $params = $this->TestData[self::DEFAULT_UID][$this->controller][$this->action];
        }
        $params['timestamp'] = time();

        //获取协议配置
        $protocol = get_config('protocol', array($this->controller, $this->action,));

        //制造token
        if (!isset($protocol['isToken'])) {
            $this->json['token'] = $token;
        }else{
            $this->json['token'] = '';
        }

        //制造params
        foreach ($protocol['params'] as $key => $value) {
            if ($key == 'password') {
                if ($this->action == 'register') {
                    $this->json['params'][$key] = md5($params[$key]);
                } else {
                    $this->json['params'][$key] = md5(md5($params[$key]) . $params['timestamp'] . get_config('uc_verify', 'password'));
                }
            } else if ($key == 'newPassword') {
                $this->json['params'][$key] = md5($params[$key]);
            } else if ($key == 'pts') {
                $this->json['params'][$key] = $params['timestamp'];
            } else {
                $this->json['params'][$key] = $params[$key];
            }
        }
//        $this->json['params']['sub_params'] = json_encode(array('guide' => array('step1'=>5001,'step2'=>716)));//生成时间
        $this->json['params']['timestamp'] = $params['timestamp'];//生成时间

        //生成sign
        $this->json['sign'] = sign_create($this->json['id'], $this->json['sid'], $this->json['method'], $this->json['params'], 'request', $this->json['ver']);

        //生成json
        $post = json_encode($this->json);

        //AES加密
        $aes = new \Org\Util\CryptAES();
        $aes->set_key($this->getAesKey());
        $aes->require_pkcs5();
        $postAes = $aes->encrypt($post);
        //发送协议
//        $cookie = "XDEBUG_SESSION=10000";
//        $ret = curl_link($this->host,'post',$post,$cookie);
        $ret = curl_link($this->host . '?c=Router&a=request', 'post', $post);

        echo '<div style="word-break:break-all;">';
        echo $this->host;
        echo '<hr />';
//        echo '服务器返回字符串：<br />';
//        echo $ret;
//        echo '<hr />';
        echo $post;
        echo '<hr />';

        //打印结果
//        dump(strlen($ret));
        $arr = explode('</pre>', $ret);
        if (count($arr) > 1) {
            $i = 1;
            foreach ($arr as $value) {
                if ($i != count($arr)) {
                    $str = substr($value, 5);
                    echo $str . '<br />';
                }
                $i++;
            }
            echo '<hr />';
        }

        //解密
        $ret = end($arr);
        $aes = new \Org\Util\CryptAES();
        $aes->set_key($this->getAesKey());
        $aes->require_pkcs5();
        $data = $aes->decrypt($ret);
//        echo($data);
//        echo '<hr />';
        echo "</div>";

        //解析结果
        $json = '';
        $arr = explode('{"id":"', $data);
        foreach ($arr as $key => $value)
            if ($key != 0)
                $json .= '{"id":"' . $arr[$key];

        $arr_json = json_decode($json, true);
        dump($arr_json);

//        dump(C('G_SQL_ERROR'));
//        dump(C('G_SQL'));

    }

    //获取AESKEY
    private function getAesKey()
    {
        $key = S(C('APC_PREFIX') . 'aes_key');
        if (empty($key)) {
            //获取当前服务器基座版本
            $content = D('StaticDyn')->access('params', 'VERLIST');
            $content = trim($content);
            $aes = new \Org\Util\CryptAES();
            $aes->set_key(C('AES_KEY'));
            $aes->require_pkcs5();
            $content = $aes->decrypt($content);
            $content = json_decode($content, true);
            $key = substr($content['currentmd5'], 0, 16);
            S(C('APC_PREFIX') . 'aes_key', $key);
        }
        return $key;
    }

    //快速测试
    public function fast_test()
    {
        C('G_SID', '0');
        change_db_config(C('G_SID'), 'all');
        return;
    }

    //宝箱测试
    public function box()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $index = $_GET['index'];
        $count = $_GET['count'];
        //测试宝箱
        $rs = D('SBox')->open($index, $count);
        dump($rs);
    }

    //检查宝箱
    public function checkBox($box, $config)
    {
        $row = $config[$box];
        dump($box . ':' . $row['prop_type']);
        for ($i = 1; $i <= 5; ++$i) {
            if ($row['item_' . $i . '_type'] == 8) {
                $this->checkBox($row['item_' . $i], $config);
            }
        }
    }

    //清除缓存
    public function clearGParams()
    {
        C('G_SID', 1);
        D('Predis')->cli('game')->del('g_params');
        echo 'success';
    }

    //清除缓存
    public function clearRedis()
    {
//        D('Predis')->cli('game')->del('g_params');
//        D('Predis')->cli('social')->flushdb();
//        D('Predis')->cli('fight')->flushdb();

        /*$keys = D('Predis')->cli('game')->keys('u:*');
        if (!empty($keys)) {
            D('Predis')->cli('game')->del($keys);
        }

        $keys = D('Predis')->cli('game')->keys('t:*');
        if (!empty($keys)) {
            D('Predis')->cli('game')->del($keys);
        }

        $keys = D('Predis')->cli('game')->keys('s:*');
        if (!empty($keys)) {
            D('Predis')->cli('game')->del($keys);
        }

        $keys = D('Predis')->cli('game')->keys('sn:*');
        if (!empty($keys)) {
            D('Predis')->cli('game')->del($keys);
        }*/

        $keys = D('Predis')->cli('fight')->keys('lf:*');
        if (!empty($keys)) {
            D('Predis')->cli('game')->del($keys);
        }


        echo 'success';
    }

    //清除缓存
    public function clearApc()
    {
        if (apc_clear_cache('user'))
            echo 'success';
        else
            echo 'fail';
    }

    //错误重现
    public function review()
    {

        $post = '{"id":2478,"sid":104,"ver":"1.0","method":"User.login","params":{"username":"a123456","password":"5e1bde978c51cd3037d0142a4579bb84","udid":"49ea3a010ec2015b03193d9722abbefe","pts":1447327200,"channel_id":2001,"base_version":"0.7.0.0","tid":0,"timestamp":1447327200},"sign":"f843e696ba3a79799dbfb2683079e7f0","token":""}';

        //AES加密
        $aes = new \Org\Util\CryptAES();
        $aes->set_key($this->getAesKey());
        $aes->require_pkcs5();
        $postAes = $aes->encrypt($post);

        $ret = curl_link($this->host, 'post', $postAes);
        echo $this->host . '?' . $post;
        echo '<hr />';

        $aes = new \Org\Util\CryptAES();
        $aes->set_key($this->getAesKey());
        $aes->require_pkcs5();
        $ret = $aes->decrypt($ret);
        echo($ret);
        echo "<br />";
        //解析结果
        $ret = json_decode($ret, true);
        dump($ret);

    }

    public function addItem()
    {
        C('G_BEHAVE', 'test');
        $tid = 1;
        $item = array(
            '50201001' => 100,
//            '50100002'=>100,
//            '50100003'=>100,
//            '50100004'=>100,
        );
        foreach ($item as $key => $value) {
            if (!D('GItem')->inc($tid, $key, $value)) {
                return false;
            }
        }

        echo 'success';

    }

    public function channelCash()
    {
        $channelList = array(
            array(
                'channel_id' => 15001,
                'goods_id' => 'com.tongbu.linekong.yhzj',
            ),
            array(
                'channel_id' => 15002,
                'goods_id' => 'com.linekong.yhzj.pp',
            ),
            array(
                'channel_id' => 15003,
                'goods_id' => 'com.linekong.yhzj.I4',
            ),
            array(
                'channel_id' => 15004,
                'goods_id' => 'com.linekong.yhzj.XY',
            ),
            array(
                'channel_id' => 15005,
                'goods_id' => 'com.linekong.yhzj.hm',
            ),
            array(
                'channel_id' => 15006,
                'goods_id' => 'com.linekong.yhzj.sky',
            ),
            array(
                'channel_id' => 15007,
                'goods_id' => 'com.linekong.yhzj.ky',
            ),
            array(
                'channel_id' => 15008,
                'goods_id' => 'com.linekong.yhzj.baidu',
            ),
        );

        $list = D('Static')->table('s_cash')->where("`channel_id`='10001'")->select();
        foreach ($list as $value) {
            foreach ($channelList as $val) {
                $row = $value;
                $row['index'] = $val['channel_id'] . substr($value['index'], -2);
                $row['channel_id'] = $val['channel_id'];
                $setList = explode('.', $value['goods_id']);
                $set = end($setList);
                $row['goods_id'] = $val['goods_id'] . '.' . $set;
                $all[] = $row;
            }
        }

        $arr = array('01', '02', '03', '04', '05', '06', '11',);
        $str = '';
        foreach ($channelList as $value) {
            foreach ($arr as $val) {
                $str .= $value['channel_id'] . $val . ',';
            }
        }

        D('Static')->table('s_cash')->addAll($all);
        echo $str;
        return;
    }

    //测试LUA
    public function lua_test()
    {
        $rs = lua('league_battle', 'league_battle_match', array(2000, '11111'));
//        $rs = lua('life_death_battle', 'life_death_battle', array(1, 2, 25,));
//        $rs = ceil($rs);
        dump($rs);
    }

    //生成json
    public function json_create()
    {
        $arr = array(
            array('id' => 10001, 'hp' => 0),
            array('id' => 10002, 'hp' => 0),
            array('id' => 10003, 'hp' => 1000),
            array('id' => 10004, 'hp' => 2000),
            array('id' => 10005, 'hp' => 2300),
            array('id' => 10001, 'hp' => 4000),
        );
        echo json_encode($arr);
    }

    //开始新游戏
    public function newGame()
    {
        $max = 10000;
        $pre = D('Predis')->cli('game')->incr('pre');
        if (empty($pre) || $pre > $max) {
            echo 'end';
        } else {
            $tid = $pre + (100 + C('G_SID')) * 1000000;
            $uid = $pre + 10000;
            D('GTeam')->usePreCreate($tid, $uid, '昵称' . $pre, 1001);
            $info = A('Team', 'Api')->getMainInfo($tid, 10);
            echo json_encode($info);
        }
        return;

    }

    //开启全部副本
    public function instance()
    {

        //获取战队ID
        $tid = $_GET['tid'];

        //计算副本表
        $num = $tid % 10;

        //删除现有数据
        M()->table('g_instance_' . $num)->where("`tid`='{$tid}'")->delete();

        //副本配置
        $mapConfig = D('Static')->access('map');
        $instanceList = array();
        foreach ($mapConfig as $key => $value) {
            $list = explode('#', $value['instance']);
            $instanceList = array_merge($instanceList, $list);
        }

        //时间
        $now = time();

        //遍历数据
        $addAll = array();
        foreach ($instanceList as $instanceId) {
            $add['tid'] = $tid;
            $add['instance'] = $instanceId;
            $add['group'] = substr($instanceId, 0, 3);
            $add['star'] = 7;
            $add['count'] = 1;
            $add['combo'] = 100;
            $add['combo_time'] = $now;
            $addAll[] = $add;
        }

        //添加数据
        M()->table('g_instance_' . $num)->addAll($addAll);

        //返回
        return;
    }

    //赠送伙伴
    public function partner()
    {

        //定义行为
        C('G_BEHAVE', 'test');

        //获取战队ID
        $tid = $_GET['tid'];

        //伙伴组
        $partnerList = array(1000, 1001, 1002, 1004, 1005, 1006, 1007, 1008, 1016, 1017, 1021, 1024, 1025, 1026, 1028, 1034, 1038, 1040, 1041, 1043, 1044, 1045, 1046, 1049, 1052);

        //添加伙伴
        foreach ($partnerList as $partner) {
            D('GPartner')->cPartner($tid, $partner);
        }

        //返回
        return;

    }


    //创建审核帐号
    public function examine()
    {

        //连接数据库
        C('G_SID', 102);
        change_db_config(C('G_SID'), 'all');

        $now = time();

        //战队ID
        $tidList[1] = array(101000016, 101000019, 101000024, 101000025);//低级
        $tidList[2] = array(101000020, 101000021, 101000026);//中级
        $tidList[3] = array(101000022, 101000023, 101000027);//高级
        $tidTotal = array_merge($tidList[1], $tidList[2], $tidList[3]);

        //发送水晶,金币,体力
        foreach ($tidList[1] as $tid) {
            $where['tid'] = $tid;
            $team1['level'] = 30;
            $team1['diamond_free'] = 500000;
            $team1['gold'] = 500000;
            $team1['vality'] = 500;
            $team1['guide_skip'] = 1;
            M('GTeam')->where($where)->save($team1);
            $vip1['index'] = 1003;
            M('GVip')->where($where)->save($vip1);
        }

        foreach ($tidList[2] as $tid) {
            $where['tid'] = $tid;
            $team2['level'] = 60;
            $team2['diamond_free'] = 5000000;
            $team2['gold'] = 50000000;
            $team2['vality'] = 2000;
            $team2['guide_skip'] = 1;
            M('GTeam')->where($where)->save($team2);
            $vip2['index'] = 1009;
            M('GVip')->where($where)->save($vip2);
        }

        foreach ($tidList[3] as $tid) {
            $where['tid'] = $tid;
            $team3['level'] = 99;
            $team3['diamond_free'] = 5000000;
            $team3['gold'] = 500000000;
            $team3['vality'] = 2000;
            $team3['guide_skip'] = 1;
            M('GTeam')->where($where)->save($team3);
            $vip3['index'] = 1015;
            M('GVip')->where($where)->save($vip3);
        }

        //开地图
        $mapConfig = D('Static')->access('map');
        $instance[1] = array();
        $instance[2] = array();
        $instance[3] = array();
        foreach ($mapConfig as $key => $value) {
            $list = explode('#', $value['instance']);
            if ($value['map'] <= 4) {
                $instance[2] = array_merge($instance[2], $list);
                $instance[3] = array_merge($instance[3], $list);
            } else if ($value['map'] <= 6) {
                $instance[3] = array_merge($instance[3], $list);
            }
        }

        foreach ($tidList as $key => $tidList2) {
            foreach ($tidList2 as $tid) {
                $addAll = array();
                if (!empty($instance[$key])) {
                    foreach ($instance[$key] as $instanceId) {
                        $add['tid'] = $tid;
                        $add['instance'] = $instanceId;
                        $add['group'] = substr($instanceId, 0, 3);
                        $add['star'] = 7;
                        $add['count'] = 1;
                        $add['combo'] = 100;
                        $add['combo_time'] = $now;
                        $addAll[] = $add;
                    }
                }
                $num = $tid % 10;
                M()->table('g_instance_' . $num)->where("`tid`='{$tid}'")->delete();
                M()->table('g_instance_' . $num)->addAll($addAll);
            }
        }

        //删除原有伙伴
        $where['tid'] = array('in', $tidTotal);
        M()->table('g_partner')->where($where)->delete();
        M()->table('g_equip')->where($where)->delete();

        //加伙伴
        $addAllPartner = array();
        $addAllEquip = array();

        $partnerList1 = array(1001);
        foreach ($partnerList1 as $group) {
            foreach ($tidList[1] as $tid) {
                //伙伴
                $addPartner['tid'] = $tid;
                $addPartner['group'] = $group;
                $addPartner['index'] = $group . '1';
                $addPartner['level'] = 30;
                $addPartner['exp'] = 0;
                $addPartner['favour'] = 0;
                $addPartner['soul'] = 0;
                $addPartner['skill_1_level'] = 0;
                $addPartner['skill_2_level'] = 0;
                $addPartner['skill_3_level'] = 0;
                $addPartner['skill_4_level'] = 0;
                $addPartner['skill_5_level'] = 0;
                $addPartner['skill_6_level'] = 0;
                $addPartner['force'] = 0;
                $addPartner['ctime'] = $now;
                $addPartner['utime'] = $now;
                $addAllPartner[] = $addPartner;

                //装备
                for ($i = 1; $i <= 3; ++$i) {
                    $addEquip['tid'] = $tid;
                    $addEquip['group'] = $group . '0' . $i;
                    $addEquip['index'] = $group . '0' . $i . '03';
                    $addEquip['partner_group'] = $group;
                    $addEquip['level'] = 1;
                    $addEquip['extra_1_type'] = 0;
                    $addEquip['extra_1_value'] = 0;
                    $addEquip['extra_2_type'] = 0;
                    $addEquip['extra_2_value'] = 0;
                    $addEquip['extra_3_type'] = 0;
                    $addEquip['extra_3_value'] = 0;
                    $addEquip['extra_4_type'] = 0;
                    $addEquip['extra_4_value'] = 0;
                    $addAllEquip[] = $addEquip;
                }
            }
        }

        $partnerList2 = array(1001, 1041, 1052);
        foreach ($partnerList2 as $group) {
            foreach ($tidList[2] as $tid) {
                //伙伴
                $addPartner['tid'] = $tid;
                $addPartner['group'] = $group;
                $addPartner['index'] = $group . '1';
                $addPartner['level'] = 60;
                $addPartner['exp'] = 0;
                $addPartner['favour'] = 0;
                $addPartner['soul'] = 0;
                $addPartner['skill_1_level'] = 0;
                $addPartner['skill_2_level'] = 0;
                $addPartner['skill_3_level'] = 0;
                $addPartner['skill_4_level'] = 0;
                $addPartner['skill_5_level'] = 0;
                $addPartner['skill_6_level'] = 0;
                $addPartner['force'] = 0;
                $addPartner['ctime'] = $now;
                $addPartner['utime'] = $now;
                $addAllPartner[] = $addPartner;

                //装备
                for ($i = 1; $i <= 3; ++$i) {
                    $addEquip['tid'] = $tid;
                    $addEquip['group'] = $group . '0' . $i;
                    $addEquip['index'] = $group . '0' . $i . '07';
                    $addEquip['partner_group'] = $group;
                    $addEquip['level'] = 1;
                    $addEquip['extra_1_type'] = 0;
                    $addEquip['extra_1_value'] = 0;
                    $addEquip['extra_2_type'] = 0;
                    $addEquip['extra_2_value'] = 0;
                    $addEquip['extra_3_type'] = 0;
                    $addEquip['extra_3_value'] = 0;
                    $addEquip['extra_4_type'] = 0;
                    $addEquip['extra_4_value'] = 0;
                    $addAllEquip[] = $addEquip;
                }
            }
        }

        $partnerList3 = array(1001, 1016, 1041, 1043, 1052);
        foreach ($partnerList3 as $group) {
            foreach ($tidList[3] as $tid) {
                //伙伴
                $addPartner['tid'] = $tid;
                $addPartner['group'] = $group;
                $addPartner['index'] = $group . '1';
                $addPartner['level'] = 99;
                $addPartner['exp'] = 0;
                $addPartner['favour'] = 0;
                $addPartner['soul'] = 0;
                $addPartner['skill_1_level'] = 0;
                $addPartner['skill_2_level'] = 0;
                $addPartner['skill_3_level'] = 0;
                $addPartner['skill_4_level'] = 0;
                $addPartner['skill_5_level'] = 0;
                $addPartner['skill_6_level'] = 0;
                $addPartner['force'] = 0;
                $addPartner['ctime'] = $now;
                $addPartner['utime'] = $now;
                $addAllPartner[] = $addPartner;

                //装备
                for ($i = 1; $i <= 3; ++$i) {
                    $addEquip['tid'] = $tid;
                    $addEquip['group'] = $group . '0' . $i;
                    $addEquip['index'] = $group . '0' . $i . '12';
                    $addEquip['partner_group'] = $group;
                    $addEquip['level'] = 1;
                    $addEquip['extra_1_type'] = 0;
                    $addEquip['extra_1_value'] = 0;
                    $addEquip['extra_2_type'] = 0;
                    $addEquip['extra_2_value'] = 0;
                    $addEquip['extra_3_type'] = 0;
                    $addEquip['extra_3_value'] = 0;
                    $addEquip['extra_4_type'] = 0;
                    $addEquip['extra_4_value'] = 0;
                    $addAllEquip[] = $addEquip;
                }
            }
        }

        //插入数据库
        M()->table('g_partner')->addAll($addAllPartner);
        M()->table('g_equip')->addAll($addAllEquip);

        //返回
        echo 'ok';
        return;

    }

}