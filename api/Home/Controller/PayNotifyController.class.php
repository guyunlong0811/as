<?php
namespace Home\Controller;

use Think\Controller;

class PayNotifyController extends Controller
{

    public function snda()
    {

        //log
//        Think\Log::record('POST数据:' . json_encode($_POST), 'DEBUG');

        //结果
        $status = -1;

        //验证请求是否正确
        $mySign = $this->sign($_POST, SNDA_APP_KEY);

        //验证失败
        if ($mySign == $_POST['sign']) {

            //造数据
            $verify = json_encode($_POST);

            //处理透传信息
            $info = explode(':', $_POST['extend']);

            //获取订单号
            $orderId = $info[1];

            //配置数据库
            $serverId = $info[0];
            C('G_SID', $serverId);
            change_db_config($serverId, 'all');

            //执行逻辑
            $rs = A('Pay', 'Api')->callback(-1, $orderId, $_POST['orderNo'], $verify, 1, $_POST['extend']);

            //解析返回
            switch ($rs) {
                case 1:
                case 2:
                    $status = 'success';
                    break;
                default:
                    $status = 'fail';
            }
        }

        //返回
        header_info('plain');
        echo $status;
        return;

    }

    // $params数组必须包含timestamp
    private function sign($params, $secret_key)
    {
        unset($params['sign']);
        ksort($params);
        $pairs = array();
        if (empty($params)) {
            return false;
        }
        foreach ($params as $k => $v) {
            $pairs[] = $k . '=' . $v;
        }
        $str = implode('&', $pairs); // 拼接字符创
        $str = $str . $secret_key; // 把APPKEY补充到最后

        return md5($str);
    }

}