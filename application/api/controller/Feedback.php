<?php
namespace app\api\controller;

use think\Db;

class Feedback extends Base
{

    //新增意见反馈
    public function add(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $post=$this->request->post();

        $data['content']=$post['content'];
        $data['nickName']=$post['nickName'];
        $data['userID']=$userid;
        $data['create_time']=time();

        $result=Db::name('s_feedback')->insert($data);
        if($result==1){
            return json(['code'=>1,'data'=>'您的宝贵意见已提交，谢谢您的支持。']);
        }else {
            return json(['code'=>-5001,'data'=>'意见反馈提交失败。']);
        }
    }

}
