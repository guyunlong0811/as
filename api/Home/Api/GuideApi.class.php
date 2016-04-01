<?php
namespace Home\Api;

use Think\Controller;

class GuideApi extends BaseApi
{

    //获取新手引导完成情况
    public function getList($tid = null)
    {
        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }
        //返回
        return D('GGuide')->getList($this->mTid);
    }

    //完成新手引导
    public function complete($step = array())
    {

        //步骤获取
        $step1 = !isset($step['step1']) ? $_POST['step1'] : $step['step1'];
        $step2 = !isset($step['step2']) ? $_POST['step2'] : $step['step2'];

        //查询当前引导情况
        $row = D('GGuide')->getRow($this->mTid, $step1);
        if (empty($row)) {
            $add['tid'] = $this->mTid;
            $add['step1'] = $step1;
            $add['step2'] = $step2;
            if (false === D('GGuide')->CreateData($add)) {
                return false;
            }
        } else {
            if ($row['step2'] < $step2) {
                $where['tid'] = $this->mTid;
                $where['step1'] = $step1;
                $where['step2'] = array('lt', $step2);
                $data['step2'] = $step2;
                if (false === D('GGuide')->UpdateData($data, $where)) {
                    return false;
                }
            }
        }

        return true;
    }

    //跳过引导
    public function skip()
    {
        $data['guide_skip'] = 1;
        $where['tid'] = $this->mTid;
        if (false === D('GTeam')->UpdateData($data, $where)) {
            return false;
        }
        return true;
    }

}