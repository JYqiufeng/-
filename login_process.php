<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once 'config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['login_error'] = '非法请求方式！';
    header('Location: login.php');
    exit;
}

// 获取表单数据
$username = trim($_POST['username']);
$password = trim($_POST['password']);

// 输入验证
if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = '用户名和密码不能为空！';
    header('Location: login.php');
    exit;
}

// 验证用户信息
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['login_error'] = '用户名或密码错误！';
        header('Location: login.php');
        exit;
    }

    // 登录成功：保存用户信息到会话
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['is_login'] = true;

    // 跳转首页
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    // 异常处理
    $_SESSION['login_error'] = '登录失败：' . $e->getMessage();
    header('Location: login.php');
    exit;
}
?>