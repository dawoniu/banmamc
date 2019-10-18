<?php
namespace app\admin\controller;
use app\admin\model\Group as m_group;

class Group extends Base
{
    public function index()
    {
        $group = new m_group();
        $data=$this->request->get();
        if(isset($data["admin/group/index_html"])){
            unset($data["admin/group/index_html"]);
        }
        $where="is_delete=0";

        if(!empty($data["keyword"])){
            $where.=" and linkman like '%".$data["keyword"]."%'";
            $this->assign("keyword",$data["keyword"]);
        }

        $list=$group->where($where)
            ->order('id', 'desc')
            ->paginate(10,false,['query'=>$data]);
        //dump($list);
        $this->assign("list",$list);
        $this -> view -> count = $group->where($where)
            ->count();
        return $this->fetch();
    }

}
