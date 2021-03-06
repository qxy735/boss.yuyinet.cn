<?php

use \Phalcon\Paginator\Adapter\Model;

class TagController extends BaseController
{
    /**
     * 标签启用状态信息
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
        'list' => [false, ['blog.boss.facility.tag.L', 'blog.boss.facility.tag.R', 'blog.boss.facility.tag.S']],
        'create' => [false, ['blog.boss.facility.tag.C']],
        'post' => [false, ['blog.boss.facility.tag.C']],
        'delete' => [false, ['blog.boss.facility.tag.D', 'blog.boss.facility.tag.BD']],
        'disabled' => [false, ['blog.boss.facility.tag.SET']],
        'enabled' => [false, ['blog.boss.facility.tag.SET']],
        'edit' => [false, ['blog.boss.facility.tag.U']],
        'save' => [false, ['blog.boss.facility.tag.U']],
    ];

    /**
     * 显示标签页面
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

            // 获取标签名
            $name = $this->getParam('name');
            $name = urldecode($name);
            $nameSql = $name ? " and name like '%{$name}%'" : '';

            // 获取启用状态
            $enabled = $this->getParam('enabled');
            $enabled = (null === $enabled) ? -1 : intval($enabled);
            $enabledSql = (-1 === $enabled) ? '' : " and enabled={$enabled}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取标签信息
            $tags = Tag::find([
                'conditions' => "1=1 {$nameSql}{$enabledSql} " . 'order by id desc',
                'columns' => 'id,name,enabled,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $tags,
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

            $tags = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 转换数值
            $tags = array_map(function ($tag) {
                $tag['createtime'] = $tag['createtime'] ? date('Y-m-d H:i:s', $tag['createtime']) : '';
                $tag['lastoperate'] = $tag['lastoperate'] ? date('Y-m-d H:i:s', $tag['lastoperate']) : '';
                $tag['enabledname'] = $this->enableds[$tag['enabled']];

                return $tag;
            }, $tags);

            // 传递标签信息
            $this->view->tags = $tags;

            // 传递分页信息
            $this->view->page = $page;

            // 传递启用状态信息
            $this->view->enableds = $this->enableds;

            // 传递查询参数
            $this->view->name = $name;
            $this->view->enabled = $enabled;

            // 传递默认启用状态
            $this->view->defaultEnabled = Tag::ENABLED_TAG_STATUS;

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

            // 传递查询条件组合
            $this->view->cond = $cond;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.facility.tag.C']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.facility.tag.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.facility.tag.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.facility.tag.U']);

            // 是否显示设置操作相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.facility.tag.SET']);

            return $this->view->pick('tag/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('标签页面加载失败', '/');
        }
    }

    /**
     * 显示标签添加页面
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

            $this->view->cond = $cond;

            // 传递默认启用状态
            $this->view->defaultEnabled = Tag::ENABLED_TAG_STATUS;

            return $this->view->pick('tag/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('标签添加页面加载失败', "/tag/list{$cond}");
        }
    }

    /**
     * 添加标签
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
            $tag = new Tag();

            // 获取需要添加的标签数据
            $posts = $this->request->getPost();

            // 判断标签名是否为空
            if (!isset($posts['name']) || !$posts['name']) {
                return $this->error('标签名称必填', "/tag/create{$cond}");
            }

            $posts['createtime'] = time();
            $posts['creator'] = $this->getUserName();

            // 添加标签信息
            $result = $tag->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('标签添加失败', "/tag/create{$cond}");
            }

            return $this->success('标签添加成功', "/tag/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('标签信息重复', "/tag/create{$cond}");
            } else {
                return $this->error('标签添加失败', "/tag/create{$cond}");
            }
        }
    }

    /**
     * 删除标签
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取标签名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取标签 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = $isRelation = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的标签信息
                $tag = Tag::findFirst(intval($id));

                // 判断标签信息是否存在,存在则做删除
                if ($tag) {
                    // 获取关联数据
                    $articleTags = ArticleTag::findFirst([
                        'conditions' => "tagid={$id}",
                        'columns' => 'id',
                    ]);

                    // 判断是否存在关联关系
                    if ($articleTags) {
                        $isRelation = true;
                        continue;
                    }

                    // 删除指定标签信息
                    $result = $tag->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断是否存在关联关系
            if ($isRelation) {
                return $this->error('标签存在关联数据,不允许删除', "/tag/list{$cond}");
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('标签删除失败', "/tag/list{$cond}");
            }

            return $this->success('标签删除成功', "/tag/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('标签删除失败', "/tag/list{$cond}");
        }
    }

    /**
     * 禁用标签
     *
     * @return mixed
     */
    public function disabledAction()
    {
        // 获取标签名称
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
            // 获取标签 ID
            $id = (int)$this->getParam('id');

            // 获取标签信息
            $tag = Tag::findFirst($id);

            // 标签信息存在则做更新操作
            if ($tag) {
                // 更新标签是否启用的状态
                $result = $tag->update([
                    'enabled' => Tag::DISABLED_TAG_STATUS,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('标签禁用失败', "/tag/list{$cond}");
                }
            }

            return $this->success('标签禁用成功', "/tag/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('标签禁用失败', "/tag/list{$cond}");
        }
    }

    /**
     * 启用标签
     *
     * @return mixed
     */
    public function enabledAction()
    {
        // 获取标签名称
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
            // 获取标签 ID
            $id = (int)$this->getParam('id');

            // 获取标签信息
            $tag = Tag::findFirst($id);

            // 标签信息存在则做更新操作
            if ($tag) {
                // 更新标签是否启用的状态
                $result = $tag->update([
                    'enabled' => Tag::ENABLED_TAG_STATUS,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('标签启用失败', "/tag/list{$cond}");
                }
            }

            return $this->success('标签启用成功', "/tag/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('标签启用失败', "/tag/list{$cond}");
        }
    }

    /**
     * 显示编辑标签页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取标签名称
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
            // 获取标签 ID
            $id = (int)$this->getParam('id');

            // 根据 ID 获取对应的标签信息
            $tag = Tag::find($id)->toArray();

            // 判断标签信息是否存在
            if (!$tag) {
                return $this->error('标签不存在', "/tag/list{$cond}");
            }

            $tag = $tag[0];

            // 传递标签信息
            $this->view->tag = $tag;

            // 卸载空闲变量
            unset($tag);

            $this->view->conds = [
                '_name' => $name,
                '_page' => $page,
                '_enabled' => $enabled,
            ];

            $this->view->enableds = $this->enableds;

            $this->view->cond = $cond;

            return $this->view->pick('tag/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('标签编辑页面加载失败', "/tag/list{$cond}");
        }
    }

    /**
     * 更新标签信息
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取标签 ID
        $id = (int)$this->request->getPost('id');

        // 获取标签名称
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
            // 获取标签信息
            $tag = Tag::findFirst($id);

            // 判断标签信息是否存在
            if (!$tag) {
                return $this->error('标签信息不存在', "/tag/list{$cond}");
            }

            // 获取需要更新的标签信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts) {
                return $this->error('更新内容不能全为空', "/tag/edit/id/{$id}{$cond}");
            }

            // 判断标签名称是否为空
            if (!isset($posts['name']) || !$posts['name']) {
                return $this->error('标签名称不能为空', "/tag/edit/id/{$id}{$cond}");
            }

            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新标签信息
            $result = $tag->update($posts);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('标签更新失败', "/tag/edit/id/{$id}{$cond}");
            }

            return $this->success('标签更新成功', "/tag/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('标签重复', "/tag/edit/id/{$id}{$cond}");
            } else {
                return $this->error('标签更新失败', "/tag/edit/id/{$id}{$cond}");
            }
        }
    }
}