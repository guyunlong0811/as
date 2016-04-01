<?php
namespace Home\Api;

use Think\Controller;

class ShopApi extends BaseApi
{

    private $info = array(
        101 => array('table' => 'Normal', 'num' => 6, 'behave' => 'normal',),//普通商店
        102 => array('table' => 'Mystery', 'num' => 12, 'behave' => 'mystery',),//神秘商店
        201 => array('table' => 'League', 'num' => 18, 'behave' => 'league',),//公会商店
//        202 => array('table' => 'Hero', 'num' => 12, 'behave' => 'hero',),//英雄商店
        301 => array('table' => 'Arena', 'num' => 12, 'behave' => 'arena',),//竞技场商店
        401 => array('table' => 'Vip', 'num' => 18, 'behave' => 'vip',),//VIP商店
        402 => array('table' => 'VipDaily', 'num' => 18, 'behave' => 'vip_daily',),//每日VIP商店
    );
    private $shopInfo;//当前商店信息

    public function _initialize()
    {
        parent::_initialize();

        //查看是否达到了开放条件
//        if($_POST['shop_type'] == 101){
//            if(!D('SOpenProcess')->checkOpen($this->mTid,1004)){exit;}
//        }

        //获取店信息
        $info = $this->info[$_POST['shop_type']];
        if (empty($info)) {
            C('G_ERROR', 'shop_not_exist');
            exit;
        }
        $config = D('Static')->access('shop', $_POST['shop_type']);
        $this->shopInfo = array_merge_recursive($info, $config);

        //非默认显示商店，查看是否开启
        if ($this->shopInfo['show_default'] != '1') {
            $where = "(`tid`='0' || `tid`='{$this->mTid}') && `type` = '{$_POST['shop_type']}'";
            $ctime = M('GShop')->where($where)->getField('ctime');
            if (empty($ctime)) {
                C('G_ERROR', 'shop_lock');
                exit;
            }
            if ($this->shopInfo['show_time'] > 0 && ($ctime + ($this->shopInfo['show_time'] * 60)) < time()) {
                D('GShop')->DeleteData($where);
                C('G_ERROR', 'shop_lock');
                exit;
            }

        }

    }

    //获取普通商店信息
    public function getList()
    {

        //获取今天手动刷新次数
        $list['refresh_count'] = D('TDailyShop')->getCount($this->mTid, $_POST['shop_type']);

        //查询玩家普通商店情况
        $shopInfo = D('GShop' . $this->shopInfo['table'])->getRow($this->mTid);
        if (empty($shopInfo)) {//第一次打开商店
            if (!$this->refresh($this->mTid, true)) {
                return false;
            }//刷新商店
            $shopInfo = D('GShop' . $this->shopInfo['table'])->getRow($this->mTid);
        } else {
            //检查玩家是否需要更新
            if ($this->isNeedRefresh($_POST['shop_type'], $shopInfo['refresh_time'])) {
                if (!$this->refresh($this->mTid)) {
                    return false;
                }//刷新商店
                $shopInfo = D('GShop' . $this->shopInfo['table'])->getRow($this->mTid);
            }
        }

        unset($shopInfo['tid']);
        $list['refresh_time'] = $shopInfo['refresh_time'];
        unset($shopInfo['refresh_time']);
        foreach ($shopInfo as $value) {
            $list['goods'][] = $value;
        }
        return $list;

    }

    //检查是否需要更新
    private function isNeedRefresh($type, $lastRefreshTime)
    {
        //检查是否到了更新的时间
        $time = D('Static')->access('shop', $type, 'refresh_time');
        if (empty($time) || $time == '0') {
            return false;
        }
        $time = explode(';', $time);

        //当前时间戳
        $now = time();

        //计算最近刷新时间
        $fresh = null;
        foreach ($time as $key => $value) {
            if (empty($value)) {
                unset($time[$key]);
                break;
            }
            if ($now >= strtotime(time2format(null, 2) . ' ' . $value)) {
                $fresh = strtotime(time2format(null, 2) . ' ' . $time[$key]);//系统更新时间
            }
        }

        //如果还没到今天的更新时间，则为昨天最后一次更新时间
        if ($fresh == null) {
            $fresh = strtotime(time2format(strtotime('yesterday'), 2) . ' ' . end($time));
        }

        //比较玩家最新更新时间是否在系统更新时间之后
        if ($lastRefreshTime <= $fresh)
            return true;//需要更新
        return false;//不需要更新

    }

