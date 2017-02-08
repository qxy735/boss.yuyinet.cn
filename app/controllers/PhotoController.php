<?php

use \Phalcon\Paginator\Adapter\Model;

class PhotoController extends BaseController
{
    /**
     * 是否删除相片
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
        'edit' => [false, ['blog.boss.facility.album.U']],
        'save' => [false, ['blog.boss.facility.album.U']],
    ];

    /**
     * 显示相片
     *
     * @return mixed
     */
    public function listAction()
    {
        // 获取相册名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('page');

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取相册 ID
            $albumId = (int)$this->getParam('albumid');

            // 获取相册信息
            $album = Album::findFirst($albumId);

            // 判断相册是否存在
            if (!$album) {
                return $this->error('相册不存在', "/album/list{$cond}");
            }

            // 获取当前页码
            $curPage = (int)$this->getParam('_page');
            $curPage = $curPage ? : 1;

            // 获取每页显示的数据量
            $pageSize = $this->pageSize;

            // 获取相片信息
            $photos = Photo::find([
                'conditions' => "albumid={$albumId} " . 'order by id desc',
                'columns' => 'id,albumid,name,url,description,liked,click,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $photos,
                    "limit" => $pageSize,
                    "page" => $curPage,
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
                'page' => $curPage,
                'pagesize' => $pageSize,
                'total' => $pageDatas->total_items,
            ];

            $photos = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 转换数值
            $photos = array_map(function ($photo) {
                $photo['createtime'] = $photo['createtime'] ? date('Y-m-d H:i:s', $photo['createtime']) : '';
                $photo['lastoperate'] = $photo['lastoperate'] ? date('Y-m-d H:i:s', $photo['lastoperate']) : '';

                return $photo;
            }, $photos);

            // 传递相片信息
            $this->view->photos = $photos;

            unset($photos);

            // 传递分页信息
            $this->view->page = $page;

            // 传递查询条件组合
            $this->view->cond = $cond;

            $this->view->curcond = "/albumid/{$albumId}" . $cond;

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.facility.album.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.facility.album.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.facility.album.U']);

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.facility.album.C']);

            // 传递相册名
            $this->view->albumName = $album->name;

            return $this->view->pick('photo/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('相片页面加载失败', "/album/list{$cond}");
        }
    }

    /**
     * 显示添加相片页面
     *
     * @return mixed
     */
    public function createAction()
    {
        // 获取相册名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取相册 ID
        $albumId = (int)$this->getParam('albumid');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('page');

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/albumid/{$albumId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取相册信息
            $album = Album::findFirst($albumId);

            // 判断相册是否存在
            if (!$album) {
                return $this->error('相册不存在', "/album/list{$cond}");
            }

            // 传递查询条件信息
            $this->view->cond = $cond;

            // 传递相册名
            $this->view->albumName = $album->name;

            return $this->view->pick('photo/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('相片添加页面加载失败', "/photo/list{$cond}");
        }
    }

    /**
     * 添加相片
     *
     * @return mixed
     */
    public function postAction()
    {
        // 获取相册名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取相册 ID
        $albumId = (int)$this->getParam('albumid');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('page');

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/albumid/{$albumId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            $photo = new Photo();

            // 获取需要添加的相片数据
            $posts = $this->request->getPost();

            // 判断相片名称是否为空
            if (!isset($posts['_name']) || !$posts['_name']) {
                return $this->error('相片名称必填', "/photo/create{$cond}");
            }

            $posts['name'] = $posts['_name'];

            unset($posts['_name']);

            // 上传相片
            if (isset($_FILES['photo']) && $_FILES['photo']['name']) {
                $upload = new Upload();

                // 上传文件
                $file = $upload->make('photo', 'album');

                // 判断上传是否成功
                if (!$file) {
                    $msg = $upload->getErr() ? : '相片上传失败';

                    return $this->error($msg, "/photo/list{$cond}");
                }

                $posts['url'] = $file;
            }

            if (isset($posts['editorValue'])) {
                $posts['description'] = $posts['editorValue'];

                unset($posts['editorValue']);
            }

            $posts['albumid'] = $albumId;
            $posts['createtime'] = time();
            $posts['creator'] = $this->getUserName();
            // 添加相片信息
            $result = $photo->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('相片添加失败', "/photo/create{$cond}");
            }

            // 获取相册信息
            $album = Album::findFirst($albumId);

            // 判断相册是否存在
            if ($album) {
                // 更新相册中的相片数量
                $album->update([
                    'photos' => $album->photos + 1,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);
            }

            return $this->success('相片添加成功', "/photo/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('相片重复', "/photo/create{$cond}");
            } else {
                return $this->error('相片添加失败', "/photo/create{$cond}");
            }
        }
    }

    /**
     * 删除相片
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取相册名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取相册 ID
        $albumId = (int)$this->getParam('albumid');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('page');

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/albumid/{$albumId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取相片信息 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的相片信息
                $photo = Photo::findFirst(intval($id));

                // 判断相片信息是否存在,存在则做删除
                if ($photo) {
                    $url = $photo->url;

                    // 删除指定相片信息
                    $result = $photo->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    } else {
                        // 获取相册信息
                        $album = Album::findFirst($albumId);

                        // 判断相册是否存在
                        if ($album) {
                            // 更新相册中的相片数量
                            $album->update([
                                'photos' => $album->photos ? $album->photo - 1 : 0,
                                'lastoperate' => time(),
                                'lastoperator' => $this->getUserName(),
                            ]);
                        }

                        // 删除相片
                        if ($url) {
                            $url = ROOT . $url;

                            file_exists($url) and unlink($url);
                        }
                    }
                }
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('相片删除失败', "/photo/list{$cond}");
            }

            return $this->success('相片删除成功', "/photo/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('相片删除失败', "/photo/list{$cond}");
        }
    }

    /**
     * 显示相片更新页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取相册名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取相册 ID
        $albumId = (int)$this->getParam('albumid');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('page');
        $curPage = (int)$this->getParam('_page');

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/_page/{$curPage}/albumid/{$albumId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取相片信息 ID
            $id = (int)$this->getParam('id');

            // 根据 ID 获取对应的相片信息
            $photo = Photo::find($id)->toArray();

            // 判断相片是否存在
            if (!$photo) {
                return $this->error('相片不存在', "/photo/list{$cond}");
            }

            $photo = $photo[0];

            // 传递相片信息
            $this->view->photo = $photo;

            $this->view->cond = $cond;

            // 传递是否删除封面图信息
            $this->view->covers = $this->covers;

            // 获取相册信息
            $album = Album::findFirst($albumId);

            // 判断相册是否存在
            if (!$album) {
                return $this->error('相册不存在', "/album/list{$cond}");
            }

            // 传递相册名
            $this->view->albumName = $album->name;

            return $this->view->pick('photo/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('相片更新页面加载失败', "/photo/list{$cond}");
        }
    }

    /**
     * 更新相片
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取相片 ID
        $id = (int)$this->getParam('id');

        // 获取相册名称
        $name = urldecode($this->getParam('name'));

        // 获取启用状态
        $enabled = (int)$this->getParam('enabled');

        // 获取相册 ID
        $albumId = (int)$this->getParam('albumid');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('page');
        $curPage = (int)$this->getParam('_page');

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/_page/{$curPage}/albumid/{$albumId}" . ($name ? "/name/{$name}" : '') . ('' !== $enabled ? "/enabled/{$enabled}" : '');

        try {
            // 获取相片信息
            $photo = Photo::findFirst($id);

            // 判断相片是否存在
            if (!$photo) {
                return $this->error('相片不存在', "/photo/list{$cond}");
            }

            $url = $photo->url;

            // 获取需要更新的相片信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts && !isset($_FILES['photo'])) {
                return $this->error('更新内容不能全为空', "/photo/edit/id/{$id}{$cond}");
            }

            // 判断相片名是否为空
            if (!isset($posts['_name']) || !$posts['_name']) {
                return $this->error('相片名称不能为空', "/photo/edit/id/{$id}{$cond}");
            }

            $posts['name'] = $posts['_name'];

            unset($posts['_name']);

            // 判断是否删除原封面图
            if (isset($posts['iscover'])) {
                if ($posts['iscover'] && $url) {
                    // 删除原封面图片
                    $path = ROOT . $url;

                    file_exists($path) and unlink($path);

                    $posts['url'] = '';
                }
                unset($posts['iscover']);
            }

            $file = '';

            // 上传相片
            if (isset($_FILES['photo']) && $_FILES['photo']['name']) {
                $upload = new Upload();

                // 上传文件
                $file = $upload->make('photo', 'album');

                // 判断上传是否成功
                if (!$file) {
                    $msg = $upload->getErr() ? : '相片上传失败';

                    return $this->error($msg, "/photo/edit/id/{$id}{$cond}");
                }

                $posts['url'] = $file;
            }

            if (isset($posts['editorValue'])) {
                $posts['description'] = $posts['editorValue'];

                unset($posts['editorValue']);
            }

            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新相片
            $result = $photo->update($posts);

            // 判断更新是否成功
            if (!$result) {
                // 删除新上传的图片
                if ($file) {
                    $path = ROOT . $file;

                    file_exists($path) and unlink($path);
                }

                return $this->error('相片更新失败', "/photo/edit/id/{$id}{$cond}");
            }

            // 删除原相片
            if ($url && $file) {
                $path = ROOT . $url;

                file_exists($path) and unlink($path);
            }

            return $this->success('相片更新成功', "/photo/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('相片重复', "/photo/edit/id/{$id}{$cond}");
            } else {
                return $this->error('相片更新失败', "/photo/edit/id/{$id}{$cond}");
            }
        }
    }
}