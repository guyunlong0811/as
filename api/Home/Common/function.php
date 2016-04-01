<?php
/************************ 服务器 **************************/
//获取服务器列表
function get_server_list()
{

    $serverList = S(C('APC_PREFIX') . 'server');

    if (empty($serverList)) {

        $params['gid'] = C('GAME_ID');

        //发送协议
        $uc = uc_link($params, 'User.getServerList');

        //登录用户中心
        if (empty($uc['list'])) {
            C('G_ERROR', 'db_error');
            return false;
        }
        foreach ($uc['list'] as $value) {

            if (isset($serverList[$value['sid']]['channel'][$value['channel_id']])) {
                $serverList[$value['sid']]['channel'][$value['channel_id']]['name'][] = $value['name'];
            } else {
                $channel = array();
                $channel['channel_id'] = $value['channel_id'];
                $channel['name'][] = $value['name'];
                $channel['type'] = $value['type'];
                $channel['activation'] = $value['activation'];
                $channel['code'] = $value['code'];
                $channel['callback'] = $value['callback'];
                $serverList[$value['sid']]['channel'][$value['channel_id']] = $channel;
            }
            $serverList[$value['sid']]['dbname'] = $value['dbname'];
            $serverList[$value['sid']]['master']['DB_DEPLOY_TYPE'] = 0;
            $serverList[$value['sid']]['master']['DB_RW_SEPARATE'] = false;
            $serverList[$value['sid']]['master']['DB_HOST'] = $value['db_m_host'];
            $serverList[$value['sid']]['master']['DB_USER'] = $value['db_m_user'];
            $serverList[$value['sid']]['master']['DB_PWD'] = $value['db_m_pwd'];
            $serverList[$value['sid']]['master']['DB_PORT'] = $value['db_m_port'];
            $serverList[$value['sid']]['all']['DB_DEPLOY_TYPE'] = 1;
            $serverList[$value['sid']]['all']['DB_RW_SEPARATE'] = true;
            $serverList[$value['sid']]['all']['DB_HOST'] = $value['db_m_host'] . ',' . $value['db_s_host'];
            $serverList[$value['sid']]['all']['DB_USER'] = $value['db_m_user'] . ',' . $value['db_s_user'];
            $serverList[$value['sid']]['all']['DB_PWD'] = $value['db_m_pwd'] . ',' . $value['db_s_pwd'];
            $serverList[$value['sid']]['all']['DB_PORT'] = $value['db_m_port'] . ',' . $value['db_s_port'];
            $serverList[$value['sid']]['redis']['host'] = $value['redis_host'];
            $serverList[$value['sid']]['redis']['port'] = $value['redis_port'];
            $serverList[$value['sid']]['redis']['game'] = $value['redis_game'];
            $serverList[$value['sid']]['redis']['social'] = $value['redis_social'];
            $serverList[$value['sid']]['redis']['fight'] = $value['redis_fight'];
            $serverList[$value['sid']]['platform']['url'] = $value['platform_url'];
            $serverList[$value['sid']]['platform']['sid'] = $value['platform_sid'];
        }

        //存储缓存
        S(C('APC_PREFIX') . 'server', $serverList);

    }

    return $serverList;
}

//改变数据库配置
function change_db_config($sid, $type)
{
    $list = get_server_list();
    if (empty($list[$sid])) {
        return false;
    }
    $config = $list[$sid][$type];
    C($config);
    C('DB_NAME', $list[$sid]['dbname']);
    return true;
}

//写文件
function write_log($str, $path, $type = 1)
{

    $path = LOG_PATH . $path;
    // 如果不存在则创建
    if (!is_dir($path)) {
        mkdirs($path);
    }
    switch ($type) {
        case 1:
            $filename = date('Ymd');
            break;
        case 2:
            $filename = date('Ym');
            break;
        case 3:
            $filename = date('YmdHi');
            break;
    }
    $file = $path . $filename . ".log";
    $wfp = fopen($file, "a");
    fputs($wfp, $str . "\r\n");
    fclose($wfp);
    return;
}

//创建目录
function mkdirs($dir)
{
    if (!is_dir($dir)) {
        if (!mkdirs(dirname($dir))) {
            return false;
        }
        if (!mkdir($dir, 0777)) {
            return false;
        }
    }
    return true;
}

//保存SQl至配置
function save_sql($sql, $error = false)
{

    if ($error || C('G_ERROR') == 'db_error') {
        $sqlList = C('G_SQL_ERROR');
        $sqlList[] = $sql;
        C('G_SQL_ERROR', $sqlList);
    }

    if (C('G_TRANS')) {
        $sqlList = C('G_SQL');
        $sqlList[] = $sql;
        C('G_SQL', $sqlList);
    }

    return;

}

