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
        $obj=['msg'=>'hello welcome','status'=>200];
        $ws->push($request->fd, json_encode($obj));
//        $str='';
//        foreach ($arr as $fd=>$user){
//            $str.=$user."<br>";
//        }
//        $obj=['msg'=>$str,'status'=>300];
//        if($str){
//            $ws->push($request->fd, json_encode($obj));
//        }
    }
    public function onMessage($ws, $frame){
        global $redis;
        $data=json_decode($frame->data);
        if($data->status==1){
            $redis->set($data->user, $frame->fd);
            $redis->hset('hash',$frame->fd,$data->user);
            $arr= $redis->hgetall('hash');
            foreach ($arr as $fd=>$user){
                if($fd!=$frame->fd){
                    $obj=['msg'=>$data->user,'status'=>300];
                    $ws->push($fd,json_encode($obj));
                }
            }
        }else{
            $msg = $redis->hget('hash',$frame->fd).":{$data->text}\n";
            $to=$data->user;
            $to=$redis->get($to);
            $obj=['msg'=>$msg,'status'=>200];
            $redis->hset($redis->hget('hash',$frame->fd).'->'.$data->user,time(),$msg);
            $ws->push($to,json_encode($obj));
        }
    }
    public function onClose($ws, $fd){

    }
}
$ws=new MyServer();