<?php
namespace Home\Controller;

use Think\Controller;
use Think;

class RouterController extends Controller
{

    private $mResult = false;//处理结果
    private $mProtocol = false;//处理结果
    private $mRequest = array();//处理结果
    private $mRespond = array('id' => 0);//回应数据
    private $mErrorReport = array(
        901, 903, 1001, 1004, 1101, 1102, 1103, 1104,
    );
    private $mErrorApp = array(
        'dev199', 'review', 'asia',
    );

    //发起请求
    public function request()
    {

        //取得post字符串
        $post = trim(file_get_contents("php://input"));
        if (empty($post)) {
            C('G_ERROR', 'illegal');
            return false;
        }

        $this->mRequest = json_decode($post, true);
        if (json_last_error() != JSON_ERROR_NONE) {

            //初始化加密
            $aes = new \Org\Util\CryptAES();
            $aes->set_key($this->getAesKey());
            $aes->require_pkcs5();

            //解密
            $post = $aes->decrypt($post);
            $this->mRequest = json_decode($post, true);//解析结果为array
        }

        //解析字符串
        if (!is_array($this->mRequest)) {
            C('G_ERROR', 'illegal');
            return false;
        }

        //post信息记录日志
        Think\Log::record('POST数据:', 'DEBUG');
        Think\Log::record(json_encode($this->mRequest), 'DEBUG');

        //返回id不变
        $this->mRespond['id'] = $this->mRequest['id'];

        //全局变量
        $_POST = $this->mRequest['params'];

        //解析方法
        $method = explode('.', $this->mRequest['method']);

        //获取验证字段名
        $this->mProtocol = get_config('protocol', array($method[0], $method[1],));
        if (!isset($this->mProtocol)) {
            C('G_ERROR', 'protocol_error');
            return false;
        }

        //没有ID
        if (!isset($this->mRequest['id'])) {
            C('G_ERROR', 'id_not_exist');
            return false;
        }

        //注册SERVER
        C('G_METHOD', $this->mRequest['method']);
        C('G_SID', $this->mRequest['sid']);
        if(!isset($this->mRequest['token'])){
            $this->mRequest['token'] = '';
        }
        C('G_TOKEN', $this->mRequest['token']);

        //配置数据库
        if (!change_db_config($this->mRequest['sid'], 'all')) {
            C('G_ERROR', 'sid_not_exist');
            return false;
        }

        //查询sign是否已经发送过
        if (D('Predis')->cli('game')->exists('sn:' . $this->mRequest['id'] . ':' . $this->mRequest['sign'])) {
            C('G_ERROR', 'sign_repeat');
            return false;
        }

        //参数&sign验证
        $verify = $this->verify();
        if ($verify !== true) {
            C('G_DEBUG_PARAMS', $verify);
            C('G_ERROR', 'params_error');
            return false;
        }

        //定义行为
        if (isset($this->mProtocol['behave'])) {
            C('G_BEHAVE', $this->mProtocol['behave']);
        }

        //分发功能
        $c = $method[0];
        $a = $method[1];

        //执行协议逻辑
        $this->mResult = A($c, 'Api')->$a();

        //执行附加功能
        if ($this->mResult !== false && isset($_POST['sub_params']) && !empty($_POST['sub_params'])) {
            foreach ($_POST['sub_params'] as $key => $value) {
                A(ucfirst($key), 'Api')->complete($value);
            }
        }
        return;

    }

