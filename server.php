<?php
class MyServer{
    public $ws = null;
    public $redis;
    public function __construct(){
        $this->ws = new swoole_websocket_server("0.0.0.0", 9501);
        $this->redis=new \Redis();
        $this->redis->connect('127.0.0.1', 6379);
        $this->ws->set(array(
            'worker_num' => 4,
            'daemonize' => true,
            'backlog' => 128,
            'log_file' => './swoole.log',
        ));
        $this->ws->on("open", [$this, 'onOpen']);
        $this->ws->on("message", [$this, 'onMessage']);
        $this->ws->on("close", [$this, 'onClose']);
        $this->ws->start();
    }
    public function onOpen($ws, $request){
        $online_user= $this->redis->hgetall('online');
        $obj=['data'=>$online_user,'status'=>300,'msg'=>'获取当前在线用户'];
        if($online_user){
            $ws->push($request->fd, json_encode($obj));
        }
    }
    public function onMessage($ws, $frame){
        $data=json_decode($frame->data);
        if($data->status==200){
            //用户发消息
            $user=$this->redis->hget('online',$frame->fd);
            $msg =$user.' '.date('Y-m-d H:i:s').":<br>{$data->text}";
            $receive_user=$data->user;
            $obj=['data'=>$msg,'status'=>200,'user'=>$user,'msg'=>'发送消息'];
            $ws->push($receive_user,json_encode($obj));
        }else{
            //新用户上线
            $online_user= $this->redis->hgetall('online');
            foreach ($online_user as $fd=>$user){
                $obj=['data'=>[$frame->fd=>$data->user],'status'=>300,'msg'=>'新用户上线'];
                $ws->push($fd,json_encode($obj));
            }
            $this->redis->hset('online',$frame->fd,$data->user);
        }
    }
    public function onClose($ws, $fd){
        $user=$this->redis->hget('online',$fd);
        $this->redis->hdel('online',$fd);
        $arr= $this->redis->hgetall('online');
        foreach ($arr as $fd=>$val){
            $obj=['data'=>$user,'status'=>400,'msg'=>'用户下线'];
            $ws->push($fd,json_encode($obj));
        }
    }
}
$ws=new MyServer();