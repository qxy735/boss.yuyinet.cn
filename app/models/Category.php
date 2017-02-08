<?php

class Category extends BaseModel
{
    /**
     * 顶级分类 ID
     */
    const TOP_CATEGORY_ID = 0;
    /**
     * 禁用分类
     */
    const IS_DISABLED_CATEGORY = 0;
    /**
     * 启用分类
     */
    const IS_ENABLED_CATEGORY = 1;

    public function getSource()
    {
        return 'blog_categorys';
    }
}