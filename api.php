<?php
// 应用入口文件

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG', true);

//状态配置
define('APP_STATUS','local');//local#master#develop

//安全文件
//define('DIR_SECURE_FILENAME', 'default.html');//配置安全文件名
//define('DIR_SECURE_FILENAME', 'index.html,index.htm');//配置多个安全文件
define('DIR_SECURE_CONTENT', 'deny Access!');//配置安全文件内容
//define('BUILD_DIR_SECURE', false);//禁止生成安全文件

//项目路径
define('APP_PATH','./api/');

//入口
if(!isset($_GET['m']))$_GET['m'] = 'Home'; // 绑定Home模块到当前入口文件
if(!isset($_GET['c'])){
	$_GET['c'] = 'Router'; // 绑定Router模块到当前入口文件
	$_GET['a'] = 'request'; // 绑定request模块到当前入口文件
}

//导入TP框架
require 'ThinkPHP/ThinkPHP.php';
