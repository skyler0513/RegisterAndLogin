<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/31 0031
 * Time: 17:13
 */

require_once './Base.php';
require_once './validater.php';
require_once './CreateIdentifyingCode.php';

class Login extends Base
{
    protected $codeImagePath  = __DIR__.'/image/';
    protected $maxLoginTimes = 7;
    //如果登录失败两次，那么第三次出验证码
    protected $showIdentifyCode = 2;
    public function handle()
    {
        //先检查session里面有没有user id
        //$this->bySession();
        //检查有没有remember key
        //只有通过账号密码登录时才会有“记住我”的选项
        //如果验证成功就不会返回而是直接进入loginSucceed函数
        $this->byRememberKey();
        //通过账户密码登录
        //如果验证成功就不会返回而是直接进入loginSucceed函数
        $this->byAccountAndPassword();

        $this->loginFail(stdOutPut([],'authorize failed!',0));
    }

    /**
     * 验证表单
     */
    public function validate()
    {
        //表单必需传过来的字段
        $require = [$this->identifier,'password'];

        //验证码
        if(isset($_SESSION['user']['code']))
            array_unshift($require,'code');

        //验证表单
        $result = $this->validater->checkForm($require);

        //如果表单验证失败就进入登录失败的程序
        if($result['code'] == 0)
            $this->loginFail($result);
        //如果验证成功那么validater会返回一个字符串$result['data']，这个字符串
        //可以确定用户是使用手机还是邮箱登录
        else $this->type = $result['data'];
    }

    /**
     *检查session
     */
    public function bySession()
    {
        //如果session里面存在user id 那么直接登录成功
        if(!is_null($_SESSION['user']['id']))
        {
            //直接返回登录成功
            $this->jsonReturn(stdOutPut([],'login successfully',1));
        }
    }

    /**
     * @return array
     * 根据账户和密码登录
     */
    public function byAccountAndPassword()
    {
        //账号密码登录必须用post
        if(strtolower($_SERVER['REQUEST_METHOD']) != 'post')
            return stdOutPut('','请使用post方式提交表单',0);

        //如果没有用户名和密码就直接返回了
        if(is_null($_REQUEST[$this->identifier]) || is_null($_REQUEST['password'])) return;

        //检查用户登录失败的次数
        $this->checkLoginFail();
        //验证表单
        $this->validate();
        $account = $_REQUEST[$this->identifier];
        $password = $_REQUEST['password'];

        $result = $this->user->login($this->type,$account,$password);

        if($result['code'] == 0) return $result;
        else $this->loginSucceed($result['data']);
    }

    /**
     * @return array
     * 根据remember key登录
     * 通过remember key 我们可以从redis中找到存储的用户名和密码
     * 然后根据用户名密码验证
     */
    public function byRememberKey()
    {
        if(is_null($rememberKey = $_COOKIE[$this->rememberKey]))
            return stdOutPut([],'invaliad remember key',0);

        //redis hash中的key
        $keys = $this->redis->hKeys($rememberKey);
        if(empty($keys)) return stdOutPut([],'invaliad remember key',0);

        //redis hash中的value
        $vals = $this->redis->hVals($rememberKey);

        //根据用户名和密码验证的结果
        //在这之前需要先给密码解密
        $result = $this->user->login($keys[0],$vals[0],$this->rememberPasswordDecrypt($vals[1]));

        if($result['code'] == 0)
        {
            //认证失败就删除cookie中的remember key
            $this->invaliateRememberKey($rememberKey);
            //返回
            return $result;
        }
        //登录成功
        else
        {
            $this->loginSucceed($result['data']);
        }

    }

    /**
     * @param $user
     * @return string
     * 记住用户
     */
    protected function rememberUser($user)
    {
        $account = $user[$this->type];
        //在存入redis之前要把用户的密码加密
        $password = $this->rememberPasswordEncrypt($_REQUEST['password']);

        //key值是随机的
        $key = $this->getRandomKey();

        //将用户的邮箱或者电话存入redis的hash结构中
        $this->redis->hSet($key,$this->type,$account);
        $this->redis->hSet($key,'password',$password);
        $this->redis->expire($key,$this->rememberTime);

        //将remember key写入cookie
        setcookie($this->rememberKey,$key,time()+$this->rememberTime);
    }

    /**
     * @param $key
     * 从redis中删除remember key并设置cookie过期
     */
    protected function invaliateRememberKey($key)
    {
        $this->redis->del($key);
        setcookie($this->rememberKey,'',time()-3600);
    }


    /**
     * @param $user
     * 登录成功
     * $user是user表中的一条记录
     */
    protected function loginSucceed($user)
    {
        //如果有share session就把当前的session id变为存储在redis中的share session key
        //如果没有就保存
        //在保存share session后再向session中写入数据
        //因此只要session中有user的id 那么肯定有share session存在
        $this->saveShareSessionId($user);

        //删除本地的验证码图片
        $this->delCodeImage();
        //删除记录用户登录失败次数的key
        $this->delLoginFailKey();

        //记住我功能
        if($_REQUEST['remember'] == 1)
            $this->rememberUser($user);

        $this->jsonReturn(stdOutPut([],'login successfully',1));
    }

