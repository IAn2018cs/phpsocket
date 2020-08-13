<?php


class CreateGroupApi extends BaseApi
{

    public function getResult()
    {
        $owner = $this->getParameter('owner', '');
        $name = $this->getParameter('name', '');

        if (empty($owner) || empty($name)) {
            return $this->fail(4, "parameter error");
        }

        $shareCode = $this->createShareCode();
        $sql = "INSERT INTO `user_group` (`owner_id`, `name`, `share_code`) 
                                VALUES ('$owner', '$name', '$shareCode');";

        mysqli_query($this->conn, $sql);
        $groupId = mysqli_insert_id($this->conn);

        $sql = "INSERT INTO `group_member` (`group_id`, `user_id`, `type`) 
                                VALUES ($groupId, '$owner', 1);";

        if (empty($groupId) || !$this->insert($sql)) {
            return $this->fail(5, "insert fail");
        }

        return $this->success(array("groupId" => $groupId, "shareCode" => $shareCode));
    }

    private function createShareCode()
    {
        $code = strtoupper(sprintf('%x', crc32(microtime())));
        if (strlen($code) == 8) {
            return $code;
        }
        return $this->createShareCode();
    }
}