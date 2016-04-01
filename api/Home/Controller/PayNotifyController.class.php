<?php
namespace Home\Controller;

use Think\Controller;

class PayNotifyController extends Controller
{

    //oksdk
    public function oksdk()
    {

        require_once(COMMON_PATH . 'Common/oksdk/common.inc.php');

        //验证请求是否正确
        $params = "oksdk_app_id={$_GET['oksdk_app_id']}&channel_code={$_GET['channel_code']}&order_id={$_GET['order_id']}&oksdk_order_id={$_GET['oksdk_order_id']}&uid={$_GET['uid']}&charge_money={$_GET['charge_money']}&charge_amount={$_GET['charge_amount']}&charge_time={$_GET['charge_time']}&gateway_id={$_GET['gateway_id']}&custom_info={$_GET['custom_info']}";
        $str = $params . OKSDK_APP_KEY;
        $mySign = md5($str);

        //验证失败
        if ($mySign != $_GET['sign']) {
            echo 'fail';
            return;
        }

        //造数据
        $status = 1;
        $verify = $params . "&sign={$_GET['sign']}";

        //获取订单号
        $orderId = $_GET['custom_info'];

        //配置数据库
        C('G_SID', $_GET['gateway_id']);
        change_db_config($_GET['gateway_id'], 'all');

        //执行逻辑
        $rs = A('Pay', 'Api')->callback($_GET['charge_money'] * 100, $orderId, $_GET['oksdk_order_id'], $verify, $status, $_GET['custom_info']);

        //解析返回
        switch ($rs) {
            case -5:
                echo 'fail';
                break;
            default:
                echo 'ok';
        }

        return;

    }

    //360平台
    public function qihoo()
    {

        //sdk验证
        $verify = require_once(COMMON_PATH . 'Common/qihoo/pay_callback.php');

        //验证失败
        if ($verify !== true) {
            echo 'verify fail';
            return;
        }

        //确定服务器ID&配置数据库
        $arr = json_decode($_GET['app_ext1'], true);
        C('G_SID', $arr['sid']);
        change_db_config(C('G_SID'), 'all');

        //参数获取
        $orderId = $_GET['app_order_id'];
        $platformOrderId = $_GET['order_id'];
        $verify = $_GET['sign'];
        $status = $_GET['gateway_flag'] == 'success' ? 1 : 0;
        $comment = 'app_ext1=' . $_GET['app_ext1'] . '&app_ext2=' . $_GET['app_ext2'];

        //执行逻辑
        $rs = A('Pay', 'Api')->callback($orderId, $platformOrderId, $verify, $status, $comment);

        //解析返回
        switch ($rs) {
            case -5:
                echo 'fail';
                break;
            default:
                echo 'ok';
        }

        return;

    }

}