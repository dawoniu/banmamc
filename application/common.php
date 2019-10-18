<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
//参数1：所有栏目数组，参数2：栏目id
//功能：根据栏目id,获取其下无限级子分类id
//返回：栏目id数组
function getsublanmu($lanmu,$pid=0){

    $data=[];
    if(gettype($lanmu)=='array'){
        foreach ($lanmu as $k=>$v){
            if($v['pid']==$pid){
                $data[]=$v['id'];
                unset($lanmu[$k]);
                $sublanmu=getsublanmu($lanmu,$v['id']);
                if(!empty($sublanmu)){
                    $data=array_merge($data,$sublanmu);
                }
            }
        }
    }else{
        foreach ($lanmu as $k=>$v){
            if($v->pid==$pid){
                $data[]=$v->id;
                unset($lanmu[$k]);
                $sublanmu=getsublanmu($lanmu,$v->id);
                if(!empty($sublanmu)){
                    $data=array_merge($data,$sublanmu);
                }
            }
        }
    }
    return $data;
}

////参数1：栏目数组，参数2：父栏目id
////功能：根据栏目id,获取其下无限级子分类id
////返回：栏目id数组
//function getsublanmu2($lanmu,$pid=0){
//    $data=[];
//    foreach ($lanmu as $k=>$v){
//        if($v['pid']==$pid){
//            $data[]=$v['id'];
//            unset($lanmu[$k]);
//            $sublanmu=getsublanmu2($lanmu,$v['id']);
//            if(!empty($sublanmu)){
//                $data=array_merge($data,$sublanmu);
//            }
//        }
//    }
//    return $data;
//}

function getFilesize($num){
    $p = 0;
    $format='bytes';
    if($num>0 && $num<1024){
        $p = 0;
        return number_format($num).' '.$format;
    }
    if($num>=1024 && $num<pow(1024, 2)){
        $p = 1;
        $format = 'KB';
    }
    if ($num>=pow(1024, 2) && $num<pow(1024, 3)) {
        $p = 2;     $format = 'MB';
    }
    if ($num>=pow(1024, 3) && $num<pow(1024, 4)) {
        $p = 3;     $format = 'GB';
    }
    if ($num>=pow(1024, 4) && $num<pow(1024, 5)) {
        $p = 3;     $format = 'TB';
    }
    $num /= pow(1024, $p);
    return number_format($num, 3).' '.$format;
}
