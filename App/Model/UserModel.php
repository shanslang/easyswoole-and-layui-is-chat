<?php
/**
 * CreateTime: 2019/7/15 11:01
 * Author: hhh
 * Description:
 */
namespace App\Model;

use App\Utility\Pool\MysqlPool;

class UserModel extends BaseModel
{
    protected  $tableName = 'user';
    protected  $pk = 'id';

    public function create(array $data): ?int
    {
       // $db = MysqlPool::defer();
        $id = $this->db()->insert($this->tableName, $data);
        return $id ? $id : null;
    }
  
    public function getUserByUsername($username)
    {
        if(empty($username)){
        	return [];
        }
        $this->db()->where('username', $username);
        $result = $this->db()->getOne('user');  // user是表名
        return $result ?? [];
    }
  
    public function isNickName($nickName)
    {
        if(empty($nickName)){
            return [];
        }
        $this->db()->where('nickname', $nickName);
        $result = $this->db()->get('user', null, 'id');
        return $result ?? [];
    }
  
    public function insertUser($insert) {
        $result = $this->db()->insert($this->tableName,$insert);
        return $result ? $this->db()->getInsertId() : null;
    }
  
    public function editUser($data, $id){
        $res    = $this->db()->where('id', $id)->update($this->tableName, $data);
        if($res){
            $result = $this->db()->where('id', $id)->getOne($this->tableName);
            return $result ?? [];
        }else{
            return [];
        }
        
    }
}