<?php

qihooLoad('Qihoo_Util');

class Qihoo_Pay
{

    const VERIFY_URL_ONLINE = 'http://msdk.mobilem.360.cn/pay/order_verify.json';
    const VERIFIED = 'verified';

    private $_appKey;
    private $_appSecret;

    /**
     * @var PayApp_Interface
     */
    private $_payApp;
    private $_request;

    public function __construct(PayApp_Interface $payApp)
    {
        $this->_payApp = $payApp;
        $this->_appKey = $payApp->getAppKey();
        $this->_appSecret = $payApp->getAppSecret();
        if (empty($this->_appSecret)) {
            die("fatal: this interface is for test app only!");
        }
    }

    public function processRequest()
    {
        $params = $_REQUEST;
        $this->_request = $params;
        if ($params['app_key'] != $this->_appKey) {
            return false;
        }

        if (!$this->_isValidRequest($params)) {
            return false;
        }

        $verfifyRet = $this->_verifyOrder($params);
        if ($verfifyRet != self::VERIFIED) {
            return false;
        }

        return true;

    }

    /**
     *
     * @param type $params
     */
    private function _isValidRequest($params)
    {
        $fields = array(
            'app_key',
            'amount',
            'product_id',
            'app_uid',
            'order_id',
            'sign_type',
            'gateway_flag',
            'sign',
            'sign_return',
        );

        foreach ($fields as $field) {
            if (empty($params[$field])) {
                return false;
            }
        }

        return $this->_isSignOk();
    }

    private function _isSignOk()
    {
        $params = $this->_request;
        $secret = $this->_appSecret;
        return Qihoo_Util::getSign($params, $secret) == $params['sign'];
    }

    private function _verifyOrder($params)
    {
        $url = self::VERIFY_URL_ONLINE;
        unset($params['gateway_flag'], $params['sign'], $params['sign_return']);
        $params['app_key'] = $this->_appKey;
        $params['sign'] = Qihoo_Util::getSign($params, $this->_appSecret);

        $url .= '?' . http_build_query($params);
        $ret = Qihoo_Util::request($url, Qihoo_Util::METHOD_POST);
        $json = json_decode($ret, TRUE);
        return $json['ret'];
    }

}

