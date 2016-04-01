<?php

class PayApp_OTL implements PayApp_Interface
{

    //需要修改为应用自身的app_key
    private $_appKey;
    //需要修改为应用自身的app_secret(服务器之间通讯使用)
    private $_appSecret;


    public function __construct($appKey, $appSecret)
    {
        $this->_appKey = $appKey;
        $this->_appSecret = $appSecret;

    }

    public function getAppKey()
    {
        return $this->_appKey;
    }

    public function getAppSecret()
    {
        return $this->_appSecret;
    }

}
