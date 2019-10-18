<?php
namespace app\api\controller;
use think\Db;

class Group extends Base
{
    //团长今日详细订单
    //例如查询今天订单，今天是5月11号
    //那么查询 5-10 21点  到  5-11 21点
    public function detail(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $page=$this->request->post('page');
        //每页数量
        $num=4;
        $start=$page*$num-$num;

        $day=$this->request->post('day');
        $groupID=$this->request->post('groupID');
        if($day=='today'){
            $start_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
            $end_time=strtotime(date("Y-m-d").' 21:00:00');
        }elseif($day=='yesterday'){
            $start_time=strtotime(date("Y-m-d",strtotime("-2 day")).' 21:00:00');
            $end_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
        }elseif($day=='history'){
            $start_date=$this->request->post('start_date');
            $end_date=$this->request->post('end_date');
            $start_time=strtotime($start_date.' 21:00:00')-86400;
            $end_time=strtotime($end_date.' 21:00:00');
        }else{
            return json(['code'=>-3,'data'=>'参数错误']);
        }

        $list=Db::name('u_order')->where('status','>',0)->where('groupID',$groupID)
            ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
            limit($start,$num)->order('id desc')->select();
        foreach ($list as $k=>$v){
            $detail=Db::name('u_orderdetail')->where('orderID',$v['id'])->select();
            $list[$k]['detail']=$detail;
        }


        return json(['code'=>1,'data'=>$list,'num'=>$num]);
    }

    //供应商今日的累计订单
    //例如今天是5月11号
    //那么查询 5-10 22点  到  5-11 22点
    public function count(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $day=$this->request->post('day');
        $groupID=$this->request->post('groupID');
        if($day=='today'){
            $start_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
            $end_time=strtotime(date("Y-m-d").' 21:00:00');
        }elseif($day=='yesterday'){
            $start_time=strtotime(date("Y-m-d",strtotime("-2 day")).' 21:00:00');
            $end_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
        }elseif($day=='history'){
            $start_date=$this->request->post('start_date');
            $end_date=$this->request->post('end_date');
            $start_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
            $end_time=strtotime($end_date.' 21:00:00');
        }else{
            return json(['code'=>-3,'data'=>'参数错误']);
        }

        $list=Db::name('u_orderdetail')->where('status','>',0)->where('groupID',$groupID)
            ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
            order('id desc')->select();

        $countlist=[];
        $sumTotal=0;
        $bonusTotal=0;
        foreach ($list as $k=>$v){
            if(array_key_exists($v['objID'],$countlist)){
                $countlist[$v['objID']]['count']+=$v['count'];
                $countlist[$v['objID']]['zongjia']=bcadd(bcmul($v['price'],$v['count'],2),$countlist[$v['objID']]['zongjia'],2);
                $countlist[$v['objID']]['zongbonus']=bcadd($v['bonus'],$countlist[$v['objID']]['zongbonus'],2);
            }else{
                $countlist[$v['objID']]=$v;
                $countlist[$v['objID']]['zongjia']=bcmul($v['price'],$v['count'],2);
                $countlist[$v['objID']]['zongbonus']=$v['bonus'];
            }
            $sumTotal=bcadd(bcmul($v['price'],$v['count'],2),$sumTotal,2);
            $bonusTotal=bcadd($v['bonus'],$bonusTotal,2);
        }

        return json(['code'=>1,'data'=>$countlist,'sumTotal'=>$sumTotal,'bonusTotal'=>$bonusTotal]);
    }

    //获取小区列表，用于用户绑定小区时
    public function grouplist(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $page=$this->request->post('page');
        $keyword=$this->request->post('keyword');
        //每页数量
        $num=8;
        $start=$page*$num-$num;

        $list=Db::name('s_group')->alias('g')->join('s_user u','g.userID = u.id')
            ->field('g.id,g.name,g.address,g.linkman,g.phone,u.thumb')->where('g.is_delete',0)
            ->where('g.name','like','%'.$keyword.'%')
            ->limit($start,$num)->select();
        return json(['code'=>1,'data'=>$list,'num'=>$num]);
    }

    //用户绑定小区
    public function bind(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $id=$this->request->post('id');
        $result=Db::name('s_user')->where('id',$userid)->update(['groupID'=>$id]);
        if($result!==false){
            return json(['code'=>1,'data'=>'绑定成功']);
        }else{
            return json(['code'=>-3001,'data'=>'绑定小区失败']);
        }
    }

    //根据团长id获取团长信息
    public function info(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $groupID=$this->request->post('groupID');
        $data=Db::name('s_group')->alias('g')->join('s_user u','g.userID = u.id')->field('g.name,g.address,g.bank,g.owner,g.banknumber,g.linkman,g.phone,u.thumb')->where('g.id',$groupID)->where('g.is_delete',0)->find();
        return json(['code'=>1,'data'=>$data]);
    }
}
