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

        $sql = "INSERT INTO `group_member` (`group_id`, `user_id`, `type`) 
                                VALUES ($groupId, '$userId', 2);";

        if ($this->insert($sql)) {
            return $this->success(array("groupId" => $groupId, "groupName" => $groupName, "owner" => $ownerId));
        }

        return $this->fail(5, "insert fail");
    }
}