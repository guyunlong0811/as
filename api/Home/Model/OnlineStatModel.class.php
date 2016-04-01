<?php
namespace Home\Model;

use Think\Model;

class OnlineStatModel extends BaseModel
{

    public function record($gatewayId, $count)
    {
        if($gatewayId > 0){
            $where['server_id'] = $gatewayId;
            $data['online_number'] = $count;
            $data['update_time'] = time2format();
            return $this->UpdateData($data, $where);
        }
        return true;
    }

    public function newServer($gatewayId)
    {
        if($gatewayId > 0){
            $add['server_id'] = $gatewayId;
            $add['online_number'] = 0;
            $add['update_time'] = time2format();
            return $this->CreateData($add);
        }
        return true;
    }

    public function getCurrentCount()
    {
        return $this->sum('online_number');
    }

}