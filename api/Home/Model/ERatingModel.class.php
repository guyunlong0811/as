<?php
namespace Home\Model;

use Think\Model;

class ERatingModel
{
    const AES_KEY = 'linekong_yingpei';//AES公钥

    private $mServerList = array();
    private $mERatingHost = '';
    private $mRequest = null;
    private $mRespond = null;
    private $mReturn = null;
    private $mCommandId = 0;
    private $mGatewayId = 0;
    private $mGameId = 0;
    private $mBody = 0;
    private $mLog = null;

    public function __construct()
    {
        $this->mServerList = get_server_list();
    }

    public function index($commandId, $sid, $channelId, $body)
    {
        //eRating HOST
        $this->mERatingHost = $this->mServerList[$sid]['eRating']['url'];

        //蓝港头部信息
        $this->mGatewayId = $this->mServerList[$sid]['eRating']['gateway_id'];
        $this->mGameId = $this->mServerList[$sid]['channel'][$channelId]['code'];
        $this->mCommandId = $commandId;
        $this->mBody = $body;

        //生成xml
        $this->createXml();

        //记录发送协议时间
        $this->mLog = 'REQUEST HOST:' . $this->mERatingHost . "\r\n";
        $this->mLog .= 'REQUEST TIME:' . time2format() . "\r\n";

        //记录加密前的request
        $this->mLog .= 'REQUEST DATA:' . "\r\n";
        $this->mLog .= $this->mRequest . "\r\n";

        //生成post信息
        $this->createPost();

        //发送协议
        $this->curl_link();

        //处理返回数据
        $this->processRespond();

        //记录返回时间
        $this->mLog .= 'RESPOND TIME:' . time2format($this->mRespond['time']) . "\r\n";

        //获取返回信息
        $this->mRespond = $this->mRespond['info'];

        //解密
        $this->decrypt();

        //记录解密后的respond
        $this->mLog .= 'RESPOND DATA:' . "\r\n";
        $this->mLog .= $this->mRespond . "\r\n";
        $this->log();

        //处理xml
        $this->processXml();

        //返回
        $this->mReturn = $this->mRespond['body'];
        if ($this->mReturn['result_code'] != '1') {
            if(empty($this->mReturn['result_code'])){
                $this->mReturn['result_code'] = '-302';
            }
            switch($this->mReturn['result_code']){
                case '-100':
                    C('G_ERROR', 'username_format');
                    break;
                case '-1251':
                    C('G_ERROR', 'nickname_existed');
                    break;
                case '-300':
                case '-301':
                case '-302':
                case '-1418':
                case '-1811':
                    C('G_ERROR', 'login_timeout');
                    break;
                default:
                    //调试
                    if (APP_DEBUG){
                        C('G_ERATING_ERROR', $this->mReturn['result_code']);
                    }
                    C('G_ERROR', 'linekong_error');
            }
            return false;
        } else {
            unset($this->mReturn['result_code']);
            return $this->mReturn;
        }

    }

