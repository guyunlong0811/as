<?php

/**
 *
 * 验证登录票据是否有效
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!qq.md#2.1.1/auth/verify_login
 *
 */
function verify_login($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/auth/verify_login';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 查询QQ账号会员VIP信息服务
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!qq.md#2.4.1_/profile/load_vip
 *
 */
function load_vip($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/profile/load_vip';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 查询QQ账号个人基本资料信息
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!qq.md#2.3.1.1接口说明
 *
 */
function qqprofile($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/relation/qqprofile';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 查询QQ同玩好友个人信息接口
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!qq.md#2.3.2/relation/qqfriends_detail
 *
 */
function qqfriends_detail($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/relation/qqfriends_detail';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 查询QQ同玩陌生人（包括好友）个人信息
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!qq.md#2.3.2/relation/qqfriends_detail
 *
 */
function qqstrange_profile($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/relation/qqstrange_profile';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 查询QQ同玩陌生人（包括好友）个人信息
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!qq.md#2.3.2/relation/qqfriends_detail
 *
 */
function qqfriends_vip($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/relation/qqfriends_vip';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 领取蓝钻礼包
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!qq.md#2.4.2_/profile/get_gift
 *
 */
function get_gift($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/profile/get_gift';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 获取随身wifi资格
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!qq.md#2.4.3_/profile/get_wifi
 *
 */
function get_wifi($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/profile/get_wifi';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 上报积分至QQ游戏中心排行榜
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!qq.md#2.4.4_/profile/qqscore_batch
 *
 */
function qqscore_batch($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/profile/qqscore_batch';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * access_token更新
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!wx.md#3.1.1_/auth/refresh_token
 *
 */
function wx_refresh_token($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/auth/refresh_token';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 验证授权凭证(access_token)是否有效
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!wx.md#3.1.2_/auth/check_token
 *
 */
function wx_check_token($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/auth/check_token';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 查询微信个人及同玩好友基本信息
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!wx.md#3.3.1_/relation/wxfriends_profile
 *
 */
function wxfriends_profile($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/relation/wxfriends_profile';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 查询微信个人及同玩好友基本信息
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!wx.md#3.3.1_/relation/wxfriends_profile
 *
 */
function wxprofile($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/relation/wxprofile';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 查询微信微信同玩好友的openid列表
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!wx.md#3.3.3/relation/wxfriends
 *
 */
function wxfriends($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/relation/wxfriends';

    return $sdk->api_msdk($script_name, $params, $qs, $method);

}

/**
 *
 * 查询微信微信同玩好友的openid列表
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!wx.md#3.3.4/relation/wxuserinfo(非精品业务使用)
 *
 */
function wxuserinfo($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/relation/wxuserinfo';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 查询微信微信同玩好友的openid列表
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!wx.md#3.4.1/profile/wxscore
 *
 */
function wxscore($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/profile/wxscore';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 上报战斗信息到微信游戏中心
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!wx.md#3.4.2/profile/wxbattle_report
 *
 */
function wxbattle_report($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/profile/wxbattle_report';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 查询微信特权信息
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array 特权信息
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!wx.md#3.4.3/profile/wxget_vip
 *
 */
function wxget_vip($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/profile/wxget_vip';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 游客模式下授权登录
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!guest.md#4.1.1_/auth/guest_check_token
 *
 */
function guest_check_token($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/auth/guest_check_token';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 分享消息给手机QQ好友, 在公众账号“QQ手游”中显示
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!qq.md#2.2.1_/share/qq
 *
 */
function share_qq($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/share/qq';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 上传图片到微信获取media_id
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!wx.md#3.2.1/share/upload_wx
 *
 */
function upload_wx($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/share/upload_wx';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

/**
 *
 * 将分享消息发送给微信好友（只能发送给安装了相同游戏的好友）
 *
 * @param object $sdk MSDK Object
 * @param array $params
 *
 * @return array
 *
 * @wiki http://wiki.dev.4g.qq.com/v2/ZH_CN/router/index.html#!wx.md#3.2.2/share/wx
 *
 */
function share_wx($sdk, $params, $qs){
    $method = 'post';
    $script_name = '/share/wx';

    return $sdk->api_msdk($script_name, $params, $qs, $method);
}

