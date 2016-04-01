<?php
namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller
{

    public function _initialize()
    {
        header_info();
    }

    public function index()
    {
        echo "手机游戏—绝对领域—服务端";
    }

    public function showPhpInfo()
    {
        phpinfo();
    }

    //获取服务器时间
    public function getTimeStamp()
    {
        echo time();
    }

    //清楚所有apc缓存
    public function clearApc()
    {
        //查看是否有token
        if (!isset($_POST['token']) || empty($_POST['token'])) {
            echo 'fail';
            return false;
        }
        //判断token是否正确
        $token = md5($_POST['key'] . C('CACHE_VERIFY'));
        if ($token != $_POST['token']) {
            echo 'fail';
            return false;
        }
        //清除apc
        if (isset($_POST['key']) && !empty($_POST['key'])) {
            S(C('APC_PREFIX') . $_POST['key'], null);
        } else {
            apc_clear_cache('user');
            apc_clear_cache();
        }
        //返回结果
        echo 'success';
        return true;
    }

    //获取服务器列表
    public function getServerList()
    {
        //header
        header_info('json');

        //获取游戏服务器配置列表
        $serverList = get_server_list();
        $channelId = $_GET['channel_id'];
        $list = array();

        //服务器列表不为空
        if (!empty($serverList)) {

            //先处理平台渠道id情况
            if (!empty($_GET['platform_channel_id'])) {
                $channel = $_GET['platform_channel_id'];
                $serverKey = 'code';
            } else {
                $channel = $_GET['channel_id'];
                $serverKey = 'channel_id';
            }

            //服务器列表
            foreach ($serverList as $key => $value) {
                foreach ($value['channel'] as $val) {
                    if ($channel == $val[$serverKey]) {
                        $channelId = $channelId > 0 ? $channelId : $val['channel_id'];
                        foreach ($val['name'] as $v) {
                            $arr = array('server_id' => (int)$key, 'name' => $v, 'type' => (int)$val['type'], 'gateway_id' => (int)$value['platform']['sid'],);
                            $list[] = $arr;
                        }
                    }
                }
            }

        }

        //返回
        $return['channel_id'] = $channelId;
        $return['list'] = $list;
        echo json_encode($return);
        
    }

    //获取VERLIST
    public function getVerList()
    {
        header_info('plain');
        echo D('StaticDyn')->access('params', 'VERLIST');
    }

    //更新失败
    public function update()
    {
        if(!empty($_GET['type']) && !empty($_GET['udid'])){
            //发送邮件
            $errorLog = $_GET['type'] . '#' . $_GET['udid'];
            if (C('WARNING_TYPE') == 'File') {
                write_log($errorLog, 'error/update/');
            } else if (C('WARNING_TYPE') == 'Mail') {
                think_send_mail('error_report@forevergame.com', 'error_report', 'UPDATE_ERROR(' . APP_STATUS . ')', $errorLog);
            }

        }
        return;
    }

}