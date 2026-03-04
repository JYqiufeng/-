<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once 'config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['register_error'] = '非法请求方式！';
    header('Location: register.php');
    exit;
}

$username = trim($_POST['username']);
$password = trim($_POST['password']);
$confirm_pwd = trim($_POST['confirm_pwd']);

if (empty($username) || empty($password) || empty($confirm_pwd)) {
    $_SESSION['register_error'] = '所有字段不能为空！';
    header('Location: register.php');
    exit;
}
if (strlen($password) < 6) {
    $_SESSION['register_error'] = '密码长度不能少于6位！';
    header('Location: register.php');
    exit;
}
if ($password !== $confirm_pwd) {
    $_SESSION['register_error'] = '两次输入的密码不一致！';
    header('Location: register.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['register_error'] = '用户名已存在，请更换！';
        header('Location: register.php');
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO users (username, password, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$username, $password]);
    $_SESSION['register_success'] = '注册成功！请使用新账号登录';
    header('Location: register.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['register_error'] = '注册失败：' . $e->getMessage();
    header('Location: register.php');
    exit;
}
?>