<?php
/**
 * TCP服务器连接
 * @date  2020-9-9
 * @author Ethan
 */

define("SHAKE_HAND_CODE",1); //与服务器握手
define("RECV_SHAKE_HAND_CODE",101); //服务器握手响应
define("RECV_SYSTEM_PULL_CODE",102); //系统推送消息
define("RECV_WECHAT_PULL_CODE",103); //微信推送消息
define("HEARTBEAT_CODE",1392); //心跳包 10s

require_once "loggle.php";

class TcpClient{

    /**
     * TCP服务地址
     */
    public $host ;

    /**
     * TCP端口号
     */
    public $port;

    /**
     * token
     */
    private $token;

    /**
     * 接收字节流
     */
    private $recvData="";

    /**
     * 定时任务ID
     * @var intger
     */
    private $timerId;

    /**
     * tcp客户端
     * @var
     */
    private $client;

    /**
     * 日志
     * @var string
     */
    private $loggle;

    /**
     * TcpClient constructor.
     * @param string $host TCP服务器地址
     * @param string $port TCP端口
     * @param string $token 购买的token
     */
    public function __construct(string $host,string $port, string $token)
    {
        $this->recvData="";
        $this->host=$host;
        $this->port=$port;
        $this->token = $token;
        $this->loggle=new Loggle();
    }

    /**
     * TCP封包
     * @param string $data
     * @param int $messageId
     * @return string
     *
     *   [4]byte -- length   4个字节
     *   [4]byte -- messageID  4个字节
     *   []byte -- body  包长度 =
     */
    private function _setTcpPack(string $data,int $messageId):string{
        return pack("N",strlen($data)+8)
            .pack("N",$messageId)
            .$data;
    }

    /**
     * TCP解包
     *
     *  [4]byte -- length
     *  [4]byte -- messageID
     *  []byte -- body
     *
     *  @return mixed
     */
    private function _getTcpPack(){
        if(empty($this->recvData)) return false;
        //截取包长度
        $packLength = unpack("N*", substr($this->recvData, 0, 4))[1];
        if(strlen($this->recvData) < $packLength){
            return false;
        }else{
            //body长度 = 包长-8字节
            $bodyLength =$packLength-8;
            //消息ID
            $msgId= unpack("N*", substr($this->recvData, 4, 4))[1];
            $body = substr($this->recvData,8,$bodyLength);
            $this->recvData = substr($this->recvData,$packLength);
            return [
                "msg_id"=>$msgId,
                "body"=>$body
            ];
        }
    }

    /**
     * 定时发送心跳包
     */
    private function _addHeartBeat(){
        $this->loggle->set("添加心跳包");
        \Swoole\Timer::tick(10000, function ($timer_id) {
            $this->timerId=$timer_id;
            $this->client->send($this->_setTcpPack("",HEARTBEAT_CODE));
        });
    }


    /**
     * Close事件
     */
    private function _closeHandle(){
        $this->loggle->set("TCP连接断开");
        //断开tcp客户端
        $this->client->close();
        if(!empty($this->timerId)){
            //清除定时任务
            \Swoole\Timer::clear((int)$this->timerId);
        }
        return;
    }

    /**
     * TCP连接示例
     * @param \Swoole\Coroutine\Channel $chan channel
     * @throws Exception
     */
    public function NewTcpClient(Swoole\Coroutine\Channel $chan){
        //创建tcp客户端
        $this->client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        if (!$this->client->connect($this->host, $this->port, 0.5)){
            throw new \Exception("TCP connect failed. Error: {$this->client->errCode}");
        }
        //tcp握手请求
        $this->client->send($this->_setTcpPack($this->token,SHAKE_HAND_CODE));
        while (true) {
            if (!$this->client->isConnected()) {
                break;
            }
            $data = $this->client->recv();
            if($data === ""){
                //服务器断开连接
                $this->_closeHandle();
                break;
            }else{
                if($data){
                    $this->recvData .= $data;
                    while ($bodyData = $this->_getTcpPack()){
                        switch ($bodyData["msg_id"]){
                            case RECV_SHAKE_HAND_CODE:
                                //tcp握手成功
                                $this->_shakeHandHandle($bodyData);
                                $chan->push(1);
                                break;
                            case RECV_SYSTEM_PULL_CODE:
                                /*系统推送消息 TODO 你的逻辑*/

                                $this->loggle->set("RECV_SYSTEM_PULL_CODE messgeId: ".$bodyData["msg_id"].", body:".$bodyData["body"],"DEBUG");
                                break;
                            case RECV_WECHAT_PULL_CODE:
                                /*微信推送消息 TODO 你的逻辑*/
                                $this->loggle->set("RECV_WECHAT_PULL_CODE messgeId: ".$bodyData["msg_id"].", body:".$bodyData["body"],"DEBUG");
                                break;
                        }
                    }
                }
            }
        }
    }

    /**
     * 握手成功处理逻辑
     * @param array $body
     */
    private function _shakeHandHandle(array $body){
        $this->loggle->set("TCP握手成功");
        $this->_addHeartBeat();//添加到心跳包
    }



}


