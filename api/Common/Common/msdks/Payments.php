<?php

/**
 *
 * 查询游戏币余额
 *
 * @param object $sdk MSDK Object
 * @param array $params params
 * @param array $cookie cookie params
 *
 * @return array
 *
 * @wiki http://wiki.mg.open.qq.com/index.php?title=Android%E6%94%AF%E4%BB%98API#.E6.9F.A5.E8.AF.A2.E4.BD.99.E9.A2.9D.E6.8E.A5.E5.8F.A3
 *
 */

function get_balance_m($sdk, $params, $cookie){
    $method="get";
    $script_name = '/mpay/get_balance_m';
    $cookie["org_loc"] = urlencode($script_name);
    return $sdk->api_pay($script_name, $params, $cookie, $method);
}


function pay_m($sdk, $params, $cookie){
    $method="get";
    $script_name = '/mpay/pay_m';
    $cookie["org_loc"] = urlencode($script_name);
    return $sdk->api_pay($script_name, $params, $cookie, $method);
}


function present_m($sdk, $params, $cookie){
    $method="get";
    $script_name = '/mpay/present_m';
    $cookie["org_loc"] = urlencode($script_name);
    return $sdk->api_pay($script_name, $params, $cookie, $method);
}

