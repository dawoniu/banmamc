<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use app\admin\model\Supplier as m_supplier;

class Supplier extends Base
{
    public function index()
    {
        $supplier = new m_supplier();

        $data=$this->request->get();
        if(isset($data["admin/supplier/index_html"])){
            unset($data["admin/supplier/index_html"]);
        }
        $where="is_delete=0";

        if(!empty($data["keyword"])){
            $where["name"]=['like','%'.$data["keyword"].'%'];
            $this->assign("keyword",$data["keyword"]);
        }

        $list=$supplier->where($where)
            ->order('id', 'desc')
            ->paginate(100,false,['query'=>$data]);

        $this->assign("list",$list);
        $this -> view -> count = $supplier->where($where)
            ->count();
        return $this->fetch();
    }

    //添加供应商
    public function add(){
        if($this->request->isPost()) {
            $data = $this->request->post();
            if (m_supplier::get(['name'=> $data['name']])) {
                //如果在表中查询到该用户名
                $status = 0;
                $message = '供应商账号重复,请重新输入~~';
            }else{
                unset($data['password2']);
                $status = 1;
                $message = '添加成功,请刷新查看';
                $data["password"]=md5($data["password"]);
                $user = m_supplier::create($data);
                if ($user === null) {
                    $status = 0;
                    $message = '添加失败~~';
                }
            }
            return json(['status'=>$status, 'message'=>$message]);
        }else{

            $this->assign("title","添加供应商");
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
            $user = m_supplier::update($data);
            if ($user == true) {
                return json(['status'=>1, 'message'=>'更新成功,请刷新查看']);
            }else{
                return json(['status'=>0, 'message'=>"更新失败,请检查"]);
            }
        }else{
            $this->view->title="编辑供应商";
            //这里用get获取不到
            $id=$this->request->param("id");

            $this->view->data=m_supplier::get($id);
            $this->view->password="不修改密码则保留空";
            $this->view->password2="不修改密码则保留空";
            return $this->fetch("form");
        }
    }

}
