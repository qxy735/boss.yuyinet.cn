<?php

class AuthController extends BaseController
{
    /**
     * 登录验证
     *
     * @var bool
     */
    protected $loginAuth = false;
    /**
     * 访问的 URL
     *
     * @var string
     */
    protected $actionUrl = '/';
    /**
     * 是否直接跳转
     *
     * @var bool
     */
    protected $isAction = true;

    /**
     * 显示登录页面
     *
     * @return mixed
     */
    public function loginAction()
    {
        // 加载登录页面
        return $this->view->pick('auth/login');
    }

    /**
     * 登录处理程序
     *
     * @return mixed
     */
    public function postLoginAction()
    {
        // 判断访问是否合法
        if (false === $this->request->isPost()) {
            // 非法访问，直接跳转至登陆页面
            return $this->response->redirect('/auth/login');
        }

        // 获取登录用户名
        $userName = $this->request->getPost('username');

        // 获取登录密码
        $passWord = $this->request->getPost('password');

        // 加密登录密码
        $passWord = md5($passWord);

        // 是否需要记住登录信息
        $remember = $this->request->getPost('remember');
        $timer = $remember ? time() + 86400 : -1;

        try {
            // 获取当前登陆用户 ID
            $ip = $this->getRemoteIp();
            $ip = $ip ? ip2long($ip) : 0;

            // 判断是否为黑名单 IP
            $blackIp = Ip::findFirst([
                'conditions' => "ip={$ip}",
                'columns' => 'expire'
            ]);

            // 黑名单IP则做处理
            if ($blackIp) {
                if (!$blackIp->expire || (time() <= $blackIp->expire)) {
                    $this->loginLog($userName, 0, "该IP禁止访问");

                    return $this->error('该IP禁止访问，请联系管理员', '/auth/login');
                }
            }

            // 获取登陆用户信息
            $user = User::findFirst([
                'conditions' => "username='{$userName}' and password='{$passWord}'",
                'columns' => 'id,username,type,status,avatar,regip,regtime,loginip,logintime'
            ]);

            // 判断登陆用户信息是否存在，不存在则给予提示并跳转至登陆页面
            if (!$user) {
                $this->loginLog($userName, 0, '帐号或密码有误');

                return $this->error('用户登陆失败,帐号或密码有误', '/auth/login');
            }

            // 判断登陆用户状态是否正常
            if ($user->status != User::NORMAL_USER_STATUS) {
                $this->loginLog($userName, 0, '非正常用户状态');

                return $this->error('非正常用户状态，请联系管理员', '/auth/login');
            }

            // 获取用户黑名单信息
            $blacks = BlackList::find([
                'conditions' => 'uid=' . $user->id,
                'columns' => 'type'
            ])->toArray();

            $blackTypes = array_map(function ($black) {
                return $black['type'];
            }, $blacks);

            unset($blacks);

            // 判断用户是否在黑名单中
            if ($blackTypes) {
                if (array_intersect($blackTypes, [BlackList::DISABLED_TYPE_ALL, BlackList::DISABLED_TYPE_ADMIN])) {
                    $this->loginLog($userName, 0, '禁止访问后台');

                    return $this->error('禁止访问后台，请联系管理员', '/auth/login');
                }
            }

            // 转为数组形式
            $user = json_decode(json_encode($user), true);

            // 判断用户类型是否合法
            $type = $user['type'];

            // 判断是否为管理员用户
            if (User::ADMIN_USER_TYPE != $type) {
                $this->loginLog($userName, 0, '非后台用户');

                return $this->error('非后台用户,禁止访问', '/auth/login');
            }

            // 设置上次登陆时间和 IP
            $user['lasttime'] = $user['logintime'];
            $user['lastip'] = $user['loginip'] ? long2ip($user['loginip']) : '';

            // 获取客户端 IP
            $remoteIps = $this->getRemoteIps();

            // 设置当前登陆时间和 IP
            $user['logintime'] = time();
            $user['loginip'] = $remoteIps[0];

            // 加密处理用户信息
            $cryptUser = $this->crypt->encrypt(json_encode($user));

            // 设置登陆用户信息
            $this->session->set('admin_user', $cryptUser);

            $this->cookies->set('admin_user', $cryptUser, $timer, '/');

            // 更新当前登陆时间和 IP
            $user = User::findFirst($user['id']);

            if ($user) {
                $user->logintime = time();
                $user->loginip = ip2long($remoteIps[0]);
                $user->save();
            }

            // 卸载空闲变量
            unset($user);

            $this->loginLog($userName, 1);

            // 跳转至后台首页
            return $this->response->redirect('/');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('抱歉!访问出错了...', '/');
        }
    }

    /**
     * 退出系统处理
     *
     * @return mixed
     */
    public function logoutAction()
    {
        try {
            // 判断是否已经退出
            if (false === $this->isLogin()) {
                return $this->response->redirect('/auth/login');
            }

            // 退出系统
            $this->session->destroy();
            $this->cookies->set('admin_user', '', -1, '/');

            // 返回到登陆页面
            return $this->response->redirect('/auth/login');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('退出系统失败', '/');
        }
    }

    /**
     * 记录登陆日志信息
     *
     * @param $userName
     * @param $status
     * @param $cause
     *
     * @return bool
     */
    public function loginLog($userName, $status, $cause = '')
    {
        try {
            // 定义需要添加的日志信息
            $inserts = [
                'username' => $userName,
                'logintime' => time(),
                'loginip' => $this->getRemoteIp() ? ip2long($this->getRemoteIp()) : 0,
                'status' => $status,
                'cause' => $cause,
                'type' => LoginLog::LOGIN_LOG_TYPE_ADMIN,
                'origin' => LoginLog::LOGIN_LOG_SOURCE_ADMIN,
                'agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            ];

            $log = new LoginLog();

            // 记录登陆日志信息
            $result = $log->save($inserts);

            // 判断记录是否成功
            if (!$result) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            return false;
        }
    }
}