<?php
namespace app\api\controller;
use think\Controller;
use think\Db;


class Base extends Controller
{
    /**
     * 初始化操作
     * @access protected
     */
    protected function _initialize()
    {

        $system=Db::name('s_system')->where('id',1)->find();
        $this->assign('system',$system);
    }

    public function check(){
        $access_token=$this->request->post('access_token');
        $check=checktoken($access_token);
        return $check;
    }
}
