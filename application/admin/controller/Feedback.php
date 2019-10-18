<?php
namespace app\admin\controller;
use app\admin\model\Feedback as m_feedback;

class Feedback extends Base
{

    public function index()
    {
        $feedback = new m_feedback();
        $list=$feedback->where('is_delete', 0)
            ->order('id', 'desc')
            ->paginate(10);
        $this -> view -> count = $feedback->where('is_delete', 0)->count();
        $this->assign("list",$list);
        return $this->fetch();
    }

    //ajax删除留言
    public function delete(){
        $id=$this->request->post("id");
        m_feedback::update(['id' => $id, 'is_delete' => 1,'delete_time'=>time()]);
    }
}
