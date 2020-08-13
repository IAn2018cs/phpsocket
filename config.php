<?php
$json = file_get_contents(__DIR__ . "/conf/localconfig.json");
$data = (array)json_decode($json);
return array(
    // 数据库连接信息
    'servername' => $data['host'],
    'username' => $data['account'],
    'password' => $data['password'],
    'dbname' => $data['database'],
    'port' => $data['port'],
    'key' => $data['securityKey'],
    'projectId' => $data['projectId'],
    'isSecurity' => $data['isSecurity']
);

