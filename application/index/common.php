<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/27
 * Time: 9:49
 */
use think\Db;

function getfirstchar($s0){   //获取单个汉字拼音首字母。注意:此处不要纠结。汉字拼音是没有以U和V开头的
    $fchar = ord($s0{0});
    if($fchar >= ord("A") and $fchar <= ord("z") )return strtoupper($s0{0});
    //$s1 = iconv("UTF-8","GBK", $s0);
    //$s2 = iconv("GBK","UTF-8", $s1);
    //if($s2 == $s0){$s = $s1;}else{$s = $s0;}
    $s = $s0;
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if($asc >= -20319 and $asc <= -20284) return "A";
    if($asc >= -20283 and $asc <= -19776) return "B";
    if($asc >= -19775 and $asc <= -19219) return "C";
    if($asc >= -19218 and $asc <= -18711) return "D";
    if($asc >= -18710 and $asc <= -18527) return "E";
    if($asc >= -18526 and $asc <= -18240) return "F";
    if($asc >= -18239 and $asc <= -17923) return "G";
    if($asc >= -17922 and $asc <= -17418) return "H";
    if($asc >= -17922 and $asc <= -17418) return "I";
    if($asc >= -17417 and $asc <= -16475) return "J";
    if($asc >= -16474 and $asc <= -16213) return "K";
    if($asc >= -16212 and $asc <= -15641) return "L";
    if($asc >= -15640 and $asc <= -15166) return "M";
    if($asc >= -15165 and $asc <= -14923) return "N";
    if($asc >= -14922 and $asc <= -14915) return "O";
    if($asc >= -14914 and $asc <= -14631) return "P";
    if($asc >= -14630 and $asc <= -14150) return "Q";
    if($asc >= -14149 and $asc <= -14091) return "R";
    if($asc >= -14090 and $asc <= -13319) return "S";
    if($asc >= -13318 and $asc <= -12839) return "T";
    if($asc >= -12838 and $asc <= -12557) return "W";
    if($asc >= -12556 and $asc <= -11848) return "X";
    if($asc >= -11847 and $asc <= -11056) return "Y";
    if($asc >= -11055 and $asc <= -10247) return "Z";
    return NULL;
    //return $s0;
}
function pinyin_long($zh){  //获取整条字符串汉字拼音首字母
    $ret = "";
    $s1 = iconv("UTF-8","GBK", $zh);
    $s2 = iconv("GBK","UTF-8", $s1);
    if($s2 == $zh){$zh = $s1;}
    for($i = 0; $i < strlen($zh); $i++){
        $s1 = substr($zh,$i,1);
        $p = ord($s1);
        if($p > 160){
            $s2 = substr($zh,$i++,2);
            $ret .= getfirstchar($s2);
        }else{
            $ret .= $s1;
        }
    }
    return strtolower($ret);
}

//获取导航栏
//参数一: 所有栏目数组
//参数二: lv 显示几级分类，目前可以最多设置二级。一级：$lv==1  二级：$lv==2
function getNav($alllanmu,$lv=1){
    $list=$alllanmu;
    $data=[];
    $i=0;
    //查找显示在导航的一级分类

    foreach ($list as $k=>$v){
        if($v['level']==1&&$v['is_nav']==1){
            $data[$i]=$v;
            $i++;
            unset($list[$k]);
        }
    }

    if($lv==2){
        //查找一级分类下的二级分类
        $i=0;
        foreach ($data as $k1=>$v1){
            foreach ($list as $k2=>$v2){
                if($v1['id']==$v2['pid']){
                    $data[$k1]['child'][$i]=$v2;
                    $i++;
                }
            }
        }
    }
    return $data;
}

//二级栏目展示
//参数一: 当前栏目id
//返回: 当前栏目信息和其子分类数组的总和
function showMenu($pid){
    $p_lanmu=Db::name('lanmu')->where('id',$pid)->
    where('status',1)->where('is_delete',0)->select();
    $c_lanmu=Db::name('lanmu')->where('pid',$pid)->
    where('status',1)->where('is_delete',0)->order('sort asc')->select();
    $all_lanmu= array_merge_recursive($p_lanmu,$c_lanmu);
    return $all_lanmu;
}

//获取该栏目下的文章、产品或文件，包括其子栏目
//参数一: 所有栏目数组
//参数二: 栏目id。
//参数三: 栏目类型
//参数四: 是否需要分页（用于列表页面调用）
//参数五: 查询的数量
//参数六: 排序的字段和方式
function getList($alllanmu,$id=1,$type,$paginate=false,$num=8,$order='update_time desc'){
    //获取所有子分类id的数组
    $lanmu_id=getsublanmu($alllanmu,$id);
    $lanmu_id[]=$id;
    $where["lanmu_id"]=['in',$lanmu_id];
    $where["is_delete"]=0;
    if($type==2){
        $table='article';
    }
    if($type==3){
        $table='product';
        $where["status"]=1;
    }
    if($type==6){
        $table='download';
    }

    $data=Db::name($table)->where($where)->order($order);
    if($paginate){
        $data=$data->paginate($num);
    }else{
        $data=$data->limit($num)->select();
    }

    return $data;
}

//翻页代码
//参数一: 文章列表对象
//参数二: 当前栏目信息数组
function render($list,$lanmu,$page=''){
    //因为要生成静态文件，所以翻页的链接则采用自定义静态的。
    if($page==''||$page<1){
        $page=1;
    }
    if($page>$list->lastPage()){
        $page=$list->lastPage();
    }

    $render='<div class="met_pager">';
    if($page==1){
        $render.='<span class="PreSpan">上一页</span>';
    }else{
        //echo $page;
        if($page==2){
            $render.='<a href="'.$lanmu['link'].'" class="PreSpan">上一页</a>';
        }else{
            $render.='<a href="'.str_replace(".html","-".($page-1).".html",$lanmu['link']).'" class="PreSpan">上一页</a>';
        }
    }
    for ($i=1;$i<=$list->lastPage();$i++){
        if($list->currentPage()==$i){
            if($i==1){
                $render.='<a href="'.$lanmu['link'].'" class="Ahover">'.$i.'</a>';
            }else{
                $render.='<a href="'.str_replace(".html","-".$i.".html",$lanmu['link']).'" class="Ahover">'.$i.'</a>';
            }
        }else{
            if($i==1){
                $render.='<a href="'.$lanmu['link'].'">'.$i.'</a>';
            }else{
                $render.='<a href="'.str_replace(".html","-".$i.".html",$lanmu['link']).'">'.$i.'</a>';
            }
        }
    }
    if($page==$list->lastPage()){
        $render.='<span class="NextSpan">下一页</span>';
    }else{
        $render.='<a href="'.str_replace(".html","-".($page+1).".html",$lanmu['link']).'" class="NextA">下一页</a>';
    }
    $render.='</div>';
    return $render;
}


function getListByLanmuID($id=1,$type,$paginate=false,$num=8,$order='update_time desc'){
    //获取所有子分类id的数组
    $where["lanmu_id"]=$id;
    $where["is_delete"]=0;
    if($type==2){
        $table='article';
    }
    if($type==3){
        $table='product';
        $where["status"]=1;
    }
    if($type==6){
        $table='download';
    }

    $data=Db::name($table)->where($where)->order($order);
    if($paginate){
        $data=$data->paginate($num);
    }else{
        $data=$data->limit($num)->select();
    }

    return $data;
}

?>