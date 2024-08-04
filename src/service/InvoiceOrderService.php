<?php

namespace xjryanse\invoice\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays;
use app\order\service\OrderBaoBusService;
/**
 * 
 */
class InvoiceOrderService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;


    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\invoice\\model\\InvoiceOrder';
    //
    protected static $directAfter = true;        

    public static function ramAfterSave(&$data, $uuid) {
        $subOrderId = Arrays::value($data, 'sub_order_id');
        OrderBaoBusService::getInstance($subOrderId)->updateRam(['has_invoice'=>1]);
    }
    /**
     * 预先删除
     */
    public function ramAfterDelete($data){
        $subOrderId = Arrays::value($data, 'sub_order_id');
        OrderBaoBusService::getInstance($subOrderId)->updateRam(['has_invoice'=>0]);
    }
}
