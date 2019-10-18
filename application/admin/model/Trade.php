<?php
namespace app\admin\model;
use think\Model;

class Trade extends Model
{
    //这个只能自动改create_time和update_time两个的时间
    protected $autoWriteTimestamp = true;
    // 设置当前模型对应的完整数据表名称
    protected $table = 's_trade';

    public function getStatusAttr($value)
    {
        $status = [1=>'已启用',0=>'已禁用'];
        return $status[$value];
    }
}
