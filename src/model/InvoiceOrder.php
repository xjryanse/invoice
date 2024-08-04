<?php
namespace xjryanse\invoice\model;

/**
 *
 */
class InvoiceOrder extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'invoice_id',
            'uni_name'  =>'invoice',
            'uni_field' =>'id',
            'del_check' => false,
        ],
        [
            'field'     =>'order_id',
            'uni_name'  =>'order',
            'uni_field' =>'id',
            // 'del_check' => false,
        ]
    ];
}