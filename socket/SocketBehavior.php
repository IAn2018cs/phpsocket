<?php

$config = include __DIR__ . '/../base/config.php';

class SocketBehavior
{
    private $conn;

    const ACTION_C2S_ONLINE = 1; // 用户连接上线
    const ACTION_C2S_CHART_CONNECT = 2; // 请求连接
    const ACTION_C2S_CHART = 3; // 聊天通信
    const ACTION_C2S_RECEIVED = 4; // 成功接收信息
    const ACTION_C2S_HISTORY_RECEIVED = 5; // 成功接收史信息
    const ACTION_C2S_CONNECT_GROUP = 6; // 请求连接组
    const ACTION_C2S_CHART_GROUP = 7; // 组聊天通信

    const ACTION_S2C_CHART_CONNECT = 1; // 返回被连接者公钥
    const ACTION_S2C_CHART = 2; // 聊天通信
    const ACTION_S2C_HISTORY_MSG = 3; // 返回历史消息
    const ACTION_S2C_CONNECT_GROUP = 4; // 返回群内成员公钥

    public function __construct()
    {
        $this->connectDB();
    }

    protected function connectDB()
    {
        global $config;

        $servername = $config['servername'];
        $username = $config['username'];
        $password = $config['password'];
        $dbname = $config['dbname'];
        $port = $config['port'];

        $this->conn = mysqli_connect($servername, $username, $password, $dbname, $port);
        // 检测连接
        if (!$this->conn) {
            die("Connection failed: " . mysqli_connect_error() . "\n");
        }
    }

    public function processInit(WebSocketServer $server, $id, $key, $fd)
    {
        $this->online($id, $key, $fd);

        // 检查消息表，是否有该用户未接受到的消息
        // TODO 考虑 累计大量数据问题
        $unSendMsg = $this->getUnSendMsg($id);
        $server->pushData($fd, array(
            "action" => $this::ACTION_S2C_HISTORY_MSG,
            "msgs" => $unSendMsg
        ));
    }

    public function processChartConnect(WebSocketServer $server, $otherId, $fd)
    {
        $key = $this->queryPublicKey($otherId);
        if (!empty($key)) {
            // 返回B的公钥
            $server->pushData($fd, array("action" => $this::ACTION_S2C_CHART_CONNECT, "publicKey" => $key));
        }
    }

    public function processChart(WebSocketServer $server, $otherId, $aesKey, $emsg, $fromId, $time, $ip, $groupId = -1)
    {
        $otherFd = $this->queryFD($otherId);
        // 先插入消息表中
        $msgId = $this->saveMsg($otherId, $fromId, $aesKey, $emsg, $time, $ip);

        // 将信息转发给B
        $server->pushData($otherFd, array(
            "action" => $this::ACTION_S2C_CHART,
            "aesKey" => $aesKey,
            "msg" => $emsg,
            "time" => $time,
            "ip" => $ip,
            "msgId" => intval($msgId),
            "fromId" => $fromId,
            "groupId" => $groupId
        ));
    }

    public function processReceived($msgId)
    {
        $this->deleteMsg($msgId);
    }

    public function processOffline($fd)
    {
        $this->offline($fd);
    }

    public function processHistoryReceived($msgIds)
    {
        $this->deleteMsgs($msgIds);
    }

    public function processConnectGroup(WebSocketServer $server, $groupId, $userId, $fd)
    {
        $groupMembers = $this->getGroupMembers($groupId, $userId);
        $server->pushData($fd, array("action" => $this::ACTION_S2C_CONNECT_GROUP, "members" => $groupMembers));
    }

    public function processChartGroup(WebSocketServer $server, $fromId, $groupId, $emsg, $members, $time, $ip)
    {
        foreach ($members as $member) {
            // 分别发送给成员
            $userId = $member["userId"];
            $aesKey = $member["aesKey"];
            $this->processChart($server, $userId, $aesKey, $emsg, $fromId, $time, $ip, $groupId);
        }
    }


