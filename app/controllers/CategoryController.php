<?php

use \Phalcon\Paginator\Adapter\Model;

class CategoryController extends BaseController
{
    /**
     * 是否启用
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
        'list' => [false, ['blog.boss.facility.category.L', 'blog.boss.facility.category.R', 'blog.boss.facility.category.S']],
        'create' => [false, ['blog.boss.facility.category.C']],
        'post' => [false, ['blog.boss.facility.category.C']],
        'delete' => [false, ['blog.boss.facility.category.D', 'blog.boss.facility.category.BD']],
        'disabled' => [false, ['blog.boss.facility.category.SET']],
        'enabled' => [false, ['blog.boss.facility.category.SET']],
        'edit' => [false, ['blog.boss.facility.category.U']],
        'save' => [false, ['blog.boss.facility.category.U']],
    ];

    /**
     * 显示分类信息页面
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

            // 获取分类名
            $name = $this->getParam('name');
            $name = urldecode($name);
            $nameSql = $name ? " and name like '%{$name}%'" : '';

            // 获取启用状态
            $enabled = $this->getParam('enabled');
            $enabled = (null === $enabled) ? -1 : intval($enabled);
            $enabledSql = (-1 === $enabled) ? '' : " and enabled={$enabled}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取顶级分类信息
            $categorys = Category::find([
                'conditions' => 'parentid=' . Category::TOP_CATEGORY_ID . " {$nameSql}{$enabledSql} " . 'order by displayorder desc,id desc',
                'columns' => 'id,name,enabled,displayorder,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $categorys,
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

            $categorys = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 转换数值
            $categorys = array_map(function ($category) {
                $category['enabledname'] = $this->enableds[$category['enabled']];
                $category['createtime'] = $category['createtime'] ? date('Y-m-d H:i:s', $category['createtime']) : '';
                $category['lastoperate'] = $category['lastoperate'] ? date('Y-m-d H:i:s', $category['lastoperate']) : '';

                return $category;
            }, $categorys);

            // 传递分类信息
            $this->view->categorys = $categorys;

            unset($categorys);

            // 传递分页信息
            $this->view->page = $page;

            // 传递启用状态信息
            $this->view->enableds = $this->enableds;

            // 传递查询参数
            $this->view->enabled = $enabled;
            $this->view->name = $name;

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

            // 传递查询条件组合
            $this->view->cond = $cond;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            $this->view->defaultEnabled = Category::IS_ENABLED_CATEGORY;

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.facility.category.C']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.facility.category.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.facility.category.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.facility.category.U']);

            // 是否显示设置操作相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.facility.category.SET']);

            return $this->view->pick('category/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('分类信息页面加载失败', '/');
        }
    }

    /**
     * 显示添加分类页面
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
            // 获取顶级分类信息
            $categorys = Category::find([
                'conditions' => 'parentid=' . Category::TOP_CATEGORY_ID . 'order by displayorder desc,id desc',
                'columns' => 'id,name'
            ])->toArray();

            // 传递顶级分类信息
            $this->view->categorys = $categorys;

            unset($categorys);

            // 传递启用状态
            $this->view->enableds = $this->enableds;

            $this->view->enabled = Category::IS_ENABLED_CATEGORY;

            $this->view->cond = $cond;

            return $this->view->pick('category/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('分类信息添加页面加载失败', "/category/list{$cond}");
        }
    }

    /**
     * 添加分类信息
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
            $category = new Category();

            // 获取需要添加的分类信息数据
            $posts = $this->request->getPost();

            // 判断分类名称是否为空
            if (!isset($posts['name']) || !$posts['name']) {
                return $this->error('分类名称必填', "/category/create{$cond}");
            }

            $posts['level'] = $posts['parentid'] ? 1 : 0;
            $posts['createtime'] = time();
            $posts['creator'] = $this->getUserName();

            // 添加分类信息
            $result = $category->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('分类信息添加失败', "/category/create{$cond}");
            }

            return $this->success('分类信息添加成功', "/category/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('分类信息重复', "/category/create{$cond}");
            } else {
                return $this->error('分类信息添加失败', "/category/create{$cond}");
            }
        }
    }

    /**
     * 删除分类信息
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取分类名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取后台用户 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = $isRelation = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的分类信息
                $category = Category::findFirst(intval($id));

                // 判断分类信息是否存在,存在则做删除
                if ($category) {
                    // 获取关联数据
                    $sonCategorys = Category::findFirst([
                        'conditions' => "parentid={$id}",
                        'columns' => 'id',
                    ]);

                    $articles = Article::findFirst([
                        'conditions' => "categoryid={$id}",
                        'columns' => 'id',
                    ]);

                    // 判断是否存在关联关系
                    if ($sonCategorys || $articles) {
                        $isRelation = true;
                        continue;
                    }

                    // 删除指定分类信息
                    $result = $category->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断是否存在关联关系
            if ($isRelation) {
                return $this->error('分类信息存在关联数据,不允许删除', "/category/list{$cond}");
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('分类信息删除失败', "/category/list{$cond}");
            }

            return $this->success('分类信息删除成功', "/category/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('分类信息删除失败', "/category/list{$cond}");
        }
    }

    /**
     * 禁用分类信息
     *
     * @return mixed
     */
    public function disabledAction()
    {
        // 获取分类名称
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
            // 获取分类信息 ID
            $id = (int)$this->getParam('id');

            // 获取分类信息
            $category = Category::findFirst($id);

            // 分类信息存在则做更新操作
            if ($category) {
                // 更新分类信息是否启用的状态
                $result = $category->update([
                    'enabled' => Category::IS_DISABLED_CATEGORY,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('分类信息禁用失败', "/category/list{$cond}");
                }
            }

            return $this->success('分类信息禁用成功', "/category/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('分类信息禁用失败', "/category/list{$cond}");
        }
    }

    /**
     * 启用分类信息
     *
     * @return mixed
     */
    public function enabledAction()
    {
        // 获取分类名称
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
            // 获取分类信息 ID
            $id = (int)$this->getParam('id');

            // 获取分类信息
            $category = Category::findFirst($id);

            // 分类信息存在则做更新操作
            if ($category) {
                // 更新分类信息是否启用的状态
                $result = $category->update([
                    'enabled' => Category::IS_ENABLED_CATEGORY,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('分类信息启用失败', "/category/list{$cond}");
                }
            }

            return $this->success('分类信息启用成功', "/category/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('分类信息启用失败', "/category/list{$cond}");
        }
    }

    /**
     * 显示分类信息编辑页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取分类名称
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
            // 获取分类信息 ID
            $id = (int)$this->getParam('id');

            // 根据 ID 获取对应的分类信息
            $category = Category::find($id)->toArray();

            // 判断分类信息是否存在
            if (!$category) {
                return $this->error('分类信息不存在', "/category/list{$cond}");
            }

            $category = $category[0];

            // 传递分类信息
            $this->view->category = $category;

            // 获取顶级分类信息
            $categorys = Category::find([
                'conditions' => 'parentid=' . Category::TOP_CATEGORY_ID . 'order by displayorder desc,id desc',
                'columns' => 'id,name'
            ])->toArray();

            // 传递顶级分类信息
            $this->view->categorys = $categorys;

            unset($categorys);

            // 传递启用状态信息
            $this->view->enableds = $this->enableds;

            $this->view->conds = [
                '_name' => $name,
                '_page' => $page,
                '_enabled' => $enabled,
            ];

            $this->view->cond = $cond;

            return $this->view->pick('category/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('分类信息更新页面加载失败', "/category/list{$cond}");
        }
    }

    /**
     * 更新分类信息
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取分类信息 ID
        $id = (int)$this->request->getPost('id');

        // 获取分类名称
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
            // 获取分类信息
            $category = Category::findFirst($id);

            // 判断分类信息是否存在
            if (!$category) {
                return $this->error('分类信息不存在', "/category/list{$cond}");
            }

            // 获取需要更新的分类信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts) {
                return $this->error('更新内容不能全为空', "/category/edit/id/{$id}{$cond}");
            }

            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新分类信息
            $result = $category->update($posts);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('分类信息更新失败', "/category/edit/id/{$id}{$cond}");
            }

            return $this->success('分类信息更新成功', "/category/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('分类信息重复', "/category/edit/id/{$id}{$cond}");
            } else {
                return $this->error('分类信息更新失败', "/category/edit/id/{$id}{$cond}");
            }
        }
    }
}