<?php

use \Phalcon\Paginator\Adapter\Model;

class NoticeController extends BaseController
{
    /**
     * 公告状态信息
     *
     * @var array
     */
    protected $status = [
        '待显示',
        '显示中',
        '预显示',
        '已关闭',
    ];
    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.facility.notice.L', 'blog.boss.facility.notice.R', 'blog.boss.facility.notice.S']],
        'create' => [false, ['blog.boss.facility.notice.C']],
        'post' => [false, ['blog.boss.facility.notice.C']],
        'delete' => [false, ['blog.boss.facility.notice.D', 'blog.boss.facility.notice.BD']],
        'edit' => [false, ['blog.boss.facility.notice.U']],
        'save' => [false, ['blog.boss.facility.notice.U']],
    ];

    /**
     * 显示公告信息页面
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

            // 获取公告标题
            $title = $this->getParam('title');
            $title = urldecode($title);
            $titleSql = $title ? " and title like '%{$title}%'" : '';

            // 获取发布人名
            $sendName = $this->getParam('sendname');
            $sendName = urldecode($sendName);
            $sendNameSql = $sendName ? " and sendname like '%{$sendName}%'" : '';

            // 获取公告状态
            $status = $this->getParam('status');
            $status = (null === $status) ? -1 : intval($status);
            $statusSql = (-1 === $status) ? '' : " and status={$status}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取公告信息
            $notices = Notice::find([
                'conditions' => "1=1 {$titleSql}{$sendNameSql}{$statusSql} " . 'order by id desc',
                'columns' => 'id,title,sendname,status,showtime,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $notices,
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

            $notices = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 转换数值
            $notices = array_map(function ($notice) {
                $notice['createtime'] = $notice['createtime'] ? date('Y-m-d H:i:s', $notice['createtime']) : '';
                $notice['lastoperate'] = $notice['lastoperate'] ? date('Y-m-d H:i:s', $notice['lastoperate']) : '';
                $notice['showtime'] = $notice['showtime'] ? date('Y-m-d H:i:s', $notice['showtime']) : '';
                $notice['statusname'] = $this->status[$notice['status']];

                return $notice;
            }, $notices);

            // 传递公告信息
            $this->view->notices = $notices;

            unset($notices);

            // 传递分页信息
            $this->view->page = $page;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($title ? "/title/{$title}" : '') . ($sendName ? "/sendname/{$sendName}" : '') . ('' !== $status ? "/status/{$status}" : '');

            // 传递查询条件信息
            $this->view->cond = $cond;

            // 传递公告状态信息
            $this->view->status = $this->status;

            // 传递查询参数
            $this->view->statu = $status;
            $this->view->title = $title;
            $this->view->sendName = $sendName;

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.facility.notice.C']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.facility.notice.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.facility.notice.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.facility.notice.U']);

            // 传递当前登陆人
            $this->view->loginName = $this->getUserName();

            // 传递删除状态
            $this->view->delStatus = Notice::DEL_NOTICE_STATUS;

            return $this->view->pick('notice/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('公告信息页面加载失败', '/');
        }
    }

    /**
     * 显示添加公告信息页面
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
            // 公告状态信息
            $this->view->status = $this->status;

            // 传递查询条件信息
            $this->view->cond = $cond;

            $this->view->uploadUrl = '/assets/ueditor/php/imageUp.php?m=notice';

            return $this->view->pick('notice/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('添加公告信息页面加载失败', "/notice/list{$cond}");
        }
    }

    /**
     * 添加公告信息
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
            $notice = new Notice();

            // 获取需要添加的公告数据
            $posts = $this->request->getPost();

            // 判断公告标题是否为空
            if (!isset($posts['title']) || !$posts['title']) {
                return $this->error('公告标题必填', "/notice/create{$cond}");
            }

            $status = isset($posts['status']) ? $posts['status'] : 0;

            if (Notice::SHOW_NOTICE_STATUS == $status) {
                $posts['showtime'] = time();
            }

            if (isset($posts['editorValue'])) {
                $posts['content'] = $posts['editorValue'];

                unset($posts['editorValue']);
            }

            $posts['sendid'] = $this->getUserId();
            $posts['sendname'] = $this->getUserName();
            $posts['createtime'] = time();
            $posts['creator'] = $this->getUserName();

            // 添加公告信息
            $result = $notice->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('公告信息添加失败', "/notice/create{$cond}");
            }

            return $this->success('公告信息添加成功', "/notice/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('公告信息重复', "/notice/create{$cond}");
            } else {
                return $this->error('公告信息添加失败', "/notice/create{$cond}");
            }
        }
    }

    /**
     * 删除公告信息
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取公告标题
        $title = urldecode($this->getParam('title'));

        // 获取公告发布人
        $sendName = urldecode($this->getParam('sendname'));

        // 获取公告状态
        $status = (int)$this->getParam('status');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($title ? "/title/{$title}" : '') . ($sendName ? "/sendname/{$sendName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取公告信息 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的公告信息
                $notice = Notice::findFirst(intval($id));

                // 判断公告信息是否存在,存在则做删除
                if ($notice) {
                    // 删除指定公告信息
                    $result = $notice->update([
                        'status' => Notice::DEL_NOTICE_STATUS,
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
                return $this->error('公告信息删除失败', "/notice/list{$cond}");
            }

            return $this->success('公告信息删除成功', "/notice/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('公告信息删除失败', "/notice/delete{$cond}");
        }
    }

    /**
     * 显示编辑公告信息页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取公告标题
        $title = urldecode($this->getParam('title'));

        // 获取公告发布人
        $sendName = urldecode($this->getParam('sendname'));

        // 获取公告状态
        $status = (int)$this->getParam('status');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($title ? "/title/{$title}" : '') . ($sendName ? "/sendname/{$sendName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取公告信息 ID
            $id = (int)$this->getParam('id');

            // 根据 ID 获取对应的公告信息
            $notice = Notice::find($id)->toArray();

            // 判断公告信息是否存在
            if (!$notice) {
                return $this->error('公告信息不存在', "/notice/list{$cond}");
            }

            $notice = $notice[0];

            // 传递公告信息
            $this->view->notice = $notice;

            $this->view->conds = [
                '_title' => $title,
                '_page' => $page,
                '_status' => $status,
                '_sendname' => $sendName,
            ];

            $this->view->cond = $cond;

            // 传递公告状态信息
            $this->view->status = $this->status;

            $this->view->uploadUrl = '/assets/ueditor/php/imageUp.php?m=notice';

            return $this->view->pick('notice/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('公告信息编辑页面加载失败', "/notice/list{$cond}");
        }
    }

    /**
     * 更新公告信息
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取公告信息 ID
        $id = (int)$this->getParam('id');

        // 获取公告标题
        $title = urldecode($this->getParam('_title'));

        // 获取公告发布人
        $sendName = urldecode($this->getParam('_sendname'));

        // 获取公告状态
        $status = (int)$this->getParam('_status');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('_page');
        $page = $page ? : 1;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($title ? "/title/{$title}" : '') . ($sendName ? "/sendname/{$sendName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取公告信息
            $notice = Notice::findFirst($id);

            // 判断公告信息是否存在
            if (!$notice) {
                return $this->error('公告信息不存在', "/notice/list{$cond}");
            }

            // 获取需要更新的公告信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts) {
                return $this->error('更新内容不能全为空', "/notice/edit/id/{$id}{$cond}");
            }

            if (Notice::SHOW_NOTICE_STATUS == $status) {
                $posts['showtime'] = time();
            }

            if (isset($posts['editorValue'])) {
                $posts['content'] = $posts['editorValue'];

                unset($posts['editorValue']);
            }

            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新公告信息
            $result = $notice->update($posts);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('公告信息更新失败', "/notice/edit/id/{$id}{$cond}");
            }

            return $this->success('公告信息更新成功', "/notice/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('公告信息重复', "/notice/edit/id/{$id}{$cond}");
            } else {
                return $this->error('公告信息更新失败', "/notice/edit/id/{$id}{$cond}");
            }
        }
    }
}