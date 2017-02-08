<?php

class IndexController extends BaseController
{
    /**
     * 后台管理首页
     */
    public function indexAction()
    {
        try {
            // 加载后台首页
            return $this->view->pick('index/index');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            echo '后台管理页面无法加载...';
        }
    }

    /**
     * 后台首页顶部页面
     *
     * @return mixed
     */
    public function topAction()
    {
        try {
            // 获取当前登录用户 ID
            $userId = $this->getUserId();

            // 获取未读收信信息
            $messages = MessageDetail::find([
                'conditions' => "postid={$userId} and isread=" . MessageDetail::MSG_READ_NO,
                'columns' => 'id'
            ])->count();

            // 传递未阅读数
            $this->view->messages = $messages;

            unset($messages);

            // 传递登录用户名
            $this->view->userName = $this->getUserName();

            return $this->view->pick('index/top');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            echo '顶部页面无法加载...';
        }
    }

    /**
     * 后台首页左侧页面
     *
     * @return mixed
     */
    public function leftAction()
    {
        try {
            // 获取后台顶级菜单
            $topMenus = Menu::find([
                'conditions' => 'parentid=' . Menu::TOP_TYPE_MENU . ' and type=' . Menu::ADMIN_BLOG_TYPE . ' and enabled=' . Menu::ENABLE_BLOG_MENU . ' order by displayorder asc,id desc',
                'columns' => 'id,name,auth',
            ])->toArray();

            // 转化权限项
            $topMenus = array_map(function ($topMenu) {
                $topMenu['auth'] = json_decode($topMenu['auth'], true);

                return $topMenu;
            }, $topMenus);

            // 获取登陆用户权限项
            $auths = $this->getAllAuths();

            // 验证菜单是否需要显示
            $topMenus = array_filter($topMenus, function ($topMenu) use ($auths) {
                foreach ($topMenu['auth'] as $code => $auth) {
                    if (in_array("{$code}.{$auth}", $auths)) {
                        return true;
                    }
                }

                return false;
            });

            // 获取顶级菜单 ID
            $topMenuIds = array_map(function ($topMenu) {
                return (int)$topMenu['id'];
            }, $topMenus);

            $sonMenus = [];

            if ($topMenuIds) {
                // 获取子级菜单
                $sonMenus = Menu::find([
                    'conditions' => 'parentid in(' . implode(',', $topMenuIds) . ') and type=' . Menu::ADMIN_BLOG_TYPE . ' and enabled=' . Menu::ENABLE_BLOG_MENU . ' order by displayorder desc,id desc',
                    'columns' => 'id,parentid,url,name,auth',
                ])->toArray();
            }

            unset($topMenuIds);

            // 转化权限项
            $sonMenus = array_map(function ($sonMenu) {
                $sonMenu['auth'] = json_decode($sonMenu['auth'], true);

                return $sonMenu;
            }, $sonMenus);

            // 验证子菜单是否需要显示
            $sonMenus = array_filter($sonMenus, function ($sonMenu) use ($auths) {
                foreach ($sonMenu['auth'] as $code => $auth) {
                    if (in_array("{$code}.{$auth}", $auths)) {
                        return true;
                    }
                }

                return false;
            });

            // 组合菜单信息
            $topMenus = array_map(function ($topMenu) use ($sonMenus) {
                $sons = [];

                foreach ($sonMenus as $sonMenu) {
                    if ($sonMenu['parentid'] == $topMenu['id']) {
                        $sons[] = $sonMenu;
                    }
                }

                $topMenu['son'] = $sons;

                return $topMenu;
            }, $topMenus);

            unset($sonMenus);

            // 传递菜单信息
            $this->view->topMenus = $topMenus;

            return $this->view->pick('index/left');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            echo '左侧页面无法加载...';
        }
    }

    /**
     * 后台首页主页面
     *
     * @return mixed
     */
    public function mainAction()
    {
        try {
            // 获取当前登录用户信息
            $user = $this->user();

            // 传递用户名
            $this->view->userName = $user['username'];

            // 获取当前时间
            $hour = date('H');

            // 定义时间问候语
            if ($hour > 1 && $hour <= 5) {
                $greet = '凌晨';
            } elseif ($hour > 5 && $hour <= 9) {
                $greet = '早晨';
            } elseif ($hour > 9 && $hour <= 11) {
                $greet = '上午';
            } elseif ($hour > 11 && $hour <= 13) {
                $greet = '中午';
            } elseif ($hour > 13 && $hour <= 15) {
                $greet = '下午';
            } elseif ($hour > 15 && $hour <= 17) {
                $greet = '傍晚';
            } elseif ($hour > 17 && $hour <= 19) {
                $greet = '黄昏';
            } elseif ($hour > 19 && $hour <= 21) {
                $greet = '晚上';
            } elseif ($hour > 21 && $hour <= 23) {
                $greet = '深夜';
            } else {
                $greet = '子夜';
            }

            // 传递时间问候语
            $this->view->greet = $greet;

            // 传递最后登录时间
            $this->view->lastTime = $user['lasttime'] ? date('Y-m-d H:i:s', $user['lasttime']) : '第一次登录';

            // 传递最后登录 IP
            $this->view->lastIp = $user['lastip'];

            // 设置服务器信息
            $servers['系统类型'] = php_uname('s');
            $servers['系统版本号'] = php_uname('r');
            $servers['PHP运行方式'] = php_sapi_name();
            $servers['PHP版本'] = PHP_VERSION;
            $servers['Zend版本'] = Zend_Version();
            $servers['PHP安装路径'] = DEFAULT_INCLUDE_PATH;
            $servers['服务器IP'] = GetHostByName($_SERVER['SERVER_NAME']);
            $servers['服务器语言'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $servers['服务器Web端口'] = $_SERVER['SERVER_PORT'];

            // 传递服务器信息
            $this->view->servers = $servers;

            // 获取后台用户数
            $adminTotals = User::find('type=' . User::ADMIN_USER_TYPE)->count();

            // 获取前台用户数
            $frontTotals = User::find('type=' . User::FRONT_USER_TYPE)->count();

            // 传递统计信息
            $this->view->totals = [
                '后台用户数' => $adminTotals,
                '前台用户数' => $frontTotals,
            ];

            return $this->view->pick('index/main');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            echo '内容页面无法加载...';
        }
    }
}