    //更新商店物品
    private function refresh($tid, $new = false)
    {

        $data['tid'] = $tid;

        //随机每一个格子的道具
        for ($i = 1; $i <= $this->shopInfo['num']; ++$i) {
            $rate = array();
            //获取商品组ID
            $groupId = $this->shopInfo['location_' . $i];

            //获取商品组
            $group = D('Static')->access('shop_goods', $groupId);

            //按权重选出商品
            foreach ($group as $key => $value) {
                $rate[$key] = $value['goods_probability'];
            }
            $rs = weight($rate);//抽签
            $goods = $group[$rs];//选中的商品

            $data['goods_' . $i] = $goods['goods_tags'] . '#';
            $data['goods_' . $i] .= $goods['goods_type'] . '#';
            $data['goods_' . $i] .= $goods['goods_value'] . '#';
            $data['goods_' . $i] .= rand($goods['min_count'], $goods['max_count']) . '#';
            $data['goods_' . $i] .= $goods['currency_type'] . '#';
            $data['goods_' . $i] .= $goods['currency_value'] . '#';
            $data['goods_' . $i] .= $goods['limit_type'] . '#';
            $data['goods_' . $i] .= $goods['limit_value'] . '#';
            $data['goods_' . $i] .= '0';

        }

        if ($new) {
            return D('GShop' . $this->shopInfo['table'])->CreateData($data);
        } else {
            $where['tid'] = $tid;
            unset($data['tid']);
            return D('GShop' . $this->shopInfo['table'])->UpdateData($data, $where);
        }


    }

    //购买物品
    public function buy()
    {

        C('G_BEHAVE', C('G_BEHAVE') . '_' . strtolower($this->shopInfo['behave']));

        //查询玩家普通商店情况
        $shop = D('GShop' . $this->shopInfo['table'])->getRow($this->mTid);

        //没有商店信息则报错
        if (empty($shop)) {
            C('G_ERROR', 'shop_lock');
            return false;
        }

        //查看有没有过刷新时间
        if ($this->isNeedRefresh($_POST['shop_type'], $shop['refresh_time'])) {
            C('G_ERROR', 'shop_need_refresh');
            return false;
        }

        //查看商品编号是否正确
        if ($_POST['goods_no'] < 1 || $_POST['goods_no'] > $this->shopInfo['num']) {
            C('G_ERROR', 'shop_goods_not_exist');
            return false;
        }

        //解析商品信息（0:商品Tag;1:商品类型(1:道具;2:属性;3:神力;)#2:商品具体ID#3:商品数量#4:货币类型(1:金币;2:水晶;3:公会贡献度;)#5:单价#6:限制类型#7:限制参数#8:是否被购买）
        $goods = explode('#', $shop['goods_' . $_POST['goods_no']]);
//        $goodTag = $goods[0];
        $goodType = $goods[1];
        $goodId = $goods[2];
        $goodCount = $goods[3];
        $goodCurrencyType = $goods[4];
        $goodCurrencyValue = $goods[5];
        $goodLimitType = $goods[6];
        $goodLimitValue = $goods[7];
        $goodStatus = $goods[8];

        //检查是否已经被购买
        if ($goodStatus == '1') {
            C('G_ERROR', 'shop_goods_bought_already');
            return false;
        }

        //检查是否有限制
        switch ($goodLimitType) {
            case 0://无限制
                break;
            case 1://公会商店等级限制
                $leagueShopLevel = D('GLeague')->getLeagueShopLevel($this->mTid);
                if ($leagueShopLevel < $goodLimitValue) {
                    C('G_ERROR', 'league_shop_level_low');
                    return false;
                }
                break;
            case 2://公会商店等级限制
                $vipShopLevel = D('GVip')->getLevel($this->mTid);
                if ($vipShopLevel < $goodLimitValue) {
                    C('G_ERROR', 'vip_level_low');
                    return false;
                }
                break;
        }

        //查看钱够不够
        $need = $goodCurrencyValue * $goodCount;//需要的数量
        if (!$now = $this->verify($need, $this->mMoneyType[$goodCurrencyType])) {
            return false;
        }

        //购买
        //开始事务
        $this->transBegin();

        //增加商品
        if (!$this->produce($this->mBonusType[$goodType], $goodId, $goodCount)) {
            goto end;
        }

        //标识已经买过
        if (!D('GShop' . $this->shopInfo['table'])->buy($this->mTid, $_POST['goods_no'], $shop['goods_' . $_POST['goods_no']])) {
            goto end;
        }

        //扣钱
        if (!$this->recover($this->mMoneyType[$goodCurrencyType], $need, null, $now)) {
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

    //花钱刷新商店
    public function refreshNow()
    {

        C('G_BEHAVE', C('G_BEHAVE') . '_' . strtolower($this->shopInfo['table']));

        //查询商店是否允许刷新
        if ($this->shopInfo['exchange'] == 0) {
            C('G_ERROR', 'shop_not_allow_refresh');
            return false;
        }

        //获取今天刷新次数
        $count = D('TDailyShop')->getCount($this->mTid, $_POST['shop_type']);
        ++$count;

        //查询购买需要的货币
        $exchange = $this->exchangeMoney($this->shopInfo['exchange'], $count);
        $needType = $exchange['needType'];
        $needValue = $exchange['needValue'];

        //检查玩家当前货币是否足够
        if (!$now = $this->verify($needValue, $this->mMoneyType[$needType])) {
            return false;
        }

        //开始事务
        $this->transBegin();

        //刷新商店
        if (!$this->refresh($this->mTid)) {
            goto end;
        }

        //记录日志
        if (!D('TDailyShop')->record($this->mTid, $_POST['shop_type'])) {
            goto end;
        }

        //扣钱
        if (!$this->recover($this->mMoneyType[$needType], $needValue, null, $now)) {
            return false;
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