<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/30 0030
 * Time: 14:14
 */

class RedisServer
{
    private $host;
    private $port;
    private $auth;
    public $redis;

    public function __construct()
    {
        $config = require_once "./config.php";
        $this->host = $config['redis']['host'];
        $this->port = $config['redis']['port'];
        $this->auth = $config['redis']['auth'];
        //连接
        $this->connect();
    }

    public function connect()
    {
        $this->redis = new Redis();
        $this->redis->connect($this->host,$this->port) or die ('redis connect failed');
    }

    public function get($key)
    {
        return $this->redis->get($key);
    }

    public function set($key,$value)
    {
        return $this->redis->set($key,$value);
    }

    public function incr($key)
    {
        $this->redis->incr($key);
    }

    public function del($key)
    {
        if($this->redis->exists($key))
            $this->redis->del($key);
    }
    public function expire($key,$time)
    {
        $this->redis->expire($key,$time);
    }

    public function has($key)
    {
        if($this->redis->exists($key)) return true;
        else return false;
    }

    public function setex($k,$v,$expire)
    {
        return $this->redis->setex($k,$expire,$v);
    }

    public function hSet($k,$f,$v)
    {
        return $this->redis->hSet($k,$f,$v);
    }

    public function hKeys($key)
    {
        return $this->redis->hKeys($key);
    }

    public function hVals($key)
    {
        return $this->redis->hVals($key);
    }

    public function ttl($key)
    {
        return $this->redis->ttl($key);
    }

    public function expireAt($key,$time)
    {
        return $this->redis->expireAt($key,$time);
    }
}

