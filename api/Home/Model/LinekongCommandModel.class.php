<?php
namespace Home\Model;

use Think\Model;

class LinekongCommandModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
    );

    //创建数据
    public function cData($commandId, $channelId, $body, $count = 0)
    {
        $data['command_id'] = $commandId;
        $data['channel_id'] = $channelId;
        $data['body'] = json_encode($body);
        $data['count'] = $count;
        return $this->CreateData($data);
    }

}