//获取Predis客户端对象
function get_predis($sid = null)
{
    $list = get_server_list();
    $sid = $sid == null ? C('G_SID') : $sid;
    if (empty($sid)) {
        return false;
    }
    $redis = $list[$sid]['redis'];
    C('REDIS_DB', $redis);
    $server = array('host' => $redis['host'], 'port' => $redis['port'], 'database' => $redis['game']);
    require_once(APP_PATH . '../Predis/Autoloader.php');
    Predis\Autoloader::register();
    $client = new Predis\Client($server);
    return $client;
}

//返回
function header_info($type = 'html', $charset = 'utf-8')
{
    switch ($type) {
        case 'html':
            $type = 'text/html';
            break;
        case 'json':
            $type = 'application/json';
            break;
        case 'xml':
            $type = 'text/xml';
            break;
        case 'plain':
            $type = 'text/plain';
            break;
    }
    header("Content-type:{$type}; charset={$charset}");
}

//时间格式转化方法
function time2format($time = null, $k = 1)
{

    $format = array(
        1 => "Y-m-d H:i:s",
        2 => "Y-m-d",
        3 => "Y/m/d",
        4 => "H:i:s",
        5 => "H:i",
        6 => "Ymd",
    );

    if ($time === null)
        return date($format[$k]);

    if ($time <= 0)
        return false;

    return date($format[$k], $time);

}

//curl链接
function curl_link($host, $method = 'get', $data = '', $cookie = '', $return = true, $agent = 'WEBSERVER')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $host);

    if (strtolower($method) == 'post')
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    if (!empty($cookie))
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, $return);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    $retData = curl_exec($ch);
//    dump(curl_getinfo($ch,CURLINFO_HTTP_CODE));
    curl_close($ch);
    return $retData;
}

//AppStore充值验证
function verify_app_store($receipt, $is_sandbox = false)
{
    //$sandbox should be TRUE if you want to test against itunes sandbox servers
    if ($is_sandbox) {
        $url = "https://sandbox.itunes.apple.com/verifyReceipt";
    } else {
        $url = "https://buy.itunes.apple.com/verifyReceipt";
    }

    $receipt = json_encode(array("receipt-data" => $receipt));
    $response_json = curl_link($url, 'post', $receipt);
    $response = json_decode($response_json, true);

//    $strLog = 'Receipt : '.$receipt."\n";
    $strLog = 'Verify : ' . $response_json . "\n";
    if ($response['status'] == 0) {//eithr OK or expired and needs to synch
//        $strLog .= "Verify OK\n";
        $return = $response;
    } else {
//        $strLog .= "Verify failed\n";
        $return = false;
    }

    $strLog .= "================================================\n";
    write_log($strLog, 'pay/apple/');
    return $return;

}

//获取IP
function get_ip()
{

    if ($_SERVER['HTTP_X_FORWARDED_FOR']) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if ($_SERVER['HTTP_CLIENT_IP']) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else if ($_SERVER['REMOTE_ADDR']) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else if (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } else if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } else if (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    } else {
        $ip = 'unknown';
    }
    return $ip;
}

//获取毫秒
function micro_time($num = 3)
{
    $format = explode(' ', microtime());
    $time = sprintf("%.{$num}f", $format[1] + $format[0]) * pow(10, $num);
    return (int)$time;
}

/************************ 服务器 **************************/

/************************ 游戏逻辑 **************************/
//过去最近的服务器自动更新时间
function get_daily_utime()
{
    $todayUtime = strtotime(time2format(null, 2) . ' ' . C('DAILY_UTIME'));
    $time = time() < $todayUtime ? ($todayUtime - 86400) : $todayUtime;
    return $time;
}

//生成sign
function sign_create($id, $sid, $method, $params, $type, $ver)
{

    //获取SALT
    $salt = get_config('verify', array($ver, $type, 'salt'));

    //排序
    ksort($params);

    //创建加密字符串
    $strSign = $id . '&' . $sid . '&' . $method . '&';
    foreach ($params as $value) {
        if (is_array($value))
            $strSign .= json_encode($value) . '&';
        else
            $strSign .= $value . '&';
    }
    $strSign .= $salt;
//    dump($strSign);
    $strSign = strtolower(md5($strSign));
    return $strSign;
}

