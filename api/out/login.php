<?php
//error_reporting(0);
header('Content-type:application/json;charset=utf-8');

require __DIR__ . '/../../phpsocket/composer/vendor/autoload.php';
require_once __DIR__ . '/../../phpsocket/api/dispatch/Dispatcher.php';

//$stime = microtime(true);
$dispatcher = new Dispatcher(Dispatcher::TYPE_LOGIN);
echo $dispatcher->getResult();
//$etime = microtime(true);
//echo $etime - $stime;
?>
