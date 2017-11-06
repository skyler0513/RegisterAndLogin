<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/30 0030
 * Time: 14:50
 */

require_once './helpers.php';

class validater
{
    protected $errorMsg = '';
    protected $type = 'email';
    protected $identifier = 'identifier';

    //用户名可以为字母数字汉字下划线但是不能以下划线开始和结束
    protected $namePattern = '/^(?!_)(?!.*?_$)[\x{4e00}-\x{9fa5}\w]{1,80}$/u';
    protected $emailPattern = '/^[\w\-\.]+@[\w\-]+[\.\w+]+$/';
    protected $phonePattern = '/^[1][3,4,5,7,8][0-9]{9}$/';
    protected $passPattern = '/^(?!_)(?!.*?_$)\w{6,16}$/';

    /**
     * @param $field
     * 设定邮箱或手机的字段名
     */
    public function setIdentifierField($field)
    {
        $this->identifier = $field;
    }
    /**
     * @param $para
     * @return array
     * 检查注册时的表单
     */
    public function checkForm($para)
    {
        //过滤数据
        $this->filter($para);
        //必须字段不能为空
        $this->checkNull($para);
        //确认是根据手机还是邮箱
        $this->checkIdentifier($_REQUEST[$this->identifier]);
        //对必需的字段进行正则验证
        $this->checkPattern($para);

        if(!empty($this->errorMsg))
        {
            $errorMsg = $this->errorMsg;
            $this->errorMsg = '';
            return $this->returnError([],$errorMsg,0);
        }
        else return $this->returnOk($this->type,'ok',1);
    }

    /**
     * @param $para
     * 过滤用户传过来的数据
     */
    public function filter($para)
    {
        foreach ($para as $k=>$v)
        {
            $_REQUEST[$v] = $this->filterMethod($_REQUEST[$v]);
        }
    }

    /**
     * @param $value
     * @return string
     * 过滤方法
     */
    public function filterMethod($value)
    {
        return htmlspecialchars($value);
    }
    /**
     * @param $para
     * 对必须的字段进行正则匹配
     */
    public function checkPattern($para)
    {
        if(!empty($this->errorMsg)) return;

        foreach ($para as $k=>$v)
        {
            //因为identifier已经在checkIdentifier中检验过了,再次不需要再检查
            if($v != $this->identifier)
            {
                $reflectionMethod = new ReflectionMethod($this, 'check'.ucfirst($v));
                $reflectionMethod->invoke($this, $_REQUEST[$v]);
            }
        }
    }

    /**
     * @param $identifier
     * 确定是根据邮箱还是手机登录
     * 初始值默认是邮箱
     */
    public function checkIdentifier($value)
    {
        if(!empty($this->errorMsg)) return;

        if(preg_match($this->emailPattern,$value))
            return;

        if(preg_match($this->phonePattern,$value))
        {
            $this->type = 'phone';
            return;
        }

        $this->errorMsg = 'invaliad email or phone format';
    }

    /**
     * @param $para
     * 检查必需的字段是否为null
     */
    public function checkNull($para)
    {
        if(!empty($this->errorMsg)) return;

        foreach ($para as $k=>$v)
        {
            if(is_null($_REQUEST[$v]))
            {
                if($v == $this->identifier)
                {
                    $this->errorMsg = 'email or phone is necessary';
                }
                else $this->errorMsg = $v.' can not be empty!';
                break;
            }
        }
    }

    /**
     * @param $name
     * 检查用户名
     */
    public function checkName($name)
    {
        if(!empty($this->errorMsg)) return;

        if(!preg_match($this->namePattern,$name))
            $this->errorMsg = 'invaliad user nickname format';
    }

    /**
     * @param $email
     * 校验邮箱
     */
    public function checkEmail($email)
    {
        if(!empty($this->errorMsg)) return;

        if(!preg_match($this->emailPattern,$email))
            $this->errorMsg = 'invaliad email format!';
    }

    /**
     * @param $phone
     * 校验手机号
     */
    public function checkPhone($phone)
    {
        if(!empty($this->errorMsg)) return;

        if(!preg_match($this->phonePattern,$phone))
            $this->errorMsg = 'invaliad phone format!';
    }

    /**
     * @param $password
     * 校验密码
     */
    public function checkPassword($password)
    {
        if(!empty($this->errorMsg)) return;

        if(!preg_match($this->passPattern,$password))
            $this->errorMsg = 'invaliad password format!';
    }

    /**
     * @param $password
     * @param $confirm
     * 校验两次输入的密码
     */
    public function checkConfirm()
    {
        if(!empty($this->errorMsg)) return;

        if($_REQUEST['password'] != $_REQUEST['confirm'])
            $this->errorMsg = 'two input password is not consistent!';
    }

    /**
     * 验证码
     */
    public function checkCode()
    {
        if(!empty($this->errorMsg)) return;

        if(strtolower($_REQUEST['code']) != strtolower($this->getIdentifyCode()))
            $this->errorMsg = 'code invalid';
    }

    /**
     * @return mixed
     * 获取验证码
     */
    public function getIdentifyCode()
    {
        return $_SESSION['user']['code'];
    }

    /**
     * @return array
     */
    public function returnError($data,$msg,$code=0)
    {
        return stdOutPut($data,$msg,$code);
    }

    /**
     * @return array
     */
    public function returnOk($data,$msg,$code=1)
    {
        return stdOutPut($data,$msg,$code);
    }

}