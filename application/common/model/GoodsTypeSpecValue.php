<?php
namespace app\common\model;

/**
 * 商品属性值表
 * Class GoodsTypeSpec
 * @package app\common\model
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 20:10
 */
class GoodsTypeSpecValue extends Common
{
    /**
     * 表名
     * @var string
     */
    protected $table='goods_type_spec_value';


    public function addAll($data = [ ])
    {
        return $this->saveAll($data);
    }

}
