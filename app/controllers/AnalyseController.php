<?php

use \Phalcon\Paginator\Adapter\Model;

class AnalyseController extends BaseController
{
    /**
     * 用户类型分析
     */
    const USER_TYPE_ANALYSE = 0;
    /**
     * 用户状态分析
     */
    const USER_STATUS_ANALYSE = 1;
    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.siteuser.analyse.L', 'blog.boss.siteuser.analyse.R', 'blog.boss.siteuser.analyse.S']],
    ];

    /**
     * 用户分析
     *
     * @return mixed
     */
    public function listAction()
    {
        try {
            // 获取分析项
            $option = (int)$this->getParam('option');

            $this->view->option = $option;

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            $cond = "/navid/{$navId}/option/{$option}";

            // 传递查询条件组合
            $this->view->cond = $cond;

            // 传递分析选项信息
            $this->view->options = [
                '按用户类型分析',
                '按用户状态分析',
            ];

            $analyses = $tips = $colors = $labels = '';

            $title = '无分析结果...';

            $colors = "['#EC0033','#A0D300','#FFCD00','#00B869','#999999','#FF7300','#004CB0']";

            // 获取分析信息
            if (self::USER_TYPE_ANALYSE == $option) {
                $users = User::find([
                    'conditions' => '1=1 group by type order by type asc',
                    'columns' => 'count(id) as total, type'
                ])->toArray();

                if ($users) {
                    $total = array_sum(array_map(function ($user) {
                        return $user['total'];
                    }, $users));

                    $colors = $tips = $labels = $analyses .= '[';
                    foreach ($users as $user) {
                        $avg = $total ? round($user['total'] / $total, 2) * 100 : 0;
                        $analyses .= "{$avg},";
                        if (User::FRONT_USER_TYPE == $user['type']) {
                            $labels .= "'前台用户',";
                            $tips .= "'前台用户占比',";
                            $colors .= "'#00ff00',";
                        } elseif (User::ADMIN_USER_TYPE == $user['type']) {
                            $labels .= "'后台用户',";
                            $tips .= "'后台用户占比',";
                            $colors .= "'#ff0000',";
                        } else {
                            $labels .= "'类型{$user['type']}用户',";
                            $tips .= "'类型{$user['type']}用户占比',";
                            $colors .= "'#000000',";
                        }
                    }
                    $analyses = trim($analyses, ',');
                    $labels = trim($labels, ',');
                    $tips = trim($tips, ',');
                    $colors = trim($colors, ',');
                    $analyses .= ']';
                    $labels .= ']';
                    $tips .= ']';
                    $colors .= ']';

                    unset($users);
                }

                $title = '用户类型占比分析图';
            } elseif (self::USER_STATUS_ANALYSE == $option) {
                $users = User::find([
                    'conditions' => '1=1 group by status order by status asc',
                    'columns' => 'count(id) as total, status'
                ])->toArray();

                if ($users) {
                    $total = array_sum(array_map(function ($user) {
                        return $user['total'];
                    }, $users));

                    $colors = $labels = $tips = $analyses .= '[';
                    foreach ($users as $user) {
                        $avg = $total ? round($user['total'] / $total, 2) * 100 : 0;
                        $analyses .= "{$avg},";

                        switch ($user['status']) {
                            case User::NORMAL_USER_STATUS :
                                $labels .= "'正常用户',";
                                $tips .= "'正常用户占比',";
                                $colors .= "'#00ff00',";
                                break;
                            case User::WAITE_USER_STATUS :
                                $labels .= "'待审核用户',";
                                $tips .= "'待审核用户占比',";
                                $colors .= "'#0000ff',";
                                break;
                            case User::DELETE_USER_STATUS :
                                $labels .= "'已删除用户',";
                                $tips .= "'已删除用户占比',";
                                $colors .= "'#000000',";
                                break;
                            case User::DISABLED_USER_STATUS :
                                $labels .= "'已禁用用户',";
                                $tips .= "'已禁用用户占比',";
                                $colors .= "'#ff0000',";
                                break;
                            default :
                                $labels .= "'状态{$user['status']}用户',";
                                $tips .= "'状态{$user['status']}用户占比',";
                                $colors .= "'#000000',";
                        }
                    }
                    $analyses = trim($analyses, ',');
                    $labels = trim($labels, ',');
                    $tips = trim($tips, ',');
                    $colors = trim($colors, ',');
                    $analyses .= ']';
                    $labels .= ']';
                    $tips .= ']';
                    $colors .= ']';

                    unset($users);
                }

                $title = '用户状态占比分析图';
            } else {
                $analyses = $tips = $labels = '[]';
            }

            $this->view->datas = $analyses;

            $this->view->lables = $labels;
            $this->view->tips = $tips;
            $this->view->title = $title;
            $this->view->colors = $colors;

            return $this->view->pick('analyse/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户分析页面加载失败', '/');
        }
    }
}