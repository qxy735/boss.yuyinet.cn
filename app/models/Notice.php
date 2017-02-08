<?php

class Notice extends BaseModel
{
    /**
     * 显示中
     */
    const SHOW_NOTICE_STATUS = 1;
    /**
     * 已删除
     */
    const DEL_NOTICE_STATUS = 3;

    public function getSource()
    {
        return 'blog_notices';
    }
}