<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use app\admin\model\Trade as m_trade;
use think\Session;

class Trade extends Base
{
    public function index()
    {
        $trade = new m_trade();

        $data=$this->request->param();
        $where="is_delete=0";

        if(empty($data["pid"])){
            $data["pid"]=0;
        }
        $where.=" and tradePId=".$data["pid"];

        $list=$trade->where($where)
            ->order('listOrder', 'desc')
            ->paginate(20,false,['query'=>$data]);

        $this->assign("list",$list);
        $this -> view -> count = $trade->where($where)
            ->count();
        return $this->fetch();
    }

    //ajax删除分类
    public function delete(){
        $id=$this->request->param("id");
        m_trade::update(['id' => $id, 'is_delete' => 1,'delete_time'=>time()]);
    }

}