    /**
     * @param $info
     * 登录失败
     * $info是一个数组，里面包含了错误信息和状态码
     */
    protected function loginFail($result)
    {
        $this->handleLoginFail($result);
        $this->jsonReturn($result);
    }

    /**
     * @param $result
     * 对用户的登录失败进行处理
     * 在用户登录失败后进行处理
     */
    protected function handleLoginFail(&$result)
    {
        $value = $this->increLoginFailNums();

        if($value <= $this->showIdentifyCode) return;

        //发送验证码
        if($value > $this->showIdentifyCode && $value < $this->maxLoginTimes )
        {
            //产生验证码，生成验证码图片并将验证码写入session
            $code = $this->getIdentifyCode();
            //验证码
            $result['data']['code'] = $_SESSION['user']['code'] = $code['code'];
            //验证码的图片地址
            $result['data']['codeImageUrl'] = $_SESSION['user']['codeImageUrl'] = $code['codeImageUrl'];
        }

        if($value == $this->maxLoginTimes)
        {
            //冻结账户
            $this->blockAccount();
            //直接返回账户被锁定
            $this->returnAccountBlocked();
        }
    }

    /**
     * 增加用户登录失败的次数
     */
    protected function increLoginFailNums()
    {
        if(is_null($_REQUEST[$this->identifier])) return;

        $key = $this->getLoginFailKey();

        $this->redis->incr($key);

        return $this->redis->get($key);
    }

    /**
     * @return string
     * 获取记录用户登录失败次数的key
     */
    protected function getLoginFailKey()
    {
        $user = $_REQUEST[$this->identifier].$_REQUEST["REMOTE_ADDR"];
        return $this->getPrefixKey('loginfail',$user);
    }

    /**
     * @return mixed
     * 获取一个账号登录失败的次数
     */
    protected function getLoginFailNum()
    {
        $key = $this->getLoginFailKey();
        return (int)$this->redis->get($key);
    }

    /**
     * 暂时冻结账户
     */
    protected function blockAccount()
    {
        $key = $this->getLoginFailKey();
        $this->redis->expireAt($key,time()+120);
    }

    /**
     * 返回账号被锁定
     */
    protected function returnAccountBlocked()
    {
        //账号剩余的冻结日期
        $expire = ceil($this->redis->ttl($this->getLoginFailKey())/60);
        $msg = "your account has been locked.please relogin after $expire minutes";
        $this->jsonReturn(stdOutPut([],$msg,0));
    }
    
    /**
     * 检查账户登录失败的次数
     * 在用户使用账号密码前检查
     */
    protected function checkLoginFail()
    {
        //获取当前帐号登录失败的次数
        $value = $this->getLoginFailNum();

        if($value == $this->maxLoginTimes)
            //直接返回账号被冻结
            $this->returnAccountBlocked();

        //第三次错误登录才会出验证码
        //这里这样做是为了避免之前的session数据干扰
        //尤其是当账号的冻结时间小于session的时间时
        if($value <= $this->showIdentifyCode)
        {
            if(isset($_SESSION['user']['code']))
                unset($_SESSION['user']['code']);
        }
    }

    /**
     * @return array
     * 产生验证码
     */
    protected function getIdentifyCode()
    {
        //先删除上一个过期的验证码图片
        $this->delCodeImage();
        $codeImage = new CreateIdentifyingCode(100,50,4,'imagick');

        //图片的名称
        $name = $this->getRandomKey().'.jpg';
        $path = $this->codeImagePath.$name;
        $codeImage->save($path);

        return ['code'=>$codeImage->getCode(),'codeImageUrl'=>$this->getCodeImageUrl($name)];
    }

    /**
     * @param $name
     * @return string
     * 获得验证码的url
     */
    protected function getCodeImageUrl($name)
    {
        //确定协议
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

        return $http_type.$_SERVER['SERVER_NAME'].'/image/'.$name;
    }

    /**
     * 删除过期的验证码
     */
    protected function delCodeImage()
    {
        if(isset($_SESSION['user']['codeImageUrl']))
        {
            $path = $this->convertUrlToPath($_SESSION['user']['codeImageUrl']);
            if(file_exists($path))
                unlink($path);
        }
    }

    /**
     * @param $url
     * @return string
     * 将url转换为本地文件路径
     */
    protected function convertUrlToPath($url)
    {
        $uri = substr($url,strpos($url,'/image'));
        return __DIR__.$uri;
    }

    /**
     * 删除记录用户登录错误次数的key
     */
    protected function delLoginFailKey()
    {
        $key = $this->getLoginFailKey();
        $this->redis->del($key);
    }
}

$a = 3;
$obj = new Login();
$obj->handle();