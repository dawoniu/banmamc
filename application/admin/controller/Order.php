<?php
namespace app\admin\controller;
use think\Db;

class Order extends Base
{
    public function index()
    {
        $data=$this->request->get();
        if(isset($data["admin/order/index_html"])){
            unset($data["admin/order/index_html"]);
        }
        $where="o.is_delete=0";
        if(!empty($data["start_time"])){
            $start_time=strtotime($data["start_time"]);
            $where.=" and o.create_time>=".$start_time;
            $this->assign("start_time",$data["start_time"]);
        }else{
            $this->assign("start_time",'');
        }
        if(!empty($data["end_time"])){
            $end_time=strtotime($data["end_time"]);
            $where.=" and o.create_time<".$end_time;
            $this->assign("end_time",$data["end_time"]);
        }else{
            $this->assign("end_time",'');
        }

        if(!empty($data["keyword"])){
            $where.=" and o.orderSN like '%".$data["keyword"]."%'";
            $this->assign("keyword",$data["keyword"]);
        }else{
            $this->assign("keyword",'');
        }

        if(!empty($data["group_id"])){
            $where.=" and o.groupID =".$data["group_id"];
            $this->assign("groupID",$data["group_id"]);
        }else{
            $this->assign("groupID",'');
        }

        $group=$this->group();
        $this->assign('group',$group);

        $list=Db::name('u_order')->alias('o')->join('s_group g','o.groupID=g.id','left')
            ->field('o.*,g.linkman as glinkman')->where($where)->order('o.id desc')
            ->paginate(10,false,['query'=>$data])->each(function($item, $key){
                $item['detail'] = Db::name('u_orderdetail')->where('orderID',$item['id'])->select();
                return $item;
            });

        $this->assign("list",$list);

        $this -> view -> count = Db::name('u_order')->alias('o')->where($where)->count();
        return $this->fetch();
    }


    public function today(){
        $data=$this->request->get();
        if(isset($data["admin/order/today_html"])){
            unset($data["admin/order/today_html"]);
        }
        $start_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
        $end_time=strtotime(date("Y-m-d").' 21:00:00');
        $where="o.is_delete=0 and o.status>0";
        $where.=" and o.create_time>=".$start_time;
        $where.=" and o.create_time<".$end_time;

        if(!empty($data["keyword"])){
            $where.=" and o.orderSN like '%".$data["keyword"]."%'";
            $this->assign("keyword",$data["keyword"]);
        }else{
            $this->assign("keyword",'');
        }

        if(!empty($data["group_id"])){
            $where.=" and o.groupID =".$data["group_id"];
            $this->assign("groupID",$data["group_id"]);
        }else{
            $this->assign("groupID",'');
        }

        $group=$this->group();
        $this->assign('group',$group);

        $list=$this->detail($where,$data);
        $this->assign("list",$list);
        $this -> view -> count = Db::name('u_order')->alias('o')->where($where)->count();
        return $this->fetch();
    }

    public function todaycount(){
        $data=$this->request->get();
        if(isset($data["admin/order/todaycount_html"])){
            unset($data["admin/order/todaycount_html"]);
        }
        $start_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
        $end_time=strtotime(date("Y-m-d").' 21:00:00');
        $where="o.status>0";
        $where.=" and o.create_time>=".$start_time;
        $where.=" and o.create_time<".$end_time;

        if(!empty($data["keyword"])){
            $where.=" and o.objTitle like '%".$data["keyword"]."%'";
            $this->assign("keyword",$data["keyword"]);
        }else{
            $this->assign("keyword",'');
        }

        if(!empty($data["group_id"])){
            $where.=" and o.groupID =".$data["group_id"];
            $this->assign("groupID",$data["group_id"]);
        }else{
            $this->assign("groupID",'');
        }

        if(!empty($data["supplier_id"])){
            $where.=" and o.supplierID =".$data["supplier_id"];
            $this->assign("supplierID",$data["supplier_id"]);
        }else{
            $this->assign("supplierID",'');
        }

        $group=$this->group();
        $this->assign('group',$group);

        $supplier=$this->supplier();
        $this->assign('supplier',$supplier);

        $data=$this->count($where);
        $this->assign("data",$data);
        return $this->fetch();
    }

