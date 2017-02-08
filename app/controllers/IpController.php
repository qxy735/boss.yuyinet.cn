<?php

use \Phalcon\Paginator\Adapter\Model;

class IpController extends BaseController
{
    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.app.ip.L', 'blog.boss.app.ip.R', 'blog.boss.app.ip.S']],
        'create' => [false, ['blog.boss.app.ip.C']],
        'post' => [false, ['blog.boss.app.ip.C']],
        'delete' => [false, ['blog.boss.app.ip.D', 'blog.boss.app.ip.BD']],
        'edit' => [false, ['blog.boss.app.ip.U']],
        'save' => [false, ['blog.boss.app.ip.U']],
    ];

    /**
     * 显示IP限制页面
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

            // 获取ip
            $curIp = $ip = $this->getParam('ip');

            $this->view->ip = $ip;

            if ($ip) {
                $ip = (int)ip2long($ip);

                $ipSql = " and ip={$ip}";
            } else {
                $ipSql = '';
            }

            // IP限制过期开始时间
            $startTime = $this->getParam('starttime');

            $this->view->startTime = $startTime;

            $startTime = $startTime ? strtotime($startTime . ' 00:00:00') : 0;
            $startTimeSql = $startTime ? " and expire >= {$startTime}" : '';

            // IP限制过期结束时间
            $endTime = $this->getParam('endtime');

            $this->view->endTime = $endTime;

            $endTime = $endTime ? strtotime($endTime . " 23:59:59") : 0;

            $endTimeSql = $endTime ? " and expire <= {$endTime}" : '';

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取IP限制信息
            $ips = Ip::find([
                'conditions' => "1=1 {$ipSql}{$startTimeSql}{$endTimeSql} order by id desc",
                'columns' => 'id,ip,expire,createtime,creator,lastoperate,lastoperator'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $ips,
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

            $ips = json_decode(json_encode($pageDatas->items), true);

            // IP 限制信息转换
            $ips = array_map(function ($ip) {
                $ip['ip'] = $ip['ip'] ? long2ip($ip['ip']) : '';

                $ip['enabled'] = (time() > $ip['expire'] && $ip['expire']) ? '是' : '否';

                $ip['expire'] = $ip['expire'] ? date('Y-m-d', $ip['expire']) : '';

                $ip['createtime'] = $ip['createtime'] ? date('Y-m-d H:i:s', $ip['createtime']) : '';

                $ip['lastoperate'] = $ip['lastoperate'] ? date('Y-m-d H:i:s', $ip['lastoperate']) : '';

                return $ip;
            }, $ips);

            // 卸载空闲变量
            unset($pageDatas);

            // 传递IP限制信息
            $this->view->ips = $ips;

            // 传递分页信息
            $this->view->page = $page;

            $startTime = $startTime ? date('Y-m-d', $startTime) : '';
            $endTime = $endTime ? date('Y-m-d', $endTime) : '';

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($curIp ? "/ip/{$curIp}" : '') . ($startTime ? "/starttime/{$startTime}" : '') . ($endTime ? "/endtime/{$endTime}" : '');

            // 传递查询条件组合
            $this->view->cond = $cond;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 是否显示添加按钮
            $this->view->isCreateBut = $this->hasAuth(['blog.boss.app.ip.C']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.app.ip.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.app.ip.BD']);

            // 是否显示编辑按钮
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.app.ip.U']);

            return $this->view->pick('ip/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('IP限制页面加载失败', '/');
        }
    }

    /**
     * 显示IP限制添加页面
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
            $this->view->cond = $cond;

            return $this->view->pick('ip/create');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('IP限制添加页面加载失败', "/ip/list{$cond}");
        }
    }

    /**
     * 添加 IP 限制信息
     *
     * @return mixed
     */
    public function postAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        $cond = "/navid/{$navId}";

        try {
            $ip = new Ip();

            // 获取需要添加的IP限制信息
            $posts = $this->request->getPost();

            // 判断IP是否为空
            if (!isset($posts['ip']) || !$posts['ip']) {
                return $this->error('IP地址不能为空', "/ip/create{$cond}");
            }

            // 处理过期时间
            $posts['expire'] = isset($posts['expire']) ? strtotime($posts['expire']) : 0;

            // 处理 IP
            $posts['ip'] = ip2long($posts['ip']);

            $posts['createtime'] = time();
            $posts['creator'] = $this->getUserName();

            // 添加IP限制信息
            $result = $ip->save($posts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('IP限制信息添加失败', "/ip/create{$cond}");
            }

            return $this->success('IP限制信息添加成功', "/ip/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('IP限制信息重复', "/ip/create{$cond}");
            } else {
                return $this->error('IP限制信息添加重复', "/ip/create{$cond}");
            }
        }
    }

    /**
     * 删除 IP 限制信息
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取ip
        $ip = $this->getParam('ip');

        // 获取IP限制过期开始时间
        $startTime = $this->getParam('starttime');

        // 获取IP限制过期结束时间
        $endTime = $this->getParam('endtime');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($ip ? "/ip/{$ip}" : '') . ($startTime ? "/starttime/{$startTime}" : '') . ($endTime ? "/endtime/{$endTime}" : '');

        try {
            // 获取IP限制信息ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = false;

            foreach ($ids as $id) {
                $id = intval($id);

                // 获取IP限制信息
                $ip = Ip::findFirst($id);

                // 判断记录信息是否存在，存在则做删除
                if ($ip) {
                    // 删除记录信息
                    $result = $ip->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            if ($isFailed) {
                return $this->error('IP限制信息删除失败', "/ip/list{$cond}");
            }

            return $this->success('IP限制信息删除成功', "/ip/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('IP限制信息删除失败', "/ip/list{$cond}");
        }
    }

    /**
     * 显示编辑IP限制信息页面
     *
     * @return mixed
     */
    public function editAction()
    {
        // 获取ip
        $ip = $this->getParam('ip');

        // 获取IP限制过期开始时间
        $startTime = $this->getParam('starttime');

        // 获取IP限制过期结束时间
        $endTime = $this->getParam('endtime');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = $this->page;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($ip ? "/ip/{$ip}" : '') . ($startTime ? "/starttime/{$startTime}" : '') . ($endTime ? "/endtime/{$endTime}" : '');

        try {
            // 获取IP限制信息ID
            $id = (int)$this->getParam('id');

            // 根据ID获取对应的IP限制信息
            $ips = Ip::find($id)->toArray();

            // 判断IP限制信息是否存在
            if (!$ips) {
                return $this->error('IP限制信息不存在', "/ip/list{$cond}");
            }

            $ips = $ips[0];

            $ips['ip'] = $ips['ip'] ? long2ip($ips['ip']) : '';
            $ips['expire'] = $ips['expire'] ? date('Y-m-d', $ips['expire']) : '';

            $this->view->ip = $ips;

            $this->view->conds = [
                '_ip' => $ip,
                '_starttime' => $startTime,
                '_endtime' => $endTime,
                '_page' => $page,
            ];

            $this->view->cond = $cond;

            return $this->view->pick('ip/edit');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('IP限制编辑页面加载失败', "/ip/list{$cond}");
        }
    }

    /**
     * 更新 IP 限制信息
     *
     * @return mixed
     */
    public function saveAction()
    {
        // 获取记录ID
        $id = (int)$this->request->getPost('id');

        // 获取ip
        $ip = $this->getParam('_ip');

        // 获取IP限制过期开始时间
        $startTime = $this->getParam('_starttime');

        // 获取IP限制过期结束时间
        $endTime = $this->getParam('_endtime');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取页码
        $page = (int)$this->getParam('_page');
        $page = $page ? : 1;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($ip ? "/ip/{$ip}" : '') . ($startTime ? "/starttime/{$startTime}" : '') . ($endTime ? "/endtime/{$endTime}" : '');

        try {
            // 获取IP限制信息
            $ips = Ip::findFirst($id);

            // 判断IP限制信息是否存在
            if (!$ips) {
                return $this->error('IP限制信息不存在', "/ip/list{$cond}");
            }

            // 获取需要更新的IP限制信息
            $posts = $this->request->getPost();

            // 判断更新内容是否全为空
            if (!$posts) {
                return $this->error('更新内容不能全为空', "/ip/edit/id/{$id}{$cond}");
            }

            $posts['ip'] = ip2long($posts['ip']);
            $posts['expire'] = $posts['expire'] ? strtotime($posts['expire']) : 0;
            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新IP限制信息
            $result = $ips->update($posts);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('IP限制信息更新失败', "/ip/edit/id/{$id}{$cond}");
            }

            return $this->success('IP限制信息更新成功', "/ip/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('IP限制信息重复', "/ip/edit/id/{$id}{$cond}");
            } else {
                return $this->error('IP限制信息更新失败', "/ip/edit/id/{$id}{$cond}");
            }
        }
    }
}