<?php
namespace Home\Controller;

use Think\Controller;
use Think;

class PayNotifyController extends Controller
{

    public function awg()
    {

        Think\Log::record('POST:' . json_encode($_POST), 'DEBUG');

        //结果
        $status = -1;

        //验证请求是否正确
        $mySign = md5($_POST['productId'] . AWG_FIX . $_POST['stamp']);

        //验证失败
        if ($mySign == $_POST['sig']) {

            //造数据
            $verify = json_encode($_POST);

            //处理透传信息
            $info = explode(':', $_POST['payload']);

            //获取订单号
            $orderId = $info[1];

            //配置数据库
            $serverId = $info[0];
            C('G_SID', $serverId);
            change_db_config($serverId, 'all');

            //执行逻辑
            $rs = A('Pay', 'Api')->callback(-1, $orderId, $_POST['transactionId'], $verify, 1, $_POST['payload']);

            //解析返回
            switch ($rs) {
                case 0:
                    $status = 1;
                    break;
                case 1:
                case 2:
                    $status = 0;
                    break;
                case -5:
                    $status = -5;
                    break;
            }
        }

        //返回
        $ret['error'] = (int)$status;
        header_info('json');
        echo json_encode($ret);
        return;

    }

}