<?php

require_once __DIR__ . '/../base/BaseApi.php';
require_once __DIR__ . '/../imp/CreateGroupApi.php';
require_once __DIR__ . '/../imp/JoinGroupApi.php';
require_once __DIR__ . '/../imp/DeleteGroupApi.php';
require_once __DIR__ . '/../imp/QueryGroupApi.php';
require_once __DIR__ . '/../imp/LoginApi.php';
require_once __DIR__ . '/../imp/QueryOnlineApi.php';

class Dispatcher
{
    var $resultList;

    const TYPE_CREATE_GROUP = 1001;  // 创建组
    const TYPE_JOIN_GROUP = 1002;  // 加入组
    const TYPE_DELETE_GROUP = 1003;  // 删除组
    const TYPE_QUERY_GROUP = 1004;  // 查询加入的组
    const TYPE_LOGIN = 1005;  // 登录
    const TYPE_QUERY_ONLINE = 1006;  // 查询在线列表

    public function __construct($type)
    {
        switch ($type) {
            case self::TYPE_CREATE_GROUP:
                $this->resultList = new CreateGroupApi();
                break;
            case self::TYPE_JOIN_GROUP:
                $this->resultList = new JoinGroupApi();
                break;
            case self::TYPE_DELETE_GROUP:
                $this->resultList = new DeleteGroupApi();
                break;
            case self::TYPE_QUERY_GROUP:
                $this->resultList = new QueryGroupApi();
                break;
            case self::TYPE_LOGIN:
                $this->resultList = new LoginApi();
                break;
            case self::TYPE_QUERY_ONLINE:
                $this->resultList = new QueryOnlineApi();
                break;
        }
    }

    public function getResult()
    {
        try {
            $result = $this->resultList->getResult();
        } catch (Exception $e) {
            $result = $this->resultList->fail(6, "error:" . $e->getMessage());
        }
        return $result;
    }

}