//连接用户中心
function uc_link($params, $method)
{

    //创建数据
    if (!isset($params['timestamp'])) {
        $params['timestamp'] = time();
    }
    $post = array();
    $post['method'] = $method;
    $post['params'] = $params;
    $post['sign'] = uc_sign_create($params, 'request');
    $post = json_encode($post);

    //发送协议
    $json = curl_link(UC_URL . '?c=Router&a=request', 'post', $post);
    if(empty($json)){
        C('G_ERROR', 'uc_curl_error');
        return false;
    }

    //解码
    $arr = json_decode($json, true);
    if (isset($arr['result'])) {
        $ret = $arr['result'];
    } else {
        C('G_ERROR', $arr['error']['code']);
        C('G_UC_ERROR', $arr['error']['debug']);
        return false;
    }

    //检查sign
    $mySign = uc_sign_create($ret, 'respond');
    if ($mySign != $arr['sign']) {
        C('G_ERROR', 'uc_sign_error');
        return false;
    }

//    dump($ret);
    return $ret;
}

//生成sign
function uc_sign_create($params, $type)
{
    //获取SALT
    $salt = get_config('uc_verify', $type);
    //排序
    ksort($params);
    //创建加密字符串
    $strSign = '';
    foreach ($params as $value) {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $strSign .= $value . '&';
    }
    $strSign .= $salt;
    $strSign = strtolower(md5($strSign));
    return $strSign;
}

//获取LUA脚本
function lua($file, $func, $argc = array(), $dir = false)
{
    $inc = get_config('LUA_URL');
    if ($dir) {
        $inc .= $dir . '/';
    }
    $inc .= $file . '.lua';
    $lua = new Lua();
    if (false === $lua->include($inc)) {
        C('G_ERROR', 'lua_error');
        return false;
    }
    if (false === $rs = $lua->call($func, $argc)) {
        C('G_ERROR', 'lua_error');
        return false;
    }
    return $rs;
}

/************************ 游戏逻辑 **************************/

/************************ 算 法 **************************/
//生成登录TOKEN
function create_login_token($uid)
{
    $str = $uid . time() . get_config('token_key');
    return strtolower(md5($str));
}

//生成订单号
function create_order_id($tid)
{
    return $tid . '_' . time();
}

//数组元素全部转化为string型
function array_value2string(&$arr)
{
    if (is_array($arr) && !empty($arr)) {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                array_value2string($arr[$key]);
            } else {
                $arr[$key] = (string)$value;
            }
        }
    }
    return;
}

//权重算法
function weight($rate)
{
    $total = array_sum($rate);//所有概率
    $rand = rand(1, $total);
    $sum = 0;
    foreach ($rate as $key => $value) {
        $sum += $value;
        if ($rand <= $sum)
            return $key;
    }
}

//读取多层配置
function get_config($first, $key = false)
{

    //转大写
    $first = strtoupper($first);

    //读一层
    if (!$key)
        return C($first);

    //读两层
    if (!is_array($key))
        return C($first . '.' . $key);

    if (count($key) == 1)
        return C($first . '.' . $key[0]);

    //读多层
    $config = C($first);
    foreach ($key as $value)
        $config = $config[$value];

    return $config;

}

//截取字符串(可以有中文)
function over_cut($string, $start = 0, $length = 10, $charset = 'UTF-8')
{

    if (strlen($string) > $length + 2) {
        $str = mb_substr($string, $start, $length, $charset);
        return $str;
    }
    return $string;

}

//拼接IN句型
function sql_in_condition($arr)
{

    if (empty($arr))
        return false;

    $in = " in (";
    foreach ($arr as $value)
        $in .= "'{$value}',";
    $in = substr($in, 0, -1) . ")";
    return $in;

}

//将json或数组拼接成便于DB查询的字符串
function field2string($data, $link = '#')
{
    if (!is_array($data)) {
        $list = json_decode($data, true);
    } else {
        $list = $data;
    }
    $str = $link;
    foreach ($list as $value) {
        $str .= $value . $link;
    }
    return $str;
}

//数组排名
function arr_rank($arr, $k = 'rank')
{

    if (empty($arr))
        return false;

    $i = 1;
    foreach ($arr as $key => $value) {
        $arr[$key][$k] = $i;
        ++$i;
    }
    return $arr;

}

//二维数组按照某一个元素排序
function arr_field_sort($arr, $field, $type = 'asc')
{
    $field_value = $new_array = array();
    foreach ($arr as $k => $v) {
        $field_value[$k] = $v[$field];
    }
    if ($type == 'asc') {
        asort($field_value);
    } else {
        arsort($field_value);
    }
    foreach ($field_value as $k => $v) {
        $new_array[$k] = $arr[$k];
    }
    return $new_array;
}

