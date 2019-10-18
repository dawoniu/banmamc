<?php
namespace app\api\controller;

use think\Controller;
use think\Db;

class Index extends Controller
{

    //获取banner
    public function banner(){
        $list=Db::name('s_banner')->select();
        return json($list);
    }

}