    //并发发送
    public function multi($sid, $list)
    {

        //eRating HOST
        $this->mERatingHost = $this->mServerList[$sid]['eRating']['url'];

        //蓝港头部信息
        $this->mGatewayId = $this->mServerList[$sid]['eRating']['gateway_id'];

        //请求数据
        $requestList = array();
        $requestCryptList = array();
        foreach ($list as $key => $value) {

            //蓝港头部信息
            $this->mGameId = $this->mServerList[$sid]['channel'][$value['channel_id']]['code'];
            $this->mCommandId = $value['command_id'];
            $this->mBody = json_decode($value['body'], true);
            if($value['count'] == '0') {
                if ($this->mCommandId == '10003412' || $this->mCommandId == '10003717') {
                    $this->mBody['detail_id'] = substr($this->mBody['detail_id'], 0, 6) . substr(100000000000 + $value['id'], 1);
                    $list[$key]['body'] = json_encode($this->mBody);
                }
            }

            //生成XML
            $this->createXml();

            //塞入请求列表
            $requestList[] = $this->mRequest;

            //生成post信息
            $this->createPost();
            $requestCryptList[] = $this->mRequest;

        }

        //并发
        $reqTime = time2format(null);
        $respondList = $this->curl_multi_link($requestCryptList);

        //记录日志
        $rs = array();
        for ($i = 0; $i < count($list); ++$i) {

            //记录发送协议时间
            $this->mLog = 'REQUEST HOST:' . $this->mERatingHost . "\r\n";
            $this->mLog .= 'REQUEST TIME:' . $reqTime . "\r\n";

            //记录加密前的request
            $this->mLog .= 'REQUEST DATA:' . "\r\n";
            $this->mLog .= $requestList[$i] . "\r\n";

            //返回信息
            $this->mRespond = $respondList[$i];

            //处理返回数据
            $this->processRespond();

            //记录返回时间
            $this->mLog .= 'RESPOND TIME:' . time2format($this->mRespond['time']) . "\r\n";

            //获取返回信息
            $this->mRespond = $this->mRespond['info'];

            //解密
            $this->decrypt();

            //记录解密后的respond
            $this->mLog .= 'RESPOND DATA:' . "\r\n";
            $this->mLog .= $this->mRespond . "\r\n";
            $this->log();

            //处理xml
            $this->processXml();

            //返回
            $this->mReturn = $this->mRespond['body'];
            if ($this->mReturn['result_code'] != '1') {
                $arr = $list[$i];
                unset($arr['id']);
                $rs['error'][] = $arr;
            }
            $rs['respond'][] = $this->mReturn;

        }

        //返回
        return $rs;

    }

    //充值回调处理完后生成数据
    public function getCallBackData($callback)
    {

        //处理返回数据
        $this->mRespond = $callback;
        $this->processRespond();
        $this->mReturn['time'] = $this->mRespond['time'];

        //获取返回信息
        $this->mRespond = $this->mRespond['info'];

        //解密
        $this->decrypt();
        $this->mReturn['xml'] = $this->mRespond;

        //处理xml
        $this->processXml();
        $this->mReturn['data'] = $this->mRespond['header'] + $this->mRespond['body'];
        return $this->mReturn;
    }

    //充值回掉返回xml
    public function createRespondData($sid, $return, $body)
    {

        //eRating地址
        $this->mERatingHost = $this->mServerList[$sid]['eRating']['url'];
        $this->mLog = 'REQUEST HOST:' . $this->mERatingHost . "\r\n";

        //记录发送协议时间
        $this->mLog .= 'REQUEST TIME:' . time2format($return['time']) . "\r\n";

        //记录加密前的request
        $this->mLog .= 'REQUEST DATA:' . "\r\n";
        $this->mLog .= $return['xml'] . "\r\n";

        //蓝港头部信息
        $this->mCommandId = '2' . substr($return['data']['command_id'], 1);
        $this->mGameId = $return['data']['game_id'];
        $this->mGatewayId = $return['data']['gateway_id'];
        $this->mBody = $body;

        //生成xml
        $this->createXml();

        //记录发送协议时间
        $this->mLog .= 'RESPOND TIME:' . time2format() . "\r\n";

        //记录加密前的request
        $this->mLog .= 'RESPOND DATA:' . "\r\n";
        $this->mLog .= $this->mRequest . "\r\n";
        $this->log();

        //生成post信息
        $this->createPost();

        //返回
        return $this->mRequest;
    }

    //生成xml
    private function createXml()
    {
        $xml = '<agip>';
        $xml .= '<header>';
        $xml .= '<command_id>' . $this->mCommandId . '</command_id>';
        $xml .= '<game_id>' . $this->mGameId . '</game_id>';
        $xml .= '<gateway_id>' . $this->mGatewayId . '</gateway_id>';
        $xml .= '</header>';
        $xml .= '<body>';
        $xml .= $this->traverseXml($this->mBody);
        $xml .= '</body>';
        $xml .= '</agip>';
        $this->mRequest = $xml;
        return true;
    }

