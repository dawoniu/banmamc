<?php
//参数1：所有栏目对象，参数2：上级id
//功能：把所有分类重新排列，按照一级下面紧跟二级这样。
function getlanmu($lanmu,$pid=0){
    $data=[];
    foreach ($lanmu as $k=>$v){
        if($v->pid==$pid){
            $array=[];
            $array["id"]=$v->id;
            $str="";
            for ($i=1;$i<$v->level;$i++){
                $str.="&nbsp;&nbsp;├ ";
            }

            $array["name"]=$str.$v->name;
            $array["filename"]=$v->filename;
            $array["sort"]=$v->sort;
            $array["type"]=$v->type;
            $array["is_nav"]=$v->is_nav;
            $array["status"]=$v->status;

            $data[]=$array;
            unset($lanmu[$k]);
            $sublanmu=getlanmu($lanmu,$v->id);
            if(!empty($sublanmu)){
                $data=array_merge($data,$sublanmu);
            }
        }
    }
    return $data;
}


/*导出excel*/
function getExcel($fileName,$headArr,$data){

    //导入PHPExcel类库，因为PHPExcel没有用命名空间，只能inport导入

    //import("Org.Util.PHPExcel");

    //import("@.phpoffice.phpexcel.Classes.PHPExcel.Writer.Excel5");
    //import("Org.Util.PHPExcel.Writer.Excel5");

    //import("Org.Util.PHPExcel.IOFactory.php");

    vendor("phpoffice.phpexcel.Classes.PHPExcel");
    vendor("phpoffice.phpexcel.Classes.PHPExcel.Writer.Excel5");
    vendor("phpoffice.phpexcel.Classes.PHPExcel.IOFactory");

    $date = date("Y_m_d",time());

    $fileName .= "_{$date}.xls";

    //创建PHPExcel对象，注意，不能少了\

    $objPHPExcel = new \PHPExcel();

    $objProps = $objPHPExcel->getProperties();

    //设置表头

    $key = ord("A");
    $key2 = ord("@");
    //print_r($headArr);exit;

    foreach($headArr as $v){
        if($key>ord("Z")){
            $key2 += 1;
            $key = ord("A");
            $colum = chr($key2).chr($key);//超过26个字母时才会启用
        }else{
            if($key2>=ord("A")){
                $colum = chr($key2).chr($key);//超过26个字母时才会启用
            }else{
                $colum = chr($key);
            }
        }

        $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v);

        //$objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v);

        $key += 1;

    }
    $column = 2;

    $objActSheet = $objPHPExcel->getActiveSheet();
    //print_r($data);exit;

    foreach($data as $key => $rows){ //行写入

        $span = ord("A");
        $span2 = ord("@");

        foreach($rows as $keyName=>$value){// 列写入
            if($span>ord("Z")){
                $span2 += 1;
                $span = ord("A");
                $j = chr($span2).chr($span);//超过26个字母时才会启用  dingling 20150626
            }else{
                if($span2>=ord("A")){
                    $j = chr($span2).chr($span);
                }else{
                    $j = chr($span);
                }
            }

            //$j = chr($span);

            $objActSheet->setCellValue($j.$column, $value);

            $span++;

        }
        $column++;
    }
    $fileName = iconv("utf-8", "gb2312", $fileName);
    //重命名表

    //$objPHPExcel->getActiveSheet()->setTitle('test');

    //设置活动单指数到第一个表,所以Excel打开这是第一个表

    $objPHPExcel->setActiveSheetIndex(0);

    ob_end_clean();//清除缓冲区,避免乱码

    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment;filename=\"$fileName\"");
    header('Cache-Control: max-age=0');

    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

    //$PHPWriter = \PHPExcel_IOFactory::createWriter( $objPHPExcel,"Excel2007");
    //dump($objWriter);die;
    $objWriter->save('php://output'); //文件通过浏览器下载

    exit;

}

/**
 * 导入excel
 * @param string $filename 上传文件地址
 * @param string $extension 上传文件后缀名
 * @param bool $value 默认是false为获取值，否则为更新
 * @return array
 */
function insert_data($filename,$extension){
    require_once dirname(realpath(APP_PATH)) . '/vendor/phpoffice/phpexcel/Classes/PHPExcel/IOFactory.php';
    //区分上传文件格式
    if($extension == 'xlsx') {
        $objReader =PHPExcel_IOFactory::createReader('Excel2007');
        $objPHPExcel = $objReader->load($filename, $encode = 'utf-8');
    }else if($extension == 'xls'){
        $objReader =PHPExcel_IOFactory::createReader('Excel5');
        $objPHPExcel = $objReader->load($filename, $encode = 'utf-8');
    }

    $excel_array = $objPHPExcel->getsheet(0)->toArray();   //转换为数组格式
    array_shift($excel_array);  //删除第一个数组(标题);

    return $excel_array;
}
?>