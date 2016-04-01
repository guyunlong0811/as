<?php
namespace Home\Api;

use Think\Controller;

class MailApi extends BaseApi
{

    //获取邮件全部信息
    public function getAll()
    {
        return D('GMail')->getAll($this->mTid);
    }

    //领取附件
    public function getAnnex($mail = array(), $tid = null)
    {
        //内部调用
        if(empty($mail)){

            //获取邮件详情
            $mail = D('GMail')->getRow($_POST['mail_id']);//查询邮件详情
            if (empty($mail)) {
                C('G_ERROR', 'mail_expired');
                return false;
            }

            //检查归属
            if ($mail['tid'] != $this->mTid) {
                C('G_ERROR', 'mail_not_belong');
                return false;
            }

            //不是附件类型邮件
            if ($mail['type'] == '1') {
                C('G_ERROR', 'mail_no_annex');
                return false;
            }

        }

        C('G_BEHAVE', $mail['behave']);

        //开始事务
        $this->transBegin();

        //加道具
        for ($i = 1; $i <= 4; ++$i) {
            //奖励类型
            if ($mail['item_' . $i . '_type'] != '0') {//奖励
                if (!$this->produce($this->mBonusType[$mail['item_' . $i . '_type']], $mail['item_' . $i . '_value_1'], $mail['item_' . $i . '_value_2'])) {
                    goto end;
                }
            }
        }

        //删除邮件
        if (!D('GMail')->DeleteData($mail['id'])) {
            goto end;
        }

        //记录日志
        D('LMail')->cLog($mail, 1);

        //结束事务
        C('G_TRANS_FLAG', true);
        end:
        if (!$this->transEnd()) {
            return false;
        }

        //返回
        return true;

    }

    //领取所有附件邮件
    public function getAnnexAll()
    {
        $list = array();

        //获取
        $mailList = D('GMail')->getAll($this->mTid, '*');
        if (!empty($mailList)) {

            $annexList = array();
            foreach ($mailList as $value) {

                if($value['type'] != 2){
                    continue;
                }

                //领取奖励
                if (false === $this->getAnnex($value, $this->mTid)) {
                    return false;
                }

                //遍历奖励清单
                for ($i = 1; $i <= 4; ++$i) {
                    if ($value['item_' . $i . '_type'] > 0) {
                        $annexList[$value['item_' . $i . '_type']][$value['item_' . $i . '_value_1']] += $value['item_' . $i . '_value_2'];
                    }
                }

            }

            //整理数据
            foreach ($annexList as $type => $value) {
                foreach ($value as $id => $count) {
                    $list[] = array(
                        'type' => $type,
                        'id' => $id,
                        'count' => $count,
                    );
                }
            }

        }

        //返回
        return $list;
    }

    //读取邮件
    public function read()
    {
        //获取邮件详情
        $mail = D('GMail')->getDetail($_POST['mail_id']);//查询邮件详情
        if (empty($mail)) {
            C('G_ERROR', 'mail_expired');
            return false;
        }
        //是否已读
        if ($mail['status'] != '0') {
            C('G_ERROR', 'mail_already_read');
            return false;
        }
        //检查归属
        if ($mail['tid'] != $this->mTid) {
            C('G_ERROR', 'mail_not_belong');
            return false;
        }
        unset($mail['tid']);

        //开始事务
        $this->transBegin();

        //标记已读
        $where['id'] = $_POST['mail_id'];
        $data['status'] = 1;
        if (false === D('GMail')->UpdateData($data, $where)) {
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

    //获取新邮件数量
    public function getNewCount($tid = null)
    {

        //内部调用
        if (!is_null($tid)) {
            $this->mTid = $tid;
        }

        //返回
        return D('GMail')->getNewCount($this->mTid);
    }

}