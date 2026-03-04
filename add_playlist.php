<?php
include('config/db.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    exit('请先登录');
}

// 获取通过 POST 传过来的参数
$user_id = $_SESSION['user_id'];
$playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : 0;
$song_id = isset($_POST['song_id']) ? (int)$_POST['song_id'] : 0;

if ($playlist_id > 0 && $song_id > 0) {
    try {
        // 验证
        $stmtOwner = $pdo->prepare("SELECT 1 FROM playlists WHERE playlist_id = ? AND user_id = ?");
        $stmtOwner->execute([$playlist_id, $user_id]);
        
        if (!$stmtOwner->fetch()) {
            exit("错误：播放列表ID [" . $playlist_id . "] 不存在或不属于你");
        }

        // 检查重复
        $stmtCheck = $pdo->prepare("SELECT 1 FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
        $stmtCheck->execute([$playlist_id, $song_id]);

        if ($stmtCheck->fetch()) {
            exit("歌曲已在播放列表中");
        }

        // 添加
        $stmtInsert = $pdo->prepare("INSERT INTO playlist_songs (playlist_id, song_id) VALUES (?, ?)");
        if ($stmtInsert->execute([$playlist_id, $song_id])) {
            echo "添加成功";
        } else {
            echo "添加失败";
        }

    } catch (PDOException $e) {
        echo "数据库错误：" . $e->getMessage();
    }
} else {
    echo "参数错误：请输入有效的播放列表ID";
}
?>