<?php

class Album extends BaseModel
{
    /**
     * 禁用相册类型
     */
    const ALBUM_DISABLED_TYPE = 0;
    /**
     * 启用相册类型
     */
    const ALBUM_ENABLED_TYPE = 1;

    public function getSource()
    {
        return 'blog_albums';
    }
}