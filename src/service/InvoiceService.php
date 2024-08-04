<?php

namespace xjryanse\invoice\service;

use xjryanse\order\service\OrderService;
use app\order\service\OrderBaoBusService;
use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays;
use xjryanse\logic\Debug;
use xjryanse\logic\DataCheck;
use Exception;
/**
 * 
 */
class InvoiceService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;
    
    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\invoice\\model\\Invoice';
    //直接执行后续触发动作
    protected static $directAfter = true;        

    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }


    
    public static function extraPreUpdate(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }
    /**
     * 20220915
     * @param type $ids
     * @return type
     */
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids){
            $cond[]         = ['invoice_id','in',$ids];
            $tangCountObj   = InvoiceOrderService::where($cond)->group('invoice_id')->field('invoice_id,count(1) as number,sum(if(has_settle, 1, 0)) as settleCount')->select();
            $tangCountArr = $tangCountObj ? $tangCountObj->toArray() : [];
            
            $tangCounts     = array_column($tangCountArr, 'number','invoice_id');
            $settleCounts   = array_column($tangCountArr, 'settleCount','invoice_id');
            
            foreach ($lists as &$v) {
                //趟数
                $v['subOrderCounts']    = Arrays::value($tangCounts, $v['id'],0);
                // 已结算趟数
                $v['hasSettleCounts']   = Arrays::value($settleCounts, $v['id'], 0);
                // 全部结算
                $v['allSettle']         = $v['hasSettleCounts'] >= $v['subOrderCounts'] ? 1 : 0;
            }

            return $lists;
        });
    }
    
    public static function ramPreSave(&$data, $uuid) {
        // 20240715:TODO:优化
        if(session(SESSION_COMPANY_ID) != 4){
            $keys = ['bil_company'];
            $notices['bil_company'] = '开票单位必须';
            DataCheck::must($data, $keys, $notices);
        }
        
        if(!$data['orderIdArr']){
            throw new Exception('请选择开票订单明细');
        }
        // 20221107
        if(!Arrays::value($data, 'invoice_prize')){
            throw new Exception('请填写开票金额');
        }
        if(isset($data['orderIdArr'])){
            $orderIdArr = $data['orderIdArr'];
            foreach($orderIdArr as &$v){
                $v['invoice_id'] = $uuid;
            }
            InvoiceOrderService::saveAllRam($orderIdArr);
        }
        // 20231216
        if(!Arrays::value($data, 'invoice_time')){
            $data['invoice_time'] = date('Y-m-d H:i:s');
        }

        $lists = InvoiceService::getInstance($uuid)->objAttrsList('invoiceOrder');
        if($lists){
            $d                  = $lists[0];
            $orderId            = Arrays::value($d, 'order_id');
            $data['dept_id']    = OrderService::getInstance($orderId)->fDeptId();
        }
        // Debug::dump($lists);
    }
    /**
     * 预先删除
     */
    public function ramPreDelete(){
        self::queryCountCheck(__METHOD__);
        // 查询发票订单
        $con[] = ['invoice_id','=',$this->uuid];
        $ids = InvoiceOrderService::ids($con);
        foreach($ids as $id){
            InvoiceOrderService::getInstance($id)->deleteRam();
        }
    }
    /**
     * 20221115更新订单金额
     */
    public function updateOrderPrize(){
        $con[]  = ['invoice_id','=',$this->uuid];
        $subIds = InvoiceOrderService::where($con)->column('sub_order_id');
        $conS[] = ['id','in',$subIds];
        $prize  = OrderBaoBusService::where($conS)->sum('prize');
        self::mainModel()->where('id',$this->uuid)->update(['order_prize'=>$prize]);
        return $prize;
    }
    
}
