<?php

require_once __DIR__ . '/../base/BaseApi.php';

use Firebase\JWT\JWT;

class LoginApi extends BaseApi
{
    var $idToken;
    var $projectId;
    var $sub;

    var $phone;
    var $email;
    var $photoUrl;
    var $displayName;
    var $signProvider = "";
    var $publicKey;

    public function getResult()
    {
        global $config;
        $this->projectId = $config['projectId'];
        $this->idToken = $this->getParameter("id_token", "");
        $this->publicKey = $this->getParameter("public_key", "");

        if (empty($this->idToken) || empty($this->publicKey)) {
            $this->saveLog("id_token or public key is empty");
            return $this->fail(4, "id_token or public key is empty");
        }

        if (!$this->checkToken()) {
            $this->saveLog("id token error");
            return $this->fail(7, 'id token error');
        }

        $this->phone = $this->getParameter("phone", "");
        $this->email = str_replace("'", "\'", $this->getParameter("email", ""));
        $this->photoUrl = $this->getParameter("photo_url", "");
        $this->displayName = str_replace("'", "\'", $this->getParameter("display_name", ""));

        if ($this->uid != $this->sub) {
            return $this->fail(4, "user_id error");
        }

        if ($this->insertUserInfo()) {
            return $this->success(null);
        }

        return $this->fail(5, "insert user info fail");
    }

    private function http_get($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        // CURLOPT_RETURNTRANSFER  设置是否有返回值
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //执行完以后的返回值
        $response = curl_exec($curl);
        //释放curl
        curl_close($curl);
        return $response;
    }

    private function getPublicKeyJson()
    {
        $result = $this->query("SELECT `securetoken`, `update_timestemp` FROM `public_key` WHERE `id`=1 LIMIT 1;");
        if (count($result) == 0) {
            $securetoken = $this->http_get("https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com");
            if ($securetoken == "") {
                return $securetoken;
            }
            $this->insert(sprintf("INSERT INTO `public_key` (`id`, `securetoken`) VALUES (1, '%s');", urlencode($securetoken)));
            return $securetoken;
        }
        $lastUpdateTime = strtotime($result[0]["update_timestemp"]);
        $securetoken = $result[0]["securetoken"];
        $intervalTime = time() - $lastUpdateTime;
        // 判断上次更新时间间隔小于20分钟
        if ($intervalTime < 20 * 60) {
            return urldecode($securetoken);
        }
        $securetoken = $this->http_get("https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com");
        if ($securetoken == "") {
            return $securetoken;
        }
        $this->update(sprintf("UPDATE `public_key` SET `securetoken` = '%s' WHERE id=1;", urlencode($securetoken)));
        return $securetoken;
    }

    private function checkToken()
    {
        $json = $this->getPublicKeyJson();
        if ($json == "") {
            $this->saveLog("获取PublicKey失败");
            return false;
        }
        $result = json_decode($json);
        while (list($k, $v) = each($result)) {
            try {
                $cer = openssl_pkey_get_public($v);
                $keyData = openssl_pkey_get_details($cer);
                $key = $keyData['key'];
                $decoded = JWT::decode($this->idToken, $key, array('RS256'));
                $decoded_array = (array)$decoded;
                // 验证颁发者信息
                $time = time();
                $aud = $decoded_array["aud"];
                $decodediss = $decoded_array["iss"];
                $exp = $decoded_array["exp"];
                $firebase = (array)$decoded_array["firebase"];
                $this->signProvider = $firebase["sign_in_provider"];
                // 同一个project 并且没有过期
                if (in_array($aud, $this->projectId) && $exp > $time) {
                    $this->sub = $decoded_array["sub"];
                    return true;
                } else {
                    $out = json_encode($decoded_array, JSON_UNESCAPED_UNICODE);
                    $this->saveLog("解析token成功, time:$time exp:$exp aud:$aud iss:$decodediss signProvider:$this->signProvider 校验失败: $out");
                    return false;
                }
            } catch (Exception $e) {
                continue;
            }
        }
        $this->saveLog("解析token失败");
        return false;
    }

    private function insertUserInfo()
    {
        $sql = "INSERT INTO `user_key` (`id`, `publicKey`) 
                                VALUES ('$this->uid', '$this->publicKey') 
                                ON DUPLICATE KEY UPDATE `publicKey`='$this->publicKey';";
        $result = $this->insert($sql);
        if (!$result) {
            $this->saveLog("插入数据库失败，sql:$sql");
        }
        return $result;
    }
}