    //验证body
    private function verify()
    {

        //数据处理
        if (isset($this->mProtocol['params'])) {

            foreach ($this->mProtocol['params'] as $key => $value) {

                //不存在报错
                if (!isset($_POST[$key])) {
                    return $key;
                }

                //url解码
                if ($key != 'channel_token') {
                    $_POST[$key] = urldecode($_POST[$key]);
                }

                //去除前后空格
                $_POST[$key] = trim($_POST[$key]);

                switch ($value['type']) {
                    case 'number':
                        if (!is_numeric($_POST[$key])) {
                            return $key;
                        }
                        if (isset($value['regex']) && !preg_match($value['regex'], $_POST[$key])) {
                            return $key;
                        }
                        if (isset($value['gt']) && !($_POST[$key] > $value['gt'])) {
                            return $key;
                        }
                        break;

                    case 'string':
                        if (!is_string($_POST[$key])) {
                            return $key;
                        }
                        if (isset($value['regex']) && !preg_match($value['regex'], $_POST[$key])) {
                            return $key;
                        }
                        if (!is_complete_string($_POST[$key])) {
                            $_POST[$key] = mb_substr($_POST[$key], 0, -1);
                        }
                        break;
                    case 'json':
                        $_POST[$key] = json_decode($_POST[$key], true);
                        if (json_last_error() != JSON_ERROR_NONE) {
                            return $key;
                        }
                        break;
                }

                //昵称特殊处理
                if ($key == 'nickname' || $key == 'league_name') {
                    $length = mb_strlen($_POST[$key], 'gb2312');
                    if ($length < 2 || $length > 14) {
                        return $key;
                    }
                }

                //combo保护
                if ($key == 'combo') {
                    if ($_POST[$key] > 15000) {
                        $_POST[$key] = 1;
                        //发送邮件
                        $errorLog = json_encode($this->mRequest);
                        if (C('WARNING_TYPE') == 'File') {
                            write_log($errorLog, 'error/combo/');
                        } else if (C('WARNING_TYPE') == 'Mail') {
                            think_send_mail('error_report@forevergame.com', 'error_report', 'COMBO_ERROR(' . APP_STATUS . ')', $errorLog);
                        }
                    }
                }

            }

        }

        //检查游戏ID
        if (isset($_POST['gid']) && $_POST['gid'] != get_config('game_id')) {
            return 'gid';
        }

        //检查附加信息
        if (isset($_POST['sub_params']) && !empty($_POST['sub_params'])) {
            $_POST['sub_params'] = json_decode($_POST['sub_params'], true);
        }

        //检查时间戳
        if (isset($_POST['pts']) && abs(time() - $_POST['pts']) > get_config('verify', 'time_limit')) {
            return 'pts';
        }

        //检查时间戳
        if (abs(time() - $_POST['timestamp']) > get_config('verify', 'time_limit')) {
            return 'timestamp';
        }

        //生成sign
        $mySign = sign_create($this->mRequest['id'], $this->mRequest['sid'], $this->mRequest['method'], $this->mRequest['params'], 'request', $this->mRequest['ver']);

        //比较sign
        if ($this->mRequest['sign'] != $mySign) {
            return 'sign';
        }

        return true;

    }

