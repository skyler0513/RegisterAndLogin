<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/30 0030
 * Time: 14:13
 */

require_once './helpers.php';
require_once './DBServer.php';
require_once './RedisServer.php';
require_once './UserModel.php';
require_once './validater.php';

class Base
{
    protected $redis;
    protected $validater;
    protected $user;
    //email或phone的字段名
    protected $identifier = 'identifier';
    public $type;
    public $sessionLifeTime = 1440;
    //记住我的时间
    public $rememberTime = 1296000;
    public $rememberKey = 'rememberme';

    public function __construct()
    {
        $this->redis = new RedisServer();
        $this->validater = new validater();
        $this->user = new UserModel();
        $this->validater->setIdentifierField($this->identifier);
    }

    /**
     * @param $data
     * @param $msg
     * @param $code
     * 返回
     */
    protected function jsonReturn($result)
    {
        header('Content-type: application/json');
        echo json_encode($result);
        exit();
    }

    /**
     * @param $record
     * 用户登录成功后将用户信息写入session
     */
   protected function writeInfoInSession($user)
    {
        if(is_null($_SESSION['user']['id']))
            $_SESSION['user']['id'] = $user['id'];

        if(is_null($_SESSION['user']['name']))
            $_SESSION['user']['name'] = $user['name'];
    }

    /**
     * @param $userId
     * 将用户的sessionId存入redis中并写入session
     */
    protected function saveShareSessionId($user)
    {
        $key = $this->getShareSessionKey($user['id']);

        //如果之前在其他的客户端已经存储了就不用再存储
        if($this->redis->has($key))
        {
            //我习惯把session设置为自动开启，因此现在这里删除掉此次session
            //然后再把已经存在的session放到http的头部中
            header_remove('Set-Cookie');
            setcookie(session_name(),$this->redis->get($key));
            return;
        }
        //session_regenerate_id();
        //存入redis
        $this->redis->setex($key,session_id(),$this->sessionLifeTime);
        //写入session
        $this->writeInfoInSession($user);
    }

    /**
     * @param $userId
     * @return string
     * 获取share session 的key,是由用户的id和一个固定的前缀获得的
     */
    public function getShareSessionKey($userId)
    {
        return $this->getPrefixKey('sharesession',$userId);
    }
    /**
     * @param $userId
     * @return string
     * 获取根据prefix,key进行加密
     */
    protected function getPrefixKey($prefix,$key)
    {
        return md5($prefix.$key);
    }

    /**
     * @return string
     * 获取一个随机的key
     */
    protected function getRandomKey()
    {
        return md5(uniqid());
    }

    /**
     * @param $password
     * @return string
     * 记住我功能的密码加密
     */
    protected function rememberPasswordEncrypt($password)
    {
        return base64_encode($password);
    }

    /**
     * @param $encrypt
     * @return bool|string
     * 记住我功能的密码解密
     */
    protected function rememberPasswordDecrypt($encrypt)
    {
        return base64_decode($encrypt);
    }

}