<?php

use \Phalcon\Paginator\Adapter\Model;

class BlackController extends BaseController
{
    /**
     * 禁用类型
     *
     * @var array
     */
    protected $types = [
        '所有功能',
        '前台功能',
        '后台功能',
        '微信功能',
    ];
    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.siteuser.black.L', 'blog.boss.siteuser.black.R', 'blog.boss.siteuser.black.S']],
        'create' => [false, ['blog.boss.siteuser.black.C']],
        'post' => [false, ['blog.boss.siteuser.black.C']],
        'delete' => [false, ['blog.boss.siteuser.black.D', 'blog.boss.siteuser.black.BD']],
        'edit' => [false, ['blog.boss.siteuser.black.U']],
        'save' => [false, ['blog.boss.siteuser.black.U']],
    ];

    /**
     * 显示黑名单信息页面
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

            // 获取用户名
            $userName = $this->getParam('username');
            $userName = urldecode($userName);
            $userNameSql = $userName ? " and username like '%{$userName}%'" : '';

            // 获取禁用功能类型
            $type = $this->getParam('type');
            $type = (null === $type) ? -1 : intval($type);
            $typeSql = (-1 === $type) ? '' : " and type={$type}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取黑名单信息
            $blacks = BlackList::find([
                'conditions' => "1=1 {$userNameSql}{$typeSql} " . 'order by id desc',
                'columns' => 'id,uid,username,type,cause,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $blacks,
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

            $blacks = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($userName ? "/username/{$userName}" : '') . ('' !== $type ? "/type/{$type}" : '');

            // 转化数值
            $blacks = array_map(function ($black) {
                $black['createtime'] = $black['createtime'] ? date('Y-m-d H:i:s', $black['createtime']) : '';
                $black['lastoperate'] = $black['lastoperate'] ? date('Y-m-d H:i:s', $black['lastoperate']) : '';
                $black['typename'] = $this->types[$black['type']];

                return $black;
            }, $blacks);

            // 传递黑名单信息
            $this->view->blacks = $blacks;

            // 传递分页信息
            $this->view->page = $page;

            $this->view->navId = $navId;

            $this->view->cond = $cond;

            // 传递查询参数
            $this->view->type = $type;
            $this->view->userName = $userName;

            // 传递禁用类型
            $this->view->types = $this->types;

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.siteuser.black.C']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.siteuser.black.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.siteuser.black.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.siteuser.black.U']);

            return $this->view->pick('black/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('黑名单页面加载失败', '/');
        }
    }

    /**
     * 显示添加黑名单页面
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
            // 传递禁用功能类型信息
            $this->view->types = $this->types;

            $this->view->cond = $cond;

            return $this->view->pick('black/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误日志信息
            return $this->error('黑名单添加页面加载失败', "/black/list{$cond}");
        }
    }

    /**
     * 添加黑名单信息
     *
     * @return mixed
     */
    public function postAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        $cond = "/navid/{$navId}";

        try {
            $black = new BlackList();

            // 获取需要添加的黑名单数据
            $posts = $this->request->getPost();

            // 判断用户ID是否为空
            if (!isset($posts['uid']) || !$posts['uid']) {
                return $this->error('用户ID必填', "/black/create{$cond}");
            }

            // 获取用户信息
            $user = User::findFirst($posts['uid']);

            // 判断用户信息是否存在
            if (!$user) {
                return $this->error('用户不存在', "/black/create{$cond}");
            }

            $posts['username'] = $user->username;
            $posts['createtime'] = time();
            $posts['creator'] = $this->getUserName();

            // 添加黑名单信息
            $result = $black->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('黑名单信息添加失败', "/black/create{$cond}");
            }

            return $this->success('黑名单信息添加成功', "/black/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('黑名单信息重复', "/black/create{$cond}");
            } else {
                return $this->error('黑名单信息添加失败', "/black/create{$cond}");
            }
        }
    }

    /**
     * 删除黑名单信息
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取禁用功能类型
        $type = (int)$this->getParam('type');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($userName ? "/username/{$userName}" : '') . ('' !== $type ? "/type/{$type}" : '');

        try {
            // 获取黑名单信息 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的黑名单信息
                $black = BlackList::findFirst(intval($id));

                // 判断黑名单信息是否存在,存在则做删除
                if ($black) {
                    // 删除指定黑名单信息
                    $result = $black->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('黑名单信息删除失败', "/black/list{$cond}");
            }

            return $this->success('黑名单信息删除成功', "/black/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('黑名单信息删除失败', "/black/list{$cond}");
        }
    }

    /**
     * 显示编辑黑名单信息页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取禁用功能类型
        $type = (int)$this->getParam('type');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($userName ? "/username/{$userName}" : '') . ('' !== $type ? "/type/{$type}" : '');

        try {
            // 获取黑名单信息 ID
            $id = (int)$this->getParam('id');

            // 根据 ID 获取对应的黑名单信息
            $black = BlackList::find($id)->toArray();

            // 判断黑名单信息是否存在
            if (!$black) {
                return $this->error('黑名单信息不存在', "/black/list{$cond}");
            }

            $black = $black[0];

            // 传递禁用功能类型信息
            $this->view->types = $this->types;

            // 传递黑名单信息
            $this->view->black = $black;

            $this->view->conds = [
                '_username' => $userName,
                '_type' => $type,
                '_page' => $page,
            ];

            $this->view->cond = $cond;

            return $this->view->pick('black/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('黑名单编辑页面加载失败', "/black/list{$cond}");
        }
    }

    /**
     * 更新黑名单信息
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('_username'));

        // 获取禁用功能类型
        $type = (int)$this->getParam('_type');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('_page');
        $page = $page ? : 1;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($userName ? "/username/{$userName}" : '') . ('' !== $type ? "/type/{$type}" : '');

        // 获取黑名单信息 ID
        $id = (int)$this->request->getPost('id');

        try {
            // 获取黑名单信息
            $black = BlackList::findFirst($id);

            // 判断黑名单信息是否存在
            if (!$black) {
                return $this->error('黑名单信息不存在', "/black/list{$cond}");
            }

            // 获取需要更新的黑名单信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts) {
                return $this->error('更新内容不能全为空', "/black/edit/id/{$id}{$cond}");
            }

            // 获取用户 ID
            $uid = isset($posts['uid']) ? $posts['uid'] : 0;

            // 获取用户信息
            $user = User::findFirst($uid);

            // 判断用户信息是否存在
            if (!$user) {
                return $this->error('用户不存在', "/black/edit/id/{$id}{$cond}");
            }

            $posts['username'] = $user->username;
            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新黑名单信息
            $result = $black->update($posts);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('黑名单信息更新失败', "/black/edit/id/{$id}{$cond}");
            }

            return $this->success('黑名单信息更新成功', "/black/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('黑名单信息重复', "/black/edit/id/{$id}{$cond}");
            } else {
                return $this->error('黑名单信息修改失败', "/black/edit/id/{$id}{$cond}");
            }
        }
    }
}