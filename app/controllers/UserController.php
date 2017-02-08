<?php

use \Phalcon\Paginator\Adapter\Model;

class UserController extends BaseController
{
    /**
     * 学历信息
     *
     * @var array
     */
    protected $edus = [
        '无',
        '幼儿园',
        '小学',
        '初中',
        '职高',
        '高中',
        '大专',
        '本科',
        '研究生',
        '硕士',
        '博士',
        '博士后',
        '科学家',
        '海归',
    ];
    /**
     * 性别信息
     *
     * @var array
     */
    protected $sexs = [
        '无',
        '男',
        '女',
        '中性',
    ];
    /**
     * 星座信息
     *
     * @var array
     */
    protected $zodiacs = [
        '无',
        '白羊座',
        '金牛座',
        '双子座',
        '巨蟹座',
        '狮子座',
        '处女座',
        '天秤座',
        '天蝎座',
        '射手座',
        '摩羯座',
        '水瓶座',
        '双鱼座',
    ];
    /**
     * 用户状态信息
     *
     * @var array
     */
    protected $status = [
        '正常',
        '待审核',
        '已删除',
        '已禁用',
    ];
    /**
     * 用户类型信息
     *
     * @var array
     */
    protected $types = [
        '前台用户',
        '后台用户',
    ];

    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'list' => [false, ['blog.boss.siteuser.user.L', 'blog.boss.siteuser.user.R', 'blog.boss.siteuser.user.S']],
        'delete' => [false, ['blog.boss.siteuser.user.D', 'blog.boss.siteuser.user.BD']],
        'setrole' => [false, ['blog.boss.siteuser.user.SET']],
        'setstatus' => [false, ['blog.boss.siteuser.user.SET']],
        'password' => [false, ['blog.boss.siteuser.user.PWD']],
        'setpassword' => [false, ['blog.boss.siteuser.user.PWD']],
        'profile' => [false, ['blog.boss.siteuser.user.L', 'blog.boss.siteuser.user.R', 'blog.boss.siteuser.user.S']],
        'auth' => [false, ['blog.boss.siteuser.user.L', 'blog.boss.siteuser.user.R', 'blog.boss.siteuser.user.S']],
        'sauth' => [false, ['blog.boss.siteuser.user.AUTH']],
        'dauth' => [false, ['blog.boss.siteuser.user.AUTH']],
        'msg' => [false, ['blog.boss.siteuser.user.SEND']],
        'sendmsg' => [false, ['blog.boss.siteuser.user.SEND']],
    ];

    /**
     * 显示用户列表信息
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
            $userNameSql = $userName ? " and username like '%{$userName}%'" : '';

            // 获取用户状态
            $status = $this->getParam('status');
            $status = (null === $status) ? -1 : intval($status);
            $statusSql = (-1 === $status) ? '' : " and status={$status}";

            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取前台用户信息
            $users = User::find([
                'conditions' => 'type=' . User::FRONT_USER_TYPE . " {$userNameSql}{$statusSql} " . 'order by id desc',
                'columns' => 'id,username,type,status,avatar,regip,regtime,logintime,loginip'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $users,
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

            $users = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 获取启用的角色信息
            $roles = Role::find([
                'conditions' => 'enabled=' . Role::ENABLE_BLOG_ROLE . ' and type=' . Role::ROLE_TYPE_FRONT . ' order by type desc,displayorder desc',
                'columns' => 'id,name,code'
            ])->toArray();

            // 传递角色信息
            $this->view->roles = $roles;

            // 获取用户 ID
            $userIds = array_map(function ($user) {
                return $user['id'];
            }, $users);

            $userRoles = [];

            // 获取用户角色信息
            if ($userIds) {
                $userRoles = UserRole::find([
                    'conditions' => 'uid in(' . implode(',', $userIds) . ')',
                    'columns' => 'uid,roleid'
                ])->toArray();
            }

            // 数值转换
            $users = array_map(function ($user) use ($userRoles, $roles) {
                $user['typename'] = $this->types[$user['type']];

                $user['statusid'] = $user['status'];
                $user['status'] = $this->status[$user['status']];

                $user['regip'] = $user['regip'] ? long2ip($user['regip']) : '';

                $user['loginip'] = $user['loginip'] ? long2ip($user['loginip']) : '';

                $user['regtime'] = $user['regtime'] ? date('Y-m-d H:i:s', $user['regtime']) : '';

                $user['logintime'] = $user['logintime'] ? date('Y-m-d H:i:s', $user['logintime']) : '';

                if ($user['avatar']) {
                    $fileName = trim(strrchr($user['avatar'], '/'), '/');
                    $user['mavatar'] = str_replace($fileName, "middle_{$fileName}", $user['avatar']);
                    $user['savatar'] = str_replace($fileName, "small_{$fileName}", $user['avatar']);
                } else {
                    $user['mavatar'] = $user['savatar'] = '';
                }

                $roleIds = $canRoles = [];

                foreach ($userRoles as $userRole) {
                    if ($userRole['uid'] == $user['id']) {
                        $roleIds[] = $userRole['roleid'];
                    }
                }

                foreach ($roleIds as $roleId) {
                    foreach ($roles as $role) {
                        if ($roleId == $role['id']) {
                            $canRoles[$roleId] = $role['name'];
                        }
                    }
                }

                $user['roleid'] = $roleIds;
                $user['role'] = $canRoles;

                return $user;
            }, $users);

            // 卸载空闲变量
            unset($userRoles);

            // 传递用户信息
            $this->view->users = $users;

            // 传递分页信息
            $this->view->page = $page;

            // 传递用户状态信息
            $this->view->status = $this->status;

            // 传递查询参数
            $this->view->statu = $status;
            $this->view->userName = $userName;

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '');

            // 传递查询条件组合
            $this->view->cond = $cond;

            // 传递导航菜单 ID
            $this->view->navId = $navId;

            // 传递删除状态
            $this->view->delStatus = User::DELETE_USER_STATUS;

            // 传递正常状态
            $this->view->normalStatus = User::NORMAL_USER_STATUS;

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.siteuser.user.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.siteuser.user.BD']);

            // 是否显示设置相关按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.siteuser.user.SET']);

            // 是否显示发消息按钮
            $this->view->isSendBut = $this->hasAuth(['blog.boss.siteuser.user.SEND']);

            // 是否显示修改密码按钮
            $this->view->isPasswdBut = $this->hasAuth(['blog.boss.siteuser.user.PWD']);

            return $this->view->pick('user/list');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户列表页面加载失败', '/');
        }
    }

    /**
     * 删除前台用户信息(设置状态为已删除)
     *
     * @return mixed
     */
    public function deleteAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取子权限是否启用状态
        $status = (int)$this->getParam('status');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取后台用户 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = false;

            foreach ($ids as $id) {
                // 根据 ID 获取对应的前台用户信息
                $user = User::findFirst(intval($id));

                // 判断前台用户信息是否存在,存在则做删除
                if ($user) {
                    // 设置用户状态为已删除
                    $result = $user->update([
                        'status' => User::DELETE_USER_STATUS,
                    ]);

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('用户删除失败', "/user/list{$cond}");
            }

            return $this->success('用户删除成功', "/user/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户删除失败', "/user/list{$cond}");
        }
    }

    /**
     * 设置用户角色信息
     *
     * @return mixed
     */
    public function setRoleAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取用户状态
        $status = (int)$this->getParam('status');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取用户提交的数据
            $posts = $this->request->getPost();

            // 判断提交的数据是否存在
            if (!$posts) {
                return $this->error('请选择角色后再提交', "/user/list{$cond}");
            }

            // 获取用户 ID
            $uid = isset($posts['uid']) ? intval($posts['uid']) : 0;

            // 获取用户信息
            $user = User::findFirst($uid);

            // 判断用户是否合法
            if (!$user) {
                return $this->error('用户不存在', "/user/list{$cond}");
            }

            // 清除原有用户关联角色信息
            $result = UserRole::find("uid={$uid}")->delete();

            // 判断清除是否成功
            if (!$result) {
                return $this->error('设置用户角色失败', "/user/list{$cond}");
            }

            // 获取角色
            $roles = $posts['role'];

            // 如果角色不存在，则不做操作
            if (!$roles) {
                return $this->response->redirect("/user/list{$cond}");
            }

            // 定义需要添加的用户关联角色信息
            $inserts = [
                'uid' => $uid,
                'creator' => $this->getUserName(),
            ];

            $isFailed = false;

            // 添加用户关联角色信息
            foreach ($roles as $role) {
                // 获取用户关联角色 model 对象
                $userRole = new UserRole();

                $role = explode('-', $role);

                $inserts['roleid'] = $role[0];
                $inserts['rolecode'] = count($role) > 1 ? $role[1] : '';
                $inserts['createtime'] = time();

                // 添加用户角色
                $result = $userRole->create($inserts);

                unset($userRole);

                // 判断添加是否成功
                if (!$result) {
                    $isFailed = true;
                }
            }

            // 判断添加是否成功
            if ($isFailed) {
                return $this->error('设置用户角色失败', "/user/list{$cond}");
            }

            return $this->success('用户角色设置成功', "/user/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            if (BaseModel::isSQLStateDuplicateEntry($e)) {
                return $this->error('用户角色信息重复', "/user/list{$cond}");
            } else {
                return $this->error('设置用户角色失败', "/user/list{$cond}");
            }
        }
    }

    /**
     * 设置用户状态
     *
     * @return mixed
     */
    public function setStatusAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取用户状态
        $status = (int)$this->getParam('status');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取用户提交的数据
            $posts = $this->request->getPost();

            // 判断提交的数据是否存在
            if (!$posts || !isset($posts['ustatus'])) {
                return $this->error('请选择状态后再提交', "/user/list{$cond}");
            }

            // 获取用户 ID
            $uid = isset($posts['uid']) ? intval($posts['uid']) : 0;

            // 获取用户信息
            $user = User::findFirst($uid);

            // 判断用户是否合法
            if (!$user) {
                return $this->error('用户不存在', "/user/list{$cond}");
            }

            // 更新用户状态
            $result = $user->update([
                'status' => $posts['ustatus'],
                'lastoperate' => time(),
                'lastoperator' => $this->getUserName(),
            ]);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('设置用户状态失败', "/user/list{$cond}");
            }

            return $this->success('用户状态设置成功', "/user/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('设置用户状态失败', "/user/list{$cond}");
        }
    }

    /**
     * 加载获取用户密码页面
     *
     * @return mixed
     */
    public function passwordAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取用户状态
        $status = (int)$this->getParam('status');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取用户 ID
            $id = (int)$this->getParam('id');

            // 获取用户信息
            $user = User::findFirst($id);

            // 判断用户信息是否存在
            if (!$user) {
                return $this->error('用户不存在', "/user/list{$cond}");
            }

            $user = $user->toArray();

            // 传递用户信息
            $this->view->user = $user;

            // 传递查询条件
            $this->view->cond = $cond;

            return $this->view->pick('user/password');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('修改密码页面加载失败', "/user/list{$cond}");
        }
    }

    /**
     * 修改用户密码
     *
     * @return mixed
     */
    public function setPasswordAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取用户状态
        $status = (int)$this->getParam('status');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取用户提交的数据
            $posts = $this->request->getPost();

            // 判断提交的数据是否存在
            if (!$posts || !isset($posts['password']) || !isset($posts['repassword'])) {
                return $this->error('请填写密码后再提交', "/user/list{$cond}");
            }

            // 验证密码是否为空
            if (!$posts['password'] || !$posts['repassword']) {
                return $this->error('请填写密码后再提交', "/user/list{$cond}");
            }

            // 验证密码的一致性
            if ($posts['password'] != $posts['repassword']) {
                return $this->error('两次密码不一致', "/user/list{$cond}");
            }

            // 加密密码
            $password = md5($posts['password']);

            // 获取用户 ID
            $id = isset($posts['id']) ? intval($posts['id']) : 0;

            // 获取用户信息
            $user = User::findFirst($id);

            // 判断用户是否合法
            if (!$user) {
                return $this->error('用户不存在', "/user/list{$cond}");
            }

            // 更新用户密码
            $result = $user->update([
                'password' => $password,
                'lastoperate' => time(),
                'lastoperator' => $this->getUserName(),
            ]);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('用户密码修改失败', "/user/list{$cond}");
            }

            return $this->success('密码修改成功', "/user/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户密码修改失败', "/user/list{$cond}");
        }
    }

    /**
     * 显示用户信息
     *
     * @return mixed
     */
    public function profileAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取用户状态
        $status = (int)$this->getParam('status');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取用户 ID
            $id = (int)$this->getParam('id');

            // 获取用户信息
            $user = User::findFirst($id);

            // 判断用户是否存在
            if (!$user) {
                return $this->error('用户不存在', "/user/list{$cond}");
            }

            $user = $user->toArray();

            $user['status'] = $this->status[$user['status']];

            $user['type'] = $this->types[$user['type']];

            $user['regip'] = $user['regip'] ? long2ip($user['regip']) : '';

            $user['regtime'] = $user['regtime'] ? date('Y-m-d H:i:s', $user['regtime']) : '';

            $user['loginip'] = $user['loginip'] ? long2ip($user['loginip']) : '';

            $user['logintime'] = $user['logintime'] ? date('Y-m-d H:i:s', $user['logintime']) : '';

            // 处理头像信息
            if ($user['avatar']) {
                $fileName = trim(strrchr($user['avatar'], '/'), '/');
                $user['mavatar'] = str_replace($fileName, "middle_{$fileName}", $user['avatar']);
                $user['savatar'] = str_replace($fileName, "small_{$fileName}", $user['avatar']);
            } else {
                $user['mavatar'] = $user['savatar'] = '';
            }

            // 获取用户详细信息
            $profile = Profile::find([
                'conditions' => "uid={$user['id']}",
                'columns' => 'id,email,mobile,zodiac,birthday,coin,happy,address,edu,age,sex',
            ])->toArray();

            // 用户详情不存在，则用空补全
            if (!$profile) {
                $profile['id'] = $profile['zodiac'] = $profile['coin'] = $profile['edu'] = $profile['sex'] = 0;
                $profile['age'] = $profile['birthday'] = $profile['email'] = $profile['mobile'] = $profile['happy'] = $profile['address'] = '无';
            } else {
                $profile = $profile[0];
                $profile['birthday'] = $profile['birthday'] ? date('Y-m-d', $profile['birthday']) : '无';
            }

            $profile['zodiac'] = $this->zodiacs[$profile['zodiac']];
            $profile['edu'] = $this->edus[$profile['edu']];
            $profile['sex'] = $this->sexs[$profile['sex']];

            // 传递用户信息
            $this->view->user = $user;

            // 传递用户详情信息
            $this->view->profile = $profile;

            // 传递查询条件信息
            $this->view->cond = $cond;

            return $this->view->pick('user/profile');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户详情信息获取失败', "/user/list{$cond}");
        }
    }

    /**
     * 加载用户权限配置页面
     *
     * @return mixed
     */
    public function authAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取用户状态
        $status = (int)$this->getParam('status');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取用户 ID
            $uid = (int)$this->getParam('uid');

            // 获取用户信息
            $user = User::findFirst($uid);

            // 判断用户信息是否存在
            if (!$user) {
                return $this->error('用户不存在', "/user/list{$cond}");
            }

            $user = $user->toArray();

            // 获取权限类型
            $authType = (int)$this->getParam('authtype');

            $this->view->authType = $authType;
            $this->view->frontType = Authitem::FRONT_AUTH_TYPE;
            $this->view->weiXinType = Authitem::WEIXIN_AUTH_TYPE;

            // 获取权限项
            $items = Authitem::find([
                'conditions' => 'enabled=' . Authitem::ENABLE_BLOG_AUTHITEM . " and type={$authType}" . ' order by displayorder desc, id desc',
                'columns' => 'id,parentid,name,auth,code',
            ])->toArray();

            // 获取顶级权限项
            $topItems = array_filter($items, function ($item) {
                if ($item['parentid']) {
                    return false;
                }

                return true;
            });

            // 获取用户关联的权限项配置信息
            $userAuthitems = UserAuthitem::find([
                'conditions' => " uid={$uid}",
                'columns' => 'itemid,auth',
            ])->toArray();

            // 获取对应子权限项
            $topItems = array_map(function ($topItem) use ($items, $userAuthitems) {
                $sonItems = [];

                foreach ($items as $item) {
                    if ($item['parentid'] == $topItem['id']) {
                        $item['auth'] = json_decode($item['auth'], true);

                        $item['auth']['checkatoms'] = [];

                        foreach ($userAuthitems as $roleAuthitem) {
                            if ($roleAuthitem['itemid'] == $item['id']) {
                                $item['auth']['checkatoms'] = explode(',', $roleAuthitem['auth']);
                                break;
                            }
                        }
                        $sonItems[] = $item;
                    }
                }

                $topItem['auth'] = json_decode($topItem['auth'], true);

                $topItem['son'] = $sonItems;

                return $topItem;
            }, $topItems);

            // 卸载空闲变量
            unset($items);

            $topItems = array_map(function ($topItem) use ($userAuthitems) {
                $checkAtoms = [];

                foreach ($userAuthitems as $roleAuthitem) {
                    if ($topItem['id'] == $roleAuthitem['itemid']) {
                        $checkAtoms = explode(',', $roleAuthitem['auth']);
                        break;
                    }
                }

                $topItem['auth']['checkatoms'] = $checkAtoms;

                return $topItem;
            }, $topItems);

            unset($userAuthitems);

            // 传递权限项信息
            $this->view->topItems = array_values($topItems);

            // 传递用户信息
            $this->view->user = $user;

            // 传递查询条件信息
            $this->view->cond = $cond;

            // 是否显示权限操作按钮
            $this->view->isAuthBut = $this->hasAuth(['blog.boss.siteuser.user.AUTH']);

            // 传递删除用户状态值
            $this->view->delStatus = User::DELETE_USER_STATUS;

            return $this->view->pick('user/auth');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户权限项加载失败', "/user/list{$cond}");
        }
    }

    /**
     * 设置用户权限项
     *
     * @return mixed
     */
    public function sauthAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取用户状态
        $status = (int)$this->getParam('status');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取权限类型
        $authType = (int)$this->getParam('authtype');

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/authtype/{$authType}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        // 获取用户 ID
        $userId = (int)$this->getParam('uid');

        try {
            // 获取权限 ID
            $authId = (int)$this->getParam('authid');

            // 获取权限配置
            $item = $this->getParam('item');

            $item = trim($item, ',');

            // 判断权限项是否为空
            if (!$item) {
                return $this->error('用户权限项配置不能为空!', "/user/auth/uid/{$userId}{$cond}");
            }

            // 获取用户信息
            $user = User::findFirst($userId);

            // 判断角色信息是否存在
            if (!$user) {
                return $this->error('用户信息不存在!', "/user/list{$cond}");
            }

            // 获取权限项信息
            $authItem = Authitem::findFirst($authId);

            // 判断权限项信息是否存在
            if (!$authItem) {
                return $this->error('权限项信息不存在！', "/user/auth/uid/{$userId}{$cond}");
            }

            // 获取用户关联的权限项信息
            $userAuthitem = UserAuthitem::findFirst([
                'conditions' => " uid={$userId} and itemid={$authId}",
                'columns' => 'id',
            ]);

            // 判断用户关联的权限项是否存在
            if ($userAuthitem) {
                $result = UserAuthitem::findFirst($userAuthitem->id)->update([
                    'auth' => $item,
                    'lastoperate' => time(),
                    'lastoperator' => $this->getUserName(),
                ]);

                if (!$result) {
                    return $this->error('用户权限项设置失败', "/user/auth/uid/{$userId}{$cond}");
                }
            } else {
                $userAuthitem = new UserAuthitem();

                $inserts = [
                    'uid' => $userId,
                    'itemid' => $authId,
                    'auth' => $item,
                    'creator' => $this->getUserName(),
                    'createtime' => time(),
                ];

                // 添加用户关联权限项信息
                $result = $userAuthitem->create($inserts);

                // 判断添加是否成功
                if (!$result) {
                    return $this->error('用户权限项设置失败', "/user/auth/uid/{$userId}{$cond}");
                }
            }

            return $this->success('用户权限项设置成功', "/user/auth/uid/{$userId}{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户权限项设置失败', "/user/auth/uid/{$userId}{$cond}");
        }
    }

    /**
     * 撤销用户权限配置
     *
     * @return mixed
     */
    public function dauthAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取用户状态
        $status = (int)$this->getParam('status');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取权限类型
        $authType = (int)$this->getParam('authtype');

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/authtype/{$authType}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        // 获取用户 ID
        $userId = (int)$this->getParam('uid');

        try {
            // 获取权限 ID
            $authId = (int)$this->getParam('authid');

            // 获取用户关联权限项信息
            $userAuthitem = UserAuthitem::findFirst([
                'conditions' => " uid={$userId} and itemid={$authId}",
                'columns' => 'id',
            ]);

            // 存在则做删除操作
            if ($userAuthitem) {
                // 获取子权限项 ID
                $sonItems = Authitem::find([
                    'conditions' => " parentid={$authId}",
                    'columns' => 'id',
                ])->toArray();

                if ($sonItems) {
                    $sonItems = array_map(function ($sonItem) {
                        return $sonItem['id'];
                    }, $sonItems);

                    $sonUserAuthitem = UserAuthitem::find([
                        'conditions' => " uid={$userId} and itemid in(" . implode(',', $sonItems) . ')',
                        'columns' => 'id',
                    ])->toArray();

                    if ($sonUserAuthitem) {
                        return $this->error('请先撤销该子权限项关联的用户配置', "/user/auth/uid/{$userId}{$cond}");
                    }
                }

                $result = UserAuthitem::findFirst($userAuthitem->id)->delete();

                // 判断删除是否成功
                if (!$result) {
                    return $this->error('用户权限项配置撤销失败', "/user/auth/uid/{$userId}{$cond}");
                }
            }

            return $this->success('用户权限项撤销成功', "/user/auth/uid/{$userId}{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户权限项配置撤销失败', "/user/auth/uid/{$userId}{$cond}");
        }
    }

    /**
     * 发送消息页面
     *
     * @return mixed
     */
    public function msgAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取用户状态
        $status = (int)$this->getParam('status');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        // 获取用户 ID
        $userId = (int)$this->getParam('uid');

        try {
            // 获取用户信息
            $user = User::findFirst($userId);

            // 判断用户是否存在
            if (!$user) {
                return $this->error('用户不存在', "/user/list{$cond}");
            }

            $user = $user->toArray();

            // 获取消息类型信息
            $msgTypes = MsgType::find([
                'conditions' => "enabled=" . MsgType::ENABLED_MSG_TYPE . ' order by createtime desc,id desc',
                'columns' => 'id,name'
            ])->toArray();

            // 传递消息类型信息
            $this->view->msgTypes = $msgTypes;

            // 传递用户信息
            $this->view->user = $user;

            // 传递查询条件
            $this->view->cond = $cond;

            $this->view->uploadUrl = '/assets/ueditor/php/imageUp.php?m=notice';

            return $this->view->pick('user/msg');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('消息发送页面加载失败', "/user/list{$cond}");
        }
    }

    /**
     * 发送消息
     *
     * @return mixed
     */
    public function sendMsgAction()
    {
        // 获取用户名称
        $userName = urldecode($this->getParam('username'));

        // 获取用户状态
        $status = (int)$this->getParam('status');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($userName ? "/username/{$userName}" : '') . ('' !== $status ? "/status/{$status}" : '');

        try {
            // 获取提交的内容
            $posts = $this->request->getPost();

            // 判断是否提交了内容
            if (!$posts) {
                return $this->error('消息信息不能为空', "/user/msg{$cond}");
            }

            // 判断标题是否为空
            if (!isset($posts['title']) || !$posts['title']) {
                return $this->error('消息标题不能为空', "/user/msg{$cond}");
            }

            // 判断内容是否为空
            if (!isset($posts['editorValue']) || !$posts['editorValue']) {
                return $this->error('消息标题不能为空', "/user/msg{$cond}");
            }

            // 获取接收对象ID
            $posterId = isset($posts['posterid']) ? $posts['posterid'] : 0;

            // 获取接收人信息
            $user = User::findFirst($posterId);

            // 判断接收人是否存在
            if (!$user) {
                return $this->error('接收人不存在', "/user/msg{$cond}");
            }

            $user = $user->toArray();

            // 获取当前登陆人
            $operator = $this->getUserName();

            $message = new Message();

            // 添加主消息
            $result = $message->save([
                'sendid' => $this->getUserId(),
                'sendname' => $operator,
                'title' => isset($posts['title']) ? $posts['title'] : '',
                'content' => isset($posts['editorValue']) ? $posts['editorValue'] : '',
                'typeid' => isset($posts['typeid']) ? $posts['typeid'] : 0,
                'msgtype' => 1,
                'sendtime' => time(),
            ]);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('消息发送失败', "/user/msg{$cond}");
            }

            // 获取消息 ID
            $messageId = $message->id;

            $inserts = [
                'messageid' => $messageId,
                'postid' => $posterId,
                'postname' => $user['username'],
                'createtime' => time(),
                'creator' => $operator,
            ];

            $detail = new MessageDetail();

            // 添加消息详情
            $result = $detail->save($inserts);

            // 判断添加是否成功
            if (!$result) {
                return $this->error('消息发送失败', "/user/msg{$cond}");
            }

            return $this->success('消息发送成功', "/user/list{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('消息发送失败', "/user/msg{$cond}");
        }
    }
}