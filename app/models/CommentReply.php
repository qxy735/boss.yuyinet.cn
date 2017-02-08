<?php

class CommentReply extends BaseModel
{
    /**
     * 隐藏评论回复
     */
    const  HIDE_REPLY_STATUS = 0;
    /**
     * 显示评论回复
     */
    const  SHOW_REPLY_STATUS = 1;

    public function getSource()
    {
        return 'blog_comment_replys';
    }
}