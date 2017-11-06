<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/30 0030
 * Time: 16:25
 */

class UserModel
{
    protected $db;
    public function __construct()
    {
        $config = require_once './config.php';
        $this->db = DBServer::getInstance($config['db']);
    }

    /**
     * @param $type
     * @param $value
     * @return array
     */
    public function checkUserExists($type,$value)
    {
        $sql = "select * from users where $type='$value'";
        $record = $this->db->fetchAll($sql);

        if($record == false) return stdOutPut('','ok',1);
        else return stdOutPut('',"this $type has been registered",0);
    }

    /**
     * @param $type
     * @param $value
     * @return array
     * 检查是否存在用户
     */
    public function createUser($type,$value)
    {
        $name = $_REQUEST['name'];
        $created_at = date('Y-m-d H:i:s',time());
        $password = $this->getPassword($_REQUEST['password']);

        $sql = "insert into users(name,$type,password,created_at)value('$name','$value','$password','$created_at')";

        $result = $this->db->exec($sql);

        if($result)
        {
            $sql = "select * from users where $type='$value'";
            $record = $this->db->fetchRow($sql);

            return stdOutPut($record,'ok',1);
        }
        else return stdOutPut('','register failed',0);
    }

    /**
     * @param $pasword
     * @return bool|string
     * 密码加密
     */
    public function getPassword($pasword)
    {
        return password_hash($pasword,PASSWORD_DEFAULT);
    }

    public function checkPassword($password,$hash)
    {
        return password_verify($password,$hash);
    }

    /**
     * @param $type
     * @param $account
     * @param $password
     * @return array
     * 根据用户名和密码登录
     */
    public function login($type,$account,$password)
    {
        $sql = "select * from users where $type='$account'";

        $record = $this->db->fetchRow($sql);

        if($record == false) return stdOutPut('','this account do not exists',0);
        if(!$this->checkPassword($password,$record['password']))
            return stdOutPut('','account or password wrong',0);

        return stdOutPut($record,'ok',1);
    }
}