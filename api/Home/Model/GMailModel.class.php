<?php
namespace Home\Model;

use Think\Model;

class GMailModel extends BaseModel
{

    protected $_auto = array(
        array('ctime', 'time', 1, 'function'), //新增的时候把ctime字段设置为当前时间
        array('status', 0), //新增的时候把等级设置为1
    );

    private function getMailInfo($mail_id, $tid, $target_tid, $params, $isDynamic = false)
    {

        $table = $isDynamic ? 'StaticDyn' : 'Static';
        $config = D($table)->access('mail', $mail_id);

        $send['tid'] = $target_tid;
        $send['type'] = $config['type'];
        $send['title'] = $config['name'];
        $send['from'] = $config['from'];
        //内容替换
        $replace = explode('#', $config['wildcards']);
        if (empty($replace)) {
            $des = $config['des'];
        } else {
            $des = $config['des'];
            //替换触发者昵称
            if (in_array('Player_Name', $replace)) {
                if (isset($params['nickname'])) {
                    $nickname = $params['nickname'];
                } else {
                    $nickname = D('GTeam')->getAttr($tid, 'nickname');
                }
                $des = str_replace('@Player_Name@', $nickname, $des);
            }
            //替换目标昵称
            if (in_array('Target_Player_Name', $replace)) {
                if (isset($params['target_nickname'])) {
                    $nickname = $params['target_nickname'];
                } else {
                    $nickname = D('GTeam')->getAttr($target_tid, 'nickname');
                }
                $des = str_replace('@Target_Player_Name@', $nickname, $des);
            }
            foreach ($replace as $value) {
                switch ($value) {
                    case 'Player_Name':
                    case 'Target_Player_Name':
                        $attr = false;
                        break;
                    case 'League_Name'://替换触发者公会名称
                        $attr = 'leaguename';
                        break;
                    case 'League_Battle_Other_Name'://替换对手公会名称
                        $attr = 'target_league_name';
                        break;
                    case 'Rank'://替换排名
                        $attr = 'rank';
                        break;
                    case 'League_Rank'://替换公会排名
                        $attr = 'league_rank';
                        break;
                    case 'League_Fund'://替换公会资金
                        $attr = 'fund';
                        break;
                    case 'Combo'://替换连击值
                        $attr = 'combo';
                        break;
                    case 'Bonus_Diamond_Count'://充值获得水晶数
                        $attr = 'bonus_diamond_count';
                        break;
                    case 'Member_Name'://充值获得会员卡
                        $attr = 'member_name';
                        break;
                    case 'Monster_Name'://充值获得会员卡
                        $attr = 'monstername';
                        break;
                    case 'Player_Name1'://昵称
                        $attr = 'nickname1';
                        break;
                    case 'Player_Name2'://昵称
                        $attr = 'nickname2';
                        break;
                    case 'Player_Name3'://昵称
                        $attr = 'nickname3';
                        break;
                    case 'Player_Name4'://昵称
                        $attr = 'nickname4';
                        break;
                    case 'Player_Name5'://昵称
                        $attr = 'nickname5';
                        break;
                    default:
                        $attr = $value;
                }
                //替换
                if ($attr) {
                    $des = str_replace('@' . $value . '@', $params[$attr], $des);
                }
            }

        }
        $send['des'] = $des;
        $send['item_1_type'] = $config['annex_type_1'];
        $send['item_1_value_1'] = $config['annex_type_1_value_1'];
        $send['item_1_value_2'] = $config['annex_type_1_value_2'];
        $send['item_2_type'] = $config['annex_type_2'];
        $send['item_2_value_1'] = $config['annex_type_2_value_1'];
        $send['item_2_value_2'] = $config['annex_type_2_value_2'];
        $send['item_3_type'] = $config['annex_type_3'];
        $send['item_3_value_1'] = $config['annex_type_3_value_1'];
        $send['item_3_value_2'] = $config['annex_type_3_value_2'];
        $send['item_4_type'] = $config['annex_type_4'];
        $send['item_4_value_1'] = $config['annex_type_4_value_1'];
        $send['item_4_value_2'] = $config['annex_type_4_value_2'];
        $send['open_script'] = $config['open_script'];
        $send['behave'] = $config['type'] == 1 ? 0 : get_config('behave', array($config['behave'], 'code',));
        if ($config['expires_type'] == '1') {
            $send['dtime'] = time() + (60 * $config['expires_value']);
        } else {
            $send['dtime'] = strtotime($config['expires_value']);
        }

        return $send;

    }

    //发送邮件(邮件ID,触发战队ID,目标战队ID)
    public function send($mail_id, $tid, $target_tid, $params = null, $isDynamic = false)
    {
        $send = $this->getMailInfo($mail_id, $tid, $target_tid, $params, $isDynamic);
        return $this->CreateData($send);
    }

    //一次发送多封邮件
    public function sendAll($mailList)
    {
        $now = time();
        foreach ($mailList as $key => $value) {
            $list[$key] = $this->getMailInfo($value['mail_id'], $value['tid'], $value['target_tid'], $value['params']);
            $list[$key]['ctime'] = $now;
            $list[$key]['status'] = 0;
        }
        return D('GMail')->CreateAllData($list);
    }

    //获取所有未过期的邮件
    public function getAll($tid, $field = array())
    {
        if (empty($field)) {
            $field = array('id', 'type', 'title', 'from', 'des', 'item_1_type', 'item_1_value_1', 'item_1_value_2', 'item_2_type', 'item_2_value_1', 'item_2_value_2', 'item_3_type', 'item_3_value_1', 'item_3_value_2', 'item_4_type', 'item_4_value_1', 'item_4_value_2', 'open_script', 'ctime', 'status',);
        }
        $where['tid'] = $tid;
        $where['dtime'] = array('gt', time());
        $order['type'] = 'desc';
        $order['status'] = 'asc';
        $order['ctime'] = 'desc';
        $select = $this->field($field)->where($where)->order($order)->limit(20)->select();
        if (empty($select)) {
            return array();
        }
        return $select;
    }

    //获取所有未过期的邮件
    public function getList($tid)
    {
        $field = array('id', 'type', 'title', 'from', 'open_script', 'ctime', 'status',);
        $where['tid'] = $tid;
        $where['dtime'] = array('gt', time());
        $select = $this->field($field)->where($where)->select();
        if (empty($select)) {
            return array();
        }
        return $select;
    }

    //获取单条未过期的邮件
    public function getDetail($id)
    {
        $where['id'] = $id;
        $where['dtime'] = array('gt', time());
        return $this->where($where)->find();
    }

    public function getRow($id)
    {
        $where['id'] = $id;
        $where['dtime'] = array('gt', time());
        return $this->getRowCondition($where);
    }

    public function getNewCount($tid)
    {
        $where['tid'] = $tid;
        $where['dtime'] = array('gt', time());
        $where['status'] = 0;
        return $this->where($where)->count();
    }

}