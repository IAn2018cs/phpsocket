<?php


class JoinGroupApi extends BaseApi
{

    public function getResult()
    {
        $userId = $this->getParameter('userId', '');
        $shareCode = $this->getParameter('shareCode', '');

        if (empty($userId) || empty($shareCode)) {
            return $this->fail(4, "parameter error");
        }

        $groupInfo = $this->query("SELECT `id`, `name`, `owner_id` FROM `user_group` WHERE `share_code` = '$shareCode';");
        if (empty($groupInfo)) {
            return $this->fail(4, "parameter error no data");
        }
        $groupId = $groupInfo[0]["id"];
        $groupName = $groupInfo[0]["name"];
        $ownerId = $groupInfo[0]["owner_id"];

        // TODO 注意判断群员上限
        $sql = "INSERT INTO `group_member` (`group_id`, `user_id`, `type`) 
                                VALUES ($groupId, '$userId', 2);";

        if ($this->insert($sql)) {
            $membersResult = $this->query("SELECT `id`, `publicKey`, `type` FROM `user_key` LEFT JOIN `group_member` 
                                                ON `user_key`.`id` = `group_member`.`user_id` WHERE `group_id` = $groupId;");
            $members = array();
            foreach ($membersResult as $item) {
                array_push($members, array("userId" => $item["id"], "publicKey" => $item["publicKey"], "type" => $item["type"]));
            }

            return $this->success(array(
                "groupId" => $groupId,
                "groupName" => $groupName,
                "owner" => $ownerId,
                "shareCode" => $shareCode,
                "members" => $members
            ));
        }

        return $this->fail(5, "insert fail");
    }
}