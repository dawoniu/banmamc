<?php
namespace app\admin\controller;
use app\admin\model\System as m_system;

class System extends Base
{

    public function index()
    {
        if($this->request->isPost()) {
            $data = $this->request->post();
            $status = 1;
            $message = '修改成功';

//            if(isset($data["editorValue"])){
//                $data["foot"]=$data["editorValue"];
//                unset($data["editorValue"]);
//            }

            $system = m_system::update($data);
            if ($system === null) {
                $status = 0;
                $message = '修改失败~~';
            }
            return json(['status'=>$status, 'message'=>$message]);
        }else{
            $system=m_system::get(1);
            $this->assign('system',$system);
            return $this->fetch();
        }
    }




}
