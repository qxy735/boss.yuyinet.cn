<?php

class Msg extends BaseModel
{
    /**
     * 管理员未阅读留言状态
     */
    const MSG_NOT_READ_STATUS = 0;
    /**
     * 管理员已阅读未回复留言状态
     */
    const MSG_READ_NOt_REPLY_STATUS = 1;
    /**
     * 管理员已回复留言状态
     */
    const MSG_REPLY_STATUS = 2;
    /**
     * 回复内容已查看
     */
    const MSG_REPLY_READ_STATUS = 3;
    /**
     * 未回复
     */
    const REPLY_STATUS_NO = 0;
    /**
     * 已回复
     */
    const REPLY_STATUS_YES = 1;

    public function getSource()
    {
        return 'blog_msgs';
    }
}