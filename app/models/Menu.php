<?php

class Menu extends BaseModel
{
    /**
     * 顶级菜单类型
     */
    const TOP_TYPE_MENU = 0;
    /**
     * 启用菜单
     */
    const ENABLE_BLOG_MENU = 1;
    /**
     * 禁用菜单
     */
    const DISABLE_BLOG_MENU = 0;
    /**
     * 前台菜单类型
     */
    const FRONT_BLOG_TYPE = 0;
    /**
     * 后台菜单类型
     */
    const ADMIN_BLOG_TYPE = 1;
    /**
     * 微信菜单类型
     */
    const WEIXIN_BLOG_TYPE = 2;

    public function getSource()
    {
        return 'blog_menus';
    }
}