    public function yesterday(){
        $data=$this->request->get();
        if(isset($data["admin/order/yesterday_html"])){
            unset($data["admin/order/yesterday_html"]);
        }
        $start_time=strtotime(date("Y-m-d",strtotime("-2 day")).' 21:00:00');
        $end_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
        $where="o.is_delete=0 and o.status>0";
        $where.=" and o.create_time>=".$start_time;
        $where.=" and o.create_time<".$end_time;
        if(!empty($data["keyword"])){
            $where.=" and o.orderSN like '%".$data["keyword"]."%'";
            $this->assign("keyword",$data["keyword"]);
        }else{
            $this->assign("keyword",'');
        }

        if(!empty($data["group_id"])){
            $where.=" and o.groupID =".$data["group_id"];
            $this->assign("groupID",$data["group_id"]);
        }else{
            $this->assign("groupID",'');
        }

        $group=$this->group();
        $this->assign('group',$group);

        $list=$this->detail($where,$data);
        $this->assign("list",$list);
        $this -> view -> count = Db::name('u_order')->alias('o')->where($where)->count();
        return $this->fetch();
    }
    //昨日销售统计
    public function yesterdaycount(){
        $data=$this->request->get();
        if(isset($data["admin/order/yesterdaycount_html"])){
            unset($data["admin/order/yesterdaycount_html"]);
        }
        $start_time=strtotime(date("Y-m-d",strtotime("-2 day")).' 21:00:00');
        $end_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
        $where="o.status>0";
        $where.=" and o.create_time>=".$start_time;
        $where.=" and o.create_time<".$end_time;

        if(!empty($data["keyword"])){
            $where.=" and o.objTitle like '%".$data["keyword"]."%'";
            $this->assign("keyword",$data["keyword"]);
        }else{
            $this->assign("keyword",'');
        }

        if(!empty($data["group_id"])){
            $where.=" and o.groupID =".$data["group_id"];
            $this->assign("groupID",$data["group_id"]);
        }else{
            $this->assign("groupID",'');
        }

        if(!empty($data["supplier_id"])){
            $where.=" and o.supplierID =".$data["supplier_id"];
            $this->assign("supplierID",$data["supplier_id"]);
        }else{
            $this->assign("supplierID",'');
        }

        $group=$this->group();
        $this->assign('group',$group);

        $supplier=$this->supplier();
        $this->assign('supplier',$supplier);

        $data=$this->count($where);
        $this->assign("data",$data);
        return $this->fetch();
    }
    //历史订单列表
    public function history(){
        $data=$this->request->get();
        if(isset($data["admin/order/history_html"])){
            unset($data["admin/order/history_html"]);
        }
        if(!empty($data["start_date"])){
            $start_time=strtotime($data["start_date"].' 21:00:00')-86400;
            $this->assign("start_date",$data["start_date"]);
        }else{
            $start_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
            $this->assign("start_date",date("Y-m-d"));
        }
        if(!empty($data["end_date"])){
            $end_time=strtotime($data["end_date"].' 21:00:00');
            $this->assign("end_date",$data["end_date"]);
        }else{
            $end_time=strtotime(date("Y-m-d").' 21:00:00');
            $this->assign("end_date",date("Y-m-d"));
        }
        $where="o.is_delete=0 and o.status>0";
        $where.=" and o.create_time>=".$start_time;
        $where.=" and o.create_time<".$end_time;
        if(!empty($data["keyword"])){
            $where.=" and o.orderSN like '%".$data["keyword"]."%'";
            $this->assign("keyword",$data["keyword"]);
        }else{
            $this->assign("keyword",'');
        }

        if(!empty($data["group_id"])){
            $where.=" and o.groupID =".$data["group_id"];
            $this->assign("groupID",$data["group_id"]);
        }else{
            $this->assign("groupID",'');
        }

        $group=$this->group();
        $this->assign('group',$group);

        $list=$this->detail($where,$data);
        $this->assign("list",$list);
        $this -> view -> count = Db::name('u_order')->alias('o')->where($where)->count();
        return $this->fetch();
    }

    //昨日销售统计
    public function historycount(){
        $data=$this->request->get();
        if(isset($data["admin/order/historycount_html"])){
            unset($data["admin/order/historycount_html"]);
        }
        if(!empty($data["start_date"])){
            $start_time=strtotime($data["start_date"].' 21:00:00')-86400;
            $this->assign("start_date",$data["start_date"]);
        }else{
            $start_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
            $this->assign("start_date",date("Y-m-d"));
        }
        if(!empty($data["end_date"])){
            $end_time=strtotime($data["end_date"].' 21:00:00');
            $this->assign("end_date",$data["end_date"]);
        }else{
            $end_time=strtotime(date("Y-m-d").' 21:00:00');
            $this->assign("end_date",date("Y-m-d"));
        }
        $where="o.status>0";
        $where.=" and o.create_time>=".$start_time;
        $where.=" and o.create_time<".$end_time;

        if(!empty($data["keyword"])){
            $where.=" and o.objTitle like '%".$data["keyword"]."%'";
            $this->assign("keyword",$data["keyword"]);
        }else{
            $this->assign("keyword",'');
        }

        if(!empty($data["group_id"])){
            $where.=" and o.groupID =".$data["group_id"];
            $this->assign("groupID",$data["group_id"]);
        }else{
            $this->assign("groupID",'');
        }

        if(!empty($data["supplier_id"])){
            $where.=" and o.supplierID =".$data["supplier_id"];
            $this->assign("supplierID",$data["supplier_id"]);
        }else{
            $this->assign("supplierID",'');
        }

        $group=$this->group();
        $this->assign('group',$group);

        $supplier=$this->supplier();
        $this->assign('supplier',$supplier);

        $data=$this->count($where);
        $this->assign("data",$data);
        return $this->fetch();
    }

