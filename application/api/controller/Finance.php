<?php
namespace app\api\controller;

use think\Db;
//财务中心
class Finance extends Base
{
    //获取当前小区团长信息
    public function info(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $group=Db::name('s_group')->field('id,total_money,now_money,examine_money')->where('userID',$userid)->find();
        return json(['code'=>1,'data'=>$group]);
    }
    //申请提现
    public function apply(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $money=$this->request->post('money');
        $type=$this->request->post('type');

        if($type==1){
            $table='s_group';
            $info=Db::name($table)->field('id,total_money,now_money,examine_money')->where('userID',$userid)->find();
        }elseif($type==2){
            $id=$this->request->post('id');
            $table='s_supplier';
            $info=Db::name($table)->field('id,total_money,now_money,examine_money')->where('id',$id)->find();
        }else{
            return json(['code'=>-4001,'data'=>'对不起，提现用户类型错误。']);
        }

        if($money>$info['now_money']){
            return json(['code'=>-4002,'data'=>'对不起，您申请的金额超出可提现金额。']);
        }
        if($info['examine_money']>0){
            return json(['code'=>-4002,'data'=>'您还有提现正在审核，暂时不能提现。']);
        }


        // 启动事务
        Db::startTrans();
        try{
            $thetime=time();
            $data['objID']=$info['id'];
            $data['type']=$type;
            $data['money']=$money;
            $data['create_time']=$thetime;

            Db::name('s_finance_record')->insert($data);
            Db::name($table)->where('id',$info['id'])
                ->dec('now_money',$money)->inc('examine_money',$money)->update();

            // 提交事务
            Db::commit();
            return json(['code'=>1,'data'=>'操作成功']);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>-4003,'data'=>'申请提现失败']);
        }
    }

    //提现记录(暂时是查询团长)
    public function record(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $type=$this->request->post('type');
        $id=Db::name('s_group')->where('userID',$userid)->value('id');
        $list=Db::name('s_finance_record')->where('objID',$id)->where('type',$type)
            ->where('is_delete',0)->order('id desc')->select();
        return json(['code'=>1,'data'=>$list]);
    }
}
