<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/2 0002
 * Time: 10:59
 */
require_once './Base.php';

class Logout extends Base
{
    public function handle()
    {
        $this->deleteShareSession();
        $this->deleterRememberKey();
        $this->invaliateCookie();
        session_destroy();
        $this->logoutSucceed();
    }

    /**
     * 删除共享session
     */
    public function deleteShareSession()
    {
        if(is_null($_SESSION['user']['id'])) return;

        $key = $this->getShareSessionKey($_SESSION['user']['id']);

        $this->redis->del($key);
    }

    /**
     * 删除rememberme 的数据
     */
    public function deleterRememberKey()
    {
        if(isset($_COOKIE[$this->rememberKey]))
            $this->redis->del($_COOKIE[$this->rememberKey]);
    }

    /**
     *使cookie无效
     */
    public function invaliateCookie()
    {
        setcookie(session_name(),'',time()-3600);
        setcookie($this->rememberKey,'',time()-3600);
    }

    public function logoutSucceed()
    {
        $this->jsonReturn(stdOutPut([],'logout sussessfully',1));
    }
}

$obj = new Logout();
$obj->handle();