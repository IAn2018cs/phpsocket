<?php

require_once __DIR__ . '/SocketBehavior.php';

class WebSocketServer
{

    private $server;
    private $behavior;

    public function __construct()
    {
        $this->behavior = new SocketBehavior();

        $this->server = new Swoole\WebSocket\Server("0.0.0.0", 9503);

        $this->server->on('open', function (Swoole\WebSocket\Server $server, $request) {
            var_dump($request->server);
            echo "当前连接数:" . sizeof($this->server->connections) . "\n";
        });

        $this->server->on('message', function (Swoole\WebSocket\Server $server, $frame) {
            echo "Message: {$frame->data}\n";
            // 这里需要先解密
            $result = json_decode($frame->data, true);

            $this->processMessage($result, $frame->fd);
        });

        $this->server->on('close', function ($ser, $fd) {
            echo "client {$fd} closed\n";
            $this->behavior->processOffline($fd);
            echo "当前连接数:" . (sizeof($this->server->connections) - 1) . "\n";
        });

        $this->server->start();
    }

    protected function processMessage($result, $fd)
    {
        $action = intval($result["action"]);

        switch ($action) {
            case SocketBehavior::ACTION_C2S_ONLINE:
                $id = $result["userId"];
                $key = $result["publicKey"];

                $this->behavior->processInit($this, $id, $key, $fd);
                break;
            case SocketBehavior::ACTION_C2S_CHART_CONNECT:
                $otherId = $result["otherUserId"];

                $this->behavior->processChartConnect($this, $otherId, $fd);
                break;
            case SocketBehavior::ACTION_C2S_CHART:
                $otherId = $result["otherUserId"];
                $aesKey = $result["encryptKey"];
                $emsg = $result["encryptMsg"];
                $fromId = $result["fromId"];

                // 获取连接者信息
                $fdInfo = $this->server->getClientInfo($fd);
                $time = intval($fdInfo["last_time"]) * 1000;
                $ip = $fdInfo["remote_ip"] . ":" . strval($fdInfo["remote_port"]);

                $this->behavior->processChart($this, $otherId, $aesKey, $emsg, $fromId, $time, $ip);
                break;
            case SocketBehavior::ACTION_C2S_RECEIVED:
                $msgId = $result["msgId"];

                $this->behavior->processReceived($msgId);
                break;
            case SocketBehavior::ACTION_C2S_HISTORY_RECEIVED:
                $msgIds = $result["msgIds"];

                $this->behavior->processHistoryReceived($msgIds);
                break;
            case SocketBehavior::ACTION_C2S_CONNECT_GROUP:
                $id = $result["userId"];
                $groupId = $result["groupId"];
                $this->behavior->processConnectGroup($this, $groupId, $id, $fd);
                break;
            case SocketBehavior::ACTION_C2S_CHART_GROUP:
                $fromId = $result["fromId"];
                $groupId = $result["groupId"];
                $emsg = $result["encryptMsg"];
                $members = $result["members"];

                // 获取连接者信息
                $fdInfo = $this->server->getClientInfo($fd);
                $time = intval($fdInfo["last_time"]) * 1000;
                $ip = $fdInfo["remote_ip"] . ":" . strval($fdInfo["remote_port"]);

                $this->behavior->processChartGroup($this, $fromId, $groupId, $emsg, $members, $time, $ip);
                break;
        }
    }

    public function pushData($fd, $data)
    {
        // 这里需要加密
        // 需要先判断是否是正确的websocket连接，否则有可能会push失败
        if (!empty($fd) && $this->server->isEstablished($fd)) {
            $data = $this->getJsonData($data);
            $this->server->push($fd, $data);
        }
    }

    private function getJsonData($result)
    {
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}

new WebSocketServer();