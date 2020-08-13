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

        if (empty($groupInfo)) {
            return $this->fail(1, "no data");
        }

        $groups = array();
        foreach ($groupInfos as $info) {
            $groupId = $info["id"];
            $groupName = $info["name"];
            $ownerId = $info["owner_id"];
            $shareCode = $info["share_code"];

            $membersResult = $this->query("SELECT `user_id`, `type` FROM `group_member` WHERE `group_id` = $groupId;");
            $members = array();
            foreach ($membersResult as $item) {
                array_push($members, array("userId" => $item["user_id"], "type" => $item["type"]));
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