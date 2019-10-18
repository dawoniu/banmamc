<?php
namespace app\api\controller;
use think\Db;

class Supplier extends Base
{
    //供应商今日订单
    //例如今天是5月11号
    //那么查询 5-10 21点  到  5-11 21点
    public function today(){
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

        $supplierID=$this->request->post('supplierID');
        $start_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
        $end_time=strtotime(date("Y-m-d").' 21:00:00');

        $list=Db::name('u_orderdetail')->where('status','>',0)->where('supplierID',$supplierID)
            ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
            limit($start,$num)->order('id desc')->select();

        return json(['code'=>1,'data'=>$list,'num'=>$num]);
    }

    //供应商今日的累计订单
    //例如今天是5月11号
    //那么查询 5-10 21点  到  5-11 21点
    public function counttoday(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $supplierID=$this->request->post('supplierID');
        $start_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
        $end_time=strtotime(date("Y-m-d").' 21:00:00');

        $list=Db::name('u_orderdetail')->where('status','>',0)->where('supplierID',$supplierID)
            ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
            order('id desc')->select();

        $countlist=[];
        $sumTotal=0;
        foreach ($list as $k=>$v){
            if(array_key_exists($v['objID'],$countlist)){
                $countlist[$v['objID']]['count']+=$v['count'];
                $countlist[$v['objID']]['zongjia']=bcadd(bcmul($v['supplierPrice'],$v['count'],2),$countlist[$v['objID']]['zongjia'],2);
            }else{
                $countlist[$v['objID']]=$v;
                $countlist[$v['objID']]['zongjia']=bcmul($v['supplierPrice'],$v['count'],2);
            }
            $sumTotal=bcadd(bcmul($v['supplierPrice'],$v['count'],2),$sumTotal,2);
        }

        return json(['code'=>1,'data'=>$countlist,'sumTotal'=>$sumTotal]);
    }

    //供应商昨日订单
    //例如今天是5月11号
    //那么查询 5-9 21点  到  5-10 21点
    public function yesterday(){
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

        $supplierID=$this->request->post('supplierID');
        $start_time=strtotime(date("Y-m-d",strtotime("-2 day")).' 21:00:00');
        $end_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');

        $list=Db::name('u_orderdetail')->where('status','>',0)->where('supplierID',$supplierID)
            ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
            limit($start,$num)->order('id desc')->select();

        return json(['code'=>1,'data'=>$list,'num'=>$num]);
    }

    //供应商今日的累计订单
    //例如今天是5月11号
    //那么查询 5-10 21点  到  5-11 21点
    public function countyesterday(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $supplierID=$this->request->post('supplierID');
        $start_time=strtotime(date("Y-m-d",strtotime("-2 day")).' 21:00:00');
        $end_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');

        $list=Db::name('u_orderdetail')->where('status','>',0)->where('supplierID',$supplierID)
            ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
            order('id desc')->select();

        $countlist=[];
        $sumTotal=0;
        foreach ($list as $k=>$v){
            if(array_key_exists($v['objID'],$countlist)){
                $countlist[$v['objID']]['count']+=$v['count'];
                $countlist[$v['objID']]['zongjia']=bcadd(bcmul($v['supplierPrice'],$v['count'],2),$countlist[$v['objID']]['zongjia'],2);
            }else{
                $countlist[$v['objID']]=$v;
                $countlist[$v['objID']]['zongjia']=bcmul($v['supplierPrice'],$v['count'],2);
            }
            $sumTotal=bcadd(bcmul($v['supplierPrice'],$v['count'],2),$sumTotal,2);
        }

        return json(['code'=>1,'data'=>$countlist,'sumTotal'=>$sumTotal]);
    }


    //供应商是订单
    //例如传入开始日期为5-9 截止日期为5-11
    //那么查询 5-8 21点  到  5-11 21点
    public function history(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $page=$this->request->post('page');
        $start_date=$this->request->post('start_date');
        $end_date=$this->request->post('end_date');
        //每页数量
        $num=4;
        $start=$page*$num-$num;

        $supplierID=$this->request->post('supplierID');
        $start_time=strtotime($start_date.' 21:00:00')-86400;
        $end_time=strtotime($end_date.' 21:00:00');

        $list=Db::name('u_orderdetail')->where('status','>',0)->where('supplierID',$supplierID)
            ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
            limit($start,$num)->order('id desc')->select();

        return json(['code'=>1,'data'=>$list,'num'=>$num]);
    }

    //供应商今日的累计订单
    //例如今天是5月11号
    //那么查询 5-10 21点  到  5-11 21点
    public function counthistory(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $start_date=$this->request->post('start_date');
        $end_date=$this->request->post('end_date');

        $supplierID=$this->request->post('supplierID');
        $start_time=strtotime($start_date.' 21:00:00')-86400;
        $end_time=strtotime($end_date.' 21:00:00');

        $list=Db::name('u_orderdetail')->where('status','>',0)->where('supplierID',$supplierID)
            ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
            order('id desc')->select();

        $countlist=[];
        $sumTotal=0;
        foreach ($list as $k=>$v){
            if(array_key_exists($v['objID'],$countlist)){
                $countlist[$v['objID']]['count']+=$v['count'];
                $countlist[$v['objID']]['zongjia']=bcadd(bcmul($v['supplierPrice'],$v['count'],2),$countlist[$v['objID']]['zongjia'],2);
            }else{
                $countlist[$v['objID']]=$v;
                $countlist[$v['objID']]['zongjia']=bcmul($v['supplierPrice'],$v['count'],2);
            }
            $sumTotal=bcadd(bcmul($v['supplierPrice'],$v['count'],2),$sumTotal,2);
        }

        return json(['code'=>1,'data'=>$countlist,'sumTotal'=>$sumTotal]);
    }

}
