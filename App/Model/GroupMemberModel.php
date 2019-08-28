<?php
namespace  App\Model;

use App\Utility\Pool\MysqlPool;

class GroupMemberModel extends BaseModel
{
    protected $tableName = 'friend_group';

    public function insertFriendGroup($data)
    {
        $result = $this->db()->insert($this->tableName, $data);
        return $result ? $this->db()->getInsertId() : null;
    }
}