<?php

use \Phalcon\Paginator\Adapter\Model;

class HomeController extends BaseController
{
    /**
     * 所有用户
     */
    const OBJECT_TYPE_ALL = 0;
    /**
     * 前台用户
     */
    const OBJECT_TYPE_FRONT = 1;
    /**
     * 后台用户
     */
    const OBJECT_TYPE_ADMIN = 2;
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
     * 消息来源
     *
     * @var array
     */
    protected $sources = [
        '网页',
        '系统',
        '微信',
    ];
    /**
     * 是否好友消息
     *
     * @var array
     */
    protected $isFriends = [
        '否',
        '是',
    ];
    /**
     * 是否已查看
     *
     * @var array
     */
    protected $isReads = [
        '否',
        '是',
    ];

    /**
     * 各个方法操作所需的用户权限
     *
     * @var array
     */
    protected $actionAuth = [
        'base' => [false, ['blog.boss.my.base.L', 'blog.boss.my.base.R', 'blog.boss.my.base.S']],
        'post' => [false, ['blog.boss.my.base.C', 'blog.boss.my.base.U']],
        'password' => [false, ['blog.boss.my.passwd.L', 'blog.boss.my.passwd.R', 'blog.boss.my.passwd.S']],
        'setpassword' => [false, ['blog.boss.my.passwd.PWD']],
        'notice' => [false, ['blog.boss.my.notice.L', 'blog.boss.my.notice.R', 'blog.boss.my.notice.S']],
        'msg' => [false, ['blog.boss.my.notice.SEND']],
        'sendmsg' => [false, ['blog.boss.my.notice.SEND']],
        'read' => [false, ['blog.boss.my.notice.SET']],
        'delnotice' => [false, ['blog.boss.my.notice.D', 'blog.boss.my.notice.BD']],
        'sendbox' => [false, ['blog.boss.my.notice.L', 'blog.boss.my.notice.R', 'blog.boss.my.notice.S']],
        'delsendbox' => [false, ['blog.boss.my.notice.D', 'blog.boss.my.notice.BD']],
        'posterlist' => [false, ['blog.boss.my.notice.L', 'blog.boss.my.notice.R', 'blog.boss.my.notice.S']],
        'myauth' => [false, ['blog.boss.my.myauth.L', 'blog.boss.my.myauth.R', 'blog.boss.my.myauth.S']],
        'sauth' => [false, ['blog.boss.my.myauth.AUTH']],
        'dauth' => [false, ['blog.boss.my.myauth.AUTH']],
    ];

    /**
     * 显示用户个人资料
     *
     * @return mixed
     */
    public function baseAction()
    {
        try {
            // 获取导航菜单 ID
            $navId = $this->navId;

            // 传递查询条件
            $this->view->cond = "/navid/{$navId}";

            // 获取当前登陆用户 ID
            $uid = $this->getUserId();

            // 获取用户信息
            $user = User::find([
                'conditions' => 'type=' . User::ADMIN_USER_TYPE . " and id={$uid}",
                'columns' => 'id,username,type,status,avatar,regip,regtime,logintime,loginip'
            ])->toArray();

            // 判断用户信息是否存在
            if (!$user) {
                return $this->error('用户不存在', '/');
            }

            $user = $user[0];

            // 转化类型
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
                'conditions' => "uid={$uid}",
                'columns' => 'id,email,mobile,zodiac,birthday,coin,happy,address,edu,age,sex',
            ])->toArray();

            // 用户详情不存在，则用空补全
            if (!$profile) {
                $profile['id'] = $profile['zodiac'] = $profile['coin'] = $profile['edu'] = $profile['age'] = $profile['sex'] = 0;
                $profile['birthday'] = $profile['email'] = $profile['mobile'] = $profile['happy'] = $profile['address'] = '';
            } else {
                $profile = $profile[0];
                $profile['birthday'] = $profile['birthday'] ? date('Y-m-d', $profile['birthday']) : '';
            }

            // 传递用户信息
            $this->view->user = $user;

            // 传递用户详细信息
            $this->view->profile = $profile;

            // 传递星座信息
            $this->view->zodiacs = $this->zodiacs;

            // 传递学历信息
            $this->view->edus = $this->edus;

            // 传递性别信息
            $this->view->sexs = $this->sexs;

            // 是否允许修改
            $this->view->isUpdateBut = $this->hasAuth(['blog.boss.my.base.C']) ? : $this->hasAuth(['blog.boss.my.base.U']);

