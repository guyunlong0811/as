<?php
namespace Home\Model;

use Think\Model;

class MsdkModel
{

//    const SERVER_NAME = 'msdktest.qq.com';
    const SERVER_NAME = 'msdk.qq.com';

//    const QQ_PAY_APP_KEY = 'BrOiPnR4Bq4mDicP';
    const QQ_PAY_APP_KEY = 'EpGxMpnEYjUOLkKEbAcTurxcgs1C22Fo';

    const APP_NAME = '永恒幻想';

    const WX_APP_ID = 'wx8e6ac36aa6213cdd';
    const WX_APP_KEY = 'cd12131f1d52b250cdb9dac8fe35546a';
    const WX_SESSION_ID = 'hy_gameid';
    const WX_SESSION_TYPE = 'wc_actoken';

    const QQ_APP_ID = '1105054016';
    const QQ_APP_KEY = 'BrOiPnR4Bq4mDicP';
    const QQ_SESSION_ID = 'openid';
    const QQ_SESSION_TYPE = 'kp_actoken';

    private $mSDK;
    private $mTS;
    private $mQS;
    private $mCookie;

    private $mAppId;
    private $mAppKey;
    private $mPayAppId;
    private $mPayAppKey;
    private $mSessionId;
    private $mSessionType;

    //用户
    private $mOpenId;

    //分区ID
    private $mZoneId = 1;


    //初始化
    public function __construct()
    {
        require_once COMMON_PATH . 'Common/msdks/Api.php';
        require_once COMMON_PATH . 'Common/msdks/Msdk.php';
        require_once COMMON_PATH . 'Common/msdks/Payments.php';
        $this->mTS = time();
        $serverList = get_server_list();
        $this->mZoneId = $serverList[C('G_SID')]['platform']['sid'];
    }

    //创建SDK对象
    private function cSDK($pt, $openId)
    {

        $this->mPayAppId = self::QQ_APP_ID;
        $this->mPayAppKey = self::QQ_PAY_APP_KEY;
        switch ($pt) {
            case '1':
                $this->mAppId = self::WX_APP_ID;
                $this->mAppKey = self::WX_APP_KEY;
                $this->mSessionId = self::WX_SESSION_ID;
                $this->mSessionType = self::WX_SESSION_TYPE;
                break;
            case '2':
                $this->mAppId = self::QQ_APP_ID;
                $this->mAppKey = self::QQ_APP_KEY;
                $this->mSessionId = self::QQ_SESSION_ID;
                $this->mSessionType = self::QQ_SESSION_TYPE;
                break;
            default:
                return false;
        }

        // 创建MSDK实例
        $this->mSDK = new \Api($this->mAppId, $this->mAppKey);
        // 设置支付信息
        $this->mSDK->setPay($this->mPayAppId, $this->mPayAppKey);
        // 设置MSDK调用环境
        $this->mSDK->setServerName(self::SERVER_NAME);

        //openId
        $this->mOpenId = $openId;

        // MSDK接口请求URI参数
        $this->mQS = array(
            'appid' => $this->mAppId,
            'timestamp' => $this->mTS,
            'sig' => md5($this->mAppKey . $this->mTS),
            'encode' => 1,
            'openid' => $this->mOpenId,
        );

        $this->mCookie = array(
            'session_id' => $this->mSessionId,
            'session_type' => $this->mSessionType,
            'org_loc' => ''
        );

        //返回
        return true;

    }

    //微信登录验证
    public function wx_check_token($openId, $openKey)
    {
        $pt = 1;

        //创建SDK对象
        if (!$this->cSDK($pt, $openId)) {
            return false;
        }

        //参数
        $params = array(
            'openid' => $this->mOpenId,
            'accessToken' => $openKey,
        );
        //返回
        return wx_check_token($this->mSDK, $params, $this->mQS);
    }

    //手Q登录验证
    public function verify_login($openId, $openKey)
    {
        $pt = 2;

        //创建SDK对象
        if (!$this->cSDK($pt, $openId)) {
            return false;
        }

        //参数
        $params = array(
            'appid' => $this->mAppId,
            'openid' => $this->mOpenId,
            'openkey' => $openKey,
            'userip' => get_ip(),
        );
        //返回
        return verify_login($this->mSDK, $params, $this->mQS);
    }

    //获取当前游戏币(diamond_pay)
    public function get_balance_m($pt, $openId, $openKey, $payToken, $pf, $pfKey)
    {
        //创建SDK对象
        if (!$this->cSDK($pt, $openId)) {
            return false;
        }

        //参数
        $params = array(
            'openid' => $openId,
            'openkey' => $openKey,
            'pay_token' => $payToken,
            'ts' => $this->mTS,
            'pf' => $pf,
            'pfkey' => $pfKey,
            'zoneid' => $this->mZoneId,
        );

        $log = 'datetime:' . time2format() . "\n";
        $log .= json_encode($params) . "\n";

        $rs = get_balance_m($this->mSDK, $params, $this->mCookie);

        $log .= json_encode($rs) . "\n";
        $log .= "====================================================\n";

        write_log($log, "tx/get_balance_m/s" . C('G_SID') . '/', 3);

        if ($rs['ret'] != 0) {
            return false;
        } else {
            $return['diamond_pay'] = $rs['balance'];
            $return['pay'] = $rs['save_amt'];
            return $return;
        }
    }

    //扣除游戏币(diamond_pay)
    public function pay_m($pt, $openId, $openKey, $payToken, $pf, $pfKey, $amt)
    {
        //创建SDK对象
        if (!$this->cSDK($pt, $openId)) {
            return false;
        }

        //参数
        $params = array(
            'openid' => $openId,
            'openkey' => $openKey,
            'pay_token' => $payToken,
            'ts' => $this->mTS,
            'pf' => $pf,
            'pfkey' => $pfKey,
            'zoneid' => $this->mZoneId,
            'amt' => $amt,
        );

        $log = 'datetime:' . time2format() . "\n";
        $log .= json_encode($params) . "\n";

        $rs = pay_m($this->mSDK, $params, $this->mCookie);

        $log .= json_encode($rs) . "\n";
        $log .= "====================================================\n";

        write_log($log, "tx/pay_m/s" . C('G_SID') . '/', 3);

        if ($rs['ret'] != 0) {
            return false;
        } else {
            return $rs['balance'];
        }
    }

}