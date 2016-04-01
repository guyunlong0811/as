<?php
$verify = require_once('verify.php');
$protocol = require_once('protocol.php');
$error = require_once('error.php');
$behave = require_once('behave.php');
$field = require_once('field.php');
$config = array(

    'URL_CASE_INSENSITIVE' => false,    // 默认false 表示URL区分大小写
    'URL_MODEL' => 0,                   //URL模式

    'SESSION_AUTO_START' => false,      // 是否自动开启Session

    //数据库部分
    'DB_TYPE' => 'mysql',
    'DB_PREFIX' => '',                  //数据库表名前缀
    'DB_CHARSET' => 'utf8',             //数据库字符类型
    'DB_FIELDS_CACHE' => false,          // 禁用字段缓存(不同库中有相同名字的表)

//    'DB_SQL_BUILD_CACHE' => true,       //SQL解析缓存
//    'DB_SQL_BUILD_QUEUE' => 'apc',      //SQL解析缓存
//    'DB_SQL_BUILD_LENGTH' => 20,        // SQL缓存的队列长度

    //缓存
    'DATA_CACHE_TIME' => 0,// 数据缓存有效期 0表示永久缓存
    'DATA_CACHE_TYPE' => 'Apc',

    //Redis缓存
    'REDIS_TOKEN_TIME' => 1200,//用户登录凭证有效时间（秒）
    'REDIS_EXPIRE_TIME' => 600,//用户登录过期凭证保存时间（秒）
    'REDIS_CHAT_WORLD_TIME' => 86400,//世界频道留言保存时间
    'REDIS_CHAT_LEAGUE_TIME' => 86400,//公会频道留言保存时间
    'REDIS_CHAT_NOTICE_TIME' => 600,//跑马灯保存时间
    'REDIS_CHAT_LEAGUE_NOTICE_TIME' => 300,//公会战公告保存时间
    'REDIS_CHAT_WORLD_ROW' => 20,//世界频道每页显示聊天数
    'REDIS_CHAT_LEAGUE_ROW' => 20,//公会频道每页显示聊天数
    'REDIS_CHAT_NOTICE_ROW' => 20,//公告一次最多收取条数

    'GAME_ID' => 1,      //游戏ID
    'LUA_URL' => './lua/',//LUA文件路径

    'DAILY_UTIME' => '03:00:00',
//    'LEAGUE_BATTLE_PROTECT_TIME' => 360,//公会活动战斗保护时间
    'MONEY_RATE' => 10,
    'RANK_MAX' => 50,//排行榜最低名次

    //全局变量
    'G_BEHAVE' => '',//当前协议的行为代号
    'G_TRANS' => false,//是否启用了事务
    'G_TRANS_FLAG' => false,//事务是否有错
    'G_ERROR' => null,//错误提示
    'G_SQL' => array(),//trans过程中的所有SQL
    'G_SQL_ERROR' => array(),//所有报错的SQL

    //日志
    'LOG_TYPE' => 'File',//日志记录类型
    'LOG_RECORD' => true,//开启了日志记录
    'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR', // 只记录EMERG ALERT CRIT ERR 错误

    'APC_PREFIX' => 'fg_yhhx_as_',

    //邮件配置
    'THINK_EMAIL' => array(
        'SMTP_HOST' => 'smtp.qq.com', //SMTP服务器
        'SMTP_PORT' => '25', //SMTP服务器端口
        'SMTP_USER' => 'error@forevergame.com', //SMTP服务器用户名
        'SMTP_PASS' => 'acWwDKiINOcH4!a', //SMTP服务器密码
        'FROM_EMAIL' => 'error@forevergame.com', //发件人EMAIL
        'FROM_NAME' => 'API_SERVER', //发件人名称
        'REPLY_EMAIL' => '', //回复EMAIL（留空则为发件人EMAIL）
        'REPLY_NAME' => '', //回复名称（留空则为发件人名称）
    ),

    'WARNING_TYPE' => 'File',
    'CASH_CLOSE' => array(),
//    'CASH_CLOSE' => array(10001,20001,21001,21002,21003,21004,21005,21006,21007,21008,21009,21010,21011,21012,21013,21014,21015,21016,21017,21018,21019,21020,21021,21022,22001,22002,22003,22004,22005,22006,22007,22008,22009,22010,22011,22012,22013,22014,22015,22016,22017,22018,22019,22020,22021,22022,22023,22024,22025,22026,22027,22028,22029,22030,22031,22032,22033,22034,22035,22036,22037,22038,22039),

);
return array_merge($config, $verify, $protocol, $error, $behave, $field);
