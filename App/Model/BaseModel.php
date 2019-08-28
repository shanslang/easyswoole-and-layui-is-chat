<?php
/**
 * CreateTime: 2019/7/15 10:55
 * Author: hhh
 * Description:
 */
namespace App\Model;

use EasySwoole\Mysqli\Mysqli;

abstract class BaseModel
{
    private $db;

    protected $tableName;
    protected $pk;

    public function __construct(Mysqli $db)
    {
        $this->db = $db;
    }

    protected function db(): Mysqli
    {
        return $this->db;
    }
}