<?php

use \Phalcon\Paginator\Adapter\Model;

class MenuController extends BaseController
{
    /**
     * 定义网站菜单类型
     *
     * @var array
     */
    protected $menuTypes = [
        '前台菜单',
        '后台菜单',
        '微信菜单',
    ];

    /**
     * 定义菜单启用类型
     *
     * @var array
     */
    protected $enables = [
        '否',
        '是',
    ];

    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.auth.menu.L', 'blog.boss.auth.menu.R', 'blog.boss.auth.menu.S']],
        'create' => [false, ['blog.boss.auth.menu.C']],
        'post' => [false, ['blog.boss.auth.menu.C']],
        'delete' => [false, ['blog.boss.auth.menu.D', 'blog.boss.auth.menu.BD']],
        'disabled' => [false, ['blog.boss.auth.menu.SET']],
        'enabled' => [false, ['blog.boss.auth.menu.SET']],
        'edit' => [false, ['blog.boss.auth.menu.U']],
        'save' => [false, ['blog.boss.auth.menu.U']],
    ];

    /**
     * 菜单列表
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

            // 获取菜单code
            $code = $this->getParam('code');
            $code = urldecode($code);
            $codeSql = $code ? " and code like '%{$code}%'" : '';

            // 获取菜单名
            $name = $this->getParam('name');
            $name = urldecode($name);
            $nameSql = $name ? " and name like '%{$name}%'" : '';

            // 获取菜单类型
            $type = $this->getParam('type');
            $type = (null === $type) ? -1 : intval($type);
            $typeSql = (-1 === $type) ? '' : " and type={$type}";

            // 菜单是否启用
            $enabled = $this->getParam('enabled');
            $enabled = (null === $enabled) ? -1 : intval($enabled);
            $enabledSql = (-1 === $enabled) ? '' : " and enabled={$enabled}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取网站菜单信息
            $menus = Menu::find([
                'conditions' => 'parentid=' . Menu::TOP_TYPE_MENU . " {$codeSql}{$nameSql}{$typeSql}{$enabledSql} " . 'order by displayorder desc,id desc',
                'columns' => 'id,parentid,haschild,name,code,url,auth,type,enabled,displayorder,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $menus,
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

            $menus = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 数值转换
            $menus = array_map(function ($menu) {
                $menu['typename'] = $this->menuTypes[$menu['type']];

                $menu['enablename'] = $this->enables[$menu['enabled']];

                return $menu;
            }, $menus);

            // 传递菜单信息
            $this->view->menus = $menus;

            $this->view->page = $page;

            // 传递查询参数信息
            $this->view->code = $code;
            $this->view->name = $name;
            $this->view->type = $type;
            $this->view->enabled = $enabled;

            // 传递查询选项信息
            $this->view->menuTypes = $this->menuTypes;
            $this->view->enables = $this->enables;

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

            // 传递查询条件组合
            $this->view->cond = $cond;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.auth.menu.C']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.auth.menu.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.auth.menu.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.auth.menu.U']);

            // 是否显示设置操作相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.auth.menu.SET']);

            return $this->view->pick('menu/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('获取菜单信息失败', '/');
        }
    }

    /**
     * 显示添加菜单页面
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
            // 获取顶级菜单信息
            $topMenus = Menu::find([
                'conditions' => 'parentid=' . Menu::TOP_TYPE_MENU . ' and enabled=' . Menu::ENABLE_BLOG_MENU . ' order by displayorder desc,id desc',
                'columns' => 'id,name'
            ])->toArray();

            // 传递顶级菜单信息
            $this->view->topMenus = $topMenus;

            // 卸载空闲变量
            unset($topMenus);

            // 传递菜单类型信息
            $this->view->types = $this->menuTypes;

            // 传递导航菜单
            $this->view->cond = $cond;

            return $this->view->pick('menu/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('菜单添加页面加载失败', "/menu/list{$cond}");
        }
    }

    /**
     * 添加菜单处理
     *
     * @return mixed
     */
    public function postAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        $cond = "/navid/{$navId}";

        try {
            $menu = new Menu();

            // 获取需要添加的菜单数据
            $posts = $this->request->getPost();

            // 添加时间
            $posts['createtime'] = time();

            // 添加者
            $posts['creator'] = $this->getUserName();

            $result = $menu->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('菜单添加失败', "/menu/create{$cond}");
            }

            return $this->success('菜单添加成功', "/menu/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('网站菜单重复', "/menu/create{$cond}");
            } else {
                return $this->error('网站菜单添加失败', "/menu/create{$cond}");
            }
        }
    }

    /**
     * 删除网站菜单
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取子权限名称
        $name = urldecode($this->getParam('name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('code'));

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取子权限类型
        $type = (int)$this->getParam('type');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取菜单 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = $isRelation = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的菜单信息
                $menu = Menu::findFirst(intval($id));

                // 判断菜单信息是否存在,存在则做删除
                if ($menu) {
                    // 获取关联数据
                    $sonMenus = Menu::findFirst([
                        'conditions' => "parentid={$id}",
                        'columns' => 'id',
                    ]);

                    $articles = Article::findFirst([
                        'conditions' => "menuid={$id}",
                        'columns' => 'id',
                    ]);

                    // 判断是否存在关联关系
                    if ($sonMenus || $articles) {
                        $isRelation = true;
                        continue;
                    }

                    // 删除指定菜单信息
                    $result = $menu->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断是否存在关联关系
            if ($isRelation) {
                return $this->error('菜单存在关联数据,不允许删除', "/menu/list{$cond}");
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('网站菜单删除失败', "/menu/list{$cond}");
            }

            return $this->success('菜单删除成功', "/menu/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('网站菜单删除失败', "/menu/list{$cond}");
        }
    }

    /**
     * 禁用菜单
     *
     * @return mixed
     */
    public function disabledAction()
    {
        // 获取子权限名称
        $name = urldecode($this->getParam('name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('code'));

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取子权限类型
        $type = (int)$this->getParam('type');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取菜单 ID
            $id = (int)$this->getParam('id');

            // 获取菜单信息
            $menu = Menu::findFirst($id);

            // 菜单信息存在则做更新操作
            if ($menu) {
                // 更新菜单是否启用的状态
                $result = $menu->update([
                    'enabled' => Menu::DISABLE_BLOG_MENU,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('菜单禁用失败', "/menu/list{$cond}");
                }
            }

            return $this->success('菜单禁用成功', "/menu/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('菜单禁用失败', "/menu/list{$cond}");
        }
    }

    /**
     * 启用菜单
     *
     * @return mixed
     */
    public function enabledAction()
    {
        // 获取子权限名称
        $name = urldecode($this->getParam('name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('code'));

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取子权限类型
        $type = (int)$this->getParam('type');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取菜单 ID
            $id = (int)$this->getParam('id');

            // 获取菜单信息
            $menu = Menu::findFirst($id);

            // 菜单信息存在则做更新操作
            if ($menu) {
                // 更新菜单是否启用的状态
                $result = $menu->update([
                    'enabled' => Menu::ENABLE_BLOG_MENU,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('菜单启用失败', "/menu/list{$cond}");
                }
            }

            return $this->success('菜单启用成功', "/menu/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('菜单启用失败', "/menu/list{$cond}");
        }
    }

    /**
     * 显示编辑菜单页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取子权限名称
        $name = urldecode($this->getParam('name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('code'));

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取子权限类型
        $type = (int)$this->getParam('type');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取菜单 ID
            $id = (int)$this->getParam('id');

            // 根据菜单 ID 获取对应的菜单信息
            $menu = Menu::find($id)->toArray();

            // 判断菜单信息是否存在
            if (!$menu) {
                return $this->error('菜单不存在', "/menu/list{$cond}");
            }

            $menu = $menu[0];

            // 获取顶级菜单信息
            $topMenus = Menu::find([
                'conditions' => 'parentid=' . Menu::TOP_TYPE_MENU . ' and enabled=' . Menu::ENABLE_BLOG_MENU . ' order by displayorder desc,id desc',
                'columns' => 'id,name'
            ])->toArray();

            // 传递顶级菜单信息
            $this->view->topMenus = $topMenus;

            // 卸载空闲变量
            unset($topMenus);

            // 传递菜单类型信息
            $this->view->types = $this->menuTypes;

            // 传递当前菜单信息
            $this->view->menu = $menu;

            $this->view->conds = [
                '_code' => $code,
                '_name' => $name,
                '_page' => $page,
                '_enabled' => $enabled,
                '_type' => $type,
            ];

            $this->view->cond = $cond;

            return $this->view->pick('menu/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('菜单信息加载失败', "/menu/list{$cond}");
        }
    }

    /**
     * 更新菜单信息
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取菜单 ID
        $id = (int)$this->request->getPost('id');

        // 获取子权限名称
        $name = urldecode($this->getParam('_name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('_code'));

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('_enabled');

        // 获取子权限类型
        $type = (int)$this->getParam('_type');

        // 获取页码
        $page = (int)$this->getParam('_page');
        $page = $page ? : 1;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取菜单信息
            $menu = Menu::findFirst($id);

            // 判断菜单信息是否存在
            if (!$menu) {
                return $this->error('菜单信息不存在', "/menu/list{$cond}");
            }

            // 获取需要更新的菜单信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts) {
                return $this->error('更新内容不能全为空', "/menu/edit/id/{$id}{$cond}");
            }

            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新菜单信息
            $result = $menu->update($posts);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('菜单更新失败', "/menu/edit/id/{$id}{$cond}");
            }

            return $this->success('菜单更新成功', "/menu/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('网站菜单重复', "/menu/edit/id/{$id}{$cond}");
            } else {
                return $this->error('菜单更新失败', "/menu/edit/id/{$id}{$cond}");
            }
        }
    }
}