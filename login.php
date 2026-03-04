<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once 'config/db.php'; 
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>极简音符 - 用户登录</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Microsoft Yahei", "PingFang SC", sans-serif;
        }
        body {
            min-height: 100vh;
            background: url('./assets/images/登录背景.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 40px 32px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* 标题 */
        .login-title {
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 32px;
        }

        /* 提示信息 */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        /* 表单组 */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            color: #4a5568;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1db954; 
            box-shadow: 0 0 0 3px rgba(29, 185, 84, 0.2);
            background: #fff;
        }

        /* 登录按钮*/
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1db954 0%, #1ed760 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(29, 185, 84, 0.4);
        }
        .login-btn:active {
            transform: translateY(0);
        }

        /* 注册链接 */
        .register-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: #718096;
        }
        .register-link a {
            color: #1db954;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .register-link a:hover {
            color: #1ed760;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }
            .login-title {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="login-title">极简音符 - 用户登录</h2>
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?></div>
        <?php endif; ?>

        <form action="login_process.php" method="POST">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" placeholder="请输入用户名" required>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" placeholder="请输入密码" required>
            </div>
            <button type="submit" class="login-btn">登录</button>
        </form>
        
        <!-- 注册跳转 -->
        <div class="register-link">
            没有账号？<a href="register.php">立即注册</a>
        </div>
    </div>
</body>
</html>