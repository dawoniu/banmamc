<?php
namespace app\admin\model;
use think\Model;

class System extends Model
{
    //这个只能自动改create_time和update_time两个的时间，在这里只用了login_time，所以没用
    protected $autoWriteTimestamp = true;
    // 设置当前模型对应的完整数据表名称
    protected $table = 's_system';
}
