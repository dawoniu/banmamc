<?php
namespace app\admin\controller;
use app\admin\model\User as m_user;
use app\admin\model\Group as m_group;

class User extends Base
{
    public function index()
    {
        $user = new m_user();
        $data=$this->request->get();
        if(isset($data["admin/user/index_html"])){
            unset($data["admin/user/index_html"]);
        }
        $where="is_delete=0";

        if(!empty($data["start_time"])){
            $start_time=strtotime($data["start_time"]);
            $where.=" and regTime>".$start_time;
            //$where["regTime"]=['>',$start_time];
            $this->assign("start_time",$data["start_time"]);
        }
        if(!empty($data["end_time"])){
            $end_time=strtotime($data["end_time"]." 23:59:59");
            $where.=" and regTime<".$end_time;
            //$where["regTime"]=['<',$end_time];
            $this->assign("end_time",$data["end_time"]);
        }

        if(!empty($data["keyword"])){
            $where.=" and nickName like '%".$data["keyword"]."%'";
            $this->assign("keyword",$data["keyword"]);
        }

        $list=$user->where($where)
            ->order('id', 'desc')
            ->paginate(10,false,['query'=>$data]);
        //dump($list);
        $this->assign("list",$list);
        $this -> view -> count = $user->where($where)
            ->count();
        return $this->fetch();
    }

    //用户升级
    public function up(){
        if($this->request->isPost()){
            $data=$this->request->post();
            $userid=$data['id'];
            $status = 1;
            $message = '添加成功';

            $res = m_user::where('id', $userid)->update(['role' => 1]);
            if ($res !== false) {
                $data['userID']=$data['id'];
                unset($data['id']);
                $group = m_group::create($data);
                if ($group === null) {
                    m_user::where('id', $userid)->update(['role' => 0]);
                    $status = 0;
                    $message = '添加失败~~';
                }
            }else{
                $status=0;
                $message="更新失败,请检查";
            }
            return json(['status'=>$status, 'message'=>$message]);

        }else{

            $this->assign("title","用户升级成小区团长");
            $this->assign("keywords","用户升级成小区团长");
            $this->assign("description","用户升级成小区团长");

            $id=$this->request->param("id");
            $data=m_user::get($id);
            $this->assign("data",$data);
            return $this->fetch("form");
        }
    }

}
