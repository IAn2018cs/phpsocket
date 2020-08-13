<?php

$config = include __DIR__ . '/config.php';

class WebSocketTest
{
    var $conn;

    var $server;

    public function __construct()
    {
        $this->connectDB();

        $this->server = new Swoole\WebSocket\Server("0.0.0.0", 9503);

        $this->server->on('open', function (Swoole\WebSocket\Server $server, $request) {
            var_dump($request->server);
            echo "当前连接数:" . sizeof($this->server->connections) . "\n";
        });

        $this->server->on('message', function (Swoole\WebSocket\Server $server, $frame) {
            echo "Message: {$frame->data}\n";
            $result = json_decode($frame->data, true);
            $this->processMessage($result, $frame->fd);
        });

        $this->server->on('close', function ($ser, $fd) {
            echo "client {$fd} closed\n";
            $this->offline($fd);
            echo "当前连接数:" . (sizeof($this->server->connections) - 1) . "\n";
        });

        $this->server->start();
    }

    protected function processMessage($result, $fd)
    {
        $fdInfo = $this->server->getClientInfo($fd);

        $time = intval($fdInfo["last_time"]) * 1000;
        $ip = $fdInfo["remote_ip"] . ":" . strval($fdInfo["remote_port"]);

        $action = $result["action"];

        switch ($action) {
            case "init":
                $id = $result["userId"];
                $key = $result["publicKey"];
                $this->insertUserInfo($id, $key, $fd);

                // 返回给在线列表
                $allOnline = $this->getAllOnline();
                $allOnlineFD = $this->getAllOnlineFD();
                foreach ($allOnlineFD as $otherFd) {
                    $this->pushData($otherFd, array("action" => "list", "onlineList" => $allOnline));
                }

                // 检查消息表，是否有该用户未接受到的消息
                // TODO 考虑 累计大量数据问题
                $unSendMsg = $this->getUnSendMsg($id);
                $this->pushData($fd, array(
                    "action" => "history",
                    "msgs" => $unSendMsg
                ));
                break;
            case "connect":
                $bId = $result["otherUserId"];
                $key = $this->query($bId);
                if (!empty($key)) {
                    // 返回B的公钥
                    $this->pushData($fd, array("action" => "connect", "publicKey" => $key));
                }
                break;
            case "chart":
                $bId = $result["otherUserId"];
                $aesKey = $result["encryptKey"];
                $emsg = $result["encryptMsg"];
                $fromId = $result["fromId"];
                $otherFd = $this->queryFD($bId);
                // 先插入消息表中
                $msgId = $this->saveMsg($bId, $fromId, $aesKey, $emsg, $time, $ip);

                // 将信息转发给B
                $this->pushData($otherFd, array(
                    "action" => "chart",
                    "aesKey" => $aesKey,
                    "msg" => $emsg,
                    "time" => $time,
                    "ip" => $ip,
                    "msgId" => intval($msgId),
                    "fromId" => $fromId,
                    "groupId" => -1
                ));
                break;
            case "received":
                $msgId = $result["msgId"];
                $this->deleteMsg($msgId);
                break;
            case "historyReceived":
                $msgIds = $result["msgIds"];
                $this->deleteMsgs($msgIds);
                break;
        }
    }

    protected function pushData($fd, $data)
    {
        // 需要先判断是否是正确的websocket连接，否则有可能会push失败
        if (!empty($fd) && $this->server->isEstablished($fd)) {
            $data = $this->getJsonData($data);
            $this->server->push($fd, $data);
        }
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

    private function insertUserInfo($id, $key, $fd)
    {
        $sql = "INSERT INTO `user_key` (`id`, `publicKey`, `fd`, `online`) 
                                VALUES ('$id', '$key', $fd, 1) 
                                ON DUPLICATE KEY UPDATE `publicKey`='$key', `fd`=$fd, `online`=1";
        return mysqli_query($this->conn, $sql);
    }

    protected function query($id)
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

    protected function queryFD($id)
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

    protected function offline($fd)
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

    protected function getAllOnline()
    {
        $ids = "";
        $result = mysqli_query($this->conn, "SELECT `id` FROM `user_key` WHERE `online`=1;");
        if (!$result) {
            return $ids;
        }
        while ($row = mysqli_fetch_assoc($result)) {
            $ids .= ($row["id"] . ",");
        }
        return $ids;
    }

    protected function getAllOnlineFD()
    {
        $fds = array();
        $result = mysqli_query($this->conn, "SELECT `fd` FROM `user_key` WHERE `online`=1;");
        if (!$result) {
            return $fds;
        }
        while ($row = mysqli_fetch_assoc($result)) {
            array_push($fds, $row["fd"]);
        }
        return $fds;
    }

    protected function saveMsg($userId, $fromId, $encryptKey, $encryptMsg, $time, $ip)
    {
        $sql = "INSERT INTO `msg` (`user_id`, `encrypt_key`, `encrypt_msg`, `send_time`, `send_ip`, `group_id`, `from_id`) 
                                VALUES ('$userId', '$encryptKey', '$encryptMsg', $time, '$ip', -1, '$fromId')";
        mysqli_query($this->conn, $sql);
        return mysqli_insert_id($this->conn);
    }

    protected function saveGroupMsg($userInfos, $fromId, $encryptMsg, $time, $ip, $groupId)
    {
        $sql = "";
        foreach ($userInfos as $userInfo) {
            $sql .= "INSERT INTO `msg` (`user_id`, `encrypt_key`, `encrypt_msg`, `send_time`, `send_ip`, `group_id`, `from_id`) 
                                VALUES ('{$userInfo["id"]}', '{$userInfo["key"]}', '$encryptMsg', $time, '$ip', $groupId, '$fromId')";
        }
        if (empty($sql)) {
            return false;
        }
        mysqli_query($this->conn, $sql);
        return mysqli_insert_id($this->conn);
    }

    protected function deleteMsg($msgId)
    {
        $sql = "DELETE FROM `msg` WHERE `id` = $msgId;";
        return mysqli_query($this->conn, $sql);
    }

    protected function deleteMsgs($msgIds)
    {
        $sql = "DELETE FROM `msg` WHERE `id` IN ($msgIds);";
        return mysqli_query($this->conn, $sql);
    }

    protected function getUnSendMsg($userId)
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

    private function getJsonData($result)
    {
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}

new WebSocketTest();