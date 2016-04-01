<?php

/**
 * PayApp_Demo 演示如何编写使用360支付的app支付接口
 */
class PayApp_Demo implements PayApp_Interface
{

    //需要修改为应用自身的app_key
    private $_appKey;
    //需要修改为应用自身的app_secret(服务器之间通讯使用)
    private $_appSecret;
    //人民币-游戏货币的兑换比例
    private $_cashRate = 10;


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

    public function isValidOrder(array $orderParams)
    {
        if (!empty($orderParams['app_order_id'])) {
            //使用应用自身的订单号
            $orderId = $orderParams['app_order_id'];
        } else {
            //使用360支付的订单号
            $orderId = $orderParams['order_id'];
        }

        $order = $this->_getOrder($orderId);
        if (empty($order)) {
            return false;
        }

        //订单是否已经处理过
        //需要根据应用自身的数据表结构修改
        $orderProcessed = $order['processed'];
        if ($orderProcessed) {
            return false;
        }

        return true;
    }

    private function _getOrder($orderId)
    {
        //应该根据360支付返回的订单号或者应用自身的订单号($order['app_order_id'])查询应用订单数据表
        return array(
            'order_id' => $orderId,
            'processed' => false,
        );
    }

    public function processOrder(array $orderParams)
    {
        $re = $this->_updateOrder($orderParams);
        if (!$re) {
            return;
        }
        $this->_addCash($orderParams);
    }

    private function _updateOrder(array $orderParams)
    {
        //更新订单,标识为已经处理，避免重复处理
        //如果更新订单状态失败,记录异常，以便再次处理。再次处理的逻辑需应用自己处理
        return true;
    }

    private function _getAmount(array $orderParams)
    {
        if (!empty($orderParams['is_sms'])) {
            //短信支付通知时，amount值不可靠，只能使用consumeCode
            $amount = 0;
            //TODO::根据$orderParams['pay_ext']['notify_data']['consumeCode'] 反推出正确金额。 注意amount的单位为分
        } else {
            $amount = $orderParams['amount'];
        }
        return $amount;
    }

    private function _addCash(array $orderParams)
    {
        //发货或者增加游戏中的货币
        //如果发货失败，记录异常，以便在再次处理。处理的逻辑需应用自己处理。
        //充值金额，以人民币分为单位。例如2000代表20元
        $amount = $this->_getAmount($orderParams);
        //兑换比例(人民币兑换游戏货币，$rate==10,表示1元人民币可兑换10游戏货币)
        $gameCashNum = $amount / 100 * $this->_cashRate;
        return true;
    }

}