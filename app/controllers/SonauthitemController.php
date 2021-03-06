<?php

use \Phalcon\Paginator\Adapter\Model;

class SonauthitemController extends BaseController
{
    /**
     * 权限项是否启用
     *
     * @var array
     */
    protected $authItemEnables = [
        '否',
        '是',
    ];
    /**
     * 是否有权限项子项
     *
     * @var array
     */
    protected $hasAuthItemChilds = [
        '否',
        '是',
    ];
    /**
     * 权限项类型
     *
     * @var array
     */
    protected $types = [
        '前台',
        '后台',
        '微信',
    ];

    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.auth.item.L', 'blog.boss.auth.item.R', 'blog.boss.auth.item.S']],
        'disabled' => [false, ['blog.boss.auth.item.SET']],
        'enabled' => [false, ['blog.boss.auth.item.SET']],
        'delete' => [false, ['blog.boss.auth.item.D', 'blog.boss.auth.item.BD']],
        'edit' => [false, ['blog.boss.auth.item.U']],
        'save' => [false, ['blog.boss.auth.item.U']],
    ];

    /**
     * 子权限项信息列表
     *
     * @return mixed
     */
    public function listAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        try {
            // 获取父权限项 ID
            $parentId = (int)$this->getParam('parentid');

            // 判断父权限项 ID 是否有效
            if (!$parentId) {
                return $this->error('上级权限项ID无效', "/authitem/list/navid/{$navId}");
            }

            // 获取当前页码
            $page = $this->page;

            // 获取每页显示的数据量
            $pageSize = $this->pageSize;

            // 获取权限项code
            $code = $this->getParam('code');
            $code = urldecode($code);
            $codeSql = $code ? " and code like '%{$code}%'" : '';

            // 获取权限项名
            $name = $this->getParam('name');
            $name = urldecode($name);
            $nameSql = $name ? " and name like '%{$name}%'" : '';

            // 权限项类型
            $type = $this->getParam('type');

            $type = (null === $type) ? -1 : intval($type);
            $typeSql = (-1 === $type) ? '' : " and type={$type}";

            // 权限项是否启用
            $enabled = $this->getParam('enabled');

            $enabled = (null === $enabled) ? -1 : intval($enabled);
            $enabledSql = (-1 === $enabled) ? '' : " and enabled={$enabled}";

            // 获取父权限项名称
            $topAuthItem = Authitem::findFirst($parentId);

            // 判断父权限项是否存在
            if (!$topAuthItem) {
                return $this->error('父权限项不存在', "/authitem/list/navid/{$navId}");
            }

            // 获取父权限项名称
            $topAuthName = $topAuthItem->name;

            // 卸载空闲变量
            unset($topAuthItem);

            // 获取权限项信息
            $authItems = Authitem::find([
                'conditions' => "parentid={$parentId}{$codeSql}{$nameSql}{$enabledSql}{$typeSql} " . 'order by displayorder desc,id desc',
                'columns' => 'id,parentid,haschild,name,code,auth,type,enabled,description,displayorder,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $authItems,
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

            $authItems = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 数值转换
            $authItems = array_map(function ($authItem) {
                $authItem['enablename'] = $this->authItemEnables[$authItem['enabled']];
                $authItem['typename'] = $this->types[$authItem['type']];
                $authItem['haschildname'] = $this->hasAuthItemChilds[$authItem['haschild']];

                return $authItem;
            }, $authItems);

            // 传递权限项信息
            $this->view->authItems = $authItems;

            $this->view->page = $page;

            // 传递查询参数信息
            $this->view->code = $code;
            $this->view->name = $name;
            $this->view->enabled = $enabled;
            $this->view->type = $type;

            // 传递查询选项信息
            $this->view->authItemChilds = $this->hasAuthItemChilds;
            $this->view->authItemEnables = $this->authItemEnables;

            // 传递顶级菜单信息
            $this->view->parentId = $parentId;
            $this->view->topAuthName = $topAuthName;

            // 传递权限项类型
            $this->view->types = $this->types;

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

            // 传递查询条件组合
            $this->view->cond = "/navid/{$navId}";

            $this->view->curcond = $cond;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.auth.item.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.auth.item.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.auth.item.U']);

            // 是否显示设置操作相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.auth.item.SET']);

            return $this->view->pick('sonauthitem/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('子权限项信息获取失败', "/authitem/list/navid/{$navId}");
        }
    }

    /**
     * 禁用子权限项
     *
     * @return mixed
     */
    public function disabledAction()
    {
        // 获取父权限项 ID
        $parentId = (int)$this->getParam('parentid');

        // 获取子权限名称
        $name = urldecode($this->getParam('name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('code'));

        // 获取子权限类型
        $type = (int)$this->getParam('type');

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取权限项 ID
            $id = (int)$this->getParam('id');

            // 获取权限项信息
            $authItem = Authitem::findFirst($id);

            // 权限项信息存在则做更新操作
            if ($authItem) {
                // 更新权限项是否启用的状态
                $result = $authItem->update([
                    'enabled' => Authitem::DISABLE_BLOG_AUTHITEM,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('权限项禁用失败', "/sonauthitem/list/parentid/{$parentId}{$cond}");
                }
            }

            return $this->success('权限项禁用成功', "/sonauthitem/list/parentid/{$parentId}{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('权限项禁用失败', "/sonauthitem/list/parentid/{$parentId}{$cond}");
        }
    }

    /**
     * 启用权限项
     *
     * @return mixed
     */
    public function enabledAction()
    {
        // 获取父权限项 ID
        $parentId = (int)$this->getParam('parentid');

        // 获取子权限名称
        $name = urldecode($this->getParam('name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('code'));

        // 获取子权限类型
        $type = (int)$this->getParam('type');

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取权限项 ID
            $id = (int)$this->getParam('id');

            // 获取权限信息
            $authItem = Authitem::findFirst($id);

            // 权限项信息存在则做更新操作
            if ($authItem) {
                // 更新权限项是否启用的状态
                $result = $authItem->update([
                    'enabled' => Authitem::ENABLE_BLOG_AUTHITEM,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('权限项启用失败', "/sonauthitem/list/parentid/{$parentId}{$cond}");
                }
            }

            return $this->success('权限项启用成功', "/sonauthitem/list/parentid/{$parentId}{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('权限项启用失败', "/sonauthitem/list/parentid/{$parentId}{$cond}");
        }
    }

    /**
     * 删除子权限项
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取父权限项 ID
        $parentId = (int)$this->getParam('parentid');

        // 获取子权限名称
        $name = urldecode($this->getParam('name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('code'));

        // 获取子权限类型
        $type = (int)$this->getParam('type');

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取子权限项 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = $isRelation = false;

            foreach ($ids as $id) {
                $id = intval($id);

                // 根据 ID 获取对应的权限项信息
                $authItem = Authitem::findFirst($id);

                // 判断权限项信息是否存在,存在则做删除
                if ($authItem) {
                    // 获取关联数据
                    $roleAuthitems = RoleAuthitem::find([
                        'conditions' => "itemid={$id}",
                        'columns' => 'id'
                    ])->count();

                    $userAuthitems = UserAuthitem::find([
                        'conditions' => "itemid={$id}",
                        'columns' => 'id'
                    ])->count();

                    if ($roleAuthitems || $userAuthitems) {
                        $isRelation = true;
                        continue;
                    }

                    // 删除指定权限项信息
                    $result = $authItem->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断是否存在关联数据
            if ($isRelation) {
                $this->error('权限项信息存在关联数据,不允许删除', "/sonauthitem/list/parentid/{$parentId}{$cond}");
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('子权限项删除失败', "/sonauthitem/list/parentid/{$parentId}{$cond}");
            }

            return $this->success('子权限项删除成功', "/sonauthitem/list/parentid/{$parentId}{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('子权限项删除失败', "/sonauthitem/list/parentid/{$parentId}{$cond}");
        }
    }

    /**
     * 显示编辑子权限项页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取父权限项 ID
        $parentId = (int)$this->getParam('parentid');

        // 获取子权限名称
        $name = urldecode($this->getParam('name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('code'));

        // 获取子权限类型
        $type = (int)$this->getParam('type');

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取子权限项 ID
            $id = (int)$this->getParam('id');

            // 根据子权限项 ID 获取对应的权限项信息
            $authItem = Authitem::find($id)->toArray();

            // 判断权限项信息是否存在
            if (!$authItem) {
                return $this->error('子权限项不存在', "/sonauthitem/list/parentid/{$parentId}{$cond}");
            }

            $authItem = $authItem[0];

            // 获取父权限项信息
            $topAuthItems = Authitem::find([
                'conditions' => 'parentid=' . Authitem::PARENT_BLOG_AUTHITEM . ' and enabled=' . Authitem::ENABLE_BLOG_AUTHITEM . ' order by displayorder desc,id desc',
                'columns' => 'id,name'
            ])->toArray();

            // 传递父权限项信息
            $this->view->topAuthItems = $topAuthItems;

            // 卸载空闲变量
            unset($topAuthItems);

            // 传递查询选项信息
            $this->view->authItemChilds = $this->hasAuthItemChilds;
            $this->view->authItemEnables = $this->authItemEnables;

            // 传递父权限项 ID
            $this->view->parentId = $parentId;

            // 传递当前权限项信息
            $this->view->authItem = $authItem;

            // 传递权限项类型信息
            $this->view->types = $this->types;

            $this->view->conds = [
                '_code' => $code,
                '_name' => $name,
                '_page' => $page,
                '_enabled' => $enabled,
                '_type' => $type,
            ];

            $this->view->cond = "/navid/{$navId}";

            $this->view->curcond = $cond;

            return $this->view->pick("sonauthitem/edit");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('子权限项信息加载失败', "/sonauthitem/list/parentid/{$parentId}{$cond}");
        }
    }

    /**
     * 更新权限项信息
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取父权限项 ID
        $parentId = (int)$this->request->getPost('parentid');

        // 获取子权限项 ID
        $id = (int)$this->request->getPost('id');

        // 获取子权限名称
        $name = urldecode($this->getParam('_name'));

        // 获取子权限 code
        $code = urldecode($this->getParam('_code'));

        // 获取子权限类型
        $type = (int)$this->getParam('_type');

        // 获取子权限是否启用状态
        $enabled = (int)$this->getParam('_enabled');

        // 获取页码
        $page = (int)$this->getParam('_page');
        $page = $page ? : 1;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ($code ? "/code/{$code}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取子权限项信息
            $authItem = Authitem::findFirst($id);

            // 判断子权限项信息是否存在
            if (!$authItem) {
                return $this->error('子权限项信息不存在', "/sonauthitem/list/parentid/{$parentId}{$cond}");
            }

            // 获取需要更新的子权限项信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts) {
                return $this->error('更新内容不能全为空', "/sonauthitem/edit/parentid/{$parentId}/id/{$id}{$cond}");
            }

            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新子权限项信息
            $result = $authItem->update($posts);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('子权限项更新失败', "/sonauthitem/edit/parentid/{$parentId}/id/{$id}{$cond}");
            }

            return $this->success('子权限项更新成功', "/sonauthitem/list/parentid/{$parentId}{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('子权限项信息重复', "/sonauthitem/edit/parentid/{$parentId}/id/{$id}{$cond}");
            } else {
                return $this->error('子权限项更新失败', "/sonauthitem/edit/parentid/{$parentId}/id/{$id}{$cond}");
            }
        }
    }
}