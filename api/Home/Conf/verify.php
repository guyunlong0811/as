<?php
//验证配置
return array(

    'AES_KEY' => 'c9d23f432dd5e41b',//aes密钥

    'CACHE_VERIFY' => 'fg_otl_cache',//清除缓存

    'VERIFY' => array(//通讯密钥

        'time_limit' => 600,//时间容错率

        '1.0' => array(
            'request' => array('word' => '5', 'salt' => 'forever',),//word:密码关键字;salt:通讯密钥;
            'respond' => array('word' => '5', 'salt' => 'forever',),
        ),

        '2.0' => array(
            'request' => array('word' => '3', 'salt' => 'game',),
            'respond' => array('word' => '3', 'salt' => 'game',),
        ),

    ),

    'UC_VERIFY' => array(//用户中心配置

        'time_limit' => 600,//时间容错率
        'request' => 'forever!23',
        'respond' => 'forever!23',
        'password' => 'fgpwdsalt',

    ),

);