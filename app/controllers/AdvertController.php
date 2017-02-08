<?php

use \Phalcon\Paginator\Adapter\Model;

class AdvertController extends BaseController
{
    /**
     * 广告显示位置
     *
     * @var array
     */
    protected $spots = [
        '文章列表页',
        '文章内容页',
        '首页',
    ];
    /**
     * 价格类型
     *
     * @var array
     */
    protected $priceTypes = [
        '常规',
        '试用',
        '优惠',
        '续费',
        '免费',
    ];
    /**
     * 广告状态信息
     *
     * @var array
     */
    protected $status = [
        '待显示',
        '显示中',
        '已到期',
        '已关闭',
    ];
    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.facility.advert.L', 'blog.boss.facility.advert.R', 'blog.boss.facility.advert.S']],
        'create' => [false, ['blog.boss.facility.advert.C']],
        'post' => [false, ['blog.boss.facility.advert.C']],
        'delete' => [false, ['blog.boss.facility.advert.D', 'blog.boss.facility.advert.BD']],
        'disabled' => [false, ['blog.boss.facility.advert.SET']],
        'edit' => [false, ['blog.boss.facility.advert.U']],
        'save' => [false, ['blog.boss.facility.advert.U']],
    ];

    /**
     * 显示广告管理页面
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

            // 获取广告名
            $name = $this->getParam('name');
            $name = urldecode($name);
            $nameSql = $name ? " and name like '%{$name}%'" : '';

            // 获取广告状态
            $status = $this->getParam('status');
            $status = (null === $status) ? -1 : intval($status);
            $statusSql = (-1 === $status) ? '' : " and status={$status}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取广告信息
            $adverts = Advert::find([
                'conditions' => "1=1 {$nameSql}{$statusSql} " . 'order by id asc',
                'columns' => 'id,name,spot,price,pricetype,starttime,endtime,status,createtime,creator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $adverts,
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

            $adverts = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 转换数值
            $adverts = array_map(function ($advert) {
                $advert['createtime'] = $advert['createtime'] ? date('Y-m-d H:i:s', $advert['createtime']) : '';
                $advert['starttime'] = $advert['starttime'] ? date('Y-m-d', $advert['starttime']) : '';
                $advert['endtime'] = $advert['endtime'] ? date('Y-m-d', $advert['endtime']) : '';
                $advert['statusname'] = $this->status[$advert['status']];
                $advert['pricetype'] = $this->priceTypes[$advert['pricetype']];
                $advert['spot'] = $this->spots[$advert['spot']];

                return $advert;
            }, $adverts);

            // 传递广告信息
            $this->view->adverts = $adverts;

            unset($adverts);

            // 传递查询参数
            $this->view->statu = $status;
            $this->view->name = $name;

            // 传递广告状态信息
            $this->view->status = $this->status;

            // 传递关闭值

            $this->view->disabled = Advert::ADVERT_STATUS_DISABLED;

            // 传递分页信息
            $this->view->page = $page;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ('' !== $status ? "/status/{$status}" : '');

            // 传递查询条件信息
            $this->view->cond = $cond;

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.facility.advert.C']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.facility.advert.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.facility.advert.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.facility.advert.U']);

            // 是否显示设置操作相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.facility.advert.SET']);

            return $this->view->pick('advert/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('广告管理页面加载失败', '/');
        }
    }

    /**
     * 添加广告信息
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
            // 传递广告价位类型信息
            $this->view->priceTypes = $this->priceTypes;

            // 传递广告位置信息
            $this->view->spots = $this->spots;

            // 传递查询条件信息
            $this->view->cond = $cond;

            return $this->view->pick('advert/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('广告添加页面加载失败', "/advert/list{$cond}");
        }
    }

    /**
     * 添加广告信息
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
            $advert = new Advert();

            // 获取需要添加的广告数据
            $posts = $this->request->getPost();

            // 判断广告名称是否为空
            if (!isset($posts['name']) || !$posts['name']) {
                return $this->error('广告名称必填', "/advert/create{$cond}");
            }

            // 判断广告封面地址是否为空
            if (!isset($posts['cover']) || !$posts['cover']) {
                return $this->error('广告封面地址必填', "/advert/create{$cond}");
            }

            // 判断广告链接地址是否为空
            if (!isset($posts['url']) || !$posts['url']) {
                return $this->error('广告链接地址必填', "/advert/create{$cond}");
            }

            $posts['starttime'] = isset($posts['starttime']) ? strtotime($posts['starttime']) : 0;
            $posts['endtime'] = isset($posts['endtime']) ? strtotime($posts['endtime']) : 0;
            $posts['createtime'] = time();
            $posts['creator'] = $this->getUserName();

            // 添加广告信息
            $result = $advert->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('广告信息添加失败', "/advert/create{$cond}");
            }

            return $this->success('广告信息添加成功', "/advert/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('广告信息重复', "/advert/create{$cond}");
            } else {
                return $this->error('广告信息添加失败', "/advert/create{$cond}");
            }
        }
    }

    /**
     * 删除广告信息
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取广告名称
        $name = urldecode($this->getParam('name'));

        // 获取广告状态
        $status = (int)$this->getParam('status');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($name ? "/name/{$name}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取广告信息 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的广告信息
                $advert = Advert::findFirst(intval($id));

                // 判断广告信息是否存在,存在则做删除
                if ($advert) {
                    // 删除指定广告信息
                    $result = $advert->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('广告信息删除失败', "/advert/list{$cond}");
            }

            return $this->success('广告信息删除成功', "/advert/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('广告信息删除失败', "/advert/list{$cond}");
        }
    }

    /**
     * 关闭广告信息
     *
     * @return mixed
     */
    public function disabledAction()
    {
        // 获取广告名称
        $name = urldecode($this->getParam('name'));

        // 获取广告状态
        $status = (int)$this->getParam('status');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取广告信息 ID
            $id = (int)$this->getParam('id');

            // 获取广告信息
            $advert = Advert::findFirst($id);

            // 广告信息存在则做更新操作
            if ($advert) {
                // 关闭广告信息
                $result = $advert->update([
                    'status' => Advert::ADVERT_STATUS_DISABLED,
                    'cause' => '广告已关闭',
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('广告信息关闭失败', "/advert/list{$cond}");
                }
            }

            return $this->success('广告信息关闭成功', "/advert/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('广告信息关闭失败', "/advert/list{$cond}");
        }
    }

    /**
     * 显示广告信息编辑页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取广告名称
        $name = urldecode($this->getParam('name'));

        // 获取广告状态
        $status = (int)$this->getParam('status');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取广告信息 ID
            $id = (int)$this->getParam('id');

            // 根据 ID 获取对应的广告信息
            $advert = Advert::find($id)->toArray();

            // 判断广告信息是否存在
            if (!$advert) {
                return $this->error('广告信息不存在', "/advert/list{$cond}");
            }

            $advert = $advert[0];

            $advert['starttime'] = $advert['starttime'] ? date('Y-m-d', $advert['starttime']) : '';
            $advert['endtime'] = $advert['endtime'] ? date('Y-m-d', $advert['endtime']) : '';

            // 传递广告信息
            $this->view->advert = $advert;

            $this->view->conds = [
                '_name' => $name,
                '_page' => $page,
                '_status' => $status,
            ];

            $this->view->cond = $cond;

            // 传递价位类型信息
            $this->view->priceTypes = $this->priceTypes;

            // 传递显示位置信息
            $this->view->spots = $this->spots;

            // 传递广告状态信息
            $this->view->status = $this->status;

            return $this->view->pick('advert/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('广告信息编辑页面加载失败', "/advert/list{$cond}");
        }
    }

    /**
     * 更新广告信息
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取广告信息 ID
        $id = (int)$this->getParam('id');

        // 获取广告名称
        $name = urldecode($this->getParam('_name'));

        // 获取广告状态
        $status = (int)$this->getParam('_status');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('_page');
        $page = $page ? : 1;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($name ? "/name/{$name}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取广告信息
            $advert = Advert::findFirst($id);

            // 判断广告信息是否存在
            if (!$advert) {
                return $this->error('广告信息不存在', "/advert/list{$cond}");
            }

            // 获取需要更新的广告信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts) {
                return $this->error('更新内容不能全为空', "/advert/edit/id/{$id}{$cond}");
            }

            $posts['starttime'] = isset($posts['starttime']) ? strtotime($posts['starttime']) : 0;
            $posts['endtime'] = isset($posts['endtime']) ? strtotime($posts['endtime']) : 0;
            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新广告信息
            $result = $advert->update($posts);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('广告信息更新失败', "/advert/edit/id/{$id}{$cond}");
            }

            return $this->success('广告信息更新成功', "/advert/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('广告信息重复', "/advert/edit/id/{$id}{$cond}");
            } else {
                return $this->error('广告信息更新失败', "/advert/edit/id/{$id}{$cond}");
            }
        }
    }
}