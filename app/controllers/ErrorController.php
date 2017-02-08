<?php

class ErrorController extends BaseController
{
    /**
     * 登录验证
     *
     * @var bool
     */
    protected $loginAuth = false;

    /**
     * 显示404页面
     *
     * @return mixed
     */
    public function pageAction()
    {
        return $this->view->pick('show/notfound');
    }
}