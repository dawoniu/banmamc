<?php
namespace app\api\controller;

use think\Db;

class Order extends Base
{
    //生成代付款订单
    public function buildorder(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }

        $canbuy=Db::name('s_system')->where('id',1)->value('canbuy');
        if($canbuy!=1){
            return json(['code'=>-2005,'data'=>'系统维护中，暂不能支付']);
            exit;
        }

        $thetime=time();

        //购物车的id用逗号分隔的字符串
        $itemID=$this->request->post('itemID');
        $count=$this->request->post('count');
        $count=explode(",",$count);

        $dataFrom=$this->request->post('dataFrom');
        if($dataFrom==1){
            //购物车购买 itemID传入的是购物车id，否则传入的是商品id
            $list=Db::name('u_cart')->alias('c')->join('s_goods g','c.objID = g.id')
                ->field('c.id,c.userID,c.objID,g.title,g.thumb,g.yuanPrice,g.youPrice,g.showsales,g.stock,g.status,g.is_delete,g.supplierID,g.supplierPrice,g.ratio,g.tradeID,g.start_time,g.end_time')
                ->where('c.userID',$userid)
                ->where('c.is_delete',0)
                ->where('c.id','in',$itemID)->order('c.id desc')->select();
        }else{
            $list=Db::name('s_goods')->where('id',$itemID)->select();
        }

        $sumTotal=0;
        $bonusTotal=0;
        //如果商品下架，则删除该商品数组
        foreach ($list as $k=>$v){
            //商品剩余库存
            $shengcount=$v['stock']-$v['showsales'];
            if($shengcount<=0){
                unset($list[$k]);
                continue;
            }
            //判断需要购买的数量是否充足
            if($count[$k]>$shengcount){
                $list[$k]['count']=$shengcount;
            }else{
                $list[$k]['count']=$count[$k];
            }

            if($v['tradeID']==1){
                if($v['start_time']>$thetime){
                    unset($list[$k]);
                    continue;
                }
                if($v['end_time']<$thetime){
                    unset($list[$k]);
                    continue;
                }
            }
            if($v['status']==0||$v['is_delete']==1){
                unset($list[$k]);
            }else{
                //这里用到了浮点型的计算函数来进行乘法和加法的运算
                //订单总金额
                $sumTotal=bcadd(bcmul($v['youPrice'],$count[$k],2),$sumTotal,2);
                //单个商品的佣金
                $bonus=bcmul($v['youPrice'],$count[$k],2);
                $bonus=bcmul($bonus,$v['ratio']/100,2);
                //订单总佣金
                $bonusTotal=bcadd($bonusTotal,$bonus,2);

                if($dataFrom==1){
                    $detaildata[] = ['objID'=>$v['objID'],'objTitle'=>$v['title'],'thumb'=>$v['thumb'],'count'=>$count[$k],'price'=>$v['youPrice'],'supplierID'=>$v['supplierID'],'supplierPrice'=>$v['supplierPrice'],'ratio'=>$v['ratio'],'bonus'=>$bonus];
                }else{
                    $detaildata[] = ['objID'=>$v['id'],'objTitle'=>$v['title'],'thumb'=>$v['thumb'],'count'=>$count[$k],'price'=>$v['youPrice'],'supplierID'=>$v['supplierID'],'supplierPrice'=>$v['supplierPrice'],'ratio'=>$v['ratio'],'bonus'=>$bonus];
                }
            }
        }

        //生成订单
        //当还有商品时，再生成订单
        if($list&&$sumTotal>0){
            //查询用户使用的优惠券信息
            $couponID=$this->request->post('couponID');
            if($couponID>0){
                $coupon=Db::name('u_coupon')->where('id',$couponID)->find();
                if($coupon&&$coupon['status']==0){
                    $insertdata['couponPrice']=$coupon['amount'];
                    $insertdata['couponID']=$couponID;
                    $sumTotal=$sumTotal-$coupon['amount'];
                }else{
                    return json(['code'=>-6003,'data'=>'该优惠券已使用，生成订单失败']);
                }
            }

            $insertdata['linkman']=$this->request->post('linkman');
            $insertdata['mobile']=$this->request->post('mobile');
            $insertdata['remark']=$this->request->post('remark');
            $insertdata['userID']=$userid;
            $insertdata['orderSN']='BM'.$thetime;
            $insertdata['sumTotal']=$sumTotal;
            $insertdata['bonusTotal']=$bonusTotal;
            $insertdata['create_time']=$thetime;
            $groupID=Db::name('s_user')->where('id',$userid)->value('groupID');
            $insertdata['groupID']=$groupID;
            // 启动事务
            Db::startTrans();
            try{
                $orderID=Db::name('u_order')->insertGetId($insertdata);
                if($orderID>0){
                    foreach ($detaildata as $k=>$v){
                        $detaildata[$k]['orderID']=$orderID;
                        $detaildata[$k]['orderSN']=$insertdata['orderSN'];
                        $detaildata[$k]['create_time']=$thetime;
                        $detaildata[$k]['groupID']=$groupID;

                        //商品库存要减，下面的代码移到了生成订单的页面，当生成订单时，就减少库存
                        Db::name('s_goods')->where('id',$v['objID'])->inc('sales',$v['count'])
                            ->inc('showsales',$v['count'])->update();

                    }
                    Db::name('u_orderdetail')->insertAll($detaildata);
                }
                if($dataFrom==1){
                    Db::name('u_cart')->where('id','in',$itemID)->update(['is_delete'=>1]);
                }
                //如果使用了优惠券，则要把优惠券的状态改为已使用
                if($couponID>0) {
                    Db::name('u_coupon')->where('id',$couponID)->update(['status'=>1,'update_time'=>$thetime]);
                }
                // 提交事务
                Db::commit();
                return json(['code'=>1,'data'=>['orderID'=>$orderID]]);
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return json(['code'=>-2001,'data'=>'生成待付款订单失败']);
            }
        }

