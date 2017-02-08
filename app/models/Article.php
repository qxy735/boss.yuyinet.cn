<?php

class Article extends BaseModel
{
    /**
     * 正常状态
     */
    const ARTICLE_STATUS_NORMAL = 0;
    /**
     * 草稿状态
     */
    const ARTICLE_STATUS_BAT = 1;
    /**
     * 删除状态
     */
    const ARTICLE_STATUS_DELETE = 2;
    /**
     * 公开文章
     */
    const ARTICLE_IS_PUBLIC = 1;
    /**
     * 不公开文章
     */
    const ARTICLE_IS_NO_PUBLIC = 0;
    /**
     * 允许评论
     */
    const ARTICLE_ALLOW_COMMENT = 1;

    public function getSource()
    {
        return 'blog_articles';
    }
}