<?php

class Advert extends BaseModel
{
    /**
     * 已关闭
     */
    const ADVERT_STATUS_DISABLED = 3;

    public function getSource()
    {
        return 'blog_adverts';
    }
}