    //遍历
    private function traverseXml($body, $lastKey = null)
    {
        $xml = '';
        foreach ($body as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $xml .= '<' . $lastKey . '>' . $this->traverseXml($value, $key) . '</' . $lastKey . '>';
                } else {
                    if (isset($value[0])) {
                        $xml .= $this->traverseXml($value, $key);
                    } else {
                        $xml .= '<' . $key . '>' . $this->traverseXml($value, $key) . '</' . $key . '>';
                    }
                }
            } else {
                $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
            }
        }
        return $xml;
    }

    //生成协议
    private function createPost()
    {
        $this->encrypt();
        $post = 'info=' . $this->mRequest;
        $post .= '&time=' . time();
        $this->mRequest = $post;
        return;
    }

    //处理返回的字符串
    private function processRespond()
    {
        //处理返回数据
        $data = explode('&', $this->mRespond);
        foreach ($data as $value) {
            $arr = explode('=', $value);
            $return[$arr[0]] = $arr[1];
        }
        $this->mRespond = $return;
        return;
    }

    //处理返回的xml
    private function processXml()
    {
        $this->mRespond = json_decode(json_encode(simplexml_load_string($this->mRespond)), true);
    }

    //curl链接
    private function curl_link()
    {

        $header = array(
            'Pragma: no-cache',
            'Accept: */*',
            'Content-Type: text/xml',
        );

        $ch = curl_init($this->mERatingHost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //设置头信息的地方
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->mRequest);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $this->mRespond = curl_exec($ch);
        curl_setopt($ch, CURLOPT_HEADER, true);
//        dump(curl_getinfo($ch));
        curl_close($ch);

        if (empty($this->mRespond)) {
            return false;
        }

        return true;
    }

    //并发curl链接
    private function curl_multi_link($link)
    {

        //生成头部
        $header = array(
            'Pragma: no-cache',
            'Accept: */*',
            'Content-Type: text/xml',
        );

        //发起并发链接
        $mh = curl_multi_init();

        //循环发送
        $count = count($link);
        for ($i = 0; $i < $count; ++$i) {
            $ch[$i] = curl_init($this->mERatingHost);
            curl_setopt($ch[$i], CURLOPT_HTTPHEADER, $header); //设置头信息的地方
            curl_setopt($ch[$i], CURLOPT_POST, true);
            curl_setopt($ch[$i], CURLOPT_POSTFIELDS, $link[$i]);
            curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch[$i], CURLOPT_TIMEOUT, 15);
            curl_setopt($ch[$i], CURLOPT_CONNECTTIMEOUT, 15);
            curl_multi_add_handle($mh, $ch[$i]);
        }

        //发送连接
        $active = true;
        $mrc = CURLM_OK;
        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) == -1) {
                usleep(100);
            }
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        //获取返回
        $rs = array();
        for ($i = 0; $i < $count; ++$i) {
            $rs[$i] = curl_multi_getcontent($ch[$i]);
            curl_multi_remove_handle($mh, $ch[$i]);
            curl_close($ch[$i]);
        }

        //关闭并发连接
        curl_multi_close($mh);
        return $rs;
    }

    //记录日志
    private function log()
    {
        write_log($this->mLog, 'eRating/S' . C('G_SID') . '/' . date('Ymd') . '/', 3);
    }

    private function encrypt()
    {
        $aes = new \Org\Util\CryptAES();
        $aes->set_key(self::AES_KEY);
        $aes->require_pkcs5();
        $this->mRequest = $aes->encrypt($this->mRequest);
        return;
    }

    private function decrypt()
    {

        //解密
        $aes = new \Org\Util\CryptAES();
        $aes->set_key(self::AES_KEY);
        $aes->require_pkcs5();
        $respond = $aes->decrypt($this->mRespond);

        //处理解密数据
        $respond = str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $respond);
        $respond = str_replace("\n", '', $respond);
        $this->mRespond = str_replace(" ", '', $respond);
        return;
    }

    //获取IP
    public function getIP()
    {
        $ip = get_ip();
        if ($ip == 'unknown') {
            $ip = 0;
        } else {
            $arr = explode('.', $ip);
            $ip = $arr[0] * pow(256, 3) + $arr[1] * pow(256, 2) + $arr[2] * 256 + $arr[3];
        }
        return $ip;
    }

    //获取port
    public function getPort()
    {
        return $_SERVER["REMOTE_PORT"];
    }

    //获取客户端OS
    public function getClientOS($type)
    {
        return floor($type / 10000);
    }

    //活动列表
    public function activity($channelId, $userId, $roleId, $level, $tid)
    {

        //获取活动信息
        $body3512 = array(
            'user_id' => $userId,
            'role_id' => $roleId,
            'activity_id' => 0,
            'role_level' => $level,
        );
        if (false === $eRating = $this->index(10003512, C('G_SID'), $channelId, $body3512)) {
            return false;
        }

        //当前时间
        $now = time();

        //处理活动数据
        if (!empty($eRating['activity_info_list']['activity_info'])) {

            //获取物品列表
            $itemLK = D('Static')->access('item_lk');

            //领取协议
            $body3506 = array(
                'user_id' => $userId,
                'role_id' => $roleId,
                'item_info_list' => array(
                    'item_info' => array()
                ),
            );

            //邮件
            $mailAll = array();

            //生成邮件
            $mail['tid'] = $tid;
            $mail['type'] = 2;
            $mail['title'] = D('Static')->access('params', 'ACTIVITY_BONUS');
            $mail['from'] = 'GM';
            $mail['open_script'] = '';
            $mail['behave'] = get_config('behave', array('platform', 'code',));
            $mail['ctime'] = $now;
            $mail['dtime'] = $now + (7 * 86400);
            $mail['status'] = 0;

            //活动列表构造
            $activityList = array();
            if(!isset($eRating['activity_info_list']['activity_info'][0])){
                $activityList[0] = $eRating['activity_info_list']['activity_info'];
            }else{
                $activityList = $eRating['activity_info_list']['activity_info'];
            }

            //遍历活动
            $activityIdList = array();
            foreach ($activityList as $activityInfo) {

                //邮件描述
                $mail['des'] = empty($activityInfo['activity_desc']) ? $mail['title'] : $activityInfo['activity_desc'];

                //奖励列表构造
                $itemList = array();
                if(!isset($activityInfo['item_info_list']['item_info'][0])){
                    $itemList[0] = $activityInfo['item_info_list']['item_info'];
                }else{
                    $itemList = $activityInfo['item_info_list']['item_info'];
                }

                //遍历奖品
                $k = 1;
                foreach ($itemList as $itemInfo) {

                    if($k == 1){
                        //生成附件基本信息
                        for ($i = 1; $i <= 4; ++$i) {
                            $mail['item_' . $i . '_type'] = 0;
                            $mail['item_' . $i . '_value_1'] = 0;
                            $mail['item_' . $i . '_value_2'] = 0;
                        }
                    }

                    //先查看是否在领取时间内$itemInfo['begin_time'] <= $now && $now <= $itemInfo['end_time'] &&
                    if (in_array($itemInfo['item_code'], $itemLK)) {

                        //邮件奖励
                        $type = floor($itemInfo['item_code'] / 100000000);
                        $id = $itemInfo['item_code'] % 100000000;
                        $mail['item_' . $k . '_type'] = (int)$type;
                        $mail['item_' . $k . '_value_1'] = (int)$id;
                        $mail['item_' . $k . '_value_2'] = (int)$itemInfo['item_num'];

                        //如果满了4个奖品则重新计算则
                        if($k == 4){
                            $mailAll[] = $mail;
                            $k = 1;
                        }else{
                            ++$k;
                        }

                    }

                    //eRating领取
                    $receiveInfo = array(
                        'activity_id' => $activityInfo['activity_id'],
                        'item_code' => $itemInfo['item_code'],
                        'item_num' => $itemInfo['item_num'],
                    );
                    $body3506['item_info_list']['item_info'][] = $receiveInfo;
                    $activityIdList[] = $activityInfo['activity_id'];

                }

                //如果有剩余的道具没发则发送
                if($k > 1){
                    $mailAll[] = $mail;
                }

            }

            //发送领取协议
            if (false === $this->index(10003506, C('G_SID'), $channelId, $body3506)) {
                return false;
            }

            //发送邮件
            if(false === D('GMail')->CreateAllData($mailAll)){
                return false;
            }

            //完成活动
            if(!empty($activityIdList)){
                foreach($activityIdList as $activityId){
                    $activity = D('Static')->access('event_dynamic_pt', $activityId);
                    D('LActivityComplete')->cLog($tid, $activity);
                }
            }

        }

        //返回
        return true;

    }


}