//将数组转换为k-v格式
//数组，作为key的数据下标，作为value的数据下标，没有则为整个数组作为value，value中是否需要保留作为key的数据
function arr_kv($arr, $key, $value = null, $isKey = true)
{
    //判断数组是否为空
    if (empty($arr)) {
        return array();
    }

    //遍历数组
    $list = array();
    foreach ($arr as $data) {
        $listKey = $data[$key];
        if (is_null($value)) {
            if ($isKey === false) {
                unset($data[$key]);
            }
            $list[$listKey] = $data;
        } else {
            $list[$listKey] = $data[$value];
        }
    }

    //返回
    return $list;
}

//位运算计算和
function sum_bit($n)
{
    $r = 0;
    while ($n != 0) {
        $r++;
        $n &= ($n - 1);
    }
    return $r;
}

//屏蔽字
function shield($name)
{
    //读取字库
    $fp = fopen('shield.txt', 'r');
    $str = fread($fp, filesize('shield.txt'));
    fclose($fp);
    //处理字库
    $str = str_replace(array("\r\n", "\r", "\n"), "#", $str);
    $arr = explode('#', $str);
    //匹配
    $flag = true;
    foreach ($arr as $value) {
        if ($value == '') {
            continue;
        }
        $value = str_replace('(', '\(', $value);
        $pattern = "/{$value}/";
        $rs = preg_match($pattern, $name);
        if ($rs) {
            $flag = false;
            break;
        }
    }
    return $flag;
}

/************************ 算 法 **************************/

/**
 * 系统邮件发送函数
 * @param string $to 接收邮件者邮箱
 * @param string $name 接收邮件者名称
 * @param string $subject 邮件主题
 * @param string $body 邮件内容
 * @param string $attachment 附件列表
 * @return boolean
 */
function think_send_mail($to, $name, $subject = '', $body = '', $attachment = null)
{
    $config = C('THINK_EMAIL');
    vendor('PHPMailer.class#phpmailer'); //从PHPMailer目录导class.phpmailer.php类文件
    $mail = new PHPMailer(); //PHPMailer对象
    $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP();  // 设定使用SMTP服务
    $mail->SMTPDebug = 0;                     // 关闭SMTP调试功能
    // 1 = errors and messages
    // 2 = messages only
    $mail->SMTPAuth = true;                  // 启用 SMTP 验证功能
//    $mail->SMTPSecure = 'ssl';                 // 使用安全协议
    $mail->Host = $config['SMTP_HOST'];  // SMTP 服务器
    $mail->Port = $config['SMTP_PORT'];  // SMTP服务器的端口号
    $mail->Username = $config['SMTP_USER'];  // SMTP服务器用户名
    $mail->Password = $config['SMTP_PASS'];  // SMTP服务器密码
    $mail->SetFrom($config['FROM_EMAIL'], $config['FROM_NAME']);
    $replyEmail = $config['REPLY_EMAIL'] ? $config['REPLY_EMAIL'] : $config['FROM_EMAIL'];
    $replyName = $config['REPLY_NAME'] ? $config['REPLY_NAME'] : $config['FROM_NAME'];
    $mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    $mail->AddAddress($to, $name);
    if (is_array($attachment)) { // 添加附件
        foreach ($attachment as $file) {
            is_file($file) && $mail->AddAttachment($file);
        }
    }
    return $mail->Send() ? true : $mail->ErrorInfo;
}

function getUtf8Size($byte)
{
    /*
     UTF-8编码时
     0xC?打头的字节，表示该文字字符实际占用2字节(参考：http://www.fileformat.info/info/charset/UTF-8/list.htm?start=0)
     0xE?打头的字节，表示该文字字符实际占用3字节(参考：http://www.fileformat.info/info/charset/UTF-8/list.htm?start=1970)
     0xF?打头的字节，表示该文字字符实际占用4字节(参考：http://www.fileformat.info/info/charset/UTF-8/list.htm?start=43590)
     理论上存在5字节和6字节UTF8文字字符，但实际上是无效字符，不用考虑，具体见http://en.wikipedia.org/wiki/UTF-8
    */
    $byte = ($byte & 240);

    switch ($byte) {
        case 240://0xF0:
            return 4;
            break;

        case 224://0xE0:
            return 3;
            break;

        case 192://0xC0:
            return 2;
            break;

        default:
            return 1;
            break;
    }

}

function is_complete_string($str)
{
    $len = strlen($str);
    $i = 0;
    while ($i < $len) {
        $bytes = ord($str[$i]);
        $wordLen = getUtf8Size($bytes);
        $i += $wordLen;
        if ($i > $len) {
            return false;
        } else if ($i == $len) {
            return true;
        }
    }
}
