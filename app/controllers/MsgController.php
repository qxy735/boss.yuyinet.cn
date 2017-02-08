<?php

use \Phalcon\Paginator\Adapter\Model;

class MsgController extends BaseController
{
    /**
     * 留言状态
     *
     * @var array
     */
    protected $status = [
        '未阅读',
        '已阅读',
        '已回复',
        '已查看',
    ];
    /**
     * 是否已回复
     *
     * @var array
     */
    protected $replys = [
        '否',
        '是',
    ];
    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.siteuser.msg.L', 'blog.boss.siteuser.msg.R', 'blog.boss.siteuser.msg.S']],
        'read' => [false, ['blog.boss.siteuser.msg.L', 'blog.boss.siteuser.msg.R', 'blog.boss.siteuser.msg.S']],
        'reply' => [false, ['blog.boss.siteuser.msg.SEND']],
    ];

    /**
     * 显示用户留言页面
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

            // 获取留言人用户名
            $userName = $this->getParam('username');
            $userName = urldecode($userName);
            $userNameSql = $userName ? " and username like '%{$userName}%'" : '';

            // 获取是否已回复
            $isreply = $this->getParam('isreply');
            $isreply = (null === $isreply) ? -1 : intval($isreply);
            $isreplySql = (-1 === $isreply) ? '' : " and isreply={$isreply}";

            // 获取留言状态
            $status = $this->getParam('status');
            $status = (null === $status) ? -1 : intval($status);
            $statusSql = (-1 === $status) ? '' : " and status={$status}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取用户留言信息
            $msgs = Msg::find([
                'conditions' => "1=1 {$userNameSql}{$statusSql}{$isreplySql} " . 'order by status asc,id asc',
                'columns' => 'id,username,replytime,replyname,isreply,status,createtime'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $msgs,
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

            $msgs = json_decode(json_encode($pageDatas->items), true);

            // 数值转换
            $msgs = array_map(function ($msg) {
                $msg['createtime'] = $msg['createtime'] ? date('Y-m-d H:i:s', $msg['createtime']) : '';
                $msg['replytime'] = $msg['replytime'] ? date('Y-m-d H:i:s', $msg['replytime']) : '';
                $msg['isreply'] = $this->replys[$msg['isreply']];
                $msg['status'] = $this->status[$msg['status']];

                return $msg;
            }, $msgs);

            // 卸载空闲变量
            unset($pageDatas);

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($userName ? "/username/{$userName}" : '') . ('' !== $isreply ? "/isreply/{$isreply}" : '') . ('' !== $status ? "/status/{$status}" : '');

            // 传递查询条件
            $this->view->cond = $cond;

            // 传递查询参数
            $this->view->statu = $status;
            $this->view->userName = $userName;
            $this->view->isReply = $isreply;

            // 传递留言状态信息
            $this->view->status = $this->status;

            // 传递回复状态信息
            $this->view->replys = $this->replys;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 传递分页信息
            $this->view->page = $page;

            // 传递留言信息
            $this->view->msgs = $msgs;

            return $this->view->pick('msg/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户留言页面加载失败', '/');
        }
    }

    /**
     * 显示留言信息页面
     *
     * @return mixed
     */
    public function readAction()
    {
        // 获取留言人
        $userName = urldecode($this->getParam('username'));

        // 获取回复状态
        $isReply = (int)$this->getParam('isreply');

        // 获取留言状态
        $status = (int)$this->getParam('status');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '') . ('' !== $isReply ? "/isreply/{$isReply}" : '');

        try {
            // 获取留言 ID
            $id = (int)$this->getParam('id');

            // 根据 ID 获取对应的留言信息
            $msg = Msg::findFirst($id);

            // 判断留言信息是否存在
            if (!$msg) {
                return $this->error('用户留言信息不存在', "/msg/list{$cond}");
            }

            if (Msg::MSG_NOT_READ_STATUS == $msg->status) {
                // 更新阅读状态
                $result = $msg->update([
                    'status' => Msg::MSG_READ_NOt_REPLY_STATUS,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName()
                ]);

                // 判断更新是否成功
                if (!$result) {
                    return $this->error('用户留言信息加载失败', "/msg/list{$cond}");
                }
            }

            $msg = $msg->toArray();

            // 传递留言信息
            $this->view->msg = $msg;

            // 传递查询条件信息
            $this->view->cond = $cond;

            // 回复按钮是否可用
            $this->view->isSendBut = $this->hasAuth(['blog.boss.siteuser.msg.SEND']) && (Msg::MSG_REPLY_READ_STATUS != $msg['status']);

            return $this->view->pick('msg/read');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户留言信息加载失败', "/msg/list{$cond}");
        }
    }

    /**
     * 用户留言回复
     *
     * @return mixed
     */
    public function replyAction()
    {
        // 获取留言人
        $userName = urldecode($this->getParam('username'));

        // 获取回复状态
        $isReply = (int)$this->getParam('isreply');

        // 获取留言状态
        $status = (int)$this->getParam('status');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '') . ('' !== $isReply ? "/isreply/{$isReply}" : '');

        // 获取留言 ID
        $id = (int)$this->getParam('id');

        try {
            // 获取用户留言信息
            $msg = Msg::findFirst($id);

            // 判断用户留言信息是否存在
            if (!$msg) {
                return $this->error('用户留言信息不存在', "/msg/list{$cond}");
            }

            // 获取需要更新的留言信息
            $posts = $this->request->getPost();

            unset($posts['id']);

            // 判断回复内容是否为空
            if (!isset($posts['reply']) || !$posts['reply']) {
                return $this->error('回复内容不能为空', "/msg/read/id/{$id}{$cond}");
            }

            $posts['replyid'] = $this->getUserId();
            $posts['replyname'] = $this->getUserName();
            $posts['replytime'] = time();
            $posts['status'] = Msg::MSG_REPLY_STATUS;
            $posts['isreply'] = Msg::REPLY_STATUS_YES;
            $posts['lastoperate'] = time();
            $posts['lastoperator'] = $this->getUserName();

            // 更新留言信息
            $result = $msg->update($posts);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('用户留言回复失败', "/msg/read/id/{$id}{$cond}");
            }

            return $this->success('用户留言回复成功', "/msg/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户留言回复失败', "/msg/read/id/{$id}{$cond}");
        }
    }
}