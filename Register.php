<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/30 0030
 * Time: 14:12
 */

require_once './Base.php';
require_once './validater.php';

class Register extends Base
{
    public function handle()
    {
        if(strtolower($_SERVER['REQUEST_METHOD']) != 'post')
            return stdOutPut([],'请使用post方式提交表单',0);
        //$this->checkLogin();
        $this->validate();
        $this->checkExists();
        $result = $this->createUser();

        if($result['code'] == 0)
            $this->registerFail($result);
        else $this->registerSucceed($result);
    }

    /**
     * 如果存在'特殊的'cookie,那么就要先转到登录
     */
    public function checkLogin()
    {
        if(isset($_COOKIE[session_name()]) || isset($_COOKIE['rememberme']))
            header("location: http://myauth.com/login.php");
    }

    /**
     *校验表单
     */
    public function validate()
    {
        //表单必须传过来的字段
        $require = ['name',$this->identifier,'password','confirm'];
        $result = $this->validater->checkForm($require);
        if($result['code'] == 0)
            $this->registerFail($result);
        else $this->type = $result['data'];
    }

    /**
     *检查邮箱或手机是否注册过
     */
    public function checkExists()
    {
        $result = $this->user->checkUserExists($this->type,$_REQUEST[$this->identifier]);

        if($result['code'] == 0)
            $this->registerFail($result);
    }

    /**
     * 向数据库插入用户
     */
    public function createUser()
    {
        return $this->user->createUser($this->type,$_REQUEST[$this->identifier]);
    }

    /**
     * @param $record
     * 注册成功
     */
    public function registerSucceed($info)
    {
        //存储共享session
        $this->saveShareSessionId($info['data']['id']);
        //将新用户的信息写入session
        $this->writeInfoInSession($info['data']);

        $this->jsonReturn(stdOutPut([],'register successfully',1));
    }

    /**
     * @param $data
     * @param $msg
     * @param $code
     * 注册失败
     */
    public function registerFail($info)
    {
        $this->jsonReturn(stdOutPut([],$info['msg'],0));
    }
}

$obj = new Register();
$obj->handle();