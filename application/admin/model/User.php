<?php
namespace app\admin\model;
use think\Model;

class User extends Model
{
    //这个只能自动改create_time和update_time两个的时间
    protected $autoWriteTimestamp = true;
    // 设置当前模型对应的完整数据表名称
    protected $table = 's_user';

    public function getRoleAttr($value)
    {
        $status = [0=>'普通用户',1=>'业务员',2=>'供应商'];
        return $status[$value];
    }

    public function getStatusAttr($value)
    {
        $status = [1=>'已启用',0=>'已禁用'];
        return $status[$value];
    }

//    public function lanmu()
//    {
//        return $this->belongsTo('lanmu');
//    }


}
