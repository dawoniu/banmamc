<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use think\Session;
use app\admin\model\Admin as m_admin;
use think\Db;

class Admin extends Base
{
    public function index()
    {
        $username=Session::get("user_info.name");
        if($username=="admin"){
            // 使用数组查询
            $list = m_admin::all(["is_delete"=>0]);
            $this -> view -> count = m_admin::where("is_delete",0)->count();
        }else{
            $list = m_admin::all(['name'=>$username]);
            $this -> view -> count = 1;
        }
        $this->assign("list",$list);

        return $this->fetch();
    }
    //ajax改变状态(启用或禁用)
    public function setStatus(){
        $id=$this->request->post("id");
        $admin=m_admin::get($id);

        //获取原始数据要用getData方法。
        if($admin->getData('status')==1){
            m_admin::update(['id' => $id, 'status' => 0]);
        }else{
            m_admin::update(['id' => $id, 'status' => 1]);
        }
    }
    //ajax删除管理员
    public function delete(){
        $id=$this->request->post("id");
        m_admin::update(['id' => $id, 'is_delete' => 1,'delete_time'=>time()]);
    }

    public function add(){
        if($this->request->isPost()){
            $data=$this->request->post();
            if (m_admin::get(['name'=> $data['name']])) {
                //如果在表中查询到该用户名
                $status = 0;
                $message = '用户名重复,请重新输入~~';
            }else{
                unset($data['password2']);
                $status = 1;
                $message = '添加成功,请刷新查看';
                $data["password"]=md5($data["password"]);
                $user = m_admin::create($data);
                if ($user === null) {
                    $status = 0;
                    $message = '添加失败~~';
                }
            }
            return json(['status'=>$status, 'message'=>$message]);
        }else{
            $this->view->title="添加管理员";
            $this->view->password="密码";
            $this->view->password2="确认新密码";
            return $this->fetch("form");
        }
    }
    public function edit(){
        if($this->request->isPost()){
            $data=$this->request->post();
            if($data["password"]==""){
                unset($data["password"]);
            }else{
                $data["password"]=md5($data["password"]);
            }
            $data["update_time"]=time();
            unset($data['password2']);
            $user = m_admin::update($data);
            if ($user == true) {
                return json(['status'=>1, 'message'=>'更新成功,请刷新查看']);
            }else{
                return json(['status'=>0, 'message'=>"更新失败,请检查"]);
            }
        }else{
            $this->view->title="编辑管理员";
            //这里用get获取不到
            $id=$this->request->param("id");

            $this->view->admin=m_admin::get($id);
            $this->view->password="不修改密码则保留空";
            $this->view->password2="不修改密码则保留空";
            return $this->fetch("form");
        }
    }
//    public function insertexcel(){
//        $file_dir='5.xls';
//        $extension='xls';
//        $excel_array = insert_data($file_dir, $extension);
//
//        $data_insert=[];
//        $j=0;
//        foreach ($excel_array as $k => $v) {
//            for($i=0;$i<=35;$i++){
//                if($v[$i]!=""){
//                    $data_insert[$j]['word']=trim($v[$i]);
//                    $data_insert[$j]['grade']=5;
//                    $j++;
//                }
//            }
//        }
//        $result = Db::name("word")->insertAll($data_insert);
//        var_dump($result);
//    }
}
