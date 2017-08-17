<?php

use \Phalcon\Paginator\Adapter\Model;

class ArticleController extends BaseController
{
    /**
     * 公开状态信息
     *
     * @var array
     */
    protected $publics = [
        '否',
        '是',
    ];
    /**
     * 是否允许评论状态信息
     *
     * @var array
     */
    protected $comments = [
        '否',
        '是',
    ];
    /**
     * 文章状态信息
     *
     * @var array
     */
    protected $status = [
        '正常',
        '草稿',
        '删除',
    ];
    /**
     * 是否清空
     *
     * @var array
     */
    protected $clears = [
        '否',
        '是'
    ];
    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.facility.article.L', 'blog.boss.facility.article.R', 'blog.boss.facility.article.S']],
        'create' => [false, ['blog.boss.facility.article.C']],
        'post' => [false, ['blog.boss.facility.article.C']],
        'delete' => [false, ['blog.boss.facility.article.D', 'blog.boss.facility.article.BD']],
        'private' => [false, ['blog.boss.facility.article.SET']],
        'public' => [false, ['blog.boss.facility.article.SET']],
        'edit' => [false, ['blog.boss.facility.article.U']],
        'save' => [false, ['blog.boss.facility.article.U']],
    ];

    /**
     * 显示文章列表页面
     *
     * @return mixed
     */
    public function listAction()
    {
        try {
            // 获取当前页码
            $page = $this->page;

            // 获取每页显示的数据量
            $pageSize = $this->pageSize;

            // 获取文章标题
            $title = $this->getParam('title');
            $title = urldecode($title);
            $titleSql = $title ? " and title like '%{$title}%'" : '';

            // 获取文章状态
            $status = $this->getParam('status');
            $status = (null === $status) ? -1 : intval($status);
            $statusSql = (-1 === $status) ? '' : " and status={$status}";

            // 获取允许评论状态
            $iscomment = $this->getParam('iscomment');
            $iscomment = (null === $iscomment) ? -1 : intval($iscomment);
            $iscommentSql = (-1 === $iscomment) ? '' : " and iscomment={$iscomment}";

            // 是否公开
            $ispublic = $this->getParam('ispublic');
            $ispublic = (null === $ispublic) ? -1 : intval($ispublic);
            $ispublicSql = (-1 === $ispublic) ? '' : " and ispublic={$ispublic}";

            // 获取分类 ID
            $cId = $this->getParam('cid');
            $cId = (null === $cId) ? -1 : intval($cId);
            $cIdSql = (-1 === $cId) ? '' : " and categoryid={$cId}";

            // 获取菜单 ID
            $menuId = $this->getParam('menuid');
            $menuId = (null === $menuId) ? -1 : intval($menuId);
            $menuIdSql = (-1 === $menuId) ? '' : " and menuid={$menuId}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取文章信息
            $articles = Article::find([
                'conditions' => "1=1 {$titleSql}{$statusSql}{$ispublicSql}{$cIdSql}{$menuIdSql}{$iscommentSql} " . 'order by id desc',
                'columns' => 'id,menuid,categoryid,cover,title,come,ispublic,status,author,visitcount,commentcount,downloadcount,iscomment,coin,createtime,creator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $articles,
                    "limit" => $pageSize,
                    "page" => $page
                )
            );

            // 获取分页结果
            $pageDatas = $paginator->getPaginate();

            // 获取分页信息
            $page = [
                'before' => $pageDatas->before,
                'last' => $pageDatas->last,
                'next' => $pageDatas->next,
                'num' => $pageDatas->total_pages,
                'page' => $page,
                'pagesize' => $pageSize,
                'total' => $pageDatas->total_items,
            ];

            $articles = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 获取前台菜单信息
            $menus = Menu::find([
                'conditions' => 'type=' . Menu::FRONT_BLOG_TYPE . ' and enabled=' . Menu::ENABLE_BLOG_MENU . ' and parentid=' . Menu::TOP_TYPE_MENU,
                'columns' => 'id,name'
            ])->toArray();

            // 传递菜单信息
            $this->view->menus = $menus;

            $menuNames = $categroyNames = [];

            foreach ($menus as $menu) {
                $menuNames[$menu['id']] = $menu['name'];
            }

            // 获取顶级分类信息
            $topCategorys = Category::find([
                'conditions' => 'parentid=' . Category::TOP_CATEGORY_ID . ' and enabled=' . Category::IS_ENABLED_CATEGORY,
                'columns' => 'id,name'
            ])->toArray();

            foreach ($topCategorys as $topCategory) {
                $categroyNames[$topCategory['id']] = $topCategory['name'];
            }

            // 获取顶级分类 ID 列表
            $topCategoryIds = array_map(function ($topCategory) {
                return $topCategory['id'];
            }, $topCategorys);

            $sonCategorys = [];

            // 获取子分类信息
            if ($topCategoryIds) {
                $sonCategorys = Category::find([
                    'conditions' => 'parentid in(' . implode(',', $topCategoryIds) . ') and enabled=' . Category::IS_ENABLED_CATEGORY,
                    'columns' => 'id,parentid,name'
                ])->toArray();
            }

            foreach ($sonCategorys as $sonCategory) {
                $categroyNames[$sonCategory['id']] = $sonCategory['name'];
            }

            // 增加子分类信息
            $topCategorys = array_map(function ($topCategory) use ($sonCategorys) {
                $sons = [];

                foreach ($sonCategorys as $sonCategory) {
                    if ($sonCategory['parentid'] == $topCategory['id']) {
                        $sons[] = $sonCategory;
                    }
                }

                $topCategory['son'] = $sons;

                return $topCategory;
            }, $topCategorys);

            unset($sonCategorys);

            // 传递分类信息
            $this->view->topCategorys = $topCategorys;

            // 获取文章 ID 列表
            $articleIds = array_map(function ($article) {
                return $article['id'];
            }, $articles);

            $tags = $tagNames = [];

            if ($articleIds) {
                // 获取文章标签信息
                $articleTags = ArticleTag::find([
                    'conditions' => 'articleid in(' . implode(',', $articleIds) . ')',
                    'columns' => 'articleid,tagid'
                ])->toArray();

                // 获取标签 ID 列表
                $tagIds = array_map(function ($articleTag) {
                    return $articleTag['tagid'];
                }, $articleTags);

                // 获取标签名
                if ($tagIds) {
                    $tmpTags = Tag::find([
                        'conditions' => 'id in(' . implode(',', $tagIds) . ') and enabled=' . Tag::ENABLED_TAG_STATUS,
                        'columns' => 'id, name'
                    ])->toArray();

                    foreach ($tmpTags as $tmpTag) {
                        $tagNames[$tmpTag['id']] = $tmpTag['name'];
                    }

                    unset($tmpTags);
                }

                foreach ($articleTags as $articleTag) {
                    $tags[$articleTag['articleid']][] = isset($tagNames[$articleTag['tagid']]) ? $tagNames[$articleTag['tagid']] : '';
                }
            }

            unset($tagNames);

            // 数值转化
            $articles = array_map(function ($article) use ($menuNames, $categroyNames, $tags) {
                $article['createtime'] = $article['createtime'] ? date('Y-m-d H:i:s', $article['createtime']) : '';
                $article['menuname'] = isset($menuNames[$article['menuid']]) ? $menuNames[$article['menuid']] : '';
                $article['cname'] = isset($categroyNames[$article['categoryid']]) ? $categroyNames[$article['categoryid']] : '';
                $article['statusname'] = $this->status[$article['status']];
                $article['ispublicname'] = $this->publics[$article['ispublic']];
                $article['iscommentname'] = $this->comments[$article['iscomment']];
                $article['tag'] = isset($tags[$article['id']]) ? implode(',', $tags[$article['id']]) : '';
                $title = mb_substr($article['title'], 0, 10, 'utf8');
                if (mb_strlen($title, 'utf8') < mb_strlen($article['title'], 'utf8')) {
                    $title .= '...';
                }
                $article['title'] = $title;

                return $article;
            }, $articles);

            // 传递文章信息
            $this->view->articles = $articles;

            unset($articles);
            unset($menus);
            unset($menuNames);
            unset($categroyNames);

            // 传递分页信息
            $this->view->page = $page;

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($title ? "/title/{$title}" : '') . ('' !== $status ? "/status/{$status}" : '') . ('' !== $iscomment ? "/iscomment/{$iscomment}" : '') . ('' !== $cId ? "/cid/{$cId}" : '') . ('' !== $menuId ? "/menuid/{$menuId}" : '') . ('' !== $ispublic ? "/ispublic/{$ispublic}" : '');

            // 传递查询条件组合
            $this->view->cond = $cond;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 传递删除状态
            $this->view->delStatus = Article::ARTICLE_STATUS_DELETE;

            // 传递默认公开状态
            $this->view->defaultPublic = Article::ARTICLE_IS_PUBLIC;

            // 传递查询参数
            $this->view->statu = $status;
            $this->view->title = $title;
            $this->view->ispublic = $ispublic;
            $this->view->cId = $cId;
            $this->view->menuId = $menuId;
            $this->view->iscomment = $iscomment;

            // 传递状态信息
            $this->view->status = $this->status;

            // 传递公开状态信息
            $this->view->publics = $this->publics;

            // 传递允许评论状态信息
            $this->view->comments = $this->comments;

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.facility.article.C']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.facility.article.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.facility.article.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.facility.article.U']);

            // 是否显示设置操作相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.facility.article.SET']);

            return $this->view->pick('article/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('文章页面加载失败', '/');
        }
    }

    /**
     * 显示文章添加页面
     *
     * @return mixed
     */
    public function createAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        // 传递查询条件
        $cond = "/navid/{$navId}";

        try {
            $this->view->cond = $cond;

            // 传递状态信息
            $this->view->status = $this->status;

            // 传递公开状态信息
            $this->view->publics = $this->publics;

            // 传递允许评论状态信息
            $this->view->comments = $this->comments;

            // 获取前台菜单信息
            $menus = Menu::find([
                'conditions' => 'type=' . Menu::FRONT_BLOG_TYPE . ' and enabled=' . Menu::ENABLE_BLOG_MENU . ' and parentid=' . Menu::TOP_TYPE_MENU,
                'columns' => 'id,name'
            ])->toArray();

            // 传递菜单信息
            $this->view->menus = $menus;

            // 获取顶级分类信息
            $topCategorys = Category::find([
                'conditions' => 'parentid=' . Category::TOP_CATEGORY_ID . ' and enabled=' . Category::IS_ENABLED_CATEGORY,
                'columns' => 'id,name,type'
            ])->toArray();

            foreach ($topCategorys as $topCategory) {
                $categroyNames[$topCategory['id']] = $topCategory['name'];
            }

            // 获取顶级分类 ID 列表
            $topCategoryIds = array_map(function ($topCategory) {
                return $topCategory['id'];
            }, $topCategorys);

            $sonCategorys = [];

            // 获取子分类信息
            if ($topCategoryIds) {
                $sonCategorys = Category::find([
                    'conditions' => 'parentid in(' . implode(',', $topCategoryIds) . ') and enabled=' . Category::IS_ENABLED_CATEGORY,
                    'columns' => 'id,parentid,name,type'
                ])->toArray();
            }

            foreach ($sonCategorys as $sonCategory) {
                $categroyNames[$sonCategory['id']] = $sonCategory['name'];
            }

            // 增加子分类信息
            $topCategorys = array_map(function ($topCategory) use ($sonCategorys) {
                $sons = [];

                foreach ($sonCategorys as $sonCategory) {
                    if ($sonCategory['parentid'] == $topCategory['id']) {
                        if (0 == $sonCategory['type']) {
                            $name_extra = '(普通)';
                        } elseif (1 == $sonCategory['type']) {
                            $name_extra = '(下载)';
                        } elseif (2 == $sonCategory['type']) {
                            $name_extra = '(作品)';
                        } else {
                            $name_extra = '(其他)';
                        }

                        $sonCategory['name_extra'] = $name_extra;

                        $sons[] = $sonCategory;
                    }
                }

                $topCategory['son'] = $sons;

                if (0 == $topCategory['type']) {
                    $name_extra = '(普通)';
                } elseif (1 == $topCategory['type']) {
                    $name_extra = '(下载)';
                } elseif (2 == $topCategory['type']) {
                    $name_extra = '(作品)';
                } else {
                    $name_extra = '(其他)';
                }

                $topCategory['name_extra'] = $name_extra;

                return $topCategory;
            }, $topCategorys);

            unset($sonCategorys);

            // 传递分类信息
            $this->view->topCategorys = $topCategorys;

            // 传递默认允许评论
            $this->view->defaultComment = Article::ARTICLE_ALLOW_COMMENT;

            $this->view->uploadUrl = '/assets/ueditor/php/controller.php?m=article';

            // 获取标签信息
            $tags = Tag::find([
                'conditions' => 'enabled=' . Tag::ENABLED_TAG_STATUS,
                'columns' => 'id,name'
            ])->toArray();

            // 传递标签信息
            $this->view->tags = $tags;

            unset($tags);

            return $this->view->pick('article/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('文章重复', "/article/list{$cond}");
            } else {
                return $this->error('文章添加页面加载失败', "/article/list{$cond}");
            }
        }
    }

    /**
     * 文章添加
     *
     * @return mixed
     */
    public function postAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        // 传递查询条件
        $cond = "/navid/{$navId}";

        try {
            // 获取提交的内容
            $posts = $this->request->getPost();

            // 上传文章封面图片
            if (isset($_FILES['cover']) && $_FILES['cover']['name']) {
                $upload = new Upload();

                // 上传文件
                $file = $upload->make('cover', 'articlecover');

                // 判断上传是否成功
                if (!$file) {
                    $msg = $upload->getErr() ?: '文章封面上传失败';

                    return $this->error($msg, "/article/list{$cond}");
                }

                $posts['cover'] = $file;
            }

            // 上传文章附件信息
            if (isset($_FILES['attachment']) && $_FILES['attachment']['name']) {
                $upload = new Upload();

                // 上传文件
                $file = $upload->make('attachment', 'attachment', '/data/sitefile/attachment');

                // 判断上传是否成功
                if (!$file) {
                    $msg = $upload->getErr() ?: '附件上传失败';

                    return $this->error($msg, "/article/list{$cond}");
                }

                $posts['attachment'] = $file;
            }

            if (isset($posts['editorValue'])) {
                $posts['content'] = $posts['editorValue'];

                unset($posts['editorValue']);
            }

            $tags = [];

            if (isset($posts['tag'])) {
                $tags = $posts['tag'];

                unset($posts['tag']);
            }

            $posts['createtime'] = time();
            $posts['creator'] = $this->getUserName();

            $article = new Article();

            // 添加文章信息
            $result = $article->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('文章添加失败', "/article/create{$cond}");
            }

            $inserts = [
                'articleid' => $article->id,
                'creator' => $this->getUserName(),
            ];

            // 添加标签
            foreach ($tags as $tagId) {
                $articleTag = new ArticleTag();

                $inserts['createtime'] = time();
                $inserts['tagid'] = $tagId;

                $articleTag->save($inserts);
            }

            return $this->success('文章添加成功', "/article/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            $this->error('文章添加失败', "/article/list{$cond}");
        }
    }

    /**
     * 删除文章
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取文章标题
        $title = urldecode($this->getParam('title'));

        // 获取文章状态
        $status = (int)$this->getParam('status');

        // 获取菜单 ID
        $menuId = (int)$this->getParam('menuid');

        // 获取文章分类 ID
        $cId = (int)$this->getParam('cid');

        // 是否公开
        $ispublic = (int)$this->getParam('ispublic');

        // 是否评论
        $iscomment = (int)$this->getParam('iscomment');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($title ? "/title/{$title}" : '') . ('' !== $iscomment ? "/iscomment/{$iscomment}" : '') . ('' !== $ispublic ? "/ispublic/{$ispublic}" : '') . ('' !== $cId ? "/cid/{$cId}" : '') . ('' !== $menuId ? "/menuid/{$menuId}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取文章 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的文章信息
                $article = Article::findFirst(intval($id));

                // 判断文章信息是否存在,存在则做删除
                if ($article) {
                    // 删除指定文章信息
                    $result = $article->update([
                        'status' => Article::ARTICLE_STATUS_DELETE,
                        'lastoperate' => time(),
                        'lastoperator' => $this->getUserName(),
                    ]);

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('文章删除失败', "/article/list{$cond}");
            }

            return $this->success('文章删除成功', "/article/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('文章删除失败', "/article/list{$cond}");
        }
    }

    /**
     * 文章不公开设置
     *
     * @return mixed
     */
    public function privateAction()
    {
        // 获取文章标题
        $title = urldecode($this->getParam('title'));

        // 获取文章状态
        $status = (int)$this->getParam('status');

        // 获取菜单 ID
        $menuId = (int)$this->getParam('menuid');

        // 获取文章分类 ID
        $cId = (int)$this->getParam('cid');

        // 是否公开
        $ispublic = (int)$this->getParam('ispublic');

        // 是否评论
        $iscomment = (int)$this->getParam('iscomment');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($title ? "/title/{$title}" : '') . ('' !== $iscomment ? "/iscomment/{$iscomment}" : '') . ('' !== $ispublic ? "/ispublic/{$ispublic}" : '') . ('' !== $cId ? "/cid/{$cId}" : '') . ('' !== $menuId ? "/menuid/{$menuId}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取文章 ID
            $id = (int)$this->getParam('id');

            // 获取文章信息
            $article = Article::findFirst($id);

            // 文章信息存在则做更新操作
            if ($article) {
                // 更新文章是否公开
                $result = $article->update([
                    'ispublic' => Article::ARTICLE_IS_NO_PUBLIC,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('文章不公开设置失败', "/article/list{$cond}");
                }
            }

            return $this->success('文章不公开设置成功', "/article/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('文章不公开设置失败', "/article/list{$cond}");
        }
    }

    /**
     * 文章公开设置
     *
     * @return mixed
     */
    public function publicAction()
    {
        // 获取文章标题
        $title = urldecode($this->getParam('title'));

        // 获取文章状态
        $status = (int)$this->getParam('status');

        // 获取菜单 ID
        $menuId = (int)$this->getParam('menuid');

        // 获取文章分类 ID
        $cId = (int)$this->getParam('cid');

        // 是否公开
        $ispublic = (int)$this->getParam('ispublic');

        // 是否评论
        $iscomment = (int)$this->getParam('iscomment');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($title ? "/title/{$title}" : '') . ('' !== $iscomment ? "/iscomment/{$iscomment}" : '') . ('' !== $ispublic ? "/ispublic/{$ispublic}" : '') . ('' !== $cId ? "/cid/{$cId}" : '') . ('' !== $menuId ? "/menuid/{$menuId}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取文章 ID
            $id = (int)$this->getParam('id');

            // 获取文章信息
            $article = Article::findFirst($id);

            // 文章信息存在则做更新操作
            if ($article) {
                // 更新文章是否公开
                $result = $article->update([
                    'ispublic' => Article::ARTICLE_IS_PUBLIC,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('文章公开设置失败', "/article/list{$cond}");
                }
            }

            return $this->success('文章公开设置成功', "/article/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('文章公开设置失败', "/article/list{$cond}");
        }
    }

    /**
     * 显示文章编辑页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取文章标题
        $title = urldecode($this->getParam('title'));

        // 获取文章状态
        $status = (int)$this->getParam('status');

        // 获取菜单 ID
        $menuId = (int)$this->getParam('menuid');

        // 获取文章分类 ID
        $cId = (int)$this->getParam('cid');

        // 是否公开
        $ispublic = (int)$this->getParam('ispublic');

        // 是否评论
        $iscomment = (int)$this->getParam('iscomment');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($title ? "/title/{$title}" : '') . ('' !== $iscomment ? "/iscomment/{$iscomment}" : '') . ('' !== $ispublic ? "/ispublic/{$ispublic}" : '') . ('' !== $cId ? "/cid/{$cId}" : '') . ('' !== $menuId ? "/menuid/{$menuId}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取文章 ID
            $id = (int)$this->getParam('id');

            // 获取文章信息
            $article = Article::findFirst($id);

            // 判断文章是否存在
            if (!$article) {
                return $this->error('文章不存在', "/article/list{$cond}");
            }

            $article = $article->toArray();

            // 传递文章信息
            $this->view->article = $article;

            // 传递分页信息
            $this->view->cond = $cond;

            $this->view->conds = [
                '_title' => $title,
                '_status' => $status,
                '_ispublic' => $ispublic,
                '_iscomment' => $iscomment,
                '_cid' => $cId,
                '_menuid' => $menuId,
                '_page' => $page,
            ];

            // 传递文章状态信息
            $this->view->status = $this->status;

            // 传递文章是否公开信息
            $this->view->publics = $this->publics;

            // 传递文章是否允许评论信息
            $this->view->comments = $this->comments;

            // 获取前台菜单信息
            $menus = Menu::find([
                'conditions' => 'type=' . Menu::FRONT_BLOG_TYPE . ' and enabled=' . Menu::ENABLE_BLOG_MENU . ' and parentid=' . Menu::TOP_TYPE_MENU,
                'columns' => 'id,name'
            ])->toArray();

            // 传递菜单信息
            $this->view->menus = $menus;

            // 获取顶级分类信息
            $topCategorys = Category::find([
                'conditions' => 'parentid=' . Category::TOP_CATEGORY_ID . ' and enabled=' . Category::IS_ENABLED_CATEGORY,
                'columns' => 'id,name'
            ])->toArray();

            foreach ($topCategorys as $topCategory) {
                $categroyNames[$topCategory['id']] = $topCategory['name'];
            }

            // 获取顶级分类 ID 列表
            $topCategoryIds = array_map(function ($topCategory) {
                return $topCategory['id'];
            }, $topCategorys);

            $sonCategorys = [];

            // 获取子分类信息
            if ($topCategoryIds) {
                $sonCategorys = Category::find([
                    'conditions' => 'parentid in(' . implode(',', $topCategoryIds) . ') and enabled=' . Category::IS_ENABLED_CATEGORY,
                    'columns' => 'id,parentid,name'
                ])->toArray();
            }

            foreach ($sonCategorys as $sonCategory) {
                $categroyNames[$sonCategory['id']] = $sonCategory['name'];
            }

            // 增加子分类信息
            $topCategorys = array_map(function ($topCategory) use ($sonCategorys) {
                $sons = [];

                foreach ($sonCategorys as $sonCategory) {
                    if ($sonCategory['parentid'] == $topCategory['id']) {
                        if (0 == $sonCategory['type']) {
                            $name_extra = '(普通)';
                        } elseif (1 == $sonCategory['type']) {
                            $name_extra = '(下载)';
                        } elseif (2 == $sonCategory['type']) {
                            $name_extra = '(作品)';
                        } else {
                            $name_extra = '(其他)';
                        }

                        $sonCategory['name_extra'] = $name_extra;

                        $sons[] = $sonCategory;
                    }
                }

                $topCategory['son'] = $sons;

                if (0 == $topCategory['type']) {
                    $name_extra = '(普通)';
                } elseif (1 == $topCategory['type']) {
                    $name_extra = '(下载)';
                } elseif (2 == $topCategory['type']) {
                    $name_extra = '(作品)';
                } else {
                    $name_extra = '(其他)';
                }

                $topCategory['name_extra'] = $name_extra;

                return $topCategory;
            }, $topCategorys);

            unset($sonCategorys);

            // 传递分类信息
            $this->view->topCategorys = $topCategorys;

            // 获取标签信息
            $tags = Tag::find([
                'conditions' => 'enabled=' . Tag::ENABLED_TAG_STATUS,
                'columns' => 'id,name'
            ])->toArray();

            $this->view->uploadUrl = '/assets/ueditor/php/controller.php?m=article';

            // 获取文章标签信息
            $articleTags = ArticleTag::find([
                'conditions' => "articleid={$id}",
                'columns' => 'tagid'
            ])->toArray();

            $articleTags = array_map(function ($articleTag) {
                return $articleTag['tagid'];
            }, $articleTags);

            $tags = array_map(function ($tag) use ($articleTags) {
                $tag['ischecked'] = in_array($tag['id'], $articleTags) ? 1 : 0;

                return $tag;
            }, $tags);

            // 传递标签信息
            $this->view->tags = $tags;

            unset($tags);
            unset($articleTags);

            // 传递是否清空上传信息
            $this->view->clears = $this->clears;

            return $this->view->pick('article/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('文章编辑页面加载失败', "/article/list{$cond}");
        }
    }

    /**
     * 更新文章
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取文章 ID
        $id = (int)$this->request->getPost('id');

        // 获取文章标题
        $title = urldecode($this->getParam('_title'));

        // 获取文章状态
        $status = (int)$this->getParam('_status');

        // 获取菜单 ID
        $menuId = (int)$this->getParam('_menuid');

        // 获取文章分类 ID
        $cId = (int)$this->getParam('_cid');

        // 是否公开
        $ispublic = (int)$this->getParam('_ispublic');

        // 是否评论
        $iscomment = (int)$this->getParam('_iscomment');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('_page');
        $page = $page ?: 1;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($title ? "/title/{$title}" : '') . ('' !== $iscomment ? "/iscomment/{$iscomment}" : '') . ('' !== $ispublic ? "/ispublic/{$ispublic}" : '') . ('' !== $cId ? "/cid/{$cId}" : '') . ('' !== $menuId ? "/menuid/{$menuId}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取文章信息
            $article = Article::findFirst($id);

            // 判断文章是否存在
            if (!$article) {
                return $this->error('文章不存在', "/article/list{$cond}");
            }

            // 获取需要更新的文章信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts) {
                return $this->error('更新内容不能全为空', "/article/edit/id/{$id}{$cond}");
            }

            // 是否取消封面图片
            if (isset($posts['cancelcover']) && $posts['cancelcover']) {
                $file = $article->cover;

                if ($file) {
                    $file = ROOT . $file;

                    file_exists($file) and unlink($file);

                    $posts['cover'] = '';
                }

                unset($posts['cancelcover']);
            }

            // 是否取消附件
            if (isset($posts['cancelattach']) && $posts['cancelattach']) {
                $file = $article->attachment;

                if ($file) {
                    $file = ROOT . $file;

                    file_exists($file) and unlink($file);

                    $posts['attachment'] = '';
                }

                unset($posts['cancelattach']);
            }

            $oldCoverFile = $oldAttachmentFile = $coverFile = $attachmentFile = '';

            // 上传文章封面图片
            if (isset($_FILES['cover']) && $_FILES['cover']['name']) {
                $upload = new Upload();

                // 上传文件
                $file = $upload->make('cover', 'articlecover');

                // 判断上传是否成功
                if (!$file) {
                    $msg = $upload->getErr() ?: '文章封面上传失败';

                    return $this->error($msg, "/article/edit/id/{$id}{$cond}");
                }

                $posts['cover'] = $file;

                $coverFile = ROOT . $file;

                if ($article->cover) {
                    $oldCoverFile = ROOT . $article->cover;
                }
            }

            // 上传文章附件信息
            if (isset($_FILES['attachment']) && $_FILES['attachment']['name']) {
                $upload = new Upload();

                // 上传文件
                $file = $upload->make('attachment', 'attachment', '/data/sitefile/attachment');

                // 判断上传是否成功
                if (!$file) {
                    $msg = $upload->getErr() ?: '附件上传失败';

                    return $this->error($msg, "/article/edit/id/{$id}{$cond}");
                }

                $posts['attachment'] = $file;

                $attachmentFile = ROOT . $file;

                if ($article->attachment) {
                    $oldAttachmentFile = ROOT . $article->attachment;
                }
            }

            if (isset($posts['editorValue'])) {
                $posts['content'] = $posts['editorValue'];

                unset($posts['editorValue']);
            }

            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新文章信息
            $result = $article->update($posts);

            // 判断更新是否成功
            if (!$result) {
                // 删除新上传的图片
                if ($coverFile) {
                    file_exists($coverFile) and unlink($coverFile);
                }

                if ($attachmentFile) {
                    file_exists($attachmentFile) and unlink($attachmentFile);
                }

                return $this->error('文章更新失败', "/article/edit/id/{$id}{$cond}");
            }

            // 删除原图片
            if ($oldCoverFile) {
                file_exists($oldCoverFile) and unlink($oldCoverFile);
            }

            if ($oldAttachmentFile) {
                file_exists($oldAttachmentFile) and unlink($oldAttachmentFile);
            }

            // 获取文章标签信息
            $articleTags = ArticleTag::find([
                'conditions' => "articleid={$id}",
                'columns' => 'tagid'
            ])->toArray();

            $articleTags = array_map(function ($articleTag) {
                return $articleTag['tagid'];
            }, $articleTags);

            $postTags = isset($posts['tag']) ? $posts['tag'] : [];

            $maxTags = (count($articleTags) > count($postTags)) ? $articleTags : $postTags;
            $minTags = (count($articleTags) < count($postTags)) ? $articleTags : $postTags;

            unset($articleTags);

            // 处理文章标签信息
            if ($postTags && array_diff($maxTags, $minTags)) {
                // 删除原有标签信息
                $result = ArticleTag::find("articleid={$id}")->delete();

                if ($result) {
                    $inserts = [
                        'articleid' => $id,
                        'creator' => $this->getUserName(),
                    ];

                    // 添加标签
                    foreach ($postTags as $tagId) {
                        $articleTag = new ArticleTag();

                        $inserts['createtime'] = time();
                        $inserts['tagid'] = $tagId;

                        $articleTag->save($inserts);
                    }
                }
            }

            return $this->success('文章更新成功', "/article/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('文章重复', "/article/edit/id/{$id}{$cond}");
            } else {
                return $this->error('文章更新失败', "/article/edit/id/{$id}{$cond}");
            }
        }
    }
}