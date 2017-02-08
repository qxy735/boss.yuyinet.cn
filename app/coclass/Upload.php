<?php

class Upload
{
    /**
     * 允许的图片类型
     *
     * @var string
     */
    protected $allowExt = 'jpg,jpeg,gif,bmp,png,doc,docx,zip,rar,txt';
    /**
     * 最大文件大小,10M,M为单位
     *
     * @var int
     */
    protected $maxSize = 10;
    /**
     * 错误码
     *
     * @var int
     */
    protected $errno = 0;
    /**
     * 图片访问地址
     *
     * @var string
     */
    protected $url = '';
    /**
     * 错误信息
     *
     * @var array
     */
    protected $error = array(
        0 => '文件不存在',
        1 => '上传文件超出系统限制',
        2 => '上传文件大小超出网页表单页面',
        3 => '文件只有部分被上传',
        4 => '没有文件被上传',
        6 => '找不到临时文件夹',
        7 => '文件写入失败',
        8 => '不允许的文件后缀',
        9 => '文件大小超出的类的允许范围',
        10 => '创建目录失败',
        11 => '移动失败'
    );

    /**
     * 上传文件
     *
     * @param        $key
     * @param string $bucket
     * @param string $dir
     *
     * @return bool|string
     */
    public function make($key, $bucket = 'article', $dir = '')
    {
        if (!isset($_FILES[$key])) {
            return false;
        }

        $f = $_FILES[$key];

        // 检验上传有没有成功
        if ($f['error']) {
            $this->errno = $f['error'];

            return false;
        }

        // 获取后缀
        $ext = $this->getExt($f['name']);

        // 检查后缀
        if (!$this->isAllowExt($ext)) {
            $this->errno = 8;

            return false;
        }

        // 检查大小
        if (!$this->isAllowSize($f['size'])) {
            $this->errno = 9;

            return false;
        }

        //创建目录
        if ($dir) {
            $path = dirname(__DIR__) . "/..{$dir}";

            is_dir($path) or mkdir($path, 0777, true);

            $this->url = $dir;

            $dir = $path;
        } else {
            $dir = $this->mk_dir($bucket);
        }

        if ($dir == false) {
            $this->error = 10;

            return false;
        }

        // 生成随机文件名
        $newname = $this->randName() . '.' . $ext;

        $dir = $dir . '/' . $newname;

        // 移动
        if (!move_uploaded_file($f['tmp_name'], $dir)) {
            $this->errno = 11;

            return false;
        }

        return $this->url . "/{$newname}";
    }

    /**
     * 获取错误信息
     *
     * @return mixed
     */
    public function getErr()
    {
        return $this->error[$this->errno];
    }

    /**
     * 设置允许的文件后缀
     *
     * @param $exts
     */
    public function setExt($exts)
    {
        $this->allowExt = $exts;
    }

    /**
     * 设置文件大小
     *
     * @param $num
     */
    public function setSize($num)
    {
        $this->maxSize = $num;
    }

    /**
     * 获取文件扩展
     *
     * @param $file
     *
     * @return mixed
     */
    protected function getExt($file)
    {
        $tmp = explode('.', $file);

        return end($tmp);
    }

    /**
     * 验证文件扩展
     *
     * @param $ext
     *
     * @return bool
     */
    protected function isAllowExt($ext)
    {
        return in_array(strtolower($ext), explode(',', strtolower($this->allowExt)));
    }


    /**
     * 验证文件的大小
     *
     * @param $size
     *
     * @return bool
     */
    protected function isAllowSize($size)
    {
        return $size <= $this->maxSize * 1024 * 1024;
    }

    /**
     * 按日期创建目录的方法
     *
     * @param $bucket
     *
     * @return bool|string
     */
    protected function mk_dir($bucket)
    {
        $this->url = $base = "/data/siteimg/{$bucket}/" . date('Ym/d');

        $dir = dirname(__DIR__) . "/..{$base}";

        if (is_dir($dir) || mkdir($dir, 0777, true)) {
            return $dir;
        } else {
            return false;
        }
    }

    /**
     * 生成随机文件名
     *
     * @param int $length
     *
     * @return string
     */
    protected function randName($length = 6)
    {
        $str = 'abcdefghijkmnpqrstuvwxyz23456789';

        return md5(substr(str_shuffle($str), 0, $length) . time());
    }
}