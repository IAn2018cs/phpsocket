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
            $msg = explode("#", $frame->data);
            $action = $msg[0];
            if ($action == "init") {
                $id = $msg[1];
                $key = $msg[2];
                $this->insertUserInfo($id, $key, $frame->fd);

                // 返回给在线列表
                $allOnline = $this->getAllOnline();
                $allOnlineFD = $this->getAllOnlineFD();
                foreach ($allOnlineFD as $fd) {
                    // 需要先判断是否是正确的websocket连接，否则有可能会push失败
                    if ($this->server->isEstablished($fd)) {
                        $server->push($fd, "list#$allOnline");
                    }
                }
            } else if ($action == "connect") {
                $bId = $msg[1];
                $key = $this->query($bId);
                if (!empty($key)) {
                    // 返回B的公钥
                    $server->push($frame->fd, "connect#$key");
                }
            } else if ($action == "chart") {
                $bId = $msg[1];
                $aesKey = $msg[2];
                $emsg = $msg[3];
                $fd = $this->queryFD($bId);
                if (!empty($fd) && $this->server->isEstablished($fd)) {
                    // 将信息转发给B
                    $server->push($fd, "chart#$aesKey#$emsg");
                }
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
}

new WebSocketTest();