    //公共方法      获取订单数据
    function detail($where,$data){
        $list=Db::name('u_order')->alias('o')->join('s_group g','o.groupID=g.id','left')
            ->field('o.*,g.linkman as glinkman')->where($where)->order('o.id desc')
            ->paginate(10,false,['query'=>$data])->each(function($item, $key){
                $item['detail'] = Db::name('u_orderdetail')->where('orderID',$item['id'])->select();
                return $item;
            });
        return $list;
    }
    //公共方法      获取销售统计
    function count($where){
        $list=Db::name('u_orderdetail')->alias('o')->join('s_supplier s','o.supplierID=s.id')
            ->field('o.*,s.name')->where($where)->order('o.id desc')->select();

        $countlist=[];
        $sumTotal=0;
        $bonusTotal=0;
        //所有供应商总销售，供应商所有商品单价*数量的累计
        $supplierTotal=0;

        foreach ($list as $k=>$v){
            if(array_key_exists($v['objID'],$countlist)){
                $countlist[$v['objID']]['count']+=$v['count'];
                $countlist[$v['objID']]['zongjia']=bcadd(bcmul($v['price'],$v['count'],2),$countlist[$v['objID']]['zongjia'],2);
                $countlist[$v['objID']]['zongbonus']=bcadd($v['bonus'],$countlist[$v['objID']]['zongbonus'],2);
                $countlist[$v['objID']]['supplierTotal']=bcadd(bcmul($v['supplierPrice'],$v['count'],2),$countlist[$v['objID']]['supplierPrice'],2);
            }else{
                $countlist[$v['objID']]=$v;
                $countlist[$v['objID']]['zongjia']=bcmul($v['price'],$v['count'],2);
                $countlist[$v['objID']]['zongbonus']=$v['bonus'];
                $countlist[$v['objID']]['supplierTotal']=bcmul($v['supplierPrice'],$v['count'],2);
            }
            $sumTotal=bcadd(bcmul($v['price'],$v['count'],2),$sumTotal,2);
            $bonusTotal=bcadd($v['bonus'],$bonusTotal,2);

            $supplierTotal=bcadd(bcmul($v['supplierPrice'],$v['count'],2),$supplierTotal,2);
        }

        return ['data'=>$countlist,'count'=>count($countlist),'sumTotal'=>$sumTotal,'bonusTotal'=>$bonusTotal,'supplierTotal'=>$supplierTotal];
    }
    //公共方法      获取所有小区数据
    function group(){
        $group=Db::name('s_group')->field('id,linkman')->select();
        return $group;
    }

    //公共方法      获取所有供应商数据
    function supplier(){
        $supplier=Db::name('s_supplier')->field('id,name')->select();
        return $supplier;
    }

