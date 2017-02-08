<?php

class Role extends BaseModel
{
    /**
     * 启用角色
     */
    const ENABLE_BLOG_ROLE = 1;
    /**
     * 禁用角色
     */
    const DISABLE_BLOG_ROLE = 0;
    /**
     * 前台角色
     */
    const ROLE_TYPE_FRONT = 0;
    /**
     * 后台角色
     */
    const ROLE_TYPE_ADMIN = 1;

    public function getSource()
    {
        return 'blog_roles';
    }
}