<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Session;


class Base extends Controller
{
    /**
     * 初始化操作
     * @access protected
     */
    protected function _initialize()
    {
        parent::_initialize();
        if(!Session::has("user_id")){
            $this->redirect("Login/index");
        }
        $system=Db::name('s_system')->where('id',1)->find();
        $this->assign('system',$system);
    }
}