            return $this->view->pick('home/base');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('基础资料页面加载失败', '/');
        }
    }

    /**
     * 设置个人资料信息
     *
     * @return mixed
     */
    public function postAction()
    {
        // 获取导航 ID
        $navId = (int)$this->getParam('navid');

        $cond = "/navid/{$navId}";

        try {
            // 获取提交的数据
            $posts = $this->request->getPost();

            // 获取用户 ID
            $uid = isset($posts['uid']) ? $posts['uid'] : 0;

            // 获取用户信息
            $user = User::findFirst($uid);

            // 判断用户信息是否存在
            if (!$user) {
                return $this->error('用户不存在', '/');
            }

            // 文件存在则做上传处理
            if (isset($_FILES['avatar']) && $_FILES['avatar']['name']) {
                $upload = new Upload();

                // 上传文件
                $file = $upload->make('avatar', 'avatar');

                // 判断上传是否成功
                if (!$file) {
                    $msg = $upload->getErr() ? : '头像上传失败';

                    return $this->error($msg, "/home/base{$cond}");
                }

                // 获取文件名
                $fileName = trim(strrchr($file, '/'), '/');

                // 获取文件目录
                $dir = substr($file, 0, strrpos($file, '/'));

                // 原图片路径
                $path = ROOT . "{$file}";

                // 中等缩略图路径
                $middle = ROOT . "/{$dir}/middle_{$fileName}";

                // 小型缩略图路径
                $small = ROOT . "/{$dir}/small_{$fileName}";

                // 生成中等缩略图
                Image::thumb($path, $middle, 100, 100);

                // 生成小型缩略图
                Image::thumb($path, $small, 50, 50);

                // 获取用户头像地址
                $avatar = $user->avatar;
                $avatar = ROOT . $avatar;

                // 更新用户头像信息
                $result = $user->update([
                    'avatar' => $file,
                ]);

                // 判断更新是否成功
                if ($result && $avatar) {
                    // 删除原有相关图片
                    file_exists($avatar) and unlink($avatar);

                    $fileName = trim(strrchr($avatar, '/'), '/');

                    $mavatar = str_replace($fileName, "middle_{$fileName}", $avatar);

                    file_exists($mavatar) and unlink($mavatar);

                    $savatar = str_replace($fileName, "small_{$fileName}", $avatar);

                    file_exists($savatar) and unlink($savatar);
                }
            }

            // 判断用户详情信息是否已存在
            $profile = Profile::find([
                'conditions' => "uid={$uid}",
                'columns' => 'id',
            ])->toArray();

            // 获取当前登陆人
            $operate = $this->getUserName();

            // 转换生日信息
            $posts['birthday'] = $posts['birthday'] ? strtotime($posts['birthday']) : 0;

            // 存在则做更新操作，否则添加用户详情
            if ($profile) {
                $profile = $profile[0];

                $posts['lastoperate'] = time();
                $posts['lastoperator'] = $operate;

                // 更新用户详情信息
                $result = Profile::findFirst($profile['id'])->update($posts);
            } else {
                $profile = new Profile();

                $posts['createtime'] = time();
                $posts['creator'] = $operate;

                // 添加用户详情信息
                $result = $profile->create($posts);
            }

            // 判断操作是否成功
            if (!$result) {
                return $this->error('个人信息设置失败', "/home/base{$cond}");
            }

            return $this->success('个人信息设置成功', "/home/base{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('个人信息设置失败', "/home/base{$cond}");
        }
    }

    /**
     * 显示消息中心页面
     *
     * @return mixed
     */
    public function noticeAction()
    {
        try {
            // 获取导航 ID
            $navId = (int)$this->getParam('navid');

            // 获取当前页码
            $page = $this->page;

            // 获取每页显示的数据量
            $pageSize = $this->pageSize;

            // 获取标题
            $title = $this->getParam('title');
            $title = urldecode($title);
            $titleSql = $title ? " and title like '%{$title}%'" : '';

            $this->view->title = $title;

            // 发信人姓名
            $sendName = $this->getParam('sendname');
            $sendName = urldecode($sendName);
            $sendNameSql = $sendName ? " and sendname like '%{$sendName}%'" : '';

            $this->view->sendName = $sendName;

            // 获取消息类型
            $typeId = $this->getParam('typeid');
            $typeId = (null === $typeId) ? -1 : intval($typeId);
            $typeIdSql = (-1 === $typeId) ? '' : " and typeid={$typeId}";

            $this->view->typeId = $typeId;

            // 获取消息来源
            $msgType = $this->getParam('msgtype');
            $msgType = (null === $msgType) ? -1 : intval($msgType);
            $msgTypeSql = (-1 === $msgType) ? '' : " and msgtype={$msgType}";

            $this->view->msgType = $msgType;

            // 获取是否好友消息
            $isFriend = $this->getParam('isfriend');
            $isFriend = (null === $isFriend) ? -1 : intval($isFriend);
            $isFriendSql = (-1 === $isFriend) ? '' : " and isfriend={$isFriend}";

            $this->view->isFriend = $isFriend;

            // 开始时间
            $startTime = $this->getParam('starttime');

            $this->view->startTime = $startTime;

            $startTime = $startTime ? strtotime($startTime . ' 00:00:00') : 0;
            $startTimeSql = $startTime ? " and sendtime >= {$startTime}" : '';

            // 结束时间
            $endTime = $this->getParam('endtime');

            $this->view->endTime = $endTime;

            $endTime = $endTime ? strtotime("{$endTime} 23:59:59") : 0;

            $endTimeSql = $endTime ? " and sendtime <= {$endTime}" : '';

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($title ? "/title/{$title}" : '') . ($sendName ? "/sendname/{$sendName}" : '') . ('' !== $typeId ? "/typeid/{$typeId}" : '');
            $cond .= ('' !== $msgType ? "/msgtype/{$msgType}" : '') . ('' !== $isFriend ? "/isfriend/{$isFriend}" : '');
            $startTime = $startTime ? date('Y-m-d', $startTime) : '';
            $endTime = $endTime ? date('Y-m-d', $endTime) : '';
            $cond .= ('' !== $startTime ? "/starttime/{$startTime}" : '') . ('' !== $endTime ? "/endtime/{$endTime}" : '');

            // 获取当前登陆人 ID
            $uid = $this->getUserId();

            // 获取收信箱信息列表
            $messages = Message::find([
                'conditions' => " 1=1 {$titleSql}{$typeIdSql}{$msgTypeSql}{$isFriendSql}{$startTimeSql}{$endTimeSql}{$sendNameSql}",
                'columns' => 'id,sendname,title,content,typeid,msgtype,isfriend,sendtime'
            ])->toArray();

            $details = [];

            if ($messages) {
                $messageIds = array_map(function ($message) {
                    return $message['id'];
                }, $messages);

                // 获取用户消息
                $details = MessageDetail::find([
                    'conditions' => "postid={$uid}" . ' and messageid in(' . implode(',', $messageIds) . ") order by isread asc,createtime desc,id desc",
                    'columns' => 'id,messageid,postid,postname,isread'
                ]);

                // 定义分页信息
                $paginator = new Model(
                    array(
                        "data" => $details,
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

                $details = json_decode(json_encode($pageDatas->items), true);

                // 卸载空闲变量
                unset($pageDatas);
            } else {
                $page = ['num' => 0];
            }

            // 获取消息类型信息
            $msgTypes = MsgType::find([
                'conditions' => "enabled=" . MsgType::ENABLED_MSG_TYPE . ' order by createtime desc,id desc',
                'columns' => 'id,name'
            ])->toArray();

            $types = [];

            foreach ($msgTypes as $msgType) {
                $types[$msgType['id']] = $msgType['name'];
            }

            // 处理查询结果
            $details = array_map(function ($detail) use ($messages, $types) {
                $detail['msg'] = [
                    'title' => '',
                    'msgtype' => 0,
                    'isfriend' => 0,
                    'typeid' => 0,
                    'sendtime' => 0,
                ];
                $detail['isreadname'] = $this->isReads[$detail['isread']];
                foreach ($messages as $message) {
                    if ($detail['messageid'] == $message['id']) {
                        $title = mb_substr($message['title'], 0, 10, 'utf8');
                        if (mb_strlen($title, 'utf8') < mb_strlen($message['title'], 'utf8')) {
                            $title .= '...';
                        }
                        $message['shorttitle'] = $title;
                        $message['msgtype'] = $this->sources[$message['msgtype']];
                        $message['isfriend'] = $this->isFriends[$message['isfriend']];
                        $message['typeid'] = isset($types[$message['typeid']]) ? $types[$message['typeid']] : '无';
                        $message['sendtime'] = $message['sendtime'] ? date('Y-m-d H:i:s', $message['sendtime']) : '无';
                        $detail['msg'] = $message;
                        break;
                    }
                }

                return $detail;
            }, $details);

            unset($messages);

            // 传递消息类型信息
            $this->view->msgTypes = $msgTypes;

            // 传递收信箱信息
            $this->view->messages = $details;

            // 传递分页信息
            $this->view->page = $page;

            // 传递查询条件
            $this->view->cond = $cond;

            // 传递导航 ID
            $this->view->navId = $navId;

            // 传递是否好友消息查询条件
            $this->view->isFriends = $this->isFriends;

            // 传递消息来源查询条件
            $this->view->sources = $this->sources;

            // 是否显示发消息按钮
            $this->view->isSendBut = $this->hasAuth(['blog.boss.my.notice.SEND']);

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.my.notice.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.my.notice.BD']);

            // 是否显示已阅按钮
            $this->view->isSetBut = $this->hasAuth(['blog.boss.my.notice.SET']);

            return $this->view->pick('home/notice');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('消息中心页面加载失败', '/');
        }
    }

    /**
     * 显示消息发送页面
     *
     * @return mixed
     */
    public function msgAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        // 传递查询条件
        $cond = "/navid/{$navId}";

        try {
            // 获取消息类型信息
            $msgTypes = MsgType::find([
                'conditions' => "enabled=" . MsgType::ENABLED_MSG_TYPE . ' order by createtime desc,id desc',
                'columns' => 'id,name'
            ])->toArray();

            // 传递消息类型信息
            $this->view->msgTypes = $msgTypes;

            // 传递查询条件
            $this->view->cond = $cond;

            $this->view->uploadUrl = '/assets/ueditor/php/controller.php?m=notice';

            return $this->view->pick('home/msg');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('消息发送页面加载失败', "/home/notice{$cond}");
        }
    }

    /**
     * 发送消息
     *
     * @return mixed
     */
    public function sendMsgAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        // 传递查询条件
        $cond = "/navid/{$navId}";

        try {
            // 获取提交的内容
            $posts = $this->request->getPost();

            // 判断是否提交了内容
            if (!$posts) {
                return $this->error('消息信息不能为空', "/home/msg{$cond}");
            }

            // 判断消息标题是否为空
            if (!isset($posts['title']) || !$posts['title']) {
                return $this->error('消息标题不能为空', "/home/msg{$cond}");
            }

            // 判断消息内容是否为空
            if (!isset($posts['editorValue']) || !$posts['editorValue']) {
                return $this->error('消息内容不能为空', "/home/msg{$cond}");
            }

            // 获取接收对象类型
            $poster = isset($posts['poster']) ? $posts['poster'] : 0;

            if (self::OBJECT_TYPE_ADMIN == $poster) {
                // 获取后台用户
                $users = User::find([
                    'conditions' => 'status=' . User::NORMAL_USER_STATUS . ' and type=' . User::ADMIN_USER_TYPE,
                    'columns' => 'id,username'
                ])->toArray();
            } elseif (self::OBJECT_TYPE_FRONT == $poster) {
                // 获取前台用户
                $users = User::find([
                    'conditions' => 'status=' . User::NORMAL_USER_STATUS . ' and type=' . User::FRONT_USER_TYPE,
                    'columns' => 'id,username'
                ])->toArray();
            } else {
                // 获取所有用户
                $users = User::find([
                    'conditions' => 'status=' . User::NORMAL_USER_STATUS,
                    'columns' => 'id,username'
                ])->toArray();
            }

            // 判断用户是否存在
            if (!$users) {
                return $this->error('没有任何收信人', "/home/msg{$cond}");
            }

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
                return $this->error('消息发送失败', "/home/msg{$cond}");
            }

            // 获取消息 ID
            $messageId = $message->id;

            $inserts = [
                'messageid' => $messageId,
                'creator' => $operator,
            ];

            // 判断添加状态
            $isFailed = false;

            // 添加消息详情信息
            foreach ($users as $user) {
                $detail = new MessageDetail();

                $inserts['postid'] = $user['id'];
                $inserts['postname'] = $user['username'];
                $inserts['createtime'] = time();

                // 添加消息详情
                $result = $detail->save($inserts);

                // 判断添加是否成功
                if (!$result) {
                    $isFailed = true;
                }
            }

            if ($isFailed) {
                return $this->error('消息发送失败', "/home/msg{$cond}");
            }

            return $this->success('消息发送成功', "/home/notice{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            //返回错误提示信息
            return $this->error('消息发送失败', "/home/msg{$cond}");
        }
    }

    /**
     * 更新阅读状态信息
     *
     * @return mixed
     */
    public function readAction()
    {
        // 获取标题信息
        $title = urldecode($this->getParam('title'));

        // 获取发信人
        $sendName = urldecode($this->getParam('sendname'));

        // 获取消息类型
        $typeId = (int)$this->getParam('typeid');

        // 获取消息来源
        $msgType = (int)$this->getParam('msgtype');

        // 获取是否好友信息
        $isfriend = (int)$this->getParam('isfriend');

        // 获取开始时间
        $startTime = $this->getParam('starttime');

        // 获取结束时间
        $endTime = $this->getParam('endtime');

        // 获取页码
        $page = $this->page;

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}" . ($title ? "/title/{$title}" : '') . ($sendName ? "/sendname/{$sendName}" : '') . ('' !== $typeId ? "/typeid/{$typeId}" : '');
        $cond .= ($msgType ? "/msgtype/{$msgType}" : '') . ('' !== $isfriend ? "/isfriend/{$isfriend}" : '') . ($startTime ? "/starttime/{$startTime}" : '') . ($endTime ? "/endtime/{$endTime}" : '');

        try {
            // 获取消息ID
            $id = (int)$this->getParam('id');

            // 获取消息详情信息
            $detail = MessageDetail::find("id={$id}");

            // 判断用户信息是否存在
            if (!$detail) {
                return $this->error('消息不存在', "/home/notice{$cond}");
            }

            // 更新消息阅读状态
            $result = $detail->update([
                'isread' => 1,
                'lastoperate' => time(),
                'lastoperator' => $this->getUserName(),
            ]);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('阅读状态更新失败', "/home/notice{$cond}");
            }

            return $this->success('阅读状态更新成功', "/home/notice{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('阅读状态更新失败', "/home/notice{$cond}");
        }
    }

    /**
     * 删除消息信息
     *
     * @return mixed
     */
    public function delNoticeAction()
    {
        // 获取标题信息
        $title = urldecode($this->getParam('title'));

        // 获取发信人
        $sendName = urldecode($this->getParam('sendname'));

        // 获取消息类型
        $typeId = (int)$this->getParam('typeid');

        // 获取消息来源
        $msgType = (int)$this->getParam('msgtype');

        // 获取是否好友信息
        $isfriend = (int)$this->getParam('isfriend');

        // 获取开始时间
        $startTime = $this->getParam('starttime');

        // 获取结束时间
        $endTime = $this->getParam('endtime');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($title ? "/title/{$title}" : '') . ($sendName ? "/sendname/{$sendName}" : '') . ('' !== $typeId ? "/typeid/{$typeId}" : '');
        $cond .= ($msgType ? "/msgtype/{$msgType}" : '') . ('' !== $isfriend ? "/isfriend/{$isfriend}" : '') . ($startTime ? "/starttime/{$startTime}" : '') . ($endTime ? "/endtime/{$endTime}" : '');

        try {
            // 获取记录 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = false;

            foreach ($ids as $id) {
                // 获取消息
                $detail = MessageDetail::findFirst($id);

                // 判断记录信息是否存在，存在则做删除
                if ($detail) {
                    // 删除记录信息
                    $result = $detail->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('消息删除失败', "/home/notice{$cond}");
            }

            return $this->success('消息删除成功', "/home/notice{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('消息删除失败', "/home/notice{$cond}");
        }
    }

    /**
     * 发信箱
     *
     * @return mixed
     */
    public function sendBoxAction()
    {
        // 获取导航 ID
        $navId = (int)$this->getParam('navid');

        try {
            // 获取当前页码
            $page = $this->page;

            // 获取每页显示的数据量
            $pageSize = $this->pageSize;

            // 获取标题
            $title = $this->getParam('title');
            $title = urldecode($title);
            $titleSql = $title ? " and title like '%{$title}%'" : '';

            $this->view->title = $title;

            // 获取消息类型
            $typeId = $this->getParam('typeid');
            $typeId = (null === $typeId) ? -1 : intval($typeId);
            $typeIdSql = (-1 === $typeId) ? '' : " and typeid={$typeId}";

            $this->view->typeId = $typeId;

            // 获取消息来源
            $msgType = $this->getParam('msgtype');
            $msgType = (null === $msgType) ? -1 : intval($msgType);
            $msgTypeSql = (-1 === $msgType) ? '' : " and msgtype={$msgType}";

            $this->view->msgType = $msgType;

            // 获取是否好友消息
            $isFriend = $this->getParam('isfriend');
            $isFriend = (null === $isFriend) ? -1 : intval($isFriend);
            $isFriendSql = (-1 === $isFriend) ? '' : " and isfriend={$isFriend}";

            $this->view->isFriend = $isFriend;

            // 开始时间
            $startTime = $this->getParam('starttime');

            $this->view->startTime = $startTime;

            $startTime = $startTime ? strtotime($startTime . ' 00:00:00') : 0;
            $startTimeSql = $startTime ? " and sendtime >= {$startTime}" : '';

            // 结束时间
            $endTime = $this->getParam('endtime');

            $this->view->endTime = $endTime;

            $endTime = $endTime ? strtotime($endTime . " 23:59:59") : 0;
            $endTimeSql = $endTime ? " and sendtime <= {$endTime}" : '';

            // 获取查询条件组合
            $cond = "/navid/{$navId}" . ($title ? "/title/{$title}" : '') . ('' !== $typeId ? "/typeid/{$typeId}" : '');
            $cond .= ('' !== $msgType ? "/msgtype/{$msgType}" : '') . ('' !== $isFriend ? "/isfriend/{$isFriend}" : '');
            $startTime = $startTime ? date('Y-m-d', $startTime) : '';
            $endTime = $endTime ? date('Y-m-d', $endTime) : '';
            $cond .= ('' !== $startTime ? "/starttime/{$startTime}" : '') . ('' !== $endTime ? "/endtime/{$endTime}" : '');

            // 获取当前登陆人 ID
            $uid = $this->getUserId();

            // 获取发信箱信息列表
            $messages = Message::find([
                'conditions' => " sendid={$uid}{$titleSql}{$typeIdSql}{$msgTypeSql}{$isFriendSql}{$startTimeSql}{$endTimeSql}",
                'columns' => 'id,title,content,typeid,msgtype,isfriend,sendtime'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $messages,
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

            $messages = json_decode(json_encode($pageDatas->items), true);

            // 卸载空闲变量
            unset($pageDatas);

            // 获取消息类型信息
            $msgTypes = MsgType::find([
                'conditions' => "enabled=" . MsgType::ENABLED_MSG_TYPE . ' order by createtime desc,id desc',
                'columns' => 'id,name'
            ])->toArray();

            $types = [];

            foreach ($msgTypes as $msgType) {
                $types[$msgType['id']] = $msgType['name'];
            }

            // 处理查询结果
            $messages = array_map(function ($message) use ($types) {
                $title = mb_substr($message['title'], 0, 10, 'utf8');
                if (mb_strlen($title, 'utf8') < mb_strlen($message['title'], 'utf8')) {
                    $title .= '...';
                }
                $message['title'] = $title;
                $message['msgtype'] = $this->sources[$message['msgtype']];
                $message['isfriend'] = $this->isFriends[$message['isfriend']];
                $message['typeid'] = isset($types[$message['typeid']]) ? $types[$message['typeid']] : '无';
                $message['sendtime'] = $message['sendtime'] ? date('Y-m-d H:i:s', $message['sendtime']) : '无';

                return $message;
            }, $messages);

            // 传递收信箱信息
            $this->view->messages = $messages;

            // 传递消息类型信息
            $this->view->msgTypes = $msgTypes;

            // 传递分页信息
            $this->view->page = $page;

            // 传递查询条件
            $this->view->cond = "/navid/{$navId}";
            $this->view->curcond = $cond;

            // 传递导航 ID
            $this->view->navId = $navId;

            // 传递是否好友消息查询条件
            $this->view->isFriends = $this->isFriends;

            // 传递消息来源查询条件
            $this->view->sources = $this->sources;

            // 是否显示删除按钮
            $this->view->isDeleteBut = $this->hasAuth(['blog.boss.my.notice.D']);

            // 是否显示批量删除按钮
            $this->view->isBatchDeleteBut = $this->hasAuth(['blog.boss.my.notice.BD']);

            return $this->view->pick('home/sendbox');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('发信箱页面加载失败', "/home/notice/navid/{$navId}");
        }
    }

    /**
     * 删除发信箱信息
     *
     * @return mixed
     */
    public function delSendBoxAction()
    {
        // 获取标题信息
        $title = urldecode($this->getParam('title'));

        // 获取消息类型
        $typeId = (int)$this->getParam('typeid');

        // 获取消息来源
        $msgType = (int)$this->getParam('msgtype');

        // 获取是否好友信息
        $isfriend = (int)$this->getParam('isfriend');

        // 获取开始时间
        $startTime = $this->getParam('starttime');

        // 获取结束时间
        $endTime = $this->getParam('endtime');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取查询条件组合
        $cond = "/navid/{$navId}" . ($title ? "/title/{$title}" : '') . ('' !== $typeId ? "/typeid/{$typeId}" : '');
        $cond .= ($msgType ? "/msgtype/{$msgType}" : '') . ('' !== $isfriend ? "/isfriend/{$isfriend}" : '') . ($startTime ? "/starttime/{$startTime}" : '') . ($endTime ? "/endtime/{$endTime}" : '');

        try {
            // 获取记录 ID
            $id = $this->getParam('id');
            $id = trim($id, ',');
            $ids = $id ? explode(',', $id) : [];

            $isFailed = false;

            foreach ($ids as $id) {
                // 获取详情信息
                $details = MessageDetail::find("messageid={$id}");

                if ($details) {
                    // 删除消息详情信息
                    $result = $details->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }

                // 获取消息信息
                $messages = Message::find("id={$id}");

                if ($messages) {
                    // 删除消息信息
                    $result = $messages->delete();

                    // 判断删除是否成功
                    if (!$result) {
                        $isFailed = true;
                    }
                }
            }

            // 判断删除是否成功
            if ($isFailed) {
                return $this->error('消息删除失败', "/home/sendbox{$cond}");
            }

            return $this->success('消息删除成功', "/home/sendbox{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('消息删除失败', "/home/sendbox{$cond}");
        }
    }

    /**
     * 获取收信人列表信息
     *
     * @return mixed
     */
    public function posterListAction()
    {
        // 获取分页
        $page = (int)$this->getParam('page');
        $_page = (int)$this->getParam('_page');
        $_page = $_page ? : 1;

        // 获取标题信息
        $title = urldecode($this->getParam('title'));

        // 获取消息类型
        $typeId = (int)$this->getParam('typeid');

        // 获取消息来源
        $msgType = (int)$this->getParam('msgtype');

        // 获取是否好友信息
        $isfriend = (int)$this->getParam('isfriend');

        // 获取开始时间
        $startTime = $this->getParam('starttime');

        // 获取结束时间
        $endTime = $this->getParam('endtime');

        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取每页显示的数据量
        $pageSize = $this->pageSize;

        // 获取消息 ID
        $messageId = (int)$this->getParam('messageid');

        // 获取查询条件组合
        $cond = "/navid/{$navId}/page/{$page}/_page/{$_page}/messageid/{$messageId}" . ($title ? "/title/{$title}" : '') . ('' !== $typeId ? "/typeid/{$typeId}" : '');
        $cond .= ($msgType ? "/msgtype/{$msgType}" : '') . ('' !== $isfriend ? "/isfriend/{$isfriend}" : '') . ($startTime ? "/starttime/{$startTime}" : '') . ($endTime ? "/endtime/{$endTime}" : '');

        try {
            // 获取消息详情信息
            $details = MessageDetail::find([
                'conditions' => "messageid={$messageId}",
                'columns' => 'messageid,postname,isread,createtime,lastoperate'
            ]);

            // 定义分页信息
            $paginator = new Model(
                array(
                    "data" => $details,
                    "limit" => $pageSize,
                    "page" => $_page,
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

            $details = json_decode(json_encode($pageDatas->items), true);

            // 转换数值信息
            $details = array_map(function ($detail) {
                $detail['isread'] = $this->isReads[$detail['isread']];

                $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s', $detail['createtime']) : '';

                $detail['lastoperate'] = $detail['lastoperate'] ? date('Y-m-d H:i:s', $detail['lastoperate']) : '';

                return $detail;
            }, $details);

            // 卸载空闲变量
            unset($pageDatas);

            // 传递收信人信息
            $this->view->details = $details;

            // 传递分页信息
            $this->view->page = $page;

            $this->view->cond = "/navid/{$navId}";

            $this->view->curcond = $cond;

            return $this->view->pick('home/posterlist');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('收信人页面加载失败', "/home/sendbox{$cond}");
        }
    }

    /**
     * 显示修改密码页面
     *
     * @return mixed
     */
    public function passwordAction()
    {
        try {
            // 获取导航菜单 ID
            $navId = $this->navId;

            // 传递查询条件
            $this->view->cond = "/navid/{$navId}";

            // 获取用户 ID
            $id = $this->getUserId();

            // 获取用户信息
            $user = User::findFirst($id);

            // 判断用户信息是否存在
            if (!$user) {
                return $this->error('用户不存在', '/');
            }

            $user = $user->toArray();

            // 传递用户信息
            $this->view->user = $user;

            // 修改密码按钮是否可用
            $this->view->isPasswdBut = $this->hasAuth(['blog.boss.my.passwd.PWD']);

            return $this->view->pick('home/password');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('修改密码页面加载失败', '/');
        }
    }

    /**
     * 修改密码
     *
     * @return mixed
     */
    public function setPasswordAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        $cond = "/navid/{$navId}";

        try {
            // 获取用户提交的数据
            $posts = $this->request->getPost();

            // 判断提交的数据是否存在
            if (!$posts || !isset($posts['password']) || !isset($posts['repassword'])) {
                return $this->error('请填写密码后再提交', "/home/password{$cond}");
            }

            // 验证密码是否为空
            if (!$posts['password'] || !$posts['repassword']) {
                return $this->error('请填写密码后再提交', "/home/password{$cond}");
            }

            // 验证密码的一致性
            if ($posts['password'] != $posts['repassword']) {
                return $this->error('两次密码不一致', "/home/password{$cond}");
            }

            // 加密密码
            $password = md5($posts['password']);

            // 获取用户 ID
            $id = isset($posts['id']) ? intval($posts['id']) : 0;

            // 获取用户信息
            $user = User::findFirst($id);

            // 判断用户是否合法
            if (!$user) {
                return $this->error('用户不存在', '/');
            }

            // 更新用户密码
            $result = $user->update([
                'password' => $password,
                'lastoperate' => time(),
                'lastoperator' => $this->getUserName(),
            ]);

            // 判断更新是否成功
            if (!$result) {
                return $this->error('密码修改失败', "/home/password{$cond}");
            }

            return $this->success('密码修改成功', "/home/password{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('密码修改失败', "/home/password{$cond}");
        }
    }

    /**
     * 显示个人权限项页面
     *
     * @return mixed
     */
    public function myAuthAction()
    {
        try {
            // 获取导航菜单 ID
            $navId = $this->navId;

            // 获取查询条件组合
            $cond = "/navid/{$navId}";

            // 获取用户 ID
            $uid = $this->getUserId();

            // 获取用户信息
            $user = User::findFirst($uid);

            // 判断用户信息是否存在
            if (!$user) {
                return $this->error('用户不存在', '/');
            }

            $user = $user->toArray();

            // 获取权限类型
            $type = (int)$this->getParam('type');

            // 传递权限类型
            $this->view->type = $type;
            $this->view->frontType = Authitem::FRONT_AUTH_TYPE;
            $this->view->adminType = Authitem::ADMIN_AUTH_TYPE;
            $this->view->weiXinType = Authitem::WEIXIN_AUTH_TYPE;

            // 获取权限项
            $items = Authitem::find([
                'conditions' => 'enabled=' . Authitem::ENABLE_BLOG_AUTHITEM . " and type={$type}" . ' order by displayorder desc, id desc',
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

            // 是否显示权限相关操作按钮
            $this->view->isAuthBut = $this->hasAuth(['blog.boss.my.myauth.AUTH']);

            return $this->view->pick('home/myauth');
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户权限项页面加载失败', '/');
        }
    }

    /**
     * 设置用户权限项信息
     *
     * @return mixed
     */
    public function sauthAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取权限类型
        $type = (int)$this->getParam('type');

        // 获取查询条件组合
        $cond = "/navid/{$navId}/type/{$type}";

        try {
            // 获取用户 ID
            $userId = (int)$this->getParam('uid');

            // 获取权限 ID
            $authId = (int)$this->getParam('authid');

            // 获取权限配置
            $item = $this->getParam('item');

            $item = trim($item, ',');

            // 判断权限项是否为空
            if (!$item) {
                return $this->error('用户权限项配置不能为空', "/home/myauth{$cond}");
            }

            // 获取用户信息
            $user = User::findFirst($userId);

            // 判断角色信息是否存在
            if (!$user) {
                return $this->error('用户信息不存在', '/');
            }

            // 获取权限项信息
            $authItem = Authitem::findFirst($authId);

            // 判断权限项信息是否存在
            if (!$authItem) {
                return $this->error('权限项信息不存在', "/home/myauth{$cond}");
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
                    return $this->error('用户权限项设置失败', "/home/myauth{$cond}");
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
                    return $this->error('用户权限项设置失败', "/home/myauth{$cond}");
                }
            }

            return $this->success('用户权限项设置成功', "/home/myauth{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户权限项设置失败', "/home/myauth{$cond}");
        }
    }

    /**
     * 撤销用户权限项信息
     *
     * @return mixed
     */
    public function dauthAction()
    {
        // 获取导航菜单 ID
        $navId = $this->navId;

        // 获取权限类型
        $type = (int)$this->getParam('type');

        // 获取查询条件组合
        $cond = "/navid/{$navId}/type/{$type}";

        try {
            // 获取用户 ID
            $userId = (int)$this->getParam('uid');

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
                        return $this->error('请先撤销该子权限项关联的用户配置', "/home/myauth{$cond}");
                    }
                }

                $result = UserAuthitem::findFirst($userAuthitem->id)->delete();

                // 判断删除是否成功
                if (!$result) {
                    return $this->error('用户权限项配置撤销失败', "/home/myauth{$cond}");
                }
            }

            return $this->success('用户权限项撤销成功', "/home/myauth{$cond}");
        } catch (Exception $e) {
            // 记录错误日志信息
            Log::error($e);

            // 返回错误提示信息
            return $this->error('用户权限项撤销失败', "/home/myauth{$cond}");
        }
    }
}