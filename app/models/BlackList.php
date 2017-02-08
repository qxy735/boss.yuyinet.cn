<?php

class BlackList extends BaseModel
{
    /**
     * 禁用全部功能类型
     */
    const DISABLED_TYPE_ALL = 0;
    /**
     * 禁用前台功能类型
     */
    const DISABLED_TYPE_FRONT = 1;
    /**
     * 禁用后台功能类型
     */
    const DISABLED_TYPE_ADMIN = 2;
    /**
     * 禁用微信功能类型
     */
    const DISABLED_TYPE_WEIXIN = 3;

    public function getSource()
    {
        return 'blog_blacklists';
    }
}