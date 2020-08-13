<?php


class DeleteGroupApi extends BaseApi
{

    public function getResult()
    {
        $groupId = $this->getParameter('group', '');

        if (empty($groupId)) {
            return $this->fail(4, "parameter error");
        }

        $sql = "DELETE FROM `user_group` WHERE `id` = $groupId;";
        $sql .= "DELETE FROM `group_member` WHERE `group_id` = $groupId;";

        if ($this->delete($sql)) {
            return $this->success(null);
        }

        return $this->fail(6, "delete fail");
    }
}