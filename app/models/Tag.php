<?php

class Tag extends BaseModel
{
    /**
     * 禁用标签
     */
    const DISABLED_TAG_STATUS = 0;
    /**
     * 启用标签
     */
    const ENABLED_TAG_STATUS = 1;

    public function getSource()
    {
        return 'blog_tags';
    }
}