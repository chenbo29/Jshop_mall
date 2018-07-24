<?php
namespace app\seller\controller;

use app\common\controller\Seller;
use app\common\model\BillRefund as BillRefundModel;
use app\common\model\PaymentsSellerRel;
use Request;

class BillRefund extends Seller
{
    public function index()
    {
        if(Request::isAjax()){
            $data = input('param.');
            $data['seller_id'] = $this->sellerId;
            $billRefundModel = new BillRefundModel();
            return $billRefundModel->tableData($data);
        }
        return $this->fetch('index');
    }

    public function view()
    {
        $this->view->engine->layout(false);
        if(!input('?param.refund_id')){
            return error_code(13215);
        }
        $billRefundModel = new BillRefundModel();
        $where['refund_id'] = input('param.refund_id');
        $where['seller_id'] = $this->sellerId;
        $info = $billRefundModel->where($where)->find();
        if(!$info){
            return error_code(13219);
        }

        $this->assign('info',$info);
        return [
            'status' => true,
            'data' => $this->fetch('view'),
            'msg' => ''
        ];
    }


    /**
     * 未退款状态做退款
     * @return array|\think\Config
     */
    public function refund()
    {
        $this->view->engine->layout(false);
        if(!input('?param.refund_id')){
            return error_code(13215);
        }
        $billRefundModel = new BillRefundModel();

        $where['refund_id'] = input('param.refund_id');
        $where['seller_id'] = $this->sellerId;
        $where['status'] = $billRefundModel::STATUS_NOREFUND;
        $info = $billRefundModel->where($where)->find();
        if(!$info){
            return error_code(13219);
        }

        if(Request::isPost()){
            if(!input('?param.status')){
                return error_code(10000);
            }

            $payment_code = input('param.payment_code',"");

            return $billRefundModel->toRefund($this->sellerId,input('param.refund_id'),input('param.status'),$payment_code);
        }



        $this->assign('info',$info);

        //取当前商户的所有支付方式
        $paymentsSellerRelModel = new PaymentsSellerRel();
        $this->assign('payment_list',$paymentsSellerRelModel->getList($this->sellerId,0));

        return [
            'status' => true,
            'data' => $this->fetch('refund'),
            'msg' => ''
        ];
    }
    /**
     * 退款失败状态再次退款
     * @return array|\think\Config
     */
    public function reaudit()
    {
        $this->view->engine->layout(false);
        if(!input('?param.refund_id')){
            return error_code(13215);
        }
        $billRefundModel = new BillRefundModel();

        $where['refund_id'] = input('param.refund_id');
        $where['seller_id'] = $this->sellerId;
        $where['status'] = $billRefundModel::STATUS_FAIL;
        $info = $billRefundModel->where($where)->find();
        if(!$info){
            return error_code(13224);
        }


        return $billRefundModel->paymentRefund($this->sellerId,input('param.refund_id'));

    }


}
