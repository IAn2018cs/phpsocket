<?php

/**
 * Class .SecurityApiController
 * 本类用途:
 * 1 实现接口加密传输协议
 * 2 验证接口签名
 * 3 重写请求参数到本类（为后续应用层使用)
 */
class SecurityApiController
{
    //私钥
    var $KEY;
    CONST CIPHER = 'AES-256-CBC';
    var $decrypted = false;
    var $signed = false;
    var $timeout = true;
    /**
     * 加密协议的版本
     * 1 base64(自定义符号表
     * 2 AES
     */
    var $securityControllerVersion = 0;
    //加解密封装函数
    var $fun = '';
    //请求参数
    var $params = [];
    //请求过期时间 单位秒
    var $timeExpireMin = 10;

    public function __construct()
    {
        global $config;
        $this->KEY = $config['key'];
        $this->securityControllerInit();
        $this->decrypt();
        $this->checkSign();
        $this->errOutput();
    }

    private function errOutput()
    {
        if (!$this->decrypted) $this->err(2, 'decrypt error');
        if ($this->timeout) $this->err(2, 'time expire error');
        if (!$this->signed) $this->err(3, 'sign error');
    }

    private function securityControllerInit()
    {
        $securityController = $_SERVER['HTTP_SECURITY_CONTROLLER'];
        $v = 0;
        /**
         * 从header中获得Safety-Controller
         */
        if (preg_match_all('/([a-z]+)=(\d+)/', $securityController, $m)) {
            foreach ($m[1] as $k => $v) {
                if ($v == 'v') {
                    $v = intval($m[2][$k]);
                    $this->securityControllerVersion = $v;
                    break;
                }
            }
        }
        $v = ($this->securityControllerVersion >> 8) & 0xff;
        if ($v == 1) {
            $this->fun = 'baseResult';
        } elseif ($v == 2) {
            $this->fun = 'AESResult';
        }
    }

    /**
     * 解密GET/POST数据
     */
    private function decrypt()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') {
            $qs = $_SERVER['QUERY_STRING'];
        } else {
            $qs = file_get_contents('php://input');
        }
        $qs = call_user_method($this->fun, $this, urldecode($qs), false);
        if ($qs) {
            parse_str($qs, $params);
            if (!$params['_sign']) {
                $this->signed = false;
            }
            $this->params = $params;

            $this->decrypted = true;
        }
    }

    /**
     * 计算签名
     * 获得计算签名专用的参数，保留_前缀
     */
    private function checkSign()
    {
        $sig = $this->params['_sign'];
        $random = intval($this->params['_random']);
        $timestamp = intval($this->params['_timestamp']);

        /**
         * 客户端时间戳的判断
         */
        if (!preg_match('/^\d{10}$/', $timestamp) || (abs(time() - $timestamp) > $this->timeExpireMin)) {
            $this->timeout = true;
        }
        $this->timeout = false;
        unset($this->params['_sign']);
        //参数重新排序
        ksort($this->params);
        //原始数据
        $raw = '';
        foreach ($this->params as $k => $v)
            $raw .= sprintf('%s%s', $k, urlencode($v));
        //准备计算位置，获得加密前的字符串
        $pos = $random % strlen($raw);
        $key = $this->KEY;
        $s = substr($raw, 0, $pos) . $key . substr($raw, $pos);
        if ($sig != strtolower(md5($s))) {
            $this->signed = false;
        } else {
            $this->signed = true;
        }
    }

    public function baseResult($result, $encrypt = 1)
    {
        if (!is_string($result) && is_array($result) && $result) $result = json_encode($result);
        // 默认的Base64编码表
        $default = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";

        // 加密处理
        if ($encrypt) {
            $baseResult = base64_encode($result);
        } else {
            $baseResult = $result;
        }
        // 获取长度  遍历修改
        $length = strlen($baseResult);
        // 遍历字符串进行偏移
        for ($i = 0; $i < $length; $i++) {
            // 补位字符不处理
            if ($baseResult[$i] == '=') {
                continue;
            }
            // 获取之前的位置
            $oldIndex = strrpos($default, $baseResult[$i]);
            // 获取偏移后实际的位置
            if ($encrypt) {
                $newIndex = ($oldIndex + $i % 10) & 0x3f;
            } else {
                $newIndex = ($oldIndex + 64 - $i % 10) & 0x3f;
            }
            // 获取替换后的字符串
            $realChar = $default[$newIndex];
            // 准备替换
            $baseResult = substr_replace($baseResult, $realChar, $i, 1);
        }
        if (!$encrypt) {
            $baseResult = base64_decode($baseResult);
        }
        // 返回结果
        return $baseResult;
    }

    public function err($code, $errmsg)
    {
        $arr = array('code' => intval($code), 'message' => $errmsg, 'data' => null);
        $e = call_user_method($this->fun, $this, json_encode($arr));
        printf("%s", $e);
        exit;
    }

    private function shuffle_assoc(&$array)
    {
        $keys = array_keys($array);

        shuffle($keys);

        foreach ($keys as $key) {
            $new[$key] = $array[$key];
        }

        $array = $new;

        return true;
    }

    public function encryptResult($json)
    {
        return call_user_method($this->fun, $this, $json);
    }

    /**
     *  AES加密和解密函数的封装
     */
    public function AESResult($str, $encrypt = true)
    {
        if ($encrypt) {
            return $this->AESEncrypt($str, $this->KEY, self::CIPHER);
        } else {
            return $this->AESDecrypt($str, $this->KEY, self::CIPHER);
        }
    }

    private function AESEncrypt($str, $key, $cipher)
    {
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($str, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext, $key, true);
        $ciphertext = base64_encode($iv . $hmac . $ciphertext);
        return $ciphertext;
    }

    private function AESDecrypt($str, $key, $cipher)
    {
        $c = base64_decode($str);
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext = substr($c, $ivlen + $sha2len);
        $original = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext, $key, true);
        if (hash_equals($hmac, $calcmac)) {
            return $original;
        }
        return false;
    }

    /**
     * 获取get参数.
     *
     * @param null $key 键值.
     *
     * @return array|mixed|null
     */
    public final function get($key = null, $default = null)
    {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        } else {
            return $default;
        }
    }
}
