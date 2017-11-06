<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/30 0030
 * Time: 14:14
 */

class DBServer
{
    private $dbconfig=array(
        'type'=>'mysql',
        'host'=>'127.0.0.1',
        'port'=>'3306',
        'user'=>'summer',
        'pwd'=>'summer',
        'charset'=>'utf8',
        'dbname'=>'test',
    );
    private $link;
    private static $instance;
    private $data=array();

    private function __construct($params=array())
    {
        $this->initAttr($params);
        $this->connectServer();
        $this->setCharset();
        $this->selectDefaultDb();
    }

    private function initAttr($params)
    {
        //$this->dbconfig=array_merge($this->dbconfig,$params['db']);
    }

    private function connectServer()
    {
        $type=$this->dbconfig['type'];
        $host=$this->dbconfig['host'];
        $port=$this->dbconfig['port'];
        $user=$this->dbconfig['user'];
        $pwd=$this->dbconfig['pwd'];
        $charset=$this->dbconfig['charset'];
        $dsn="$type:host=$host;port=$port;charset=$charset";
        if($link=new PDO($dsn,$user,$pwd))
        {
            $this->link=$link;
        }
        else
        {
            die('数据库连接失败,请与管理员联系');
        }
    }

    private function setCharset()
    {
        $sql="set names {$this->dbconfig['charset']}";
        $this->query($sql);
    }

    private function selectDefaultDb()
    {
        if($this->dbconfig['dbname']=='') return;
        $sql="use `{$this->dbconfig['dbname']}`";
        $this->query($sql);
    }

    public function insertQuery($sql,$batch=false)
    {
        $data= $batch? $this->data : array($this->data);
        $this->data=array();
        $stmt=$this->link->prepare($sql);
        foreach ($data as $v)
        {
            if($stmt->execute($v)===false)
            {
                die('数据库操作失败，请与管理员联系');
            }
        }
        return $stmt;
    }

    public function createData($data)
    {
        $this->data=$data;
        return $this;
    }

    private function __clone(){}

    public function fetchRow($sql)
    {
        return $this->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll($sql)
    {
        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function query($sql)
    {
        return $this->link->query($sql);
    }

    public function exec($sql)
    {
        return $this->link->exec($sql);
    }

    public static function getInstance($params=array())
    {
        if(!self::$instance instanceof self)
        {
            self::$instance=new self($params);
        }
        return self::$instance;
    }

}