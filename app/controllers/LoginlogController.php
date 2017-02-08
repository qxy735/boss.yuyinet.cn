<?php

use \Phalcon\Paginator\Adapter\Model;

class LoginLogController extends BaseController
{
    /**
     * 登陆状态
     *
     * @var array
     */
    protected $status = [
        '失败',
        '成功',
    ];
    /**
     * 登陆用户类型
     *
     * @var array
     */
    protected $types = [
        '前台',
        '后台',
    ];
    /**
     * 登陆来源信息
     *
     * @var array
     */
    protected $sources = [
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
        'list' => [false, ['blog.boss.app.loginlog.L', 'blog.boss.app.loginlog.R', 'blog.boss.app.loginlog.S']],
        'delete' => [false, ['blog.boss.app.loginlog.D', 'blog.boss.app.loginlog.BD']],
    ];

    /**
     * 显示登陆日志页面
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

            // 获取用户名
            $userName = $this->getParam('username');
            $userName = urldecode($userName);

            $this->view->userName = $userName;

            $userNameSql = $userName ? " and username like '%{$userName}%'" : '';

            // 获取ip
            $curIp = $ip = $this->getParam('loginip');

            $this->view->ip = $ip;

            if ($ip) {
                $ip = (int)ip2long($ip);

                $ipSql = " and loginip={$ip}";
            } else {
                $ipSql = '';
            }

            // 登陆开始时间
            $startTime = $this->getParam('starttime');

            $this->view->startTime = $startTime;

            $startTime = $startTime ? strtotime($startTime . ' 00:00:00') : 0;
            $startTimeSql = $startTime ? " and logintime >= {$startTime}" : '';

            // 登陆结束时间
            $endTime = $this->getParam('endtime');

            $this->view->endTime = $endTime;

            $endTime = $endTime ? strtotime($endTime . " 23:59:59") : 0;
            $endTimeSql = $endTime ? " and logintime <= {$endTime}" : '';

            // 获取登陆状态
            $status = $this->getParam('status');
            $status = (null === $status) ? -1 : intval($status);
            $statusSql = (-1 === $status) ? '' : " and status={$status}";

            $this->view->statu = $status;

            // 获取登陆用户类型
            $type = $this->getParam('type');
            $type = (null === $type) ? -1 : intval($type);
            $typeSql = (-1 === $type) ? '' : " and type={$type}";

            $this->view->type = $type;

            // 获取登陆来源
            $source = $this->getParam('source');
            $source = (null === $source) ? -1 : intval($source);
            $sourceSql = (-1 === $source) ? '' : " and origin={$source}";

            $this->view->source = $source;

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取登陆日志信息
            $logs = LoginLog::find([
                'conditions' => "1=1 {$ipSql}{$startTimeSql}{$endTimeSql}{$statusSql}{$typeSql}{$sourceSql}{$userNameSql} order by logintime desc,id desc",
                'columns' => 'id,username,logintime,loginip,status,cause,type,origin,agent'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $logs,
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

            $logs = json_decode(json_encode($pageDatas->items), true);

            // IP 限制信息转换
            $logs = array_map(function ($log) {
                $log['loginip'] = $log['loginip'] ? long2ip($log['loginip']) : '';

                $log['logintime'] = $log['logintime'] ? date('Y-m-d H:i:s', $log['logintime']) : '';

                $log['status'] = isset($this->status[$log['status']]) ? $this->status[$log['status']] : '未知';

                $log['type'] = isset($this->types[$log['type']]) ? $this->types[$log['type']] : '未知';

                $log['origin'] = isset($this->sources[$log['origin']]) ? $this->sources[$log['origin']] : '未知';

                return $log;
            }, $logs);

            // 卸载空闲变量
            unset($pageDatas);

            // 传递登陆信息
            $this->view->logs = $logs;

            // 传递分页信息
            $this->view->page = $page;

            $startTime = $startTime ? date('Y-m-d', $startTime) : '';
            $endTime = $endTime ? date('Y-m-d', $endTime) : '';

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($curIp ? "/loginip/{$curIp}" : '') . ($userName ? "/username/{$userName}" : '') . ($startTime ? "/starttime/{$startTime}" : '') . ($endTime ? "/endtime/{$endTime}" : '');
            $cond .= ('' !== $status ? "/status/{$status}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $source ? "/source/{$source}" : '');

            // 传递查询条件组合
            $this->view->cond = $cond;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            $this->view->status = $this->status;

            $this->view->types = $this->types;

            $this->view->sources = $this->sources;

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.app.loginlog.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.app.loginlog.BD']);

            return $this->view->pick('loginlog/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('登录日志页面加载失败', '/');
        }
    }

    /**
     * 删除登陆日志信息
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取登陆ip
        $ip = $this->getParam('ip');

        // 获取登陆开始时间
        $startTime = $this->getParam('starttime');

        // 获取登陆结束时间
        $endTime = $this->getParam('endtime');

        // 获取登陆状态
        $status = (int)$this->getParam('status');

        // 获取用户类型
        $type = (int)$this->getParam('type');

        // 获取登陆来源
        $source = (int)$this->getParam('source');

        // 获取用户名
        $userName = urldecode($this->getParam('username'));

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($ip ? "/ip/{$ip}" : '') . ($startTime ? "/starttime/{$startTime}" : '') . ($endTime ? "/endtime/{$endTime}" : '');
        $cond .= ('' !== $status ? "/status/{$status}" : '') . ('' !== $type ? "/type/{$type}" : '') . ('' !== $source ? "/source/{$source}" : '') . ($userName ? "/username/{$userName}" : '');

        try {
            // 获取登陆日志信息ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = false;

            foreach ($ids as $id) {
                $id = intval($id);

                // 获取登陆日志信息
                $log = LoginLog::findFirst($id);

                // 判断记录信息是否存在，存在则做删除
                if ($log) {
                    // 删除记录信息
                    $result = $log->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            if ($isFailed) {
                return $this->error('登录日志信息删除失败', "/loginlog/list{$cond}");
            }

            return $this->success('登录日志信息删除成功', "/loginlog/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('登录日志信息删除失败', "/loginlog/list{$cond}");
        }
    }
}