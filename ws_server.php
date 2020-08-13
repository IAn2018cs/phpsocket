<?php

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
            $fdInfo = $this->server->getClientInfo($frame->fd);

            $time = intval($fdInfo["last_time"]) * 1000;
            $ip = $fdInfo["remote_ip"] . ":" . strval($fdInfo["remote_port"]);

            $result = json_decode($frame->data, true);
            $action = $result["action"];
            if ($action == "init") {
                $id = $result["userId"];
                $key = $result["publicKey"];
                $this->insertUserInfo($id, $key, $frame->fd);

                // 返回给在线列表
                $allOnline = $this->getAllOnline();
                $allOnlineFD = $this->getAllOnlineFD();
                foreach ($allOnlineFD as $fd) {
                    // 需要先判断是否是正确的websocket连接，否则有可能会push失败
                    if ($this->server->isEstablished($fd)) {
                        $data = $this->getJsonData(array("action" => "list", "onlineList" => $allOnline));
                        $server->push($fd, $data);
                    }
                }

                // 检查消息表，是否有该用户未接受到的消息
                $unSendMsg = $this->getUnSendMsg($id);
                foreach ($unSendMsg as $msg) {
                    $data = $this->getJsonData(array(
                        "action" => "chart",
                        "aesKey" => $msg["encrypt_key"],
                        "msg" => $msg["encrypt_msg"],
                        "time" => $msg["send_time"],
                        "ip" => $msg["send_ip"],
                        "msgId" => intval($msg["id"])
                    ));
                    $server->push($frame->fd, $data);
                }
            } else if ($action == "connect") {
                $bId = $result["otherUserId"];
                $key = $this->query($bId);
                if (!empty($key)) {
                    // 返回B的公钥
                    $data = $this->getJsonData(array("action" => "connect", "publicKey" => $key));
                    $server->push($frame->fd, $data);
                }
            } else if ($action == "chart") {
                $bId = $result["otherUserId"];
                $aesKey = $result["encryptKey"];
                $emsg = $result["encryptMsg"];
                $fd = $this->queryFD($bId);
                // 先插入消息表中
                $msgId = $this->saveMsg($bId, $aesKey, $emsg, $time, $ip);

                if (!empty($fd) && $this->server->isEstablished($fd)) {
                    // 将信息转发给B
                    $data = $this->getJsonData(array(
                        "action" => "chart",
                        "aesKey" => $aesKey,
                        "msg" => $emsg,
                        "time" => $time,
                        "ip" => $ip,
                        "msgId" => intval($msgId)
                    ));
                    $server->push($fd, $data);
                }
            } else if ($action == "received") {
                $msgId = $result["msgId"];
                $this->deleteMsg($msgId);
            }
        });

        $this->server->on('close', function ($ser, $fd) {
            echo "client {$fd} closed\n";
            $this->offline($fd);
            echo "当前连接数:" . (sizeof($this->server->connections) - 1) . "\n";
        });

        $this->server->start();
    }

    protected function connectDB()
    {
        $servername = "localhost";
        $username = "root";
        $password = "chenshuaide";
        $dbname = "e2ee";

        $this->conn = mysqli_connect($servername, $username, $password, $dbname, 3307);
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

    protected function saveMsg($userId, $encryptKey, $encryptMsg, $time, $ip)
    {
        $sql = "INSERT INTO `msg` (`user_id`, `encrypt_key`, `encrypt_msg`, `send_time`, `send_ip`) 
                                VALUES ('$userId', '$encryptKey', '$encryptMsg', $time, '$ip')";
        mysqli_query($this->conn, $sql);
        return mysqli_insert_id($this->conn);
    }

    protected function deleteMsg($msgId)
    {
        $sql = "DELETE FROM `msg` WHERE `id` = $msgId;";
        return mysqli_query($this->conn, $sql);
    }

    protected function getUnSendMsg($userId)
    {
        $msgs = array();
        $result = mysqli_query($this->conn, "SELECT `id`, `encrypt_key`, `encrypt_msg`, `send_time`, `send_ip` FROM `msg` WHERE `user_id`='$userId';");
        if (!$result) {
            return $msgs;
        }
        while ($row = mysqli_fetch_assoc($result)) {
            array_push($msgs, $row);
        }
        return $msgs;
    }

    private function getJsonData($result)
    {
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}

new WebSocketTest();