<?php

use \Phalcon\Paginator\Adapter\Model;

class MsgTypeController extends BaseController
{
    /**
     * 启用状态
     *
     * @var array
     */
    protected $enableds = [
        '否',
        '是',
    ];
    /**
     * 公开状态
     *
     * @var array
     */
    protected $publics = [
        '否',
        '是',
    ];
    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.app.msgtype.L', 'blog.boss.app.msgtype.R', 'blog.boss.app.msgtype.S']],
        'create' => [false, ['blog.boss.app.msgtype.C']],
        'post' => [false, ['blog.boss.app.msgtype.C']],
        'delete' => [false, ['blog.boss.app.msgtype.D', 'blog.boss.app.msgtype.BD']],
        'disabled' => [false, ['blog.boss.app.msgtype.SET']],
        'enabled' => [false, ['blog.boss.app.msgtype.SET']],
        'edit' => [false, ['blog.boss.app.msgtype.U']],
        'save' => [false, ['blog.boss.app.msgtype.U']],
    ];

    /**
     * 显示消息类型页面
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

            // 获取类型名称
            $name = $this->getParam('name');
            $name = urldecode($name);
            $nameSql = $name ? " and name like '%{$name}%'" : '';

            // 获取启用状态
            $enabled = $this->getParam('enabled');
            $enabled = (null === $enabled) ? -1 : intval($enabled);
            $enabledSql = (-1 === $enabled) ? '' : " and enabled={$enabled}";

            // 获取公开状态
            $ispublic = $this->getParam('ispublic');
            $ispublic = (null === $ispublic) ? -1 : intval($ispublic);
            $ispublicSql = (-1 === $ispublic) ? '' : " and ispublic={$ispublic}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取消息类型信息
            $msgTypes = MsgType::find([
                'conditions' => "1=1 {$nameSql}{$enabledSql}{$ispublicSql} " . 'order by id desc',
                'columns' => 'id,name,enabled,ispublic,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $msgTypes,
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

            $msgTypes = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 处理数值
            $msgTypes = array_map(function ($msgType) {
                $msgType['createtime'] = $msgType['createtime'] ? date('Y-m-d H:i:s', $msgType['createtime']) : '';
                $msgType['lastoperate'] = $msgType['lastoperate'] ? date('Y-m-d H:i:s', $msgType['lastoperate']) : '';
                $msgType['enabledname'] = $this->enableds[$msgType['enabled']];
                $msgType['ispublic'] = $this->publics[$msgType['ispublic']];

                return $msgType;
            }, $msgTypes);

            // 传递消息类型信息
            $this->view->msgTypes = $msgTypes;

            // 传递分页信息
            $this->view->page = $page;

            // 传递启用状态
            $this->view->enableds = $this->enableds;

            // 传递公开状态
            $this->view->publics = $this->publics;

            $this->view->navId = $navId;

            // 传递查询参数
            $this->view->ispublic = $ispublic;
            $this->view->enabled = $enabled;
            $this->view->name = $name;

            // 传递禁用状态
            $this->view->disableValue = MsgType::DISABLED_MSG_TYPE;

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '') . ('' !== $ispublic ? "/ispublic/{$ispublic}" : '');

            // 传递查询条件信息
            $this->view->cond = $cond;

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.app.msgtype.C']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.app.msgtype.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.app.msgtype.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.app.msgtype.U']);

            // 是否显示设置相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.app.msgtype.SET']);

            return $this->view->pick('msgtype/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('消息类型页面加载失败', '/');
        }
    }

    /**
     * 显示消息类型添加页面
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
            // 传递启用状态信息
            $this->view->enableds = $this->enableds;
            $this->view->defaultEnabled = MsgType::ENABLED_MSG_TYPE;

            // 传递公开状态信息
            $this->view->publics = $this->publics;
            $this->view->defaultPublic = MsgType::IS_PUBLIC_TYPE;

            // 传递查询条件
            $this->view->cond = $cond;

            return $this->view->pick('msgtype/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('消息类型添加页面加载失败', "/msgtype/list{$cond}");
        }
    }

    /**
     * 添加消息类型信息
     *
     * @return mixed
     */
    public function postAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        $cond = "/navid/{$navId}";

        try {
            $msgType = new MsgType();

            // 获取需要添加的消息类型数据
            $posts = $this->request->getPost();

            // 判断消息类型名称是否为空
            if (!isset($posts['name']) || !$posts['name']) {
                return $this->error('消息类型名称必填', "/msgtype/create{$cond}");
            }

            $posts['createtime'] = time();
            $posts['creator'] = $this->getUserName();

            // 添加消息类型信息
            $result = $msgType->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('消息类型信息添加失败', "/msgtype/create{$cond}");
            }

            return $this->success('消息类型信息添加成功', "/msgtype/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('消息类型信息重复', "/msgtype/create{$cond}");
            } else {
                return $this->error('消息类型信息添加失败', "/msgtype/create{$cond}");
            }
        }
    }

    /**
     * 删除消息类型信息
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取消息类型名称
        $name = urldecode($this->getParam('name'));

        // 获取是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取是否公开状态
        $ispublic = (int)$this->getParam('ispublic');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '') . ('' !== $ispublic ? "/ispublic/{$ispublic}" : '');

        try {
            // 获取消息类型信息 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = $isRelation = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的消息类型信息
                $msgType = MsgType::findFirst(intval($id));

                // 判断消息类型信息是否存在,存在则做删除
                if ($msgType) {
                    // 判断是否存在消息信息
                    $messages = Message::find([
                        'conditions' => "typeid={$id}",
                        'columns' => 'id',
                    ])->count();

                    // 判断是否存在关联数据
                    if ($messages) {
                        $isRelation = true;
                        continue;
                    }

                    // 删除指定消息类型信息
                    $result = $msgType->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断是否存在关联数据
            if ($isRelation) {
                return $this->error('消息类型存在关联数据,不允许删除', "/msgtype/list{$cond}");
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('消息类型信息删除失败', "/msgtype/list{$cond}");
            }

            return $this->success('消息类型信息删除成功', "/msgtype/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('消息类型信息删除失败', "/msgtype/list{$cond}");
        }
    }

    /**
     * 禁用消息类型
     *
     * @return mixed
     */
    public function disabledAction()
    {
        // 获取消息类型名称
        $name = urldecode($this->getParam('name'));

        // 获取是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取是否公开状态
        $ispublic = (int)$this->getParam('ispublic');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '') . ('' !== $ispublic ? "/ispublic/{$ispublic}" : '');

        try {
            // 获取消息类型 ID
            $id = (int)$this->getParam('id');

            // 获取消息类型信息
            $msgType = MsgType::findFirst($id);

            // 消息类型信息存在则做更新操作
            if ($msgType) {
                // 更新消息类型是否启用的状态
                $result = $msgType->update([
                    'enabled' => MsgType::DISABLED_MSG_TYPE,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('消息类型禁用失败', "/msgtype/list{$cond}");
                }
            }

            return $this->success('消息类型禁用成功', "/msgtype/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('消息类型禁用失败', "/msgtype/list{$cond}");
        }
    }

    /**
     * 启用消息类型
     *
     * @return mixed
     */
    public function enabledAction()
    {
        // 获取消息类型名称
        $name = urldecode($this->getParam('name'));

        // 获取是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取是否公开状态
        $ispublic = (int)$this->getParam('ispublic');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '') . ('' !== $ispublic ? "/ispublic/{$ispublic}" : '');

        try {
            // 获取消息类型 ID
            $id = (int)$this->getParam('id');

            // 获取消息类型信息
            $msgType = MsgType::findFirst($id);

            // 消息类型信息存在则做更新操作
            if ($msgType) {
                // 更新消息类型是否启用的状态
                $result = $msgType->update([
                    'enabled' => MsgType::ENABLED_MSG_TYPE,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('消息类型启用失败', "/msgtype/list{$cond}");
                }
            }

            return $this->success('消息类型启用成功', "/msgtype/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('消息类型启用失败', "/msgtype/list{$cond}");
        }
    }

    /**
     * 显示编辑消息类型信息页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取消息类型名称
        $name = urldecode($this->getParam('name'));

        // 获取是否启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取是否公开状态
        $ispublic = (int)$this->getParam('ispublic');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '') . ('' !== $ispublic ? "/ispublic/{$ispublic}" : '');

        try {
            // 获取消息类型 ID
            $id = (int)$this->getParam('id');

            // 根据消息类型 ID 获取对应的消息类型信息
            $msgType = MsgType::find($id)->toArray();

            // 判断消息类型信息是否存在
            if (!$msgType) {
                return $this->error('消息类型信息不存在', "/msgtype/list{$cond}");
            }

            $msgType = $msgType[0];

            // 传递消息类型信息
            $this->view->msgType = $msgType;

            // 卸载空闲变量
            unset($msgType);

            // 传递启用状态信息
            $this->view->enableds = $this->enableds;

            // 传递是否公开状态信息
            $this->view->publics = $this->publics;

            $this->view->conds = [
                '_name' => $name,
                '_page' => $page,
                '_enabled' => $enabled,
                '_ispublic' => $ispublic,
            ];

            $this->view->cond = $cond;

            return $this->view->pick('msgtype/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('消息类型编辑页面加载失败', "/msgtype/list{$cond}");
        }
    }

    /**
     * 更新消息类型信息
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取消息类型 ID
        $id = (int)$this->getParam('id');

        // 获取消息类型名称
        $name = urldecode($this->getParam('_name'));

        // 获取是否启用状态
        $enabled = (int)$this->getParam('_enabled');

        // 获取是否公开状态
        $ispublic = (int)$this->getParam('_ispublic');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('_page');
        $page = $page ? : 1;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '') . ('' !== $ispublic ? "/ispublic/{$ispublic}" : '');

        try {
            // 获取消息类型信息
            $msgType = MsgType::findFirst($id);

            // 判断消息类型信息是否存在
            if (!$msgType) {
                return $this->error('消息类型信息不存在', "/msgtype/list{$cond}");
            }

            // 获取需要更新的消息类型信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts) {
                return $this->error('更新内容不能全为空', "/msgtype/edit/id/{$id}{$cond}");
            }

            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新消息类型信息
            $result = $msgType->update($posts);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('消息类型更新失败', "/msgtype/edit/id/{$id}{$cond}");
            }

            return $this->success('消息类型更新成功', "/msgtype/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('消息类型信息重复', "/msgtype/edit/id/{$id}{$cond}");
            } else {
                return $this->error('消息类型更新失败', "/msgtype/edit/id/{$id}{$cond}");
            }
        }
    }
}