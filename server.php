<?php
class MyServer{
    public $ws = null;
    public $redis;
    public function __construct(){
        $this->ws = new swoole_websocket_server("0.0.0.0", 9501);
        $this->redis=new \Redis();
        $this->redis->connect('127.0.0.1', 6379);
        $this->redis->sadd('user','robot');
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
            $receive_user=$data->user;
            $user=$this->redis->hget('online',$frame->fd);
            //机器人
            if($receive_user==-1){
                //聊天记录
                $receive_name='robot';
                $list_name=$user>$receive_name?$user.'-'.$receive_name:$receive_name.'-'.$user;
                $this->redis->lpush($list_name, $data->text);
                $robot_msg=$this->robot_answer($data->text,'robot'.$frame->fd);
                foreach ($robot_msg as $key=>$val){
                    $temp_msg=$val['values'][$val['resultType']];
                    if($val['resultType']=='url'){
                        $temp_msg='<a target="_blank" href="'.$temp_msg.'">'.$temp_msg.'</a>';
                    }else if($val['resultType']=='image'){
                        $temp_msg='<img src="'.$temp_msg.'">';
                    }
                    $msg='智能机器人 '.date('Y-m-d H:i:s')."<br>{$temp_msg}";
                    //聊天记录
                    $this->redis->lpush($list_name,$msg);
                    $obj=['data'=>$msg,'status'=>200,'user'=>'robot','msg'=>'机器人回复'];
                    $ws->push($frame->fd,json_encode($obj));
                }
            }else{
                //聊天记录
                $receive_name=$this->redis->hget('online',$receive_user);
                $list_name=$user>$receive_name?$user.'-'.$receive_name:$receive_name.'-'.$user;
                //用户发消息
                $msg =$user.' '.date('Y-m-d H:i:s')."<br>{$data->text}";
                $this->redis->lpush($list_name, $msg);
                $obj=['data'=>$msg,'status'=>200,'user'=>$user,'msg'=>'发送消息'];
                $ws->push($receive_user,json_encode($obj));
            }
        }else{
            //新用户上线
            $online_user= $this->redis->hgetall('online');
            foreach ($online_user as $fd=>$user){
                $obj=['data'=>[$frame->fd=>$data->user],'status'=>300,'msg'=>'新用户上线'];
                $ws->push($fd,json_encode($obj));
            }
            $this->redis->hset('online',$frame->fd,$data->user);
            $this->redis->sadd('user',$data->user);
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
        $this->redis->srem('user',$user);
    }
    public function robot_answer($msg,$user){
        $arr=['inputText'=>['text'=>$msg]];
        $user_arr=['apiKey'=>'ce9a2413ada445caad83487d756d77f2','userId'=>$user];
        $param=[
            "perception"=>$arr,
            "userInfo"=>$user_arr,
        ];
        $param=json_encode($param);
        $url='http://openapi.tuling123.com/openapi/api/v2';
        $res=$this->curlPost($url,$param);
        $res=json_decode($res,true);
        return $res['results'];
    }
    public function curlPost($url, $post_data = [], $type = 0)
    {
        //初始化
        $ch = curl_init();
        //设置抓取的url
        curl_setopt($ch, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        //curl_setopt($ch, CURLOPT_HEADER, 1);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);
        //设置post数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $result = curl_exec($ch);
        //关闭URL请求
        curl_close($ch);
        //显示获得的数据
        if ($type) {
            $result = iconv("gbk//IGNORE", "utf-8", $result);
        }
        return $result;
    }
}
$ws=new MyServer();