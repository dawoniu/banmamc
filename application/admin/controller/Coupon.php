<?php
namespace app\admin\controller;
use app\admin\model\Coupon as m_coupon;

class Coupon extends Base
{
    public function index()
    {
        $coupon = new m_coupon();
        $list=$coupon->where('is_delete', 0)
            ->order('id', 'desc')
            ->paginate(10);
        $this -> view -> count = $coupon->where('is_delete', 0)->count();
        $this->assign("list",$list);
        return $this->fetch();
    }
    //ajax改变状态(启用或禁用)
    public function setStatus(){
        $id=$this->request->post("id");
        $coupon=m_coupon::get($id);

        //获取原始数据要用getData方法。
        if($coupon->getData('status')==1){
            m_coupon::update(['id' => $id, 'status' => 0]);
        }else{
            m_coupon::update(['id' => $id, 'status' => 1]);
        }
    }
    //ajax删除优惠券
    public function delete(){
        $id=$this->request->post("id");
        m_coupon::update(['id' => $id, 'is_delete' => 1,'delete_time'=>time()]);
    }

    public function add(){
        if($this->request->isPost()){
            $data=$this->request->post();
            if(isset($data["start_time"])){
                $data['start_time']=strtotime($data["start_time"].":00");
            }
            if(isset($data["end_time"])){
                $data['end_time']=strtotime($data["end_time"].":00");
            }

            $status = 1;
            $message = '添加成功,请刷新查看';

            $coupon = m_coupon::create($data);
            if ($coupon === null) {
                $status = 0;
                $message = '添加失败~~';
            }

            return json(['status'=>$status, 'message'=>$message]);
        }else{
            $this->view->title="添加优惠券";
            return $this->fetch("form");
        }
    }
    public function edit(){
        if($this->request->isPost()){
            $data=$this->request->post();
            if(isset($data["start_time"])){
                $data['start_time']=strtotime($data["start_time"].":00");
            }
            if(isset($data["end_time"])){
                $data['end_time']=strtotime($data["end_time"].":00");
            }
            $data["update_time"]=time();

            $coupon = m_coupon::update($data);
            if ($coupon == true) {
                return json(['status'=>1, 'message'=>'更新成功,请刷新查看']);
            }else{
                return json(['status'=>0, 'message'=>"更新失败,请检查"]);
            }
        }else{
            $this->view->title="编辑优惠券";
            //这里用get获取不到
            $id=$this->request->param("id");
            $this->view->coupon=m_coupon::get($id);
            return $this->fetch("form");
        }
    }
}
