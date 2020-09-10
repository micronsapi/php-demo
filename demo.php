<?php
/**
 * 微信登陆过程DEMO，只做演示使用
 * 本DEMO只能在装有swoole拓展的linux下运行，如需要CGI模式，请自行修改
 * 版本说明： swoole 4.x , php 7.3 以上版本
 * swoole需要协程短名称 可修改 php.ini 设置 swoole.use_shortname=On/Off 来开启 / 关闭短名（默认为开启）。
 *
 * @version  1.0
 * @date 2020-9-10
 * @author Ethan
 */

require_once "./lib/http_request.php";
require_once "./lib/tcp_client.php";
require_once "./lib/loggle.php";

$host="8.129.188.188"; //tcp服务器地址
$port=8010; //tcp端口
$httpDomain="http://8.129.188.188:8000"; //http请求地址
$token =""; //您购买的CODE

/**
 * 运行命令：  php demo.php
 * 步骤说明：
 *  1、 连接微米TCP
 *   1.1 、 连接微米tcp服务器
 *   1.2 、 发送您购买的code到服务器进行握手
 *   1.3 、 握手成功后需要每10秒发送心跳包
 *   1.4 、 循环堵塞获取微米服务器发来的数据
 *
 *  2、 发送HTTP请求(需要等TCP握手成功才能发送)
 *    2.1 、 判断要登陆的微信是否处于登陆状态（如果微信处于登陆状态则跳过以下2步）
 *    2.2 、 初始化登陆实例
 *    2.3 、 获取登陆二维码（也可以进行62登陆）
 *
 * 补充说明：
 *  TCP支持断线重连机制，TCP断开后在30秒以内重连无需再次登陆
 *
 */
go(function ()use($host,$port,$httpDomain,$token){
    $loggle = new Loggle();
    //1 、连接TCP
    $chan = new Swoole\Coroutine\Channel(1);
    try{
        go(function () use($chan, $host,$port,$token){
            $tcp =new TcpClient($host,$port,$token);
            $tcp->NewTcpClient($chan);
        });
    }catch (\Swoole\ExitException $e){
        $loggle->set($e->getFlags(),"WARNING");
        return;
    }
    $chan->pop(); //tcp连接握手完成
    //2、HTTP请求
    $http =new HttpRequest($httpDomain,$token);
    //2.1 查看是否处于登陆状态
    $loggle->set("判断微信登陆状态","DEBUG");
    $isLogin = $http->isLogin();
    if($isLogin["result"] == false){
        $loggle->set($http->isLogin()["msg"],"ERROR");
        return;
    }
    if( $isLogin["msg"] == true ){
        //登陆状态无需再次登陆
        $loggle->set("您已经处于登陆状态","DEBUG");
    }else{
        //2.2 初始化登陆
        $loggle->set("初始化微信登陆","DEBUG");
        $isInit = $http->init();
        $loggle->set($isInit["msg"],"DEBUG");

        //2.3 拉取二维码
        $loggle->set("获取登陆二维码","DEBUG");
        $qr =  $http->getQr();
        if($qr["result"]){
            $loggle->set("登陆二维码（请使用浏览器打开该连接）：".$qr["msg"],"DEBUG");
        }else{
            $loggle->set("获取登陆二维码失败","ERROR");
        }
    }
});