    //数据导出
    public function dataExport(){
        $data=$this->request->get();
        $method=$data['method'];
        $type=$data['type'];              //类型  1普通类型 2统计类型
        if($type==1){
            $where="o.is_delete=0 and o.status>0";
            $fileName='订单列表';
        }else{
            $fileName='销售统计';
            $where="o.status>0";
        }

        switch ($method)
        {
            case 'index':
                $where="o.is_delete=0";
                if(!empty($data["start_time"])){
                    $start_time=strtotime($data["start_time"]);
                    $where.=" and o.create_time>=".$start_time;
                }
                if(!empty($data["end_time"])){
                    $end_time=strtotime($data["end_time"]);
                    $where.=" and o.create_time<".$end_time;
                }
                $fileName='订单列表导出';
                break;
            case 'today':
                $start_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
                $end_time=strtotime(date("Y-m-d").' 21:00:00');
                $where.=" and o.create_time>=".$start_time;
                $where.=" and o.create_time<".$end_time;
                $fileName='今日'.$fileName.'导出';
                break;
            case 'yesterday':
                $start_time=strtotime(date("Y-m-d",strtotime("-2 day")).' 21:00:00');
                $end_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
                $where.=" and o.create_time>=".$start_time;
                $where.=" and o.create_time<".$end_time;
                $fileName='昨日'.$fileName.'导出';
                break;
            case 'history':
                if(!empty($data["start_date"])){
                    $start_time=strtotime($data["start_date"].' 21:00:00')-86400;
                }else{
                    $start_time=strtotime(date("Y-m-d",strtotime("-1 day")).' 21:00:00');
                }
                if(!empty($data["end_date"])){
                    $end_time=strtotime($data["end_date"].' 21:00:00');
                }else{
                    $end_time=strtotime(date("Y-m-d").' 21:00:00');
                }
                $where.=" and o.create_time>=".$start_time;
                $where.=" and o.create_time<".$end_time;
                $fileName='历史'.$fileName.'导出';
                break;
        }

        if(!empty($data["keyword"])){
            if($type==1){
                $where.=" and o.orderSN like '%".$data["keyword"]."%'";
            }else{
                $where.=" and o.objTitle like '%".$data["keyword"]."%'";
            }
        }

        if(!empty($data["group_id"])) {
            $where .= " and o.groupID =" . $data["group_id"];
        }

        //销售统计导出会用到
        if(!empty($data["supplier_id"])) {
            $where .= " and o.supplierID =" . $data["supplier_id"];
        }

        if($type==1){
            //导出订单列表
            $list=Db::name('u_order')->alias('o')->join('s_group g','o.groupID=g.id')
                ->field('o.*,g.name')->where($where)->order('o.id desc')->select();
            $header[1] = 'ID';
            $header[2] = '订单号';
            $header[3] = '订单商品';
            $header[4] = '联系人';
            $header[5] = '联系手机';
            $header[6] = '订单备注';
            $header[7] = '下单时间';
            $header[8] = '总价';
            $header[9] = '佣金总金额';
            $header[10] = '状态';
            $header[10] = '所属小区';

            $status=[0=>'待付款',1=>'待发货',2=>'已发货',3=>'已完成',4=>'已退货'];

            $body = [];
            foreach ($list as $key=>$vo) {
                $body[$key][1] = $vo['id'];
                $body[$key][2] = $vo['orderSN'];

                $detail = Db::name('u_orderdetail')->where('orderID',$vo['id'])->select();
                $goods='';
                foreach ($detail as $k=>$v) {
                    $goods.=$v['objTitle'].' x'.$v['count']."\n";
                }
                $body[$key][3] = $goods;

                $body[$key][4] = $vo['linkman'];
                $body[$key][5] = $vo['mobile'];
                $body[$key][6] = $vo['remark'];
                $body[$key][7] = date('Y-m-d H:i:s',$vo['create_time']);
                $body[$key][8] = $vo['sumTotal'].'元';
                $body[$key][9] = $vo['bonusTotal'].'元';
                $body[$key][10] = $status[$vo['status']];
                $body[$key][11] = $vo['name'];
            }
        }else{
            //导出销售统计
            $data=$this->count($where);
            $body = [];

            $header[1] = '总价：'.$data['sumTotal'];
            $header[2] = '总佣金：'.$data['bonusTotal'];
            $header[3] = '供应商总销售：'.$data['supplierTotal'];
            $header[4] = '';
            $header[5] = '';
            $header[6] = '';
            $header[7] = '';
            $header[8] = '';
            $header[9] = '';
            $header[10] = '';

            $body[0][1] = '商品ID';
            $body[0][2] = '商品名称';
            $body[0][3] = '销量';
            $body[0][4] = '单价(仅供参考，如果改动过则不准)';
            $body[0][5] = '总价';
            $body[0][6] = '团长佣金比例(仅供参考，如果改动过则不准)';
            $body[0][7] = '总提成';
            $body[0][8] = '所属供应商';
            $body[0][9] = '供应商单价(仅供参考，如果改动过则不准)';
            $body[0][10] = '供应商总销售';

            foreach ($data['data'] as $key=>$vo) {
                $body[$key][1] = $key;
                $body[$key][2] = $vo['objTitle'];
                $body[$key][3] = $vo['count'];
                $body[$key][4] = $vo['price'];
                $body[$key][5] = $vo['zongjia'];
                $body[$key][6] = $vo['ratio'];
                $body[$key][7] = $vo['zongbonus'];
                $body[$key][8] = $vo['name'];
                $body[$key][9] = $vo['supplierPrice'];
                $body[$key][10] = $vo['supplierTotal'];
            }
        }

        getExcel($fileName,$header,$body);

    }

}
