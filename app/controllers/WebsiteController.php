<?php

use \Phalcon\Paginator\Adapter\Model;

class WebsiteController extends BaseController
{
    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.app.website.L', 'blog.boss.app.website.R', 'blog.boss.app.website.S']],
        'post' => [false, ['blog.boss.app.website.SET']],
    ];

    /**
     * 显示网站信息页面
     *
     * @return mixed
     */
    public function listAction()
    {
        try {
            // 获取导航菜单 ID
            $navId = $this->navId;

            // 传递查询条件
            $cond = "/navid/{$navId}";

            // 传递查询条件信息
            $this->view->cond = $cond;

            // 获取网站信息
            $file = file_get_contents(ROOT . '/public/blogwebsite.json');
            $file = $file ? json_decode($file, true) : '';
            $file = $file ? : [];

            // 传递网站信息
            $this->view->file = $file;

            // 是否显示设置网站信息按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.app.website.SET']);

            return $this->view->pick('website/index');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('网站信息页面加载失败', '/');
        }
    }

    /**
     * 设置网站信息
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
            // 获取提交的内容
            $posts = $this->request->getPost();

            $posts = json_encode($posts);

            // 写入网站信息文件
            $result = file_put_contents(ROOT . '/public/blogwebsite.json', $posts);

            // 判断写入是否成功
            if (!$result) {
                return $this->error('网站信息设置失败', "/website/list{$cond}");
            }

            return $this->success('网站信息设置成功', "/website/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('网站信息设置失败', "/website/list{$cond}");
        }
    }
}