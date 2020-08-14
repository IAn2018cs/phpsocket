<?php


class QueryOnlineApi extends BaseApi
{

    public function getResult()
    {
        $result = $this->query("SELECT `id`, `publicKey` FROM `user_key` WHERE `online`=1;");

        if (empty($result)) {
            return $this->fail(1, "no data");
        }

        $userInfos = array();
        foreach ($result as $info) {
            $userId = $info["id"];
            $key = $info["publicKey"];

            array_push($userInfos, array(
                "userId" => $userId,
                "publicKey" => $key
            ));
        }

        return $this->success($userInfos);
    }
}