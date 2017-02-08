<?php

use Phalcon\Mvc\Controller;

/**
 * 基础控制器(处理公共业务)
 *
 * Class BaseController
 */
class BaseController extends Controller
{
    /**
     * 默认数据大小
     */
    const PAGE_SIZE = 15;
    /**
     * 当前页，默认第一页
     *
     * @var int
     */
    protected $page = 1;
    /**
     * 每页数据量
     *
     * @var int
     */
    protected $pageSize = 0;
    /**
     * 导航菜单 ID
     *
     * @var int
     */
    protected $navId = 0;
    /**
     * 登录验证
     *
     * @var bool
     */
    protected $loginAuth = true;
    /**
     * 访问的 URl
     *
     * @var string
     */
    protected $actionUrl = '';
    /**
     * 是否直接访问
     *
     * @var bool
     */
    protected $isAction = false;
    /**
     * 访问权限
     *
     * @var array
     */
    protected $actionAuth = [];

    /**
     * 初始化处理
     */
    public function initialize()
    {
        $this->setDefaultParam();

        $this->setNavMenu();

        return $this->accessInvokeAction();
    }

    /**
     * 设置默认请求参数数据
     */
    protected function setDefaultParam()
    {
        // 获取当前页码
        $page = (int)$this->getParam('page');

        // 默认为第一页
        $this->page = $page ? : 1;

        // 获取每页显示的数据量
        $pageSize = (int)$this->getParam('pagesize');

        // 默认为15条
        $this->pageSize = $pageSize ? : self::PAGE_SIZE;

        // 获取导航菜单 ID
        $this->navId = (int)$this->getParam('navid');
    }

    /**
     * 设置导航菜单
     */
    protected function setNavMenu()
    {
        // 获取子菜单
        $sonMenuId = $this->navId;

        // 获取子菜单信息
        $sonMenu = Menu::findFirst([
            'conditions' => "id={$sonMenuId} and enabled=" . Menu::ENABLE_BLOG_MENU,
            'columns' => 'id,name,url,parentid',
        ]);

        $sonMenu = $sonMenu ? $sonMenu->toArray() : [];

        $topMenu = [];

        // 获取父菜单信息
        if ($sonMenu) {
            $topMenu = Menu::findFirst([
                'conditions' => "id={$sonMenu['parentid']} and enabled=" . Menu::ENABLE_BLOG_MENU,
                'columns' => 'name',
            ]);

            $topMenu = $topMenu ? $topMenu->toArray() : [];
        }

        $this->view->navTopMenu = $topMenu;
        $this->view->navSonMenu = $sonMenu;
    }

    /**
     * 验证访问是否授权
     *
     * @return mixed
     */
    public function beforeExecuteRoute()
    {
        // 访问验证
        if (false === $this->checkActionAuth()) {
            $this->unauthorized();
        }
    }

    /**
     * 显示未授权页面信息
     */
    protected function unauthorized()
    {
        include(dirname(__DIR__) . '/views/show/unauthorized.html');
        exit;
    }

    /**
     * 访问验证
     *
     * @return bool
     */
    protected function checkActionAuth()
    {
        // 获取 Action 名称
        $action = $this->dispatcher->getActionName();

        // 获取验证信息
        $actionAuths = isset($this->actionAuth[$action]) ? $this->actionAuth[$action] : [];

        // 如果验证信息为空则无需验证
        if (!$actionAuths || count($actionAuths) < 2) {
            return true;
        }

        // 获取验证方式
        $isAnd = $actionAuths[0];

        // 获取验证规则
        $rules = $actionAuths[1];

        // 获取当前登陆用户权限信息
        $userAuths = array_values($this->getAllAuths());

        // 判断当前用户权限是否为空
        if (!$userAuths) {
            return false;
        }

        // 验证用户多权限
        if ($isAnd) {
            if (array_diff($rules, $userAuths)) {
                return false;
            }

            return true;
        }

        // 验证用户单权限
        if (!array_intersect($rules, $userAuths)) {
            return false;
        }

        return true;
    }

    /**
     * 访问控制
     *
     * @return null
     */
    public function accessInvokeAction()
    {
        if ($this->isAction && $this->isLogin()) {
            if ($url = $this->actionUrl) {
                return $this->response->redirect($url);
            }

            return null;
        }

        if (!$this->loginAuth) {
            return null;
        }

        if (!$this->isLogin()) {
            return $this->response->redirect('/auth/login');
        }

        if ($url = $this->actionUrl) {
            return $this->response->redirect($url);
        }

        return null;
    }

    /**
     * 检测登录状态(后台用户是否已登录)
     *
     * @return bool
     */
    public function isLogin()
    {
        // 根据用户信息判断登录状态
        if ($this->user()) {
            return true;
        }

        return false;
    }

