<?php
error_reporting(0);
header('Content-type:application/json;charset=utf-8');

require_once '/root/phpsocket/api/dispatch/Dispatcher.php';

//$stime = microtime(true);
$dispatcher = new Dispatcher(Dispatcher::TYPE_QUERY_GROUP);
echo $dispatcher->getResult();
//$etime = microtime(true);
//echo $etime - $stime;
?>