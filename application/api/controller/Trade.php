<?php
namespace app\api\controller;

use think\Controller;
use think\Db;

class Trade extends Controller
{

    //获取所有分类,除限时抢购
    public function alltrade(){
        $list=Db::name('s_trade')->where('status',1)->where('id','neq',1)
            ->where('is_delete',0)->select();
        return json($list);
    }

}
