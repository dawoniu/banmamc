<?php
namespace app\api\controller;

use think\Db;

class Address extends Base
{

    //提货信息列表
    public function adrlist(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $adr=Db::name('u_address')->where('userID',$userid)->where('is_delete',0)->order('dft desc')
            ->select();
        return json(['code'=>1,'data'=>$adr]);
    }

    //单个提货人信息详情
    public function detail(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $id=$this->request->post('id');

        $adr=Db::name('u_address')->where('userID',$userid)->where('id',$id)
            ->find();
        return json(['code'=>1,'data'=>$adr]);
    }

    //新增提货信息
    public function add(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $post=$this->request->post();

        $data['linkman']=$post['linkman'];
        $data['mobile']=$post['mobile'];
        $data['address']=$post['address'];
        $data['dft']=$post['dft'];
        $data['userID']=$userid;

        $defaultid=$this->hasdefault($userid);
        if($defaultid==0){
            $data['dft']=1;
        }else{
            if($data['dft']==1){
                Db::name('u_address')->where('userID',$userid)->update(['dft'=>0]);
            }
        }

        $result=Db::name('u_address')->insert($data);
        if($result==1){
            return json(['code'=>1,'data'=>'添加成功']);
        }else {
            return json(['code'=>-3,'data'=>'网络出错,请稍后重试']);
        }
    }

    //修改提货信息
    public function edit(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $post=$this->request->post();

        $data['linkman']=$post['linkman'];
        $data['mobile']=$post['mobile'];
        $data['address']=$post['address'];
        $data['dft']=$post['dft'];
        $data['userID']=$userid;
        $data['id']=$post['id'];

        $defaultid=$this->hasdefault($userid);
        if($defaultid==0){
            $data['dft']=1;
        }else{
            if($data['dft']==1){
                Db::name('u_address')->where('userID',$userid)->update(['dft'=>0]);
            }
        }

        $result=Db::name('u_address')->update($data);
        if($result!==false){
            return json(['code'=>1,'data'=>'更新成功']);
        }else{
            return json(['code'=>-3,'data'=>'网络出错,请稍后重试']);
        }
    }

    //删除提货信息
    public function delete(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $id=$this->request->post('id');
        $result=Db::name('u_address')->where('userID',$userid)->where('id',$id)->update(['is_delete'=>1]);
        if($result!==false){
            return json(['code'=>1,'data'=>'删除成功']);
        }else{
            return json(['code'=>-3,'data'=>'网络出错,请稍后重试']);
        }
    }

    //查询是否有默认的收货地址
    //有则返回id，无则返回0
    public function hasdefault($userid){
        $result=Db::name('u_address')->where('userID',$userid)->where('dft',1)->find();
        if($result){
            return $result['id'];
        }else{
            return 0;
        }
    }

}
