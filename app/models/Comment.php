<?php

class Comment extends BaseModel
{
    /**
     * 隐藏评论
     */
    const HIDE_COMMENT_STATUS = 0;
    /**
     * 显示评论
     */
    const SHOW_COMMENT_STATUS = 1;
    /**
     * 取消置顶
     */
    const TOP_COMMENT_NO = 0;
    /**
     * 置顶评论
     */
    const TOP_COMMENT_YES = 1;

    public function getSource()
    {
        return 'blog_comments';
    }
}