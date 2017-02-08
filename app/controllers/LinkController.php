<?php

use \Phalcon\Paginator\Adapter\Model;

class LinkController extends BaseController
{
    /**
     * 友情链接启用信息
     *
     * @var array
     */
    protected $enableds = [
        '否',
        '是',
    ];
    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.facility.link.L', 'blog.boss.facility.link.R', 'blog.boss.facility.link.S']],
        'create' => [false, ['blog.boss.facility.link.C']],
        'post' => [false, ['blog.boss.facility.link.C']],
        'delete' => [false, ['blog.boss.facility.link.D', 'blog.boss.facility.link.BD']],
        'disabled' => [false, ['blog.boss.facility.link.SET']],
        'enabled' => [false, ['blog.boss.facility.link.SET']],
        'edit' => [false, ['blog.boss.facility.link.U']],
        'save' => [false, ['blog.boss.facility.link.U']],
    ];

    /**
     * 显示友情链接页面
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

            // 获取友情链接名
            $name = $this->getParam('name');
            $name = urldecode($name);
            $nameSql = $name ? " and name like '%{$name}%'" : '';

            // 获取启用状态
            $enabled = $this->getParam('enabled');
            $enabled = (null === $enabled) ? -1 : intval($enabled);
            $enabledSql = (-1 === $enabled) ? '' : " and enabled={$enabled}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取友情链接信息
            $links = Link::find([
                'conditions' => "1=1 {$nameSql}{$enabledSql} " . 'order by displayorder desc,id asc',
                'columns' => 'id,name,enabled,displayorder,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $links,
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

            $links = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 转换数值
            $links = array_map(function ($link) {
                $link['createtime'] = $link['createtime'] ? date('Y-m-d H:i:s', $link['createtime']) : '';
                $link['lastoperate'] = $link['lastoperate'] ? date('Y-m-d H:i:s', $link['lastoperate']) : '';
                $link['enabledname'] = $this->enableds[$link['enabled']];

                return $link;
            }, $links);

            // 传递友情链接
            $this->view->links = $links;

            unset($links);

            // 传递查询参数
            $this->view->enabled = $enabled;
            $this->view->name = $name;

            // 传递启用值
            $this->view->defaultEnabled = Link::ENABLE_BLOG_LINK;

            // 传递启用信息
            $this->view->enableds = $this->enableds;

            // 传递分页信息
            $this->view->page = $page;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

            // 传递查询条件信息
            $this->view->cond = $cond;

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.facility.link.C']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.facility.link.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.facility.link.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.facility.link.U']);

            // 是否显示设置操作相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.facility.link.SET']);

            return $this->view->pick('link/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('友情链接页面加载失败', '/');
        }
    }

    /**
     * 显示添加友情链接页面
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
            // 启用状态信息
            $this->view->enableds = $this->enableds;

            // 传递默认启用状态
            $this->view->defaultEnabled = Link::ENABLE_BLOG_LINK;

            // 传递查询条件信息
            $this->view->cond = $cond;

            return $this->view->pick('link/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('友情链接添加页面加载失败', "/link/list{$cond}");
        }
    }

    /**
     * 添加友情链接信息
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
            $link = new Link();

            // 获取需要添加的友情链接数据
            $posts = $this->request->getPost();

            // 判断友情链接名称是否为空
            if (!isset($posts['name']) || !$posts['name']) {
                return $this->error('友情链接名称必填', "/link/create{$cond}");
            }

            // 判断友情链接地址是否为空
            if (!isset($posts['url']) || !$posts['url']) {
                return $this->error('友情链接地址必填', "/link/create{$cond}");
            }

            $posts['createtime'] = time();
            $posts['creator'] = $this->getUserName();

            // 添加友情链接信息
            $result = $link->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('友情链接信息添加失败', "/link/create{$cond}");
            }

            return $this->success('友情链接信息添加成功', "/link/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('友情链接信息重复', "/link/create{$cond}");
            } else {
                return $this->error('友情链接信息添加失败', "/link/create{$cond}");
            }
        }
    }

    /**
     * 删除友情链接
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取友情链接名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取友情链接信息 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的友情链接信息
                $link = Link::findFirst(intval($id));

                // 判断友情链接信息是否存在,存在则做删除
                if ($link) {
                    // 删除指定友情链接信息
                    $result = $link->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('友情链接删除失败', "/link/list{$cond}");
            }

            return $this->success('友情链接删除成功', "/link/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('友情链接删除失败', "/link/list{$cond}");
        }
    }

    /**
     * 禁用友情链接
     *
     * @return mixed
     */
    public function disabledAction()
    {
        // 获取友情链接名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取友情链接信息 ID
            $id = (int)$this->getParam('id');

            // 获取友情链接信息
            $link = Link::findFirst($id);

            // 友情链接信息存在则做更新操作
            if ($link) {
                // 禁用友情链接信息
                $result = $link->update([
                    'enabled' => Link::DISABLE_BLOG_LINK,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('友情链接禁用失败', "/link/list{$cond}");
                }
            }

            return $this->success('友情链接禁用成功', "/link/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('友情链接禁用失败', "/link/list{$cond}");
        }
    }

    /**
     * 启用友情链接
     *
     * @return mixed
     */
    public function enabledAction()
    {
        // 获取友情链接名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取友情链接信息 ID
            $id = (int)$this->getParam('id');

            // 获取友情链接信息
            $link = Link::findFirst($id);

            // 友情链接信息存在则做更新操作
            if ($link) {
                // 启用友情链接信息
                $result = $link->update([
                    'enabled' => Link::ENABLE_BLOG_LINK,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('友情链接启用失败', "/link/list{$cond}");
                }
            }

            return $this->success('友情链接启用成功', "/link/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('友情链接启用失败', "/link/list{$cond}");
        }
    }

    /**
     * 显示编辑友情链接页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取友情链接名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取友情链接信息 ID
            $id = (int)$this->getParam('id');

            // 根据 ID 获取对应的友情链接信息
            $link = Link::find($id)->toArray();

            // 判断链接信息是否存在
            if (!$link) {
                return $this->error('友情链接信息不存在', "/link/list{$cond}");
            }

            $link = $link[0];

            // 传递友情链接信息
            $this->view->link = $link;

            $this->view->conds = [
                '_name' => $name,
                '_page' => $page,
                '_enabled' => $enabled,
            ];

            $this->view->cond = $cond;

            // 传递启用状态信息
            $this->view->enableds = $this->enableds;

            return $this->view->pick('link/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('友情链接编辑页面加载失败', "/link/list{$cond}");
        }
    }

    /**
     * 更新友情链接
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取友情链接信息 ID
        $id = (int)$this->getParam('id');

        // 获取友情链接名称
        $name = urldecode($this->getParam('_name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('_enabled');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('_page');
        $page = $page ? : 1;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取友情链接信息
            $link = Link::findFirst($id);

            // 判断友情链接信息是否存在
            if (!$link) {
                return $this->error('友情链接信息不存在', "/link/list{$cond}");
            }

            // 获取需要更新的友情链接信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts) {
                return $this->error('更新内容不能全为空', "/link/edit/id/{$id}{$cond}");
            }

            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新友情链接信息
            $result = $link->update($posts);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('友情链接更新失败', "/link/edit/id/{$id}{$cond}");
            }

            return $this->success('友情链接更新成功', "/link/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('友情链接信息重复', "/link/edit/id/{$id}{$cond}");
            } else {
                return $this->error('友情链接更新失败', "/link/edit/id/{$id}{$cond}");
            }
        }
    }
}