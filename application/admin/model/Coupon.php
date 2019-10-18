<?php
namespace app\admin\model;
use think\Model;

class Coupon extends Model
{
    //这个只能自动改create_time和update_time两个的时间，在这里只用了login_time，所以没用
    protected $autoWriteTimestamp = true;
    // 设置当前模型对应的完整数据表名称
    protected $table = 's_coupon';

    public function getStatusAttr($value)
    {
        $status = [1=>'已启用',0=>'已禁用'];
        return $status[$value];
    }
//    public function getRoleAttr($value)
//    {
//        $role = [1=>'超级管理员',0=>'管理员'];
//        return $role[$value];
//    }
//    public function getLoginTimeAttr($value){
//        if($value>0){
//            return date('Y/m/d H:i:s', $value);
//        }else{
//            return "未登录过";
//        }
//    }
}
