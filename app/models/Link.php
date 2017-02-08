<?php

class Link extends BaseModel
{
    /**
     * 启用友情链接
     */
    const ENABLE_BLOG_LINK = 1;
    /**
     * 禁用友情链接
     */
    const DISABLE_BLOG_LINK = 0;

    public function getSource()
    {
        return 'blog_links';
    }
}