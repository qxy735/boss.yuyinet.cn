<?php

use \Phalcon\Paginator\Adapter\Model;

class CommentController extends BaseController
{
    /**
     * 显示状态信息
     *
     * @var array
     */
    protected $shows = [
        '否',
        '是',
    ];
    /**
     * 置顶状态信息
     *
     * @var array
     */
    protected $tops = [
        '否',
        '是',
    ];
    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.facility.comment.L', 'blog.boss.facility.comment.R', 'blog.boss.facility.comment.S']],
        'delete' => [false, ['blog.boss.facility.comment.D', 'blog.boss.facility.comment.BD']],
        'profile' => [false, ['blog.boss.facility.comment.L', 'blog.boss.facility.comment.R', 'blog.boss.facility.comment.S']],
        'hide' => [false, ['blog.boss.facility.comment.SET']],
        'show' => [false, ['blog.boss.facility.comment.SET']],
        'top' => [false, ['blog.boss.facility.comment.SET']],
        'cancel' => [false, ['blog.boss.facility.comment.SET']],
    ];

    /**
     * 显示评论信息
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

            // 获取置顶状态
            $isTop = $this->getParam('istop');
            $isTop = (null === $isTop) ? -1 : intval($isTop);
            $isTopSql = (-1 === $isTop) ? '' : " and istop={$isTop}";

            // 获取是否显示
            $isShow = $this->getParam('isshow');
            $isShow = (null === $isShow) ? -1 : intval($isShow);
            $isShowSql = (-1 === $isShow) ? '' : " and isshow={$isShow}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取评论信息
            $comments = Comment::find([
                'conditions' => "1=1 {$isTopSql}{$isShowSql} " . 'order by articleid desc,id desc',
                'columns' => 'id,articleid,istop,isshow,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $comments,
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

            $comments = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 获取文章ID
            $articleIds = array_map(function ($comment) {
                return $comment['articleid'];
            }, $comments);

            $articles = [];

            // 获取文章标题
            if ($articleIds) {
                $articleIds = array_unique($articleIds);

                $tmpArticles = Article::find([
                    'conditions' => 'id in(' . implode(',', $articleIds) . ')',
                    'columns' => 'id,title'
                ])->toArray();

                foreach ($tmpArticles as $tmpArticle) {
                    $articles[$tmpArticle['id']] = $tmpArticle['title'];
                }

                unset($tmpArticles);
            }

            // 数值转换
            $comments = array_map(function ($comment) use ($articles) {
                $comment['createtime'] = $comment['createtime'] ? date('Y-m-d H:i:s', $comment['createtime']) : '';
                $comment['showname'] = $this->shows[$comment['isshow']];
                $comment['topname'] = $this->tops[$comment['istop']];
                $title = isset($articles[$comment['articleid']]) ? $articles[$comment['articleid']] : '';
                $count = mb_strlen($title, 'utf-8');
                $title = $title ? mb_substr($title, 0, 20, 'utf-8') : '';
                $comment['title'] = ($title && (mb_strlen($title, 'utf-8') < $count)) ? $title . '...' : $title;

                return $comment;
            }, $comments);

            // 传递评论信息
            $this->view->comments = $comments;

            unset($comments);

            // 传递分页信息
            $this->view->page = $page;

            // 传递显示状态信息
            $this->view->shows = $this->shows;

            // 传递置顶状态信息
            $this->view->tops = $this->tops;

            // 传递查询参数
            $this->view->istop = $isTop;
            $this->view->isshow = $isShow;

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ('' !== $isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

            // 传递查询条件组合
            $this->view->cond = $cond;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 传递默认置顶状态
            $this->view->defaultTop = Comment::TOP_COMMENT_YES;

            // 传递默认显示状态
            $this->view->defaultShow = Comment::SHOW_COMMENT_STATUS;

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.facility.comment.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.facility.comment.BD']);

            // 是否显示设置操作相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.facility.comment.SET']);

            return $this->view->pick('comment/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('评论信息加载失败', '/');
        }
    }

    /**
     * 删除评论信息
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取显示状态
        $isShow = (int)$this->getParam('isshow');

        // 获取置顶状态
        $isTop = (int)$this->getParam('istop');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

        try {
            // 获取评论 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = $isRelation = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的评论信息
                $comment = Comment::findFirst(intval($id));

                // 判断评论信息是否存在,存在则做删除
                if ($comment) {
                    // 获取评论回复信息
                    $replys = CommentReply::find([
                        'conditions' => "commentid={$id}",
                        'columns' => 'id'
                    ])->count();

                    // 判断是否存在评论回复信息
                    if ($replys) {
                        $isRelation = true;
                        continue;
                    }

                    // 删除指定评论信息
                    $result = $comment->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断是否存在关联信息
            if ($isRelation) {
                return $this->error('评论信息存在关联数据,不允许删除', "/comment/list{$cond}");
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('评论删除失败', "/comment/list{$cond}");
            }

            return $this->success('评论删除成功', "/comment/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('评论信息删除失败', "/comment/list{$cond}");
        }
    }

    /**
     * 显示评论信息
     *
     * @return mixed
     */
    public function profileAction()
    {
        // 获取显示状态
        $isShow = (int)$this->getParam('isshow');

        // 获取置顶状态
        $isTop = (int)$this->getParam('istop');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ('' !== $isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

        try {
            // 获取评论 ID
            $id = (int)$this->getParam('id');

            // 获取评论内容
            $comment = Comment::findFirst($id);

            // 判断评论内容是否存在
            if (!$comment) {
                return $this->error('评论内容不存在', "/comment/list{$cond}");
            }

            // 获取文章ID
            $articleId = $comment->articleid;

            // 获取文章信息
            $article = Article::findFirst($articleId);

            // 判断文章信息是否存在
            if (!$article) {
                return $this->error('文章不存在', "/comment/list{$cond}");
            }

            // 传递文章标题信息
            $this->view->title = $article->title;

            // 传递评论内容
            $this->view->content = $comment->content;

            // 传递评论人
            $this->view->name = $comment->creator;

            // 传递评论 ID
            $this->view->id = $comment->id;

            $this->view->cond = $cond;

            unset($comment);
            unset($article);

            return $this->view->pick('comment/profile');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('评论信息获取失败', "/comment/list{$cond}");
        }
    }

    /**
     * 隐藏评论信息
     *
     * @return mixed
     */
    public function hideAction()
    {
        // 获取显示状态
        $isShow = (int)$this->getParam('isshow');

        // 获取置顶状态
        $isTop = (int)$this->getParam('istop');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ('' !== $isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

        try {
            // 获取评论 ID
            $id = (int)$this->getParam('id');

            // 获取评论信息
            $comment = Comment::findFirst($id);

            // 评论信息存在则做更新操作
            if ($comment) {
                // 更新评论是否显示的状态
                $result = $comment->update([
                    'isshow' => Comment::HIDE_COMMENT_STATUS,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('评论隐藏设置失败', "/comment/list{$cond}");
                }
            }

            return $this->success('评论隐藏设置成功', "/comment/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('评论隐藏设置失败', "/comment/list{$cond}");
        }
    }

    /**
     * 显示评论信息
     *
     * @return mixed
     */
    public function showAction()
    {
        // 获取显示状态
        $isShow = (int)$this->getParam('isshow');

        // 获取置顶状态
        $isTop = (int)$this->getParam('istop');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ('' !== $isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

        try {
            // 获取评论 ID
            $id = (int)$this->getParam('id');

            // 获取评论信息
            $comment = Comment::findFirst($id);

            // 评论信息存在则做更新操作
            if ($comment) {
                // 更新评论是否显示的状态
                $result = $comment->update([
                    'isshow' => Comment::SHOW_COMMENT_STATUS,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('评论显示设置失败', "/comment/list{$cond}");
                }
            }

            return $this->success('评论显示设置成功', "/comment/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('评论显示设置失败', "/comment/list{$cond}");
        }
    }

    /**
     * 置顶文章评论
     *
     * @return mixed
     */
    public function topAction()
    {
        // 获取显示状态
        $isShow = (int)$this->getParam('isshow');

        // 获取置顶状态
        $isTop = (int)$this->getParam('istop');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ('' !== $isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

        try {
            // 获取评论 ID
            $id = (int)$this->getParam('id');

            // 获取评论信息
            $comment = Comment::findFirst($id);

            // 评论信息存在则做更新操作
            if ($comment) {
                // 判断该文章是否已有置顶评论
                $comments = Comment::find([
                    'conditions' => 'articleid=' . $comment->articleid . ' and istop=' . Comment::TOP_COMMENT_YES,
                    'columns' => 'id'
                ])->toArray();

                if ($comments) {
                    return $this->error('已存在文章置顶评论', "/comment/list{$cond}");
                }

                // 置顶评论
                $result = $comment->update([
                    'istop' => Comment::TOP_COMMENT_YES,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('评论置顶失败', "/comment/list{$cond}");
                }
            }

            return $this->success('评论置顶成功', "/comment/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('评论置顶失败', "/comment/list{$cond}");
        }
    }

    /**
     * 取消置顶文章评论
     *
     * @return mixed
     */
    public function cancelAction()
    {
        // 获取显示状态
        $isShow = (int)$this->getParam('isshow');

        // 获取置顶状态
        $isTop = (int)$this->getParam('istop');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ('' !== $isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

        try {
            // 获取评论 ID
            $id = (int)$this->getParam('id');

            // 获取评论信息
            $comment = Comment::findFirst($id);

            // 评论信息存在则做更新操作
            if ($comment) {
                // 取消置顶评论
                $result = $comment->update([
                    'istop' => Comment::TOP_COMMENT_NO,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('评论取消置顶失败', "/comment/list{$cond}");
                }
            }

            return $this->success('评论取消置顶成功', "/comment/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('评论取消置顶失败', "/comment/list{$cond}");
        }
    }
}