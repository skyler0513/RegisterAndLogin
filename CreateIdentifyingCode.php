<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/2 0002
 * Time: 18:56
 */

include_once './vendor/autoload.php';

use Intervention\Image\ImageManager as Image;

class CreateIdentifyingCode
{
    private $img;
    private $manager;
    private $code;
    private $width;
    private $height;
    private $length;
    private $textArray = [
        1,2,3,4,5,6,7,8,9,'a','b','c','d','e','f','g','h','i','j','k','l','m','n', 'p',
        'q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I', 'J',
        'K','L','M','N','P','Q','R','S','T','U','V','W','X','Y','Z'
    ];

    public function __construct($width,$height,$length,$driver='gd')
    {
        //验证码图片的宽度
        $this->width = $width;
        //验证码图片的高度
        $this->height = $height;
        //验证码的字符长度
        $this->length = $length;

        //管理器对象
        $this->manager = new Image(array('driver'=>$driver));
        //画布对象
        $this->img = $this->manager->canvas($width,$height,$this->randColor());
        //获取文字
        $this->code =$this->code($length);

        $this->product();
    }

    public function code($length)
    {
        if($length>$count=count($this->textArray)) die('text too long');

        $str = '';
        for ($i=0;$i<$length;$i++)
        {
            $str.= $this->textArray[mt_rand(0,$count-1)];
        }

        return $str;
    }
    /**
     *生成文字
     */
    public function productCode()
    {
        $this->img->text($this->code,0.5*$this->width,0.25*$this->width,function ($font)
        {
            $fontSize = floor($this->width/$this->length+1);
            //echo $fontSize;
            $font->file('./fonts.ttf');
            $font->size($fontSize);
            $font->color($this->randColor());
            $font->align('center');
            $font->valign('center');
            $font->angle(mt_rand(0,30));
        });
    }

    /**
     * 生成像素点
     */
    public function productPixel()
    {
        $add = $this->width + $this->height;
        $nums = mt_rand(($add/2),$add);
        for($i=0;$i<$nums;$i++)
        {
            $this->img->pixel($this->randColor(),mt_rand(0,$this->width),mt_rand(0,$this->height));
        }
    }

    /**
     * 线条
     */
    public function productLine()
    {
        $nums = mt_rand(3,5);
        for($i=0;$i<$nums;$i++)
        {
            //起点
            $startX = mt_rand(0,$this->width);
            $startY = mt_rand(0,$this->height);
            //终点
            $endX = mt_rand(0,$this->width);
            $endY = mt_rand(0,$this->height);
            $this->img->line($startX,$startY,$endX,$endY,function ($draw){
                $draw->color($this->randColor());
            });
        }
    }

    public function product()
    {
        $this->productCode();
        $this->productLine();
        $this->productPixel();
    }

    /**
     * @return string
     * 获取随机颜色
     */
    public function randColor()
    {
        $str='0123456789ABCDEF';
        $color='#';
        $len=strlen($str);
        for($i=1;$i<=6;$i++)
        {
            $num=rand(0,$len-1);
            $color=$color.$str[$num];
        }
        return $color;
    }

    /**
     * @return string
     * 获取文字
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param $path
     * 保存图片
     */
    public function save($path)
    {
        $this->img->save($path);
    }

    /**
     * @param string $format
     * 直接输出
     */
    public function response($format='jpg')
    {
        echo $this->img->response($format);
    }
}

