<?php
namespace app\api\controller;

use think\Db;

class Coupon extends Base
{
    //在小程序首页显示，并且没有过期的优惠券（可以是还没开始的）
    public function index(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $coupon=Db::name('s_coupon')->where('status',1)->where('is_delete',0)
           ->where('end_time','gt',time())->order('id desc')->select();
        foreach ($coupon as $k=>$v){
            $result=Db::name('u_coupon')->where('coupon_id',$v['id'])
                ->where('user_id',$userid)->find();
            if($result===null){
                $coupon[$k]['isReceive']=0;
            }else{
                $coupon[$k]['isReceive']=1;
            }
        }
        return json(['code'=>1,'data'=>$coupon]);
    }

    //小程序首页，用户领取优惠券
    public function getcoupon(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $id=$this->request->post('id');

        $result=Db::name('u_coupon')->where('coupon_id',$id)
            ->where('user_id',$userid)->find();
        if($result===null){
            $coupon=Db::name('s_coupon')->field('name,quota,amount,start_time,end_time')
                ->where('id',$id)->find();
            $coupon['create_time']=time();
            $coupon['coupon_id']=$id;
            $coupon['user_id']=$userid;
            $res=Db::name('u_coupon')->insert($coupon);
            if($res===1){
                return json(['code'=>1,'data'=>'优惠券领取成功']);
            }else{
                return json(['code'=>-6002,'data'=>'优惠券领取失败']);
            }
        }else{
            return json(['code'=>-6001,'data'=>'优惠券已领取，请勿重复领取']);
        }
    }

    //我的页面，获取有效的优惠券张数
    public function canusecoupon(){
        //返回有效的优惠券张数，在我的页面显示
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $thetime=time();
        $canuserCoupon=Db::name('u_coupon')->where('status',0)->where('user_id',$userid)
            ->where('start_time','elt',$thetime)->where('end_time','gt',$thetime)->count();
        return json(['code'=>1,'data'=>$canuserCoupon]);
    }

    //获取优惠券列表(一进来全部查)
    public function couponlist(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        //0可用优惠券 1未开始 2已使用 3已过期
        $thetime=time();
        $list=Db::name('u_coupon')->where('user_id',$userid)->order('id desc')->select();

        $list0=[];
        $list1=[];
        $list2=[];
        $list3=[];
        foreach ($list as $k=>$v){
            if($v['status']==0&&$v['start_time']<=$thetime&&$v['end_time']>$thetime){
                $list0[]=$v;
            }
            if($v['status']==0&&$v['start_time']>$thetime){
                $list1[]=$v;
            }
            if($v['status']==1&&$v['start_time']>$thetime){
                $list2[]=$v;
            }
            if($v['end_time']<$thetime){
                $list3[]=$v;
            }
        }

        foreach ($list0 as $k=>&$v){
            $v['start_time']=date('Y.m.d H:i',$v['start_time']);
            $v['end_time']=date('Y.m.d H:i',$v['end_time']);
        }

        foreach ($list1 as $k=>&$v){
            $v['start_time']=date('Y.m.d H:i',$v['start_time']);
            $v['end_time']=date('Y.m.d H:i',$v['end_time']);
        }

        foreach ($list2 as $k=>&$v){
            $v['start_time']=date('Y.m.d H:i',$v['start_time']);
            $v['end_time']=date('Y.m.d H:i',$v['end_time']);
        }

        foreach ($list3 as $k=>&$v){
            $v['start_time']=date('Y.m.d H:i',$v['start_time']);
            $v['end_time']=date('Y.m.d H:i',$v['end_time']);
        }

        $list0_count=count($list0);
        $list1_count=count($list1);
        $list2_count=count($list2);
        $list3_count=count($list3);

        return json(['code'=>1,'data'=>['list0'=>$list0,'list0_count'=>$list0_count,'list1'=>$list1,'list1_count'=>$list1_count,'list2'=>$list2,'list2_count'=>$list2_count,'list3'=>$list3,'list3_count'=>$list3_count]]);
    }


    //订单确定页面，查询可用的优惠券
    public function couponorder(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        //0可用优惠券 1未开始 2已使用 3已过期
        $thetime=time();
        $list=Db::name('u_coupon')->where('status',0)->where('user_id',$userid)
            ->where('start_time','elt',$thetime)->where('end_time','gt',$thetime)
            ->order('amount desc')->select();

        foreach ($list as $k=>&$v){
            $v['start_time']=date('Y.m.d H:i',$v['start_time']);
            $v['end_time']=date('Y.m.d H:i',$v['end_time']);
        }


        return json(['code'=>1,'data'=>$list]);
    }

}
