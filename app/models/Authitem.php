<?php

class Authitem extends BaseModel
{
    /**
     * 启用权限项
     */
    const ENABLE_BLOG_AUTHITEM = 1;
    /**
     * 禁用权限项
     */
    const DISABLE_BLOG_AUTHITEM = 0;
    /**
     * 父权限项 ID
     */
    const PARENT_BLOG_AUTHITEM = 0;
    /**
     * 没有子权限项
     */
    const IS_NOT_HAS = 0;
    /**
     * 有子权限项
     */
    const IS_HAS = 1;
    /**
     * 前台权限项类型
     */
    const FRONT_AUTH_TYPE = 0;
    /**
     * 后台权限项类型
     */
    const ADMIN_AUTH_TYPE = 1;
    /**
     * 微信权限项类型
     */
    const WEIXIN_AUTH_TYPE = 2;

    public function getSource()
    {
        return 'blog_authitems';
    }
}