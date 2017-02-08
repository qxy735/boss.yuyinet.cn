<?php

class MessageDetail extends BaseModel
{
    /**
     * 未阅读
     */
    const MSG_READ_NO = 0;
    /**
     * 已阅读
     */
    const MSG_READ_YES = 1;

    public function getSource()
    {
        return 'blog_messagedetails';
    }
}