<?php
namespace app\api\controller;

use think\Db;

class Cart extends Base
{
    //获取购物车列表
    public function cartlist(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $list=Db::name('u_cart')->alias('c')->join('s_goods g','c.objID = g.id')
            ->field('c.id,c.userID,c.objID,c.count,g.title,g.brief,g.thumb,g.yuanPrice,g.youPrice,g.showsales,g.stock,g.tradeID,g.status,g.is_delete,g.start_time,g.end_time')
            ->where('c.userID',$userid)
            ->where('c.is_delete',0)
            ->order('c.id desc')->select();

        $validlist=[];          //有效的列表
        $invalidlist=[];        //无效的列表

        //如果是限时抢购商品的话，需要循环判断。
        foreach ($list as $k=>$v){
            //如果商品已下架或者已删除，则列入无效列表中
            if($v['status']==0||$v['is_delete']==1){
                $v['showstatus']='已下架';
                $invalidlist[]=$v;
                continue;
            }
            //如果商品库存-购物车数量<0,则要修改数量，如果本身库存不足，则列入失效列表
            if ($v['stock'] - $v['showsales'] - $v['count'] < 0) {
                $count=$v['stock'] - $v['showsales'];
                if($count<=0){
                    $v['showstatus']='库存不足';
                    $invalidlist[]=$v;
                    continue;
                }else{
                    $v['count']=$count;
                }
            }
            if($v['tradeID']==1){
                $thetime=time();
                if($v['start_time']>$thetime){
                    $v['showstatus']='活动未开始';
                    $invalidlist[]=$v;
                    continue;
                }
                if($v['end_time']<$thetime){
                    $v['showstatus']='活动已结束';
                    $invalidlist[]=$v;
                    continue;
                }
            }
            $validlist[]=$v;
        }
        return json(['code'=>1,'data'=>['validlist'=>$validlist,'invalidlist'=>$invalidlist]]);

    }

    //删除购物车
    public function delcart(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $id=$this->request->post('id');
        $result=Db::name('u_cart')->where('userID',$userid)->where('id',$id)
            ->update(['is_delete' => 1]);
        if($result !== false){
            return json(['code'=>1,'data'=>'删除成功']);
        }else{
            return json(['code'=>-3,'data'=>'网络出错,请稍后重试']);
        }
    }

    //获取选中购物车里并提交到订单页面的商品列表
    public function selectcart(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $itemID=$this->request->post('itemID');

        $list=Db::name('u_cart')->alias('c')->join('s_goods g','c.objID = g.id')
            ->field('c.id,c.userID,c.objID,c.count,g.title,g.brief,g.thumb,g.yuanPrice,g.youPrice,g.showsales,g.stock,g.tradeID,g.status,g.is_delete,g.start_time,g.end_time')
            ->where('c.userID',$userid)
            ->where('c.is_delete',0)
            ->where('c.id','in',$itemID)->order('c.id desc')->select();

        $validlist=[];          //有效的列表
        $invalidlist=[];        //无效的列表

        //如果是限时抢购商品的话，需要循环判断。
        foreach ($list as $k=>$v){
            //如果商品已下架或者已删除，则列入无效列表中
            if($v['status']==0||$v['is_delete']==1){
                $v['showstatus']='已下架';
                $invalidlist[]=$v;
                continue;
            }
            //如果商品库存-购物车数量<0,则要修改数量，如果本身库存不足，则列入失效列表
            if ($v['stock'] - $v['showsales'] - $v['count'] < 0) {
                $count=$v['stock'] - $v['showsales'];
                if($count<=0){
                    $v['showstatus']='库存不足';
                    $invalidlist[]=$v;
                    continue;
                }else{
                    $v['count']=$count;
                }
            }
            if($v['tradeID']==1){
                $thetime=time();
                if($v['start_time']>$thetime){
                    $v['showstatus']='活动未开始';
                    $invalidlist[]=$v;
                    continue;
                }
                if($v['end_time']<$thetime){
                    $v['showstatus']='活动已结束';
                    $invalidlist[]=$v;
                    continue;
                }
            }
            $validlist[]=$v;
        }

        return json(['code'=>1,'data'=>$validlist]);
    }

    //商品加入购物车
    function addcart(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $id=$this->request->post('id');
        $count=$this->request->post('count');

        //查询一下购物车信息
        $cart=Db::name('u_cart')->where('objID',$id)->where('userID',$userid)->where('is_delete',0)->find();
        if($cart){
            $count=$cart['count']+$count;
        }

        //查询一下商品信息
        $good=Db::name('s_goods')->where('id',$id)->find();
        //如果是限时抢购商品，需要判断一下限时时间

        if($good['tradeID']==1){
            $thetime=time();
            if($good['start_time']>$thetime){
                return json(['code' => -1002, 'data' => '未到抢购时间']);
            }
            if($good['end_time']<$thetime){
                return json(['code' => -1003, 'data' => '已过抢购时间']);
            }
        }

        if($good['stock']-$good['showsales']-$count<0) {
            return json(['code' => -1001, 'data' => '库存不足']);
        }else{

            $data['thumb']=$good['thumb'];
            $data['title']=$good['title'];
            $data['price']=$good['youPrice'];
            $data['brief']=$good['brief'];
            $data['count']=$count;

            if($cart){
                $result=Db::name('u_cart')->where('id',$cart['id'])->where('userID',$userid)->where('is_delete',0)->update($data);
                if($result !== false){
                    return json(['code'=>1,'data'=>'添加成功']);
                }else{
                    return json(['code'=>-3,'data'=>'网络出错,请稍后重试']);
                }
            }else{
                $data['userID']=$userid;
                $data['objID']=$id;
                $result=Db::name('u_cart')->insert($data);
                if($result==1){
                    return json(['code'=>1,'data'=>'添加成功']);
                }else {
                    return json(['code'=>-3,'data'=>'网络出错,请稍后重试']);
                }
            }
        }
    }

}
