<?php
namespace Home\Model;

use Think\Model;

class PredisModel
{

    private $mSid;
    private $mClient;
    private $mList = array();

    public function __construct()
    {
        $this->mSid = C('G_SID');
        $this->mClient = get_predis($this->mSid);
        $this->mList = C('REDIS_DB');
    }

    //获取实例
    public function cli($dbName, $sid = null)
    {
        if (!is_null($sid) && $this->mSid != $sid) {
            $this->mSid = $sid;
            $this->mClient = get_predis($sid);
            $this->mList = C('REDIS_DB');
        }
        $this->mClient->select($this->mList[$dbName]);
        return $this->mClient;
    }

}