<?php
namespace app\api\controller;

use think\Controller;
use think\Db;

class Goods extends Controller
{
    //获取商品列表
    public function goodslist(){
        $data=$this->request->post();
        if($data['listsort']==''){
            $order='update_time desc';
        }else{
            $order=str_replace(":"," ",$data['listsort']);
        }
        $num=8;
        $start=$data['page']*$num-$num;

        $where='status=1 and is_delete=0';
        if($data['tradeID']>0){
            $where.=' and tradeID='.$data['tradeID'];
        }

        $goodslist=Db::name('s_goods')->where($where)
            ->where('title','like','%'.$data['keyword'].'%')
            ->order($order)->limit($start,$num)->select();

        foreach ($goodslist as $k=>$v){
            $goodslist[$k]['lunbo']=explode(',',$v['lunbo']);
        }

        $array=['data'=>$goodslist,'num'=>$num];
        return json($array);
    }

    //推荐商品，搜索页初始化要显示推荐商品
    public function recommendlist(){
        $num = 8;    //需要抽取的默认条数
        $table = 's_goods';    //需要抽取的数据表
        //$pk = Db::name($table)->getPK();//获取主键
        $pk = 'id';
        $countcus = Db::name($table)->field($pk)->where('status',1)->where('is_delete',0)->select();//查询数据

        $con = '';
        foreach($countcus as $v=>$val){
            $con.= $val[$pk].'|';
        }
        $array = explode("|",$con);// 拆分
        $data = [];
        foreach ($array as $v){
            if (!empty($v)){
                $data[$v]=$v;//循环健值
            };
        }
        $count=count($countcus);
        if($count==0){
            return json([]);
        }
        if($count<$num){
            $num=$count;
        }
        $a=array_rand($data,$num) ;//随机数组
        $list = Db::name($table)->where($pk,'in',$a)->select();
        return json($list);

    }

    //获取热门搜索关键词
    public function hotsearchlist(){
        $list=Db::name('s_hotsearch')->where('is_delete',0)->select();
        return json($list);
    }

    //获取限时抢购商品列表
    public function rushgoodslist(){
        $where='status=1 and is_delete=0 and tradeID=1';
        $start_time=strtotime(date('Y-m-d').' 00:00:00');
        $end_time=strtotime(date('Y-m-d').' 23:59:59');
        $thetime=time();
        //开始时间是今天
        $where.=' and start_time >='.$start_time.' and start_time <='.$end_time;
        //结束时间没有到
        $where.=' and end_time >='.$thetime;

        $goodslist=Db::name('s_goods')->where($where)
            ->order('start_time asc')->select();

        //有几种时间
        $time=[];
        foreach ($goodslist as $k=>$v){
            //购买状态（能否购买，几点可以购买）
            if($thetime>=$v['start_time']){
                $goodslist[$k]['buystatus']=1;
            }else{
                $goodslist[$k]['buystatus']=date("H:i",$v['start_time']).'开抢';
            }
            $goodslist[$k]['percent']=($v['stock']-$v['showsales'])*100/$v['stock'];

            //查找二维数组中，项出现的所在索引
            if (array_search($v['start_time'], array_column($time, 'start_time'))===false)
            {
                if($thetime>=$v['start_time']){
                    $status='已开抢';
                }else{
                    $status='即将开抢';
                }
                $time[]=['start_time'=>$v['start_time'],'showtime'=>date("H:i",$v['start_time']),'status'=>$status];
            }
        }

        foreach ($time as $k=>$v){
            if($v['status']=='已开抢'){
                $time[$k]['status']='抢购中';
                if($k>0&&$time[$k-1]['status']=='抢购中'){
                    $time[$k-1]['status']='已开抢';
                }
            }
        }

        return json(['code'=>1,'data'=>$goodslist,'time'=>$time]);
    }

    //获取商品详情(商品详情页和订单确定页会用到)
    public function detail(){
        $id=$this->request->post('id');
        $data=Db::name('s_goods')->where('id',$id)->find();

        //当是限时抢购商品时
        if($data['tradeID']==1){
            $thetime=time();
            //购买状态（能否购买，几点可以购买）
            if($thetime>=$data['start_time']){
                $data['buystatus']=1;
            }else{
                $data['buystatus']=date("H:i",$data['start_time']).'开抢';
            }

            if($thetime>=$data['end_time']){
                $data['buystatus']='活动过期了';
            }

            $data['percent']=($data['stock']-$data['showsales'])*100/$data['stock'];
        }
        return json($data);
    }


}
