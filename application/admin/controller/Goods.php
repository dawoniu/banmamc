<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use app\admin\model\Goods as m_goods;
use app\admin\model\Trade as m_trade;
use think\Session;

class Goods extends Base
{
    public function index()
    {
//        $alllanmu=m_lanmu::all(["is_delete"=>0,"status"=>1,"type_id"=>3]);
//        $lanmu=getlanmu($alllanmu,0);
//        $this->assign("lanmu",$lanmu);

        $goods = new m_goods();
        $data=$this->request->get();
        if(isset($data["admin/goods/index_html"])){
            unset($data["admin/goods/index_html"]);
        }
        $where="is_delete=0";
//        if(!empty($data["lanmu_id"])){
//            $lanmu_id=getsublanmu($alllanmu,$data["lanmu_id"]);
//            $lanmu_id[]=$data["lanmu_id"];
//            $where["lanmu_id"]=['in',$lanmu_id];
//            $this->assign("lanmu_id",$data["lanmu_id"]);
//        }
        if(!empty($data["start_time"])){
            $start_time=strtotime($data["start_time"]);
            $where.=" and create_time>".$start_time;
            //$where["update_time"]=['>',$start_time];
            $this->assign("start_time",$data["start_time"]);
        }
        if(!empty($data["end_time"])){
            $end_time=strtotime($data["end_time"]." 23:59:59");
            $where.=" and create_time<".$end_time;
            $this->assign("end_time",$data["end_time"]);
        }
        if(!empty($data["keyword"])){
            $where["title"]=['like','%'.$data["keyword"].'%'];
            $this->assign("keyword",$data["keyword"]);
        }

        $list=$goods->where($where)
            ->order('update_time', 'desc')
            ->paginate(10,false,['query'=>$data]);

        $this->assign("list",$list);
        $this -> view -> count = $goods->where($where)
            ->count();
        return $this->fetch();
    }

    //ajax删除商品
    public function delete(){
        $id=$this->request->post("id");
        m_goods::update(['id' => $id, 'is_delete' => 1,'delete_time'=>time()]);
    }

    public function add(){
        if($this->request->isPost()) {
            $data = $this->request->post();
            $data['lunbo'] = implode(",",$data['lunbo']);
            $data['showsales']=$data['initsales'];
            if(isset($data["start_time"])){
                $data['start_time']=strtotime($data["start_time"].":00:00");
            }
            if(isset($data["end_time"])){
                $data['end_time']=strtotime($data["end_time"].":00:00");
            }

            $status = 1;
            $message = '添加成功';

            $goods = m_goods::create($data);
            if ($goods === null) {
                $status = 0;
                $message = '添加失败~~';
            }
            //return json($data);
            return json(['status'=>$status, 'message'=>$message]);
        }else{

            $this->assign("title","添加商品");
            $this->assign("keywords","添加商品");
            $this->assign("description","添加商品");

            $trade_id=$this->request->param("trade_id");
            $trade=m_trade::get($trade_id);
            $this->assign('trade',$trade);

            return $this->fetch("form");
        }
    }

    public function upload(){
        $file = $this->request->file('file');

        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                //记录上一次的图片，到时候好删除，可能会有问题，当用户一下同一个浏览器开两个添加商品页面，
                //上传图片的时候，第二个页面会覆盖掉上一个
//                if(Session::has("thumb") and Session::get("thumb")!=""){
//                    //删除旧图片
//                    @unlink("./uploads/".Session::get("thumb"));
//                    Session::set("thumb",$info->getSaveName());
//                }else{
//                    Session::set("thumb",$info->getSaveName());
//                }

                return json(["status"=>1,"path"=>"https://banmamc.banmayg.com/uploads/".$info->getSaveName()]);
                //echo $info->getSaveName();

            }else{
                return json(["status"=>0,"message"=>$file->getError()]);
                // 上传失败获取错误信息
                //echo $file->getError();
            }
        }
    }


    public function edit(){
        if($this->request->isPost()){
            $data=$this->request->post();
            $data['lunbo'] = implode(",",$data['lunbo']);
            if(isset($data["start_time"])){
                $data['start_time']=strtotime($data["start_time"].":00:00");
            }
            if(isset($data["end_time"])){
                $data['end_time']=strtotime($data["end_time"].":00:00");
            }
            $res = m_goods::update($data);
            if ($res == true) {
                $status=1;
                $message="更新成功,请刷新查看";
            }else{
                $status=0;
                $message="更新失败,请检查";
            }
            return json(['status'=>$status, 'message'=>$message]);
        }else{
            $this->assign("title","修改商品");
            $this->assign("keywords","修改商品");
            $this->assign("description","修改商品");

            $id=$this->request->param("id");
            $data=m_goods::get($id);

            $trade=m_trade::get($data['tradeID']);

            $this->assign('trade',$trade);

            $lunbo=explode(",",$data['lunbo']);
            $data['lunbo']=$lunbo;
            //dump($data);

            $this->assign("data",$data);
            return $this->fetch("form");
        }
    }

    //ajax改变状态(启用或禁用)
    public function setStatus(){
        $id=$this->request->post("id");
        $goods=m_goods::get($id);

        //获取原始数据要用getData方法。
        if($goods->status==1){
            m_goods::update(['id' => $id, 'status' => 0]);
        }else{
            m_goods::update(['id' => $id, 'status' => 1]);
        }
    }


}
