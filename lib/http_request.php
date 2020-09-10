<?php

/**
 * http请求示例
 * Class HttpRequest
 */
class HttpRequest
{
    /**
     * http请求地址
     * @var string
     */
    private $domain;

    /**
     * 购买的code
     * @var string
     */
    private $token;

    public function __construct(string $domain,string $token)
    {
        $this->token=$token;
        $this->domain=$domain;
    }

    /**
     * curl POST请求
     * @param $url
     * @param array $data
     * @param array header
     * @return bool|string
     */
    private function curlPost(string $uri, array $data = [],array $header=[])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->domain.$uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //json
        $headers=[
            'Content-Type: application/json',
            'Content-Length: ' . strlen(\json_encode($data))
        ];
        foreach ($header as $item){
            $headers[]=$item;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * CURL get请求
     * @param string $url
     * @param array $header
     * @return bool|string
     */
    private function curlGet(string $uri, array $header=[]) {
        if (empty($uri)) {
            return false;
        }
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$this->domain.$uri);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        $headers=[
            'Content-Type: application/json',
        ];
        foreach ($header as $item){
            $headers[]=$item;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }

    /**
     * 查询当前token绑定的微信是否已登录
     * 【GET】 /api/v1/login/islogin
     */
    public function isLogin(){
       $result =  $this->curlGet("/api/v1/login/islogin",["Token:{$this->token}"]);
       return $this->_responseHandle($result);
    }

    /**
     * 登陆初始化
     * [GET] /api/v1/login/init
     */
    public function init(){
        $result =  $this->curlGet("/api/v1/login/init",["Token:{$this->token}"]);
        return $this->_responseHandle($result);
    }


    /**
     * 获取登陆二维码
     * [POST] api/v1/login/loginqrcode
     */
    public function getQr(){
        $result =  $this->curlGet("/api/v1/login/loginqrcode",["Token:{$this->token}"]);
        return $this->_responseHandle($result);
    }

    private function _responseHandle(string $result):array {
        $infoArr= \json_decode($result,true);
        if(isset($infoArr['code']) and $infoArr['code'] == 0){
            return ['result'=>true,"msg"=>$infoArr["data"]];
        }else{
            return ['result'=>false,"msg"=>$infoArr["msg"]];
        }
    }


}