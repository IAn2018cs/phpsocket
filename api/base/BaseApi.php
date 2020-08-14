<?php

$config = include __DIR__ . '/../../base/config.php';
require_once __DIR__ . '/Result.php';
require_once __DIR__ . '/../../base/SecurityApiController.php';

abstract class BaseApi
{
    var $apiController;

    var $conn;

    var $uid;
    var $deviceId;
    var $pkg;
    var $osName;

    public function __construct()
    {
        global $config;
        if ($config['isSecurity']) {
            $this->apiController = new SecurityApiController();
        }

        $this->connectDB();

        $this->initBaseParameter();
    }

    private function initBaseParameter()
    {
        $this->uid = $this->getParameter("user_id", "");
        $this->deviceId = $this->getParameter("uid", "");
        $this->pkg = $this->getParameter("pkg", "");
        $this->osName = $this->getParameter("os_name", "");
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
            die("Connection failed: " . mysqli_connect_error());
        }
    }

    protected function closeDB()
    {
        mysqli_close($this->conn);
    }

    public function query($sql)
    {
        $rows = array();
        $result = mysqli_query($this->conn, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            array_push($rows, $row);
        }
        return $rows;
    }

    public function insert($sql)
    {
        return mysqli_query($this->conn, $sql);
    }

    public function update($sql)
    {
        return mysqli_query($this->conn, $sql);
    }

    public function delete($sql)
    {
        return mysqli_query($this->conn, $sql);
    }

    public function fail($code, $msg)
    {
        $result = new Result();
        $result->code = intval($code);
        $result->message = $msg;
        $result->data = null;
        return $this->output($result);
    }

    public function success($dada, $diy = null)
    {
        if ($diy != null) {
            return $this->output($diy);
        }
        $result = new Result();
        $result->code = 0;
        $result->message = "success";
        $result->data = $dada;
        return $this->output($result);
    }

    private function output($result)
    {
        global $config;
        if ($config['isSecurity']) {
            return $this->apiController->encryptResult(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function getParameter($key, $default)
    {
        global $config;
        if ($config['isSecurity']) {
            return $this->apiController->get($key, $default);
        }
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        return $default;
    }

    public function getRealIp()
    {
        $ip = false;
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match("^(10│172.16│192.168).", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

    public function saveLog($msg)
    {
        $content = $this->getRealIp() . " - " . date("Y-m-d H:i:s") . " - " . $msg . "\n\n";
        error_log($content, 0);
    }

    abstract public function getResult();
}