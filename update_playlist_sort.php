<?php
include('config/db.php');

if (!isset($_SESSION['user_id'])) {
    exit('请先登录');
}

$sortData = json_decode(file_get_contents('php://input'), true);
if (!is_array($sortData)) {
    exit('参数错误');
}

try {
    $pdo->beginTransaction();
    foreach ($sortData as $item) {
        $ps_id = (int)$item['ps_id'];
        $sort = (int)$item['sort'];
        $stmt = $pdo->prepare("UPDATE playlist_songs SET sort = ? WHERE ps_id = ?");
        $stmt->execute([$sort, $ps_id]);
    }
    $pdo->commit();
    echo '排序更新成功';
} catch (PDOException $e) {
    $pdo->rollBack();
    echo '排序更新失败：' . $e->getMessage();
}
?>