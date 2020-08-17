<?php


class QueryGroupApi extends BaseApi
{

    public function getResult()
    {
        $userId = $this->getParameter('userId', '');

        if (empty($userId)) {
            return $this->fail(4, "parameter error");
        }

        $groupInfos = $this->query("SELECT `id`, `name`, `owner_id`, `share_code` FROM `user_group` WHERE `id` IN 
                (SELECT DISTINCT `group_id` FROM `group_member` WHERE `user_id` = '$userId');");

        if (empty($groupInfos)) {
            return $this->fail(1, "no data");
        }

        $groups = array();
        foreach ($groupInfos as $info) {
            $groupId = $info["id"];
            $groupName = $info["name"];
            $ownerId = $info["owner_id"];
            $shareCode = $info["share_code"];

            $membersResult = $this->query("SELECT `id`, `publicKey`, `type` FROM `user_key` LEFT JOIN `group_member` 
                                                ON `user_key`.`id` = `group_member`.`user_id` WHERE `group_id` = $groupId;");
            $members = array();
            foreach ($membersResult as $item) {
                array_push($members, array("userId" => $item["id"], "publicKey" => $item["publicKey"], "type" => $item["type"]));
            }

            array_push($groups, array(
                "groupId" => $groupId,
                "groupName" => $groupName,
                "owner" => $ownerId,
                "shareCode" => $shareCode,
                "members" => $members
            ));
        }

        return $this->success($groups);
    }
}