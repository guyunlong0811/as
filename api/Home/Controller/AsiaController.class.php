<?php
namespace Home\Controller;

use Think\Controller;
use Think;

class AsiaController extends Controller
{

    const SALT_NICK = 'ksG9Gw';
    const SALT_PAY = 'Ys1Jm9';

    public function nick()
    {

        Think\Log::record('POST:' . json_encode($_POST), 'DEBUG');

        //结果
        $ret['found'] = false;

        //验证时间戳
        if(abs($_POST['query_time'] - time()) > 300){
            goto end;
        }

        //计算签名
        $mySign = $this->mySign($_POST, 'auth', self::SALT_NICK);

        //验证签名
        if ($mySign != $_POST['auth']) {
            goto end;
        }

        //配置数据库
        if (!change_db_config($_POST['server_id'], 'all')) {
            goto end;
        }

        //查询昵称
        $row = D('GTeam')->getRow($_POST['user_tid'], array('uid', 'nickname',));
        if (!empty($row)) {
            $ret['found'] = true;
            $ret['rolename'] = $row['nickname'];
            $params['uid'] = $row['uid'];
            if (!$return = uc_link($params, 'User.getChannelUid')) {
                goto end;
            }
            $ret['platform_role_id'] = $return['channel_uid'];
        }

        end:
        header_info('json');
        echo json_encode($ret);
        return;

    }

    public function pay()
    {

        Think\Log::record('POST:' . json_encode($_POST), 'DEBUG');

        //结果
        $ret['result'] = false;

        //验证时间戳
        if(abs($_POST['query_time'] - time()) > 300){
            $ret['reason'] = 'Order Expire';
            goto end;
        }

        //计算签名
        $mySign = $this->mySign($_POST, 'signature', self::SALT_PAY);

        //验证成功
        if ($mySign != $_POST['signature']) {
            $ret['reason'] = 'Sign Error';
            goto end;
        }

        //造数据
        $verify = json_encode($_POST);

        //配置数据库
        $serverId = $_POST['server_id'];
        C('G_SID', $serverId);
        change_db_config($serverId, 'all');

        //执行逻辑
        $rs = A('Pay', 'Api')->callbackWeb($_POST['user_tid'], $_POST['product_id'], $_POST['order_id'], $_POST['rate'], $verify);

        //解析返回
        switch ($rs) {
            case 1:
                $ret['result'] = true;
                break;
            case -2:
                $ret['reason'] = 'Order Expire';
                break;
            case -5:
                $ret['reason'] = 'Server Busy';
                break;
            case -100:
                $ret['reason'] = 'Recharge Error';
                break;
        }

        //返回
        end:
        header_info('json');
        echo json_encode($ret);
        return;

    }

    //计算签名
    private function mySign($arr, $field, $salt)
    {
        unset($arr[$field]);
        ksort($arr);
        $str = implode('', $arr);
        return md5($str . $salt);
    }

}