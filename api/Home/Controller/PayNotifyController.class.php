<?php
namespace Home\Controller;

use Think\Controller;

class PayNotifyController extends Controller
{

    //eRating
    public function eRatingBind()
    {

        $body['result_code'] = 0;
        //获取post数据
        $post = file_get_contents("php://input");

        if (empty($post)){
            die('fail');
        }else{

            //xml处理
            $return = D('ERating')->getCallBackData($post);

            //获取服务器信息
            $sid = 0;
            $serverList = get_server_list();
            foreach($serverList as $key => $value){
                if($value['eRating']['gateway_id'] == $return['data']['gateway_id']){
                    $sid = $key;
                    break;
                }
            }

            //如果sid是空则返回错误
            if($sid == 0){
                die('fail');
            }

            //服务器
            C('G_SID', $sid);
            if (is_array($return['data']) && $return['data']['gateway_id'] > 0) {

                if($return['data']['command_id'] == '10003413'){

                    //造数据
                    $status = 1;
                    $verify = json_encode($return['data']);

                    //获取订单号
                    $orderId = $return['data']['attach_code'];

                    //配置数据库
                    change_db_config($sid, 'all');

                    //执行逻辑
                    C('LK_USER_ID', $return['data']['user_id']);
                    $rs = A('Pay', 'Api')->callback($return['data']['amount'], $orderId, $return['data']['detail_id'], $verify, $status, $return['data']['attach_code']);

                    //解析返回
                    switch ($rs) {
                        case -5:
                            break;
                        default:
                            $body['result_code'] = 1;
                    }

                }else if($return['data']['command_id'] == '10000001'){
                    $body['result_code'] = 1;
                }

            }else{
                die('fail');
            }

        }

        //显示
        header_info('xml');
        echo D('ERating')->createRespondData($sid, $return, $body);
        return;
    }

}