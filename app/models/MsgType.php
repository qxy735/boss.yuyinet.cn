<?php

class MsgType extends BaseModel
{
    /**
     * 关闭消息类型
     */
    const DISABLED_MSG_TYPE = 0;
    /**
     * 启用消息类型
     */
    const ENABLED_MSG_TYPE = 1;
    /**
     * 非公开消息类型
     */
    const IS_NOT_PUBLIC_TYPE = 0;
    /**
     * 公开消息类型
     */
    const IS_PUBLIC_TYPE = 1;

    public function getSource()
    {
        return 'blog_msgtypes';
    }
}