    /**
     * 获取当前登陆用户信息
     *
     * @return mixed|null
     */
    public function user()
    {
        try {
            // 初始用户登陆信息
            $user = '';

            // 从 session 中获取登录用户信息
            if ($this->session->has('admin_user')) {
                $user = $this->session->get('admin_user');
            }

            // 从 cookie 中获取登录用户信息
            if ($this->cookies->has('admin_user') && empty($user)) {
                $user = $this->cookies->get('admin_user')->getValue();
            }

            // 获取用户登录信息
            $cookUser = $user ? json_decode($this->crypt->decrypt($user), true) : null;

            if (!$cookUser) {
                return null;
            }

            // 获取用户 ID
            $userId = isset($cookUser['id']) ? $cookUser['id'] : 0;

            if (!$userId) {
                return null;
            }

            // 获取用户信息
            $user = User::findFirst([
                'conditions' => "id={$userId} and status=" . User::NORMAL_USER_STATUS,
                'columns' => 'id,username,type,status,avatar,regip,regtime,loginip,logintime'
            ]);

            if (!$user) {
                return null;
            }

            $user = $user->toArray();

            $user['lasttime'] = $cookUser['lasttime'];
            $user['lastip'] = $cookUser['lastip'];

            unset($cookUser);

            return $user;
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            return null;
        }
    }

    /**
     * 获取用户头像信息
     *
     * @return mixed
     */
    public function getUserAvatar()
    {
        // 获取用户信息
        $user = $this->user();

        // 处理头像信息
        if ($user['avatar']) {
            $fileName = trim(strrchr($user['avatar'], '/'), '/');
            $avatars['avatar'] = $user['avatar'];
            $avatars['mavatar'] = str_replace($fileName, "middle_{$fileName}", $user['avatar']);
            $avatars['savatar'] = str_replace($fileName, "small_{$fileName}", $user['avatar']);
        } else {
            $avatars['avatar'] = $avatars['mavatar'] = $avatars['savatar'] = '';
        }

        return $avatars;
    }

    /**
     * 获取用户名
     *
     * @return string
     */
    public function getUserName()
    {
        $user = $this->user();

        return $user ? $user['username'] : 'sys';
    }

    /**
     * 获取用户 ID
     *
     * @return int
     */
    public function getUserId()
    {
        $user = $this->user();

        return $user ? intval($user['id']) : 0;
    }

    /**
     * 错误提示处理
     *
     * @param string $message
     * @param string $url
     * @param int    $timer
     *
     * @return mixed
     */
    public function error($message = '', $url = '', $timer = 3)
    {
        // 错误提示信息
        $this->view->msg = $message;

        // 跳转地址
        $this->view->url = $url;

        // 错误信息显示时长，以秒为单位
        $this->view->timer = $timer;

        // 显示错误提示页面
        return $this->view->pick('show/error');
    }

    /**
     * 操作成功处理
     *
     * @param string $message
     * @param string $url
     * @param int    $timer
     *
     * @return mixed
     */
    public function success($message = '', $url = '', $timer = 3)
    {
        // 错误提示信息
        $this->view->msg = $message;

        // 跳转地址
        $this->view->url = $url;

        // 错误信息显示时长，以秒为单位
        $this->view->timer = $timer;

        // 显示错误提示页面
        return $this->view->pick('show/success');
    }

    /**
     * 获取请求参数值
     *
     * @param $name
     *
     * @return null
     */
    public function getParam($name)
    {
        $value = $this->request->get($name);

        if (null !== $value) {
            return $value;
        }

        $inputs = $this->dispatcher->getParams();

        $index = array_search($name, $inputs);

        if (false === $index) {
            return null;
        }

        return isset($inputs[$index + 1]) ? $inputs[$index + 1] : null;
    }

    /**
     * 获取客户端多个 IP
     *
     * @return array
     */
    public function getRemoteIps()
    {
        // 获取客户端 IP
        $remoteIps = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        $remoteIps = explode(',', $remoteIps);

        return $remoteIps;
    }

    /**
     * 获取客户端真实 IP
     *
     * @return string
     */
    public function getRemoteIp()
    {
        $ips = $this->getRemoteIps();

        return $ips ? $ips[0] : '';
    }

    /**
     * 获取用户角色信息
     *
     * @param int $uid
     *
     * @return mixed
     */
    public function getUserRoles($uid = 0)
    {
        try {
            // 获取用户 ID
            $uid = $uid ? : $this->getUserId();

            // 获取用户关联的角色信息
            $userRoles = UserRole::find([
                'conditions' => "uid={$uid}",
                'columns' => 'roleid',
            ])->toArray();

            // 获取用户关联的角色 ID
            $roleIds = array_map(function ($userRole) {
                return intval($userRole['roleid']);
            }, $userRoles);

            unset($userRoles);

            if (!$roleIds) {
                return [];
            }

            // 获取角色信息
            $roles = Role::find([
                'conditions' => 'id in(' . implode(',', $roleIds) . ') and enabled=' . Role::ENABLE_BLOG_ROLE,
                'columns' => 'id,name,code,type',
            ])->toArray();

            return $roles;
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            return [];
        }
    }

