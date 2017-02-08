<?php

use \Phalcon\Paginator\Adapter\Model;

class AlbumController extends BaseController
{
    /**
     * 启用状态信息
     *
     * @var array
     */
    protected $enableds = [
        '否',
        '是',
    ];
    /**
     * 是否删除封面图
     *
     * @var array
     */
    protected $covers = [
        '否',
        '是',
    ];
    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.facility.album.L', 'blog.boss.facility.album.R', 'blog.boss.facility.album.S']],
        'create' => [false, ['blog.boss.facility.album.C']],
        'post' => [false, ['blog.boss.facility.album.C']],
        'delete' => [false, ['blog.boss.facility.album.D', 'blog.boss.facility.album.BD']],
        'disabled' => [false, ['blog.boss.facility.album.SET']],
        'enabled' => [false, ['blog.boss.facility.album.SET']],
        'edit' => [false, ['blog.boss.facility.album.U']],
        'save' => [false, ['blog.boss.facility.album.U']],
    ];

    /**
     * 显示相册页面
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

            // 获取相册名
            $name = $this->getParam('name');
            $name = urldecode($name);
            $nameSql = $name ? " and name like '%{$name}%'" : '';

            // 获取启用状态
            $enabled = $this->getParam('enabled');
            $enabled = (null === $enabled) ? -1 : intval($enabled);
            $enabledSql = (-1 === $enabled) ? '' : " and enabled={$enabled}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取相册信息
            $albums = Album::find([
                'conditions' => "1=1{$nameSql}{$enabledSql} " . 'order by id desc',
                'columns' => 'id,name,cover,photos,enabled,displayorder,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $albums,
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

            $albums = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 转换数值
            $albums = array_map(function ($album) {
                $album['createtime'] = $album['createtime'] ? date('Y-m-d H:i:s', $album['createtime']) : '';
                $album['lastoperate'] = $album['lastoperate'] ? date('Y-m-d H:i:s', $album['lastoperate']) : '';
                $album['enabledname'] = $this->enableds[$album['enabled']];

                return $album;
            }, $albums);

            // 传递相册信息
            $this->view->albums = $albums;

            unset($albums);

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

            // 传递默认启用类型
            $this->view->defaultEnabled = Album::ALBUM_ENABLED_TYPE;

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.facility.album.C']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.facility.album.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.facility.album.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.facility.album.U']);

            // 是否显示设置操作相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.facility.album.SET']);

            return $this->view->pick('album/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('相册页面加载失败', '/');
        }
    }

    /**
     * 显示添加相册页面
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
            $this->view->defaultEnabled = Album::ALBUM_ENABLED_TYPE;

            // 传递查询条件信息
            $this->view->cond = $cond;

            return $this->view->pick('album/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('相册页面加载失败', "/album/list{$cond}");
        }
    }

    /**
     * 添加相册
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
            $album = new Album();

            // 获取需要添加的相册数据
            $posts = $this->request->getPost();

            // 判断相册名称是否为空
            if (!isset($posts['name']) || !$posts['name']) {
                return $this->error('相册名称必填', "/album/create{$cond}");
            }

            // 上传相册封面图片
            if (isset($_FILES['cover']) && $_FILES['cover']['name']) {
                $upload = new Upload();

                // 上传文件
                $file = $upload->make('cover', 'album');

                // 判断上传是否成功
                if (!$file) {
                    $msg = $upload->getErr() ? : '相册封面上传失败';

                    return $this->error($msg, "/album/list{$cond}");
                }

                $posts['cover'] = $file;
            }

            $posts['createtime'] = time();
            $posts['creator'] = $this->getUserName();

            // 添加相册信息
            $result = $album->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('相册添加失败', "/album/create{$cond}");
            }

            return $this->success('相册添加成功', "/album/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('相册重复', "/album/create{$cond}");
            } else {
                return $this->error('相册添加失败', "/album/create{$cond}");
            }
        }
    }

    /**
     * 删除相册
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取相册名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取相册信息 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = $isRelation = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的相册信息
                $album = Album::findFirst(intval($id));

                // 判断相册信息是否存在,存在则做删除
                if ($album) {
                    // 判断是否存在相片信息
                    $photos = Photo::find([
                        'conditions' => "albumid={$id}",
                        'columns' => 'id',
                    ])->count();

                    // 判断是否存在关联数据
                    if ($photos) {
                        $isRelation = true;
                        continue;
                    }

                    $cover = $album->cover;

                    // 删除指定相册信息
                    $result = $album->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    } else {
                        // 删除相册封面
                        if ($cover) {
                            $cover = ROOT . $cover;

                            file_exists($cover) and unlink($cover);
                        }
                    }
                }
            }

            // 判断是否存在关联数据
            if ($isRelation) {
                return $this->error('相册中存在相片,不允许删除', "/album/list{$cond}");
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('相册删除失败', "/album/list{$cond}");
            }

            return $this->success('相册删除成功', "/album/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('相册删除失败', "/album/list{$cond}");
        }
    }

    /**
     * 禁用相册
     *
     * @return mixed
     */
    public function disabledAction()
    {
        // 获取相册名称
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
            // 获取相册信息 ID
            $id = (int)$this->getParam('id');

            // 获取相册信息
            $album = Album::findFirst($id);

            // 相册信息存在则做更新操作
            if ($album) {
                // 禁用相册信息
                $result = $album->update([
                    'enabled' => Album::ALBUM_DISABLED_TYPE,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('相册禁用失败', "/album/list{$cond}");
                }
            }

            return $this->success('相册禁用成功', "/album/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('相册禁用失败', "/album/list{$cond}");
        }
    }

    /**
     * 启用相册
     *
     * @return mixed
     */
    public function enabledAction()
    {
        // 获取相册名称
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
            // 获取相册信息 ID
            $id = (int)$this->getParam('id');

            // 获取相册信息
            $album = Album::findFirst($id);

            // 相册信息存在则做更新操作
            if ($album) {
                // 启用相册信息
                $result = $album->update([
                    'enabled' => Album::ALBUM_ENABLED_TYPE,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('相册启用失败', "/album/list{$cond}");
                }
            }

            return $this->success('相册启用成功', "/album/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('相册启用失败', "/album/list{$cond}");
        }
    }

    /**
     * 显示相册编辑页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取相册名称
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
            // 获取相册信息 ID
            $id = (int)$this->getParam('id');

            // 根据 ID 获取对应的相册信息
            $album = Album::find($id)->toArray();

            // 判断相册是否存在
            if (!$album) {
                return $this->error('相册不存在', "/album/list{$cond}");
            }

            $album = $album[0];

            // 传递相册信息
            $this->view->album = $album;

            $this->view->conds = [
                '_name' => $name,
                '_page' => $page,
                '_enabled' => $enabled,
            ];

            $this->view->cond = $cond;

            // 传递启用状态信息
            $this->view->enableds = $this->enableds;

            // 传递是否删除封面图信息
            $this->view->covers = $this->covers;

            return $this->view->pick('album/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('相册编辑页面加载失败', "/album/list{$cond}");
        }
    }

    /**
     * 更新相册
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取相册 ID
        $id = (int)$this->getParam('id');

        // 获取相册名称
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
            // 获取相册信息
            $album = Album::findFirst($id);

            // 判断相册是否存在
            if (!$album) {
                return $this->error('相册不存在', "/album/list{$cond}");
            }

            $cover = $album->cover;

            // 获取需要更新的相册信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts && !isset($_FILES['cover'])) {
                return $this->error('更新内容不能全为空', "/album/edit/id/{$id}{$cond}");
            }

            // 判断是否删除原封面图
            if (isset($posts['iscover'])) {
                if ($posts['iscover'] && $cover) {
                    // 删除原封面图片
                    $path = ROOT . $cover;

                    file_exists($path) and unlink($path);

                    $posts['cover'] = '';
                }
                unset($posts['iscover']);
            }

            $file = '';

            // 上传相册封面图片
            if (isset($_FILES['cover']) && $_FILES['cover']['name']) {
                $upload = new Upload();

                // 上传文件
                $file = $upload->make('cover', 'album');

                // 判断上传是否成功
                if (!$file) {
                    $msg = $upload->getErr() ? : '相册封面上传失败';

                    return $this->error($msg, "/album/edit/id/{$id}{$cond}");
                }

                $posts['cover'] = $file;
            }

            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新相册
            $result = $album->update($posts);

            // 判断更新是否成功
            if (!$result) {
                // 删除新上传的图片
                if ($file) {
                    $path = ROOT . $file;

                    file_exists($path) and unlink($path);
                }

                return $this->error('相册更新失败', "/album/edit/id/{$id}{$cond}");
            }

            // 删除原封面图片
            if ($cover && $file) {
                $path = ROOT . $cover;

                file_exists($path) and unlink($path);
            }

            return $this->success('相册更新成功', "/album/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('相册重复', "/album/edit/id/{$id}{$cond}");
            } else {
                return $this->error('相册更新失败', "/album/edit/id/{$id}{$cond}");
            }
        }
    }
}