        return json(['code'=>-2004,'data'=>'订单商品全部失效，请重新选择商品']);
    }


    //支付成功的回调，需要修改订单状态，改为已付款
    public function payorder(){
        $xml= file_get_contents("php://input");
        $array=xmlToArray($xml);
        var_dump($array);
        if($array['result_code'] == 'SUCCESS' && $array['return_code'] == 'SUCCESS') {
            //告诉微信已经接受到了信息，不然微信会一直提交很多次过来
            $returnxml='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            echo $returnxml;
            //订单状态改为已付款
            Db::name('u_order')->where('orderSN',$array['out_trade_no'])->update(['status'=>1]);
            //订单详情表状态改为已付款
            Db::name('u_orderdetail')->where('orderSN',$array['out_trade_no'])->update(['status'=>1]);
            //商品库存要减，下面的代码移到了生成订单的页面，当生成订单时，就减少库存

        }
    }

    //订单列表
    public function orderlist(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $page=$this->request->post('page');
        $status=$this->request->post('status');

        if($page==1&&$status==0){

            $thetime=time()-600;
            //当第一次加载待付款订单时，删除已经过期的待付款订单
            $orderid=Db::name('u_order')->where('status',0)
                ->where('create_time','<=',$thetime)->column('id');

            // 启动事务
            Db::startTrans();
            try{
                Db::name('u_order')->where('id','in',$orderid)
                    ->update(['is_delete'=>1,'delete_time'=>$thetime+600]);

                foreach ($orderid as $k=>$v){
                    $detail=Db::name('u_orderdetail')->where('orderID',$v)->select();
                    foreach ($detail as $vo){
                        //商品库存要减，下面的代码移到了生成订单的页面，当生成订单时，就减少库存
                        Db::name('s_goods')->where('id',$vo['objID'])->dec('sales',$vo['count'])
                            ->dec('showsales',$vo['count'])->update();
                    }
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }

        }

        $where='is_delete=0 and userID='.$userid;
        //每页数量
        $num=4;
        $start=$page*$num-$num;
        if($status!='all'){
            $where.=' and status='.$status;
        }

        $list=Db::name('u_order')->where($where)->limit($start,$num)->order('id desc')->select();
        foreach ($list as $k=>$v){
            $detail=Db::name('u_orderdetail')->where('orderID',$v['id'])->select();
            $list[$k]['detail']=$detail;
        }
        return json(['code'=>1,'data'=>$list,'num'=>$num]);
    }

    public function delorder(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $id=$this->request->post('id');

        $result=Db::name('u_order')->where('id',$id)->where('userID',$userid)->update(['is_delete'=>1,'delete_time'=>time()]);
        if($result!==false){
            return json(['code'=>1,'data'=>'订单删除成功']);
        }else{
            return json(['code'=>-2002,'data'=>'订单删除失败']);
        }
    }
    //订单详情
    public function detail(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        //订单ID
        $id=$this->request->post('id');
        $data=Db::name('u_order')->where('id',$id)->where('userID',$userid)->find();
        $data['create_time']=date('Y-m-d H:i:s',$data['create_time']);
        $detail=Db::name('u_orderdetail')->where('orderID',$data['id'])->select();
        $data['detail']=$detail;
        return json(['code'=>1,'data'=>$data]);
    }
    //确认收货订单
    public function received(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $rate=$this->request->post('rate');
        //订单ID
        $id=$this->request->post('id');
        // 启动事务
        Db::startTrans();
        try{
            Db::name('u_order')->where('id',$id)->where('status',2)->where('userID',$userid)->update(['rate'=>$rate,'received_time'=>time(),'status'=>3]);
            Db::name('u_orderdetail')->where('orderID',$id)->where('status',2)->update(['status'=>3]);
            // 提交事务
            Db::commit();
            return json(['code'=>1,'data'=>'操作成功']);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>-2003,'data'=>'确认收货失败']);
        }
    }


    //自动发货
    //每天21点15分，因为有个待付款时间为10分钟，把昨天21点到今天21点的订单自动发货
    public function autosend(){
        $start_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
        $end_time=strtotime(date("Y-m-d").' 21:00:00');
        //查询所有待自动发货的订单，以便写入记事本中
        $list=Db::name('u_order')->where('status',1)
            ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
            select();

        // 启动事务
        Db::startTrans();
        try{
            //自动发货
            //订单表状态改为2
            Db::name('u_order')->where('status',1)
                ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
                update(['status'=>2]);
            //订单详情表状态改为2
            Db::name('u_orderdetail')->where('status',1)
                ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
                update(['status'=>2]);

            // 提交事务
            Db::commit();

            //把订单发货情况写入记事本
            $data="\n".date('Y-m-d H:i:s')."\n";
            foreach ($list as $k => $v){
                $data.="订单ID:".$v['id'].",订单号：".$v['orderSN']."自动发货成功\n";
            }
            file_put_contents("./sendorder.txt", $data, FILE_APPEND);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            //把订单发货情况写入记事本
            $data="\n".date('Y-m-d H:i:s')."\n";
            $data.="订单自动发货失败\n";
            file_put_contents("./sendorder.txt", $data, FILE_APPEND);
        }
        echo date('Y-m-d H:i:s').'已执行自动发货';
    }

    //自动收货和自动结算佣金
    //每天晚上9点统计之前第五天的订单，把未收货的订单，自动收货，并结算各个团长的佣金
    public function autocensus(){
        $start_time=strtotime(date("Y-m-d",strtotime("-5 day")).' 21:00:00');
        $end_time=strtotime(date("Y-m-d",strtotime("-4 day")).' 21:00:00');
        //查询所有待自动收货的订单，以便写入记事本中，这里还要加上用户自己收货的，要结算给团长佣金
        $list=Db::name('u_order')->where('status =2 or status =3')
            ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
            select();

        // 启动事务
        Db::startTrans();
        try{
            //自动收货
            //订单表状态改为3
            Db::name('u_order')->where('status',2)
                ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
                update(['isauto'=>1,'received_time'=>time(),'status'=>3]);
            //订单详情表状态改为3
            Db::name('u_orderdetail')->where('status',2)
                ->where('create_time','>=',$start_time)->where('create_time','<',$end_time)->
                update(['status'=>3]);
            $data="\n".date('Y-m-d H:i:s')."\n";
            foreach ($list as $k => $v){
                //结算团长佣金
                Db::name('s_group')->where('id',$v['groupID'])->inc('now_money',$v['bonusTotal'])
                    ->update();
                $data.="订单ID:".$v['id'].",订单号：".$v['orderSN']."自动收货成功\n";
                $data.="团长ID:".$v['groupID'].",增加佣金：".$v['bonusTotal']."\n";
            }
            // 提交事务
            Db::commit();
            //把订单收货情况写入记事本
            file_put_contents("./receivedorder.txt", $data, FILE_APPEND);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            //把订单收货情况写入记事本
            $data="\n".date('Y-m-d H:i:s')."\n";
            $data.="订单自动收货失败\n";
            file_put_contents("./receivedorder.txt", $data, FILE_APPEND);
        }
        echo date('Y-m-d H:i:s').'已执行自动收货';
    }

    //自动删除超时的待付款订单，并且返还库存
    //每隔60秒执行一次。
    public function autodel(){
        $thetime=time()-600;
        //当第一次加载待付款订单时，删除已经过期的待付款订单
        $orderid=Db::name('u_order')->where('status',0)->where('is_delete',0)
            ->where('create_time','<=',$thetime)->column('id');
        $recordID='';

        // 启动事务
        Db::startTrans();
        try{
            Db::name('u_order')->where('id','in',$orderid)
                ->update(['is_delete'=>1,'delete_time'=>$thetime+600]);

            foreach ($orderid as $v){
                if($recordID==''){
                    $recordID=$v;
                }else{
                    $recordID.=','.$v;
                }
                $detail=Db::name('u_orderdetail')->where('orderID',$v)->select();

                foreach ($detail as $vo){
                    //商品库存要减，下面的代码移到了生成订单的页面，当生成订单时，就减少库存
                    Db::name('s_goods')->where('id',$vo['objID'])->dec('sales',$vo['count'])
                        ->dec('showsales',$vo['count'])->update();
                }
            }
            // 提交事务
            Db::commit();
            if($recordID!=''){
                //把自动删除订单情况写入记事本
                $data="\n".date('Y-m-d H:i:s')."\n";
                $data.="自动删除超时的待付款订单,订单ID为".$recordID."\n";
                file_put_contents("./delorder.txt", $data, FILE_APPEND);
            }
            echo date('Y-m-d H:i:s').'已执行自动删除超时的待付款订单,订单ID为'.$recordID;

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();

            //把订单收货情况写入记事本
            $data="\n".date('Y-m-d H:i:s')."\n";
            $data.="自动删除超时的待付款订单失败\n";
            file_put_contents("./delorder.txt", $data, FILE_APPEND);
        }
    }
}