    private function online($id, $key, $fd)
    {
        $sql = "INSERT INTO `user_key` (`id`, `publicKey`, `fd`, `online`) 
                                VALUES ('$id', '$key', $fd, 1) 
                                ON DUPLICATE KEY UPDATE `publicKey`='$key', `fd`=$fd, `online`=1";
        return mysqli_query($this->conn, $sql);
    }

    private function offline($fd)
    {
        $sql = "SELECT `id` FROM `user_key` WHERE `fd`=$fd AND `online`=1 LIMIT 1;";
        $result = mysqli_query($this->conn, $sql);
        if (!$result) {
            return;
        }
        if ($row = mysqli_fetch_assoc($result)) {
            $id = $row["id"];
            mysqli_query($this->conn, "UPDATE `user_key` SET `online` = 0 WHERE `id` = '$id';");
        }
    }

    private function queryPublicKey($id)
    {
        $result = mysqli_query($this->conn, "SELECT `publicKey` FROM `user_key` WHERE `id`='$id' LIMIT 1;");
        if (!$result) {
            return null;
        }
        if ($row = mysqli_fetch_assoc($result)) {
            return $row["publicKey"];
        }
        return null;
    }

    private function queryFD($id)
    {
        $result = mysqli_query($this->conn, "SELECT `fd` FROM `user_key` WHERE `id`='$id' AND `online`=1 LIMIT 1;");
        if (!$result) {
            return null;
        }
        if ($row = mysqli_fetch_assoc($result)) {
            return $row["fd"];
        }
        return null;
    }

    private function saveMsg($userId, $fromId, $encryptKey, $encryptMsg, $time, $ip)
    {
        $sql = "INSERT INTO `msg` (`user_id`, `encrypt_key`, `encrypt_msg`, `send_time`, `send_ip`, `group_id`, `from_id`) 
                                VALUES ('$userId', '$encryptKey', '$encryptMsg', $time, '$ip', -1, '$fromId')";
        mysqli_query($this->conn, $sql);
        return mysqli_insert_id($this->conn);
    }

    private function deleteMsg($msgId)
    {
        $sql = "DELETE FROM `msg` WHERE `id` = $msgId;";
        return mysqli_query($this->conn, $sql);
    }

    private function deleteMsgs($msgIds)
    {
        $sql = "DELETE FROM `msg` WHERE `id` IN ($msgIds);";
        return mysqli_query($this->conn, $sql);
    }

    private function getUnSendMsg($userId)
    {
        $msgs = array();
        $result = mysqli_query($this->conn, "SELECT `id`, `encrypt_key`, `encrypt_msg`, `send_time`, `send_ip`, `group_id`, `from_id` FROM `msg` WHERE `user_id`='$userId';");
        if (!$result) {
            return $msgs;
        }
        while ($row = mysqli_fetch_assoc($result)) {
            array_push($msgs, array(
                "aesKey" => $row["encrypt_key"],
                "msg" => $row["encrypt_msg"],
                "time" => $row["send_time"],
                "ip" => $row["send_ip"],
                "msgId" => intval($row["id"]),
                "fromId" => $row["from_id"],
                "groupId" => intval($row["group_id"])
            ));
        }
        return $msgs;
    }

    private function getGroupMembers($groupId, $userId)
    {
        $members = array();
        $result = mysqli_query($this->conn, "SELECT `id`, `publicKey` FROM `user_key` WHERE `id` IN 
        (SELECT `user_id` FROM `group_member` WHERE `group_id` = $groupId AND `user_id` != '$userId');");
        if (!$result) {
            return $members;
        }
        while ($row = mysqli_fetch_assoc($result)) {
            array_push($members, array(
                "userId" => $row["id"],
                "publicKey" => $row["publicKey"]
            ));
        }
        return $members;
    }
}