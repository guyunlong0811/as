<?php
namespace Home\Api;

use Think\Controller;

class FundApi extends BaseApi
{

    //获取信息
    public function getInfo()
    {
        //获取系数
        $close = D('GParams')->getValue('FUND_CLOSE');
        if(strtotime($close) < time()){
            $info['open'] = 0;
        }else{
            $info['open'] = 1;
        }

        //计算购买人数
        $rate = D('GParams')->getValue('FUND_RATE');//获取系数
        $maxRank = D('Predis')->cli('game')->get('arena_max_rank');//获取竞技场最大排名
        $maxRank -= 10000;
        $count = D('GTeam')->where("`fund`='1'")->count();
        $info['count'] = ceil($maxRank * $rate) + ceil($count * 1.5);

        //已领取列表
        $info['list'] = D('GFund')->getList($this->mTid);

        //返回
        return $info;
    }

    //购买基金
    public function buy()
    {
        //获取系数
        $close = D('GParams')->getValue('FUND_CLOSE');
        if(strtotime($close) < time()){
            C('G_ERROR', 'fund_close');
            return false;
        }

        //检查VIP等级
//        $needVip = D('Static')->access('params', '');
        $needVip = 3;
        if (!$this->verify($needVip, 'vip')) {
            return false;
        }

        //查看是否购买过
        $isFund = D('GTeam')->getAttr($this->mTid, 'fund');
        if($isFund == '1'){
            C('G_ERROR', 'fund_buy_already');
            return false;
        }

        //获取基金价格
        $need = D('Static')->access('params', 'FUND_BUY_MONEY');

        //检查水晶是否足够
        if (!$diamondNow = $this->verify($need, 'diamond')) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //扣除水晶
        if (!$this->recover('diamond', $need, null, $diamondNow)) {
            goto end;
        }

        //更改购买状态
        $data['fund'] = 1;
        $where['tid'] = $this->mTid;
        if (!D('GTeam')->UpdateData($data, $where)) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return true;

    }

    //领取奖励
    public function receive(){

        //查询是否已经购买
        $fund = D('GTeam')->getAttr($this->mTid, 'fund');
        if($fund != '1'){
            C('G_ERROR', 'fund_not_buy');
            return false;
        }

        //查询是否已经领取
        if (D('GFund')->isCompleted($this->mTid, $_POST['level'])) {
            C('G_ERROR', 'fund_received');
            return false;
        }

        //等级是否足够
        if (!$this->verify($_POST['level'], 'level')) {
            return false;
        }

        //获取配置
        $diamond = D('Static')->access('fund', $_POST['level'], 'diamond');
        if(empty($diamond)){
            C('G_ERROR', 'fund_not_exist');
            return false;
        }

        //开始事务
        $this->transBegin();

        //获得水晶
        if (!$this->produce('diamond', $diamond)) {
            goto end;
        }

        //标识领取
        if (!D('GFund')->receive($this->mTid, $_POST['level'])) {
            goto end;
        }

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return true;

    }

}