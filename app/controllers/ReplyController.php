<?php

use \Phalcon\Paginator\Adapter\Model;

class ReplyController extends BaseController
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
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.facility.comment.L', 'blog.boss.facility.comment.R', 'blog.boss.facility.comment.S']],
        'create' => [false, ['blog.boss.facility.comment.SEND']],
        'post' => [false, ['blog.boss.facility.comment.SEND']],
        'delete' => [false, ['blog.boss.facility.comment.D', 'blog.boss.facility.comment.BD']],
        'hide' => [false, ['blog.boss.facility.comment.SET']],
        'show' => [false, ['blog.boss.facility.comment.SET']],
    ];

    /**
     * 显示评论回复页面
     *
     * @return mixed
     */
    public function listAction()
    {
        // 获取显示状态
        $isShow = (int)$this->getParam('isshow');

        // 获取置顶状态
        $isTop = (int)$this->getParam('istop');

        // 获取分页信息
        $page = (int)$this->getParam('page');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

        try {
            // 获取评论 ID
            $commentId = (int)$this->getParam('commentid');

            // 获取评论信息
            $comment = Comment::findFirst($commentId);

            // 判断评论信息是否存在
            if (!$comment) {
                return $this->error('评论信息不存在', "/comment/list{$cond}");
            }

            // 获取当前页码
            $curPage = (int)$this->getParam('_page');
            $curPage = $curPage ? : 1;

            // 获取每页显示的数据量
            $pageSize = $this->pageSize;

            // 获取评论回复信息
            $replys = CommentReply::find([
                'conditions' => "commentid={$commentId}" . 'order by createtime desc,id desc',
                'columns' => 'id,commentid,isshow,content,createtime,creator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $replys,
                    "limit" => $pageSize,
                    "page" => $curPage
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
                'page' => $curPage,
                'pagesize' => $pageSize,
                'total' => $pageDatas->total_items,
            ];

            $replys = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 数值转换
            $replys = array_map(function ($reply) {
                $reply['createtime'] = $reply['createtime'] ? date('Y-m-d H:i:s', $reply['createtime']) : '';
                $reply['showname'] = $this->shows[$reply['isshow']];

                return $reply;
            }, $replys);

            // 传递评论回复信息
            $this->view->replys = $replys;

            unset($replys);

            // 传递查询条件组合
            $this->view->cond = "/commentid/{$commentId}" . $cond;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 传递分页信息
            $this->view->page = $page;

            // 传递默认显示状态
            $this->view->defaultShow = CommentReply::SHOW_REPLY_STATUS;

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.facility.comment.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.facility.comment.BD']);

            // 是否显示设置操作相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.facility.comment.SET']);

            // 是否显示回复按钮
            $this->view->isSendBut = $this->hasAuth(['blog.boss.facility.comment.SEND']);

            return $this->view->pick('reply/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('评论回复信息加载失败', "/comment/list{$cond}");
        }
    }

    /**
     * 显示回复页面
     *
     * @return mixed
     */
    public function createAction()
    {
        // 获取显示状态
        $isShow = (int)$this->getParam('isshow');

        // 获取置顶状态
        $isTop = (int)$this->getParam('istop');

        // 获取分页信息
        $page = (int)$this->getParam('page');

        // 获取评论 ID
        $commentId = (int)$this->getParam('commentid');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/commentid/{$commentId}" . ($isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

        try {
            // 获取评论信息
            $comment = Comment::findFirst($commentId);

            // 判断评论信息是否存在
            if (!$comment) {
                return $this->error('评论信息不存在', "/comment/list{$cond}");
            }

            // 传递评论内容
            $this->view->content = $comment->content;

            // 传递评论人
            $this->view->name = $comment->creator;

            // 传递评论 ID
            $this->view->id = $comment->id;

            unset($comment);

            // 传递导航菜单
            $this->view->cond = $cond;

            return $this->view->pick('reply/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('回复页面加载失败', "/reply/list{$cond}");
        }
    }

    /**
     * 回复评论
     *
     * @return mixed
     */
    public function postAction()
    {
        // 获取显示状态
        $isShow = (int)$this->getParam('isshow');

        // 获取置顶状态
        $isTop = (int)$this->getParam('istop');

        // 获取分页信息
        $page = (int)$this->getParam('page');

        // 获取评论 ID
        $commentId = (int)$this->getParam('commentid');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/commentid/{$commentId}" . ($isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

        try {
            $reply = new CommentReply();

            // 获取需要添加的评论回复数据
            $posts = $this->request->getPost();

            // 判断回复内容是否为空
            if (!isset($posts['content']) || !$posts['content']) {
                return $this->error('评论回复内容不能为空', "/reply/create{$cond}");
            }

            $posts['uid'] = $this->getUserId();
            $posts['isshow'] = CommentReply::SHOW_REPLY_STATUS;

            // 添加时间
            $posts['createtime'] = time();

            // 添加者
            $posts['creator'] = $this->getUserName();

            $result = $reply->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('评论回复失败', "/reply/create{$cond}");
            }

            return $this->success('评论回复成功', "/reply/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('评论回复重复', "/reply/create{$cond}");
            } else {
                return $this->error('评论回复失败', "/reply/create{$cond}");
            }
        }
    }

    /**
     * 删除评论回复内容
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取显示状态
        $isShow = (int)$this->getParam('isshow');

        // 获取置顶状态
        $isTop = (int)$this->getParam('istop');

        // 获取分页信息
        $page = (int)$this->getParam('page');

        // 获取评论 ID
        $commentId = (int)$this->getParam('commentid');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/commentid/{$commentId}" . ($isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

        try {
            // 获取评论回复 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的评论回复信息
                $reply = CommentReply::findFirst(intval($id));

                // 判断评论回复信息是否存在,存在则做删除
                if ($reply) {
                    // 删除指定评论回复信息
                    $result = $reply->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('评论回复信息删除失败', "/reply/list{$cond}");
            }

            return $this->success('评论回复信息删除成功', "/reply/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('评论回复信息删除失败', "/reply/list{$cond}");
        }
    }

    /**
     * 隐藏评论回复信息
     *
     * @return mixed
     */
    public function hideAction()
    {
        // 获取显示状态
        $isShow = (int)$this->getParam('isshow');

        // 获取置顶状态
        $isTop = (int)$this->getParam('istop');

        // 获取分页信息
        $page = (int)$this->getParam('page');
        $curPage = (int)$this->getParam('_page');

        // 获取评论 ID
        $commentId = (int)$this->getParam('commentid');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/_page/{$curPage}/commentid/{$commentId}" . ($isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

        try {
            // 获取评论回复 ID
            $id = (int)$this->getParam('id');

            // 获取评论回复信息
            $reply = CommentReply::findFirst($id);

            // 评论回复信息存在则做更新操作
            if ($reply) {
                // 更新评论回复是否显示的状态
                $result = $reply->update([
                    'isshow' => CommentReply::HIDE_REPLY_STATUS,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('评论回复隐藏设置失败', "/reply/list{$cond}");
                }
            }

            return $this->success('评论回复隐藏设置成功', "/reply/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('评论回复隐藏设置失败', "/reply/list{$cond}");
        }
    }

    /**
     * 显示评论回复信息
     *
     * @return mixed
     */
    public function showAction()
    {
        // 获取显示状态
        $isShow = (int)$this->getParam('isshow');

        // 获取置顶状态
        $isTop = (int)$this->getParam('istop');

        // 获取分页信息
        $page = (int)$this->getParam('page');
        $curPage = (int)$this->getParam('_page');

        // 获取评论 ID
        $commentId = (int)$this->getParam('commentid');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/_page/{$curPage}/commentid/{$commentId}" . ($isShow ? "/isshow/{$isShow}" : '') . ('' !== $isTop ? "/istop/{$isTop}" : '');

        try {
            // 获取评论回复 ID
            $id = (int)$this->getParam('id');

            // 获取评论回复信息
            $reply = CommentReply::findFirst($id);

            // 评论回复信息存在则做更新操作
            if ($reply) {
                // 更新评论回复是否显示的状态
                $result = $reply->update([
                    'isshow' => CommentReply::SHOW_REPLY_STATUS,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('评论回复显示设置失败', "/reply/list{$cond}");
                }
            }

            return $this->success('评论回复显示设置成功', "/reply/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('评论回复显示设置失败', "/reply/list{$cond}");
        }
    }
}