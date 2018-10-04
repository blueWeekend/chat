<?php
$name=$_POST['name'];
$redis=new \Redis();
$redis->connect('127.0.0.1', 6379);
if($redis->sismember('user', $name)){
	echo 1;die;
}
echo 0;