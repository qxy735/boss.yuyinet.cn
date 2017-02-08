<?php

use \Phalcon\Paginator\Adapter\Model;

class RoleController extends BaseController
{
    /**
     * 角色类型
     *
     * @var array
     */
    protected $roleTypes = [
        '前台角色',
        '后台角色',
    ];

    /**
     * 角色是否启用
     *
     * @var array
     */
    protected $roleEnables = [
        '否',
        '是',
    ];

    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.auth.role.L', 'blog.boss.auth.role.R', 'blog.boss.auth.role.S']],
        'create' => [false, ['blog.boss.auth.role.C']],
        'post' => [false, ['blog.boss.auth.role.C']],
        'delete' => [false, ['blog.boss.auth.role.D', 'blog.boss.auth.role.BD']],
        'disabled' => [false, ['blog.boss.auth.role.SET']],
        'enabled' => [false, ['blog.boss.auth.role.SET']],
        'edit' => [false, ['blog.boss.auth.role.U']],
        'save' => [false, ['blog.boss.auth.role.U']],
        'auth' => [false, ['blog.boss.auth.role.L', 'blog.boss.auth.role.R', 'blog.boss.auth.role.S']],
        'sauth' => [false, ['blog.boss.auth.role.AUTH']],
        'dauth' => [false, ['blog.boss.auth.role.AUTH']],
    ];

    /**
     * 角色列表
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

            // 获取角色信息
            $roles = Role::find([
                'conditions' => "1=1 {$codeSql}{$nameSql}{$typeSql}{$enabledSql} " . 'order by displayorder desc,id desc',
                'columns' => 'id,name,code,type,enabled,displayorder,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $roles,
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

            $roles = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 数值转换
            $roles = array_map(function ($role) {
                $role['typename'] = $this->roleTypes[$role['type']];

                $role['enablename'] = $this->roleEnables[$role['enabled']];

                return $role;
            }, $roles);

            // 传递菜单信息
            $this->view->roles = $roles;

            $this->view->page = $page;

            // 传递查询参数信息
            $this->view->code = $code;
            $this->view->name = $name;
            $this->view->type = $type;
            $this->view->enabled = $enabled;

            // 传递角色类型信息
            $this->view->roleTypes = $this->roleTypes;

            // 传递是否启用信息
            $this->view->roleEnables = $this->roleEnables;

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

            // 传递查询条件组合
            $this->view->cond = $cond;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.auth.role.C']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.auth.role.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.auth.role.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.auth.role.U']);

            // 是否显示设置操作相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.auth.role.SET']);

            return $this->view->pick('role/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('获取角色列表失败', '/');
        }
    }

    /**
     * 显示添加角色页面
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
            // 传递角色类型信息
            $this->view->roleTypes = $this->roleTypes;

            // 传递是否启用信息
            $this->view->roleEnables = $this->roleEnables;

            // 传递启用角色值
            $this->view->enabled = Role::ENABLE_BLOG_ROLE;

            // 传递导航菜单
            $this->view->cond = $cond;

            return $this->view->pick('role/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('角色添加页面加载失败', "/role/list{$cond}");
        }
    }

    /**
     * 添加角色信息
     *
     * @return mixed
     */
    public function postAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        $cond = "/navid/{$navId}";

        try {
            // 获取角色数据库 model
            $role = new Role();

            // 获取需要添加的角色信息
            $posts = $this->request->getPost();

            // 添加时间
            $posts['createtime'] = time();

            // 添加者
            $posts['creator'] = $this->getUserName();

            // 添加角色信息
            $result = $role->save($posts);

            // 判断添加是否成功
            if (!$result) {
                $this->error('角色添加失败', "/role/create{$cond}");
            }

            return $this->success('角色添加成功', "/role/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('角色信息重复', "/role/create{$cond}");
            } else {
                return $this->error('角色信息添加失败', "/role/create{$cond}");
            }
        }
    }

    /**
     * 删除角色信息
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
            // 获取角色 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = $isRelation = false;

            foreach ($ids as $id) {
                $id = intval($id);

                // 根据 ID 获取对应的角色信息
                $role = Role::findFirst($id);

                // 判断角色信息是否存在,存在则做删除
                if ($role) {
                    // 获取关联信息
                    $roleAuthitems = RoleAuthitem::find([
                        'conditions' => "roleid={$id}",
                        'columns' => 'id',
                    ])->count();

                    $userRoles = UserRole::find([
                        'conditions' => "roleid={$id}",
                        'columns' => 'id',
                    ])->count();

                    // 判断是否存在关联数据
                    if ($roleAuthitems || $userRoles) {
                        $isRelation = true;
                        continue;
                    }

                    // 删除指定角色信息
                    $result = $role->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断是否存在关联数据
            if ($isRelation) {
                return $this->error('角色信息存在关联数据,不允许删除', "/role/list{$cond}");
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('角色信息删除失败', "/role/list{$cond}");
            }

            return $this->success('角色删除成功', "/role/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('角色信息删除失败', "/role/list{$cond}");
        }
    }

    /**
     * 禁用角色
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
            // 获取角色 ID
            $id = (int)$this->getParam('id');

            // 获取角色信息
            $role = Role::findFirst($id);

            // 角色信息存在则做更新操作
            if ($role) {
                // 更新菜单是否启用的状态
                $result = $role->update([
                    'enabled' => Role::DISABLE_BLOG_ROLE,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('角色禁用失败', "/role/list{$cond}");
                }
            }

            return $this->success('角色禁用成功', "/role/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('角色禁用失败', "/role/list{$cond}");
        }
    }

    /**
     * 启用角色
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
            // 获取角色 ID
            $id = (int)$this->getParam('id');

            // 获取角色信息
            $role = Role::findFirst($id);

            // 角色信息存在则做更新操作
            if ($role) {
                // 更新角色是否启用的状态
                $result = $role->update([
                    'enabled' => Role::ENABLE_BLOG_ROLE,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('角色启用失败', "/role/list{$cond}");
                }
            }

            return $this->success('角色启用成功', "/role/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('角色启用失败', "/role/list{$cond}");
        }
    }

    /**
     * 显示编辑角色页面
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
            // 获取角色 ID
            $id = (int)$this->getParam('id');

            // 根据角色 ID 获取对应的角色信息
            $role = Role::find($id)->toArray();

            // 判断角色信息是否存在
            if (!$role) {
                return $this->error('角色不存在', "/role/list{$cond}");
            }

            $role = $role[0];

            // 传递角色类型信息
            $this->view->roleTypes = $this->roleTypes;

            // 传递角色启用类型信息
            $this->view->roleEnables = $this->roleEnables;

            // 传递当前角色信息
            $this->view->role = $role;

            $this->view->conds = [
                '_code' => $code,
                '_name' => $name,
                '_page' => $page,
                '_enabled' => $enabled,
                '_type' => $type,
            ];

            $this->view->cond = $cond;

            return $this->view->pick('role/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('角色信息加载失败', "/menu/list{$cond}");
        }
    }

    /**
     * 更新角色信息
     *
     * @return mixed
     */
    public function saveAction()
    {
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

        // 获取角色 ID
        $id = (int)$this->request->getPost('id');

        try {
            // 获取角色信息
            $role = Role::findFirst($id);

            // 判断角色信息是否存在
            if (!$role) {
                return $this->error('角色信息不存在', "/role/list{$cond}");
            }

            // 获取需要更新的角色信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts) {
                return $this->error('更新内容不能全为空', "/role/edit/id/{$id}{$cond}");
            }

            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新角色信息
            $result = $role->update($posts);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('角色更新失败', "/role/edit/id/{$id}{$cond}");
            }

            return $this->success('角色更新成功', "/role/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('角色信息重复', "/role/edit/id/{$id}{$cond}");
            } else {
                return $this->error('角色更新失败', "/role/edit/id/{$id}{$cond}");
            }
        }
    }

    /**
     * 角色权限项配置页面
     *
     * @return mixed
     */
    public function authAction()
    {
        // 获取子权限名称
        $name = urldecode($this->getParam('name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('code'));

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取子权限类型
        $type = (int)$this->getParam('type');
        $curType = (int)$this->getParam('curtype');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        // 获取角色 ID
        $roleId = (int)$this->getParam('id');

        try {
            // 获取角色信息
            $role = Role::findFirst($roleId);

            if (!$role) {
                return $this->error('角色不存在', "/role/list{$cond}");
            }

            // 获取角色名称
            $roleName = $role->name;

            // 获取权限项
            $items = Authitem::find([
                'conditions' => 'enabled=' . Authitem::ENABLE_BLOG_AUTHITEM . " and type={$curType}" . ' order by displayorder desc, id desc',
                'columns' => 'id,parentid,name,auth,code',
            ])->toArray();

            // 获取顶级权限项
            $topItems = array_filter($items, function ($item) {
                if ($item['parentid']) {
                    return false;
                }

                return true;
            });

            // 获取角色关联的权限项配置信息
            $roleAuthitems = RoleAuthitem::find([
                'conditions' => " roleid={$roleId}",
                'columns' => 'itemid,auth',
            ])->toArray();

            // 获取对应子权限项
            $topItems = array_map(function ($topItem) use ($items, $roleAuthitems) {
                $sonItems = [];

                foreach ($items as $item) {
                    if ($item['parentid'] == $topItem['id']) {
                        $item['auth'] = json_decode($item['auth'], true);

                        $item['auth']['checkatoms'] = [];

                        foreach ($roleAuthitems as $roleAuthitem) {
                            if ($roleAuthitem['itemid'] == $item['id']) {
                                $item['auth']['checkatoms'] = explode(',', $roleAuthitem['auth']);
                                break;
                            }
                        }
                        $sonItems[] = $item;
                    }
                }

                $topItem['auth'] = json_decode($topItem['auth'], true);

                $topItem['son'] = $sonItems;

                return $topItem;
            }, $topItems);

            // 卸载空闲变量
            unset($items);

            $topItems = array_map(function ($topItem) use ($roleAuthitems) {
                $checkAtoms = [];

                foreach ($roleAuthitems as $roleAuthitem) {
                    if ($topItem['id'] == $roleAuthitem['itemid']) {
                        $checkAtoms = explode(',', $roleAuthitem['auth']);
                        break;
                    }
                }

                $topItem['auth']['checkatoms'] = $checkAtoms;

                return $topItem;
            }, $topItems);

            unset($roleAuthitems);

            // 传递权限项信息
            $this->view->topItems = array_values($topItems);

            // 传递角色 ID
            $this->view->roleId = $roleId;

            // 传递查询条件
            $this->view->cond = $cond;

            // 传递角色名称
            $this->view->roleName = $roleName;

            $this->view->curType = $curType;
            $this->view->frontType = Authitem::FRONT_AUTH_TYPE;
            $this->view->adminType = Authitem::ADMIN_AUTH_TYPE;
            $this->view->weiXinType = Authitem::WEIXIN_AUTH_TYPE;

            // 权限按钮是否可用
            $this->view->isAuthBut = $this->hasAuth(['blog.boss.auth.role.AUTH']);

            return $this->view->pick('role/auth');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('角色权限项配置加载失败', "/role/list{$cond}");
        }
    }

    /**
     * 添加角色权限项配置
     *
     * @return mixed
     */
    public function sauthAction()
    {
        // 获取子权限名称
        $name = urldecode($this->getParam('name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('code'));

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取子权限类型
        $type = (int)$this->getParam('type');
        $curType = (int)$this->getParam('curtype');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/curtype/{$curType}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        // 获取角色 ID
        $roleId = (int)$this->getParam('roleid');

        // 获取权限 ID
        $authId = (int)$this->getParam('authid');

        // 获取权限配置
        $item = $this->getParam('item');

        try {
            $item = trim($item, ',');

            // 判断权限项是否为空
            if (!$item) {
                return $this->error('角色权限项配置不能为空', "/role/auth/id/{$roleId}{$cond}");
            }

            // 获取角色信息
            $role = Role::findFirst($roleId);

            // 判断角色信息是否存在
            if (!$role) {
                return $this->error('角色信息不存在', "/role/list{$cond}");
            }

            // 获取权限项信息
            $authItem = Authitem::findFirst($authId);

            // 判断权限项信息是否存在
            if (!$authItem) {
                return $this->error('权限项信息不存在', "/role/auth/id/{$roleId}{$cond}");
            }

            // 获取角色关联的权限项信息
            $roleAuthitem = RoleAuthitem::findFirst([
                'conditions' => " roleid={$roleId} and itemid={$authId}",
                'columns' => 'id',
            ]);

            // 判断角色关联的权限项是否存在
            if ($roleAuthitem) {
                $result = RoleAuthitem::findFirst($roleAuthitem->id)->update([
                    'auth' => $item,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                if (!$result) {
                    return $this->error('角色权限项配置失败', "/role/auth/id/{$roleId}{$cond}");
                }
            } else {
                $roleAuthitem = new RoleAuthitem();

                $inserts = [
                    'roleid' => $roleId,
                    'itemid' => $authId,
                    'auth' => $item,
                    'creator' => $this->getUserName(),
                    'createtime' => time(),
                ];

                // 添加角色关联权限项信息
                $result = $roleAuthitem->create($inserts);

                // 判断添加是否成功
                if (!$result) {
                    return $this->error('角色权限项配置失败', "/role/auth/id/{$roleId}{$cond}");
                }
            }

            return $this->success('角色权限项配置成功', "/role/auth/id/{$roleId}{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('角色权限项配置失败', "/role/auth/id/{$roleId}{$cond}");
        }
    }

    /**
     * 撤销角色权限项配置信息
     *
     * @return mixed
     */
    public function dauthAction()
    {
        // 获取子权限名称
        $name = urldecode($this->getParam('name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('code'));

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取子权限类型
        $type = (int)$this->getParam('type');
        $curType = (int)$this->getParam('curtype');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}page/{$page}/curtype/{$curType}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        // 获取角色 ID
        $roleId = (int)$this->getParam('roleid');

        // 获取权限 ID
        $authId = (int)$this->getParam('authid');

        try {
            // 获取角色关联权限项信息
            $roleAuthitem = RoleAuthitem::findFirst([
                'conditions' => " roleid={$roleId} and itemid={$authId}",
                'columns' => 'id',
            ]);

            // 存在则做删除操作
            if ($roleAuthitem) {
                // 获取子权限项 ID
                $sonItems = Authitem::find([
                    'conditions' => " parentid={$authId}",
                    'columns' => 'id',
                ])->toArray();

                if ($sonItems) {
                    $sonItems = array_map(function ($sonItem) {
                        return $sonItem['id'];
                    }, $sonItems);

                    $sonRoleAuthitem = RoleAuthitem::find([
                        'conditions' => " roleid={$roleId} and itemid in(" . implode(',', $sonItems) . ')',
                        'columns' => 'id',
                    ])->toArray();

                    if ($sonRoleAuthitem) {
                        return $this->error('请先撤销该子权限项关联的角色配置', "/role/auth/id/{$roleId}{$cond}");
                    }
                }

                $result = RoleAuthitem::findFirst($roleAuthitem->id)->delete();

                // 判断删除是否成功
                if (!$result) {
                    return $this->error('角色权限项配置撤销失败', "/role/auth/id/{$roleId}{$cond}");
                }
            }

            return $this->success('角色权限项撤销成功', "/role/auth/id/{$roleId}{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('角色权限项配置撤销失败', "/role/auth/id/{$roleId}{$cond}");
        }
    }
}