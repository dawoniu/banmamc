<?php
namespace app\admin\controller;
use think\Db;

class Finance extends Base
{
    //小区团长提现申请列表
    public function index()
    {
        $where="f.is_delete=0 and f.type=1";

        $list=Db::name('s_finance_record')->alias('f')->join('s_group g','g.id=f.objID')
            ->field('f.*,u.nickName,g.linkman,g.phone')->join('s_user u','g.userID=u.id')
            ->where($where)->order('f.id', 'desc')
            ->paginate(10);

        $this -> view -> count=Db::name('s_finance_record')->alias('f')->where($where)->count();
        $this->assign("list",$list);

        return $this->fetch();
    }

    //处理团长提现记录
    public function form(){
        if($this->request->isPost()){
            $id=$this->request->post('id');
            $status=$this->request->post('status');
            if($status!=1&&$status!=2){
                return json(['status'=>0, 'message'=>'操作失败，请检查']);
                die;
            }
            $info = Db::name('s_finance_record')->where('id', $id)->find();
            // 启动事务
            Db::startTrans();
            try{
                Db::name('s_finance_record')->where('id', $id)->update(['status' => $status,'update_time'=>time()]);
                if($status==1){
                    Db::name('s_group')->where('id', $info['objID'])
                        ->dec('examine_money',$info['money'])->inc('total_money',$info['money'])->update();
                }
                if($status==2){
                    Db::name('s_group')->where('id', $info['objID'])
                        ->dec('examine_money',$info['money'])->inc('now_money',$info['money'])->update();
                }

                // 提交事务
                Db::commit();
                return json(['status'=>1, 'message'=>'操作成功']);
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return json(['status'=>0, 'message'=>'操作失败，请检查']);
            }



        }else{

            $this->assign("title","处理提现申请");
            $this->assign("keywords","处理提现申请");
            $this->assign("description","处理提现申请");

            $objID=$this->request->param("objid");
            $id=$this->request->param("id");
            $data=Db::name('s_group')->where('id',$objID)->find();
            $this->assign("id",$id);
            $this->assign("data",$data);
            return $this->fetch("form");
        }
    }

}
