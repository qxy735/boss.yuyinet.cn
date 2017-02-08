<?php

class LoginLog extends BaseModel
{
    /**
     * 前台用户类型
     */
    const LOGIN_LOG_TYPE_FRONT = 0;
    /**
     * 后台用户类型
     */
    const LOGIN_LOG_TYPE_ADMIN = 1;
    /**
     * 前台登陆
     */
    const LOGIN_LOG_SOURCE_FRONT = 0;
    /**
     * 后台登陆
     */
    const LOGIN_LOG_SOURCE_ADMIN = 1;
    /**
     * 微信登陆
     */
    const LOGIN_LOG_SOURCE_WEIXIN = 2;

    public function getSource()
    {
        return 'blog_loginlogs';
    }
}