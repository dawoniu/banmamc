<?php
namespace app\admin\model;
use think\Model;

class Goods extends Model
{
    //这个只能自动改create_time和update_time两个的时间
    protected $autoWriteTimestamp = true;

    // 设置当前模型对应的完整数据表名称
    protected $table = 's_goods';

    public function trade()
    {
        return $this->belongsTo('trade','tradeID','id');
    }

//    public function getStatusAttr($value){
//        $status=[1=>"已发布",0=>"已下架"];
//        return $status[$value];
//    }
}
