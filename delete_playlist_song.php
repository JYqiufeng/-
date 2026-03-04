<?php
include('config/db.php');

if (!isset($_SESSION['user_id'])) {
    exit('请先登录');
}

$ps_id = isset($_POST['ps_id']) ? (int)$_POST['ps_id'] : 0;
if ($ps_id === 0) {
    exit('参数错误');
}

try {
    $stmt = $pdo->prepare("DELETE FROM playlist_songs WHERE ps_id = ?");
    $stmt->execute([$ps_id]);
    echo '删除成功';
} catch (PDOException $e) {
    echo '删除失败：' . $e->getMessage();
}
?>