    //返回方法
    private function respond()
    {

        if (C('G_ERROR') == 'db_error') {

            //请求详细信息
            $str = 'REQUEST:' . "\r\n";
            $str .= 'REQUEST:' . __SELF__ . '?' . file_get_contents("php://input") . "\r\n";

            //错误的SQL
            $str .= 'ERROR:' . "\r\n";
            foreach (C('G_SQL_ERROR') as $value)
                $str .= $value . ";\r\n";

            if (C('G_TRANS')) {//如果事务出错
                //所有的sql
                $str .= 'SQL:' . "\r\n";
                foreach (C('G_SQL') as $value)
                    $str .= $value . ";\r\n";
                write_log($str, 'error/trans/');//写入
            } else {//日志出错
                write_log($str, 'error/log/');//写入
            }

        }

        //返回参数
        if ($this->mResult !== false) {

            if ($this->mResult !== true) {
                //sign写入Redis
                D('Predis')->cli('game')->setex('sn:' . $this->mRequest['id'] . ':' . $this->mRequest['sign'], get_config('verify', 'time_limit'), '1');
                if (isset($this->mProtocol['key'])) {
                    $rs = $this->mResult;
                    $this->mResult = array();
                    $this->mResult[$this->mProtocol['key']] = $rs;
                } else if ($this->mResult === true) {
                    $this->mResult = array();
                }
                $this->mRespond['result'] = $this->mResult;
            }
            $this->mRespond['result']['timestamp'] = time();

        } else {
            $this->mRespond['error'] = $this->getError();
            $this->mRespond['error']['timestamp'] = time();
//            if (APP_DEBUG === true) {
                //参数错误
                $params = C('G_DEBUG_PARAMS');
                if (!empty($params)) {
                    $this->mRespond['error']['params'] = $params;
                }
                //静态表错误
                $static = C('G_DEBUG_STATIC');
                if (!empty($static)) {
                    $this->mRespond['error']['static'] = $static;
                }
                //静态表(D)错误
                $dyn = C('G_DEBUG_DYNAMIC');
                if (!empty($dyn)) {
                    $this->mRespond['error']['dynamic'] = $dyn;
                }
                //DB错误
                $sql = C('G_SQL_ERROR');
                if (!empty($sql)) {
                    $this->mRespond['error']['sql'] = $sql;
                }
                //UC错误
                $uc = C('G_UC_ERROR');
                if (!empty($uc)) {
                    $this->mRespond['error']['uc'] = $uc;
                }
                //平台报错
                $pt = C('G_DEBUG_PT_ERROR');
                if (!empty($pt)) {
                    $this->mRespond['error']['pt'] = $pt;
                }
//            }
            if (!empty($this->mRequest) && in_array($this->mRespond['error']['code'], $this->mErrorReport) && in_array(APP_STATUS, $this->mErrorApp)) {

                //发送邮件
                $errorLog = json_encode($this->mRequest) . '#' . json_encode($this->mRespond);
                if (C('WARNING_TYPE') == 'File') {
                    write_log($errorLog, 'error/api/');
                } else if (C('WARNING_TYPE') == 'Mail') {
                    think_send_mail('error_report@forevergame.com', 'error_report', 'API_ERROR(' . APP_STATUS . ')', $errorLog);
                }

            }

        }

        array_value2string($this->mRespond);

        //生成sign
//        $this->mRespond['sign'] = sign_create($params,'respond',$this->mRespond['ver']);

        //输出结果
        header_info('plain');

        //json格式
        $this->mRespond = json_encode($this->mRespond);

        //初始化加密
        $aes = new \Org\Util\CryptAES();
        $aes->set_key($this->getAesKey());
        $aes->require_pkcs5();

        //AES加密
        $this->mRespond = $aes->encrypt($this->mRespond);
        $this->mRespond = strtoupper($this->mRespond);

        //结果
        echo $this->mRespond;
        return;

    }

    //获取AESKEY
    private function getAesKey()
    {
        $key = S(C('APC_PREFIX') . 'aes_key');
        if (empty($key)) {
            //获取当前服务器基座版本
            $content = D('StaticDyn')->access('params', 'VERLIST');
            $content = trim($content);
            $aes = new \Org\Util\CryptAES();
            $aes->set_key(C('AES_KEY'));
            $aes->require_pkcs5();
            $content = $aes->decrypt($content);
            $content = json_decode($content, true);
            $key = substr($content['currentmd5'], 0, 16);
            S(C('APC_PREFIX') . 'aes_key', $key);
        }
        return $key;
    }


    //解析数据
    private function getError()
    {

        //错误码信息
        $error = C('G_ERROR') ? C('G_ERROR') : 'unknown';
        $errorInfo = get_config('error', $error);//返回错误信息

        //服务器维护特殊逻辑
        if($errorInfo['code'] == 1106){
            $msg = D('GParams')->getValue('MAINTAIN_TIPS');
            if(!empty($msg)){
                $errorInfo['message'] = $msg;
            }
        }

        //返回
        return $errorInfo;
    }

    //空操作
    public function _empty()
    {
        return false;
    }

    //析构
    public function __destruct()
    {
        parent::__destruct();
        $this->respond();//返回方法
    }

}