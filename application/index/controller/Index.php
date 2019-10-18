<?php
namespace app\index\controller;

use think\Controller;
use think\Cache;
use think\Cache\driver\Redis;


class Index extends Controller
{
    public function index(){
        //在配置文件中配置好了混合缓存类型。
        dump(Cache::store('redis')->set('sfdsf','yingying',1000000));
        echo Cache::store('redis')->get('sfdsf');
        echo Cache::store('redis')->get('name');
        return '斑马美厨';
    }
    public function redis(){
        $redis=new Redis();

        $num = 10;
        $len = $redis->lLen('goods');
        if($len<10){
            $redis->lPush('goods',rand(1000,9999));

//            $data['pid'] = 1;
//            $res = db('goods_user')->insert($data);
//            if($res ==1){
//                exit('抢购成功');
//            }
            exit('抢购成功');
        }else{
            //echo '已抢光';
            exit('已抢光');
        }


        //$redis->lPush('goods','num2');
//        $redis=new Redis();
//        $num = 10;
//        $len = $redis->lLen('redis');
//        if($len<10){
//            $id = $this->push();
//            $data['pid'] = 1;
//            $data['name'] = $id;
//            $res = db('goods_user')->insert($data);
//            if($res ==1){
//                exit('抢购成功');
//            }
//        }else{
//            //echo '已抢光';
//            exit('已抢光');
//        }
    }
}
