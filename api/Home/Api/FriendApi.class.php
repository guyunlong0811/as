<?php
namespace Home\Api;

use Think\Controller;

class FriendApi extends BaseApi
{

    //获取好友信息
    public function getList()
    {

        //查询所有好友tid
        $tidList = D('GFriend')->getList($this->mTid, 1);

        if (empty($tidList)) {
            return array();
        } else {

            //查询好友赠送体力情况
            $select = D('Predis')->cli('social')->keys('fv:*:' . $this->mTid);
            $friendValityList = array();
            if (!empty($select)) {
                foreach ($select as $value) {
                    $arr = explode(':', $value);
                    $status = D('Predis')->cli('social')->get($value);
                    $friendValityList[$arr[1]] = $status;
                }
            }

            //查询好友信息
            $list = D('GTeam')->getFriendList($tidList);

            //计算赠予和获取数量
            foreach ($list as $key => $value) {
                if (isset($friendValityList[$value['tid']])) {
                    if ($friendValityList[$value['tid']] == 1) {
                        $list[$key]['send'] = '1';
                        $list[$key]['get'] = '1';
                    } else {
                        $list[$key]['send'] = '1';
                        $list[$key]['get'] = '0';
                    }
                } else {
                    $list[$key]['send'] = '0';
                    $list[$key]['get'] = '0';
                }
            }

        }

        //返回
        return $list;

    }

    //获取添加好友请求列表
    public function getApplyList()
    {

        //查询所有好友tid
        $tidList = D('GFriend')->getList($this->mTid, 2);
        if (empty($tidList)) {
            return array();
        } else {
            //查询好友信息
            return D('GTeam')->getFriendList($tidList);
        }

    }

    //发起请求
    public function apply()
    {

        //查询好友情况
        $friend = D('GFriend')->getRow($this->mTid, $_POST['friend_tid']);
        if ($friend) {//已经有数据

            //已经是好友关系
            if ($friend['status'] == 1) {
                C('G_ERROR', 'friend_already');
                return false;
            }

            //自己已经发过请求
            if ($friend['tid_1'] == $this->mTid) {
                C('G_ERROR', 'friend_send_already');
                return false;
            }

            //对方已经向我发过请求
            if ($friend['tid_1'] == $_POST['friend_tid'])
                if (!D('GFriend')->updateStatus($this->mTid, $_POST['friend_tid'], 1))//同意加好友
                    return false;

        } else {//无数据

            //查询对方是否存在
            if (!D('GTeam')->isExist($_POST['friend_tid'])) {
                C('G_ERROR', 'friend_not_exist');
                return false;
            }

            //检查自己是否到达好友上限
            if ($this->isMax($this->mTid)) {
                C('G_ERROR', 'friend_max');
                return false;
            }

            //检查对方是否到达好友上限
            if ($this->isMax($_POST['friend_tid'])) {
                C('G_ERROR', 'friend_friend_max');
                return false;
            }

            $data['tid_1'] = $this->mTid;
            $data['tid_2'] = $_POST['friend_tid'];
            if (!D('GFriend')->CreateData($data))//申请加好友
                return false;

        }

        return true;

    }

    //同意加好友
    public function agree()
    {

        //查询好友情况
        $friend = D('GFriend')->getRow($this->mTid, $_POST['friend_tid']);
        if (empty($friend)) {
            C('G_ERROR', 'friend_add_fail');
            return false;
        }

        //检查是否已经是好友关系
        if ($friend['status'] == 1) {
            C('G_ERROR', 'friend_already');
            return false;
        }

        //检查自己是否到达好友上限
        if ($this->isMax($friend['tid_2'])) {
            C('G_ERROR', 'friend_max');
            return false;
        }

        //检查对方是否到达好友上限
        if ($this->isMax($friend['tid_1'])) {
            C('G_ERROR', 'friend_friend_max');
            return false;
        }

        //修改状态
        if (!D('GFriend')->updateStatus($this->mTid, $_POST['friend_tid'], 1))
            return false;

        return true;

    }


    //拒绝加好友
    public function refuse()
    {

        //查询好友情况
        $friend = D('GFriend')->getRow($this->mTid, $_POST['friend_tid']);
        if (empty($friend)) {
            return true;
        }

        //检查是否已经是好友关系
        if ($friend['status'] == 1) {
            C('G_ERROR', 'friend_already');
            return false;
        }

        //拒绝加好友
        if (!D('GFriend')->DeleteData($friend['id']))
            return false;

        return true;

    }

    //删除好友
    public function remove()
    {

        //查询好友情况
        $friend = D('GFriend')->getRow($this->mTid, $_POST['friend_tid']);
        if (empty($friend)) {
            return true;
        }

        //检查是否已经是好友关系
        if ($friend['status'] != 1) {
            C('G_ERROR', 'friend_not_yet');
            return false;
        }

        //删除好友
        if (!D('GFriend')->DeleteData($friend['id']))
            return false;

        return true;

    }


    //查询是否已达到好友上限
    private function isMax($tid)
    {

        //查询好友上限
        $friendMax = D('Static')->access('params', 'FRIENDS_MAX');

        //查询好友情况
        $friendList = D('GFriend')->getList($tid, 1);
        $count = count($friendList);

        //检查是否到达好友上限
        if ($friendMax > $count)
            return false;//未达上限
        return true;//已达上限

    }

    //送出体力
    public function sendVality()
    {

        //查询好友关系
        $friend = D('GFriend')->getRow($this->mTid, $_POST['friend_tid']);
        if (empty($friend)) {
            C('G_ERROR', 'friend_not_yet');
            return false;
        }

        //查看今天有没有送过体力
        $friendVality = D('Predis')->cli('social')->get('fv:' . $this->mTid . ':' . $_POST['friend_tid']);
        if (!is_null($friendVality)) {
            C('G_ERROR', 'friend_vality_send');//已经送过
            return false;
        }

        //加金币
        $gold = D('Static')->access('params', 'FRIEND_REBATE_MONEY');
        if (!$this->produce('attr', 3002, $gold)) {
            return false;
        }

        //发送体力
        D('Predis')->cli('social')->setex('fv:' . $this->mTid . ':' . $_POST['friend_tid'], 86400, 0);

        //返回
        return true;

    }

    //获取体力
    public function getVality()
    {

        //查询好友赠送体力情况
        $friendVality = D('Predis')->cli('social')->get('fv:' . $_POST['friend_tid'] . ':' . $this->mTid);
        if (is_null($friendVality)) {
            C('G_ERROR', 'friend_vality_not_send');
            return false;
        }

        //收取状态
        if ($friendVality != 0) {
            C('G_ERROR', 'friend_vality_get');
            return false;
        }

        //开始事务
        $this->transBegin();

        //加体力
        $vality = D('Static')->access('params', 'FRIEND_GET_POW');//获取玩家可获得的体力值
        if (!$this->produce('vality', $vality)) {
            return false;
        }

        //修改状态
        D('Predis')->cli('social')->set('fv:' . $this->mTid . ':' . $_POST['friend_tid'], 1);

        //返回
        return true;

    }

}