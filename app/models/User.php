<?php

class User extends BaseModel
{
    /**
     * 后台用户类型
     */
    const ADMIN_USER_TYPE = 1;
    /**
     * 前台用户类型
     */
    const FRONT_USER_TYPE = 0;
    /**
     * 正常状态用户
     */
    const NORMAL_USER_STATUS = 0;
    /**
     * 等待状态用户
     */
    const WAITE_USER_STATUS = 1;
    /**
     * 已删除状态用户
     */
    const DELETE_USER_STATUS = 2;
    /**
     * 已禁用状态用户
     */
    const DISABLED_USER_STATUS = 3;

    public function getSource()
    {
        return 'blog_users';
    }
}