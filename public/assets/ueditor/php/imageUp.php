<?php
    header("Content-Type:text/html;charset=utf-8");
    error_reporting( E_ERROR | E_WARNING );
    date_default_timezone_set("Asia/chongqing");
    include "Uploader.class.php";

    $m = isset($_GET['m']) ? $_GET['m'] : 'article';

    //上传文件目录
    $Path = dirname(dirname(dirname(dirname(__DIR__)))) . "/data/siteimg/{$m}/";

    //上传配置
    $config = array(
        "url" => "/data/siteimg/{$m}/",
        "savePath" => $Path ,             //存储文件夹
        "maxSize" => 1024000 ,                   //允许的文件最大尺寸，单位KB
        "allowFiles" => array( ".gif" , ".png" , ".jpg" , ".jpeg" , ".bmp" )  //允许的文件格式
    );

    $up = new Uploader( "upfile" , $config );
    $type = $_REQUEST['type'];
    $callback=$_GET['callback'];

    $info = $up->getFileInfo();

    /**
     * 返回数据
     */
    if($callback) {
        return '<script>'.$callback.'('.json_encode($info).')</script>';
    } else {
        return json_encode($info);
    }