    /**
     * 获取用户角色 ID
     *
     * @return array
     */
    public function getUserRoleIds()
    {
        return array_map(function ($role) {
            return intval($role['id']);
        }, $this->getUserRoles());
    }

    /**
     * 获取用户所有权限
     *
     * @return array
     */
    public function getAllAuths()
    {
        return array_unique(array_merge($this->getUserAuths(), $this->getRoleAuths()));
    }

    /**
     * 获取用户权限
     *
     * @return array
     */
    public function getUserAuths()
    {
        try {
            // 获取用户 ID
            $uid = $this->getUserId();

            $items = $userItems = $auths = [];

            // 获取用户关联的权限
            $userAuthItems = UserAuthitem::find([
                'conditions' => "uid={$uid}",
                'columns' => 'itemid,auth',
            ])->toArray();

            // 获取权限项 ID
            $itemIds = array_map(function ($item) {
                return (int)$item['itemid'];
            }, $userAuthItems);

            // 获取权限信息
            if ($itemIds) {
                $items = Authitem::find([
                    'conditions' => 'id in(' . implode(',', $itemIds) . ') and enabled=' . Authitem::ENABLE_BLOG_AUTHITEM,
                    'columns' => 'id,code',
                ])->toArray();
            }

            foreach ($items as $item) {
                $userItems[$item['id']] = $item['code'];
            }

            // 卸载空闲变量
            unset($items);

            // 获取用户的权限
            $userAuthItems = array_map(function ($userAuthItem) use ($userItems) {
                $tmpAuths = [];

                if (!isset($userItems[$userAuthItem['itemid']]) || !$userItems[$userAuthItem['itemid']]) {
                    return [];
                }

                // 获取权限code
                $code = $userItems[$userAuthItem['itemid']];

                foreach (explode(',', $userAuthItem['auth']) as $auth) {
                    $tmpAuths[] = "{$code}.{$auth}";
                }

                return $tmpAuths;
            }, $userAuthItems);

            unset($userItems);

            foreach ($userAuthItems as $userAuthItem) {
                $auths = array_merge($auths, $userAuthItem);
            }

            return $auths;
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            return [];
        }
    }

    /**
     * 获取用户角色关联的权限
     *
     * @return array
     */
    public function getRoleAuths()
    {
        try {
            // 获取用户角色 ID
            $roleIds = $this->getUserRoleIds();

            $roleItems = $items = $userItems = $auths = [];

            // 根据角色 ID 获取对应的权限项
            if ($roleIds) {
                $roleItems = RoleAuthitem::find([
                    'conditions' => 'roleid in(' . implode(',', $roleIds) . ')',
                    'columns' => 'itemid,auth',
                ])->toArray();
            }

            // 获取权限项 ID
            $itemIds = array_map(function ($item) {
                return (int)$item['itemid'];
            }, $roleItems);

            // 获取权限信息
            if ($itemIds) {
                $items = Authitem::find([
                    'conditions' => 'id in(' . implode(',', $itemIds) . ') and enabled=' . Authitem::ENABLE_BLOG_AUTHITEM,
                    'columns' => 'id,code',
                ])->toArray();
            }

            foreach ($items as $item) {
                $userItems[$item['id']] = $item['code'];
            }

            // 卸载空闲变量
            unset($items);

            // 获取用户的角色权限
            $roleItems = array_map(function ($roleItem) use ($userItems) {
                $tmpAuths = [];

                if (!isset($userItems[$roleItem['itemid']]) || !$userItems[$roleItem['itemid']]) {
                    return [];
                }

                // 获取权限code
                $code = $userItems[$roleItem['itemid']];

                foreach (explode(',', $roleItem['auth']) as $auth) {
                    $tmpAuths[] = "{$code}.{$auth}";
                }

                return $tmpAuths;
            }, $roleItems);

            unset($userItems);

            foreach ($roleItems as $roleItem) {
                $auths = array_merge($auths, $roleItem);
            }

            unset($roleItems);

            return $auths;
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            return [];
        }
    }

    /**
     * 验证是否授权
     *
     * @param      $auth
     * @param bool $isAnd
     *
     * @return bool
     */
    public function hasAuth($auth, $isAnd = false)
    {
        try {
            if (!is_array($auth)) {
                $auth = [$auth];
            }

            if ($auths = array_intersect($auth, $this->getAllAuths())) {
                if ($isAnd) {
                    if (array_diff($auth, $auths)) {
                        return false;
                    }
                }

                return true;
            }

            return false;
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            return false;
        }
    }
}