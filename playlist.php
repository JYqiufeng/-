<?php
session_start();

$host = 'localhost';
$dbname = 'music_platform';
$username = 'root';
$password = 'root'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 处理创建播放列表请求
$createError = '';
$createSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['playlist_name'])) {
    $playlistName = trim($_POST['playlist_name']);
    if (empty($playlistName)) {
        $createError = '播放列表名称不能为空！';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO playlists (name, user_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$playlistName, $_SESSION['user_id']]);
            $createSuccess = '播放列表创建成功！';
        } catch (PDOException $e) {
            $createError = '创建失败：' . $e->getMessage();
        }
    }
}

// 处理删除播放
$deleteError = '';
$deleteSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ps_id'])) {
    $psId = (int)$_POST['delete_ps_id'];
    try {
        // 验证是否属于当前用户的播放列表
        $stmt = $pdo->prepare("
            DELETE ps FROM playlist_songs ps
            JOIN playlists p ON ps.playlist_id = p.playlist_id
            WHERE ps.ps_id = ? AND p.user_id = ?
        ");
        $stmt->execute([$psId, $_SESSION['user_id']]);
        if ($stmt->rowCount() > 0) {
            $deleteSuccess = '歌曲已从播放列表移除！';
        } else {
            $deleteError = '无权删除该歌曲！';
        }
    } catch (PDOException $e) {
        $deleteError = '删除失败：' . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT * FROM playlists WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);

$playlistId = isset($_GET['playlist_id']) ? (int)$_GET['playlist_id'] : 0;
$currentPlaylist = null;
$playlistSongs = [];
if ($playlistId > 0) {
    // 验证
    $stmt = $pdo->prepare("SELECT * FROM playlists WHERE playlist_id = ? AND user_id = ?");
    $stmt->execute([$playlistId, $_SESSION['user_id']]);
    $currentPlaylist = $stmt->fetch(PDO::FETCH_ASSOC);

    // 获取播放列表中歌曲
    if ($currentPlaylist) {
        $stmt = $pdo->prepare("
            SELECT ps.ps_id, s.* 
            FROM playlist_songs ps
            JOIN songs s ON ps.song_id = s.song_id
            WHERE ps.playlist_id = ?
            ORDER BY ps.sort ASC
        ");
        $stmt->execute([$playlistId]);
        $playlistSongs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>极简音符- 我的播放列表</title>
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
            padding-bottom: 70px; 
        }
        .container {
            width: 92%;
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: rgba(34, 34, 34, 0.9); 
            color: #fff;
            padding: 16px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: transparent; 
            backdrop-filter: none;
            box-shadow: none;
            padding: 0 20px;
            margin: 0 auto;
            width: 92%;
        }

        .header-actions a {
            color: #ccc;
            text-decoration: none;
            margin-left: 24px;
            font-size: 14px;
        }

        .header-actions a:hover {
            color: #fff;
        }

        /* 页面标题 & 提示样式 */
        .page-title {
            margin: 24px 0;
            color: #333;
            font-size: 20px;
            font-weight: 600;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin: 16px 0;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* 创建播放列表表单样式 */
        .create-playlist {
            margin: 20px 0 30px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.8); 
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .create-playlist h3 {
            color: #333;
            margin-bottom: 16px;
            font-size: 18px;
            font-weight: 600;
        }

        .create-playlist form {
            display: flex;
            gap: 12px;
        }

        .create-playlist input {
            flex: 1;
            padding: 10px 16px;
            font-size: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            outline: none;
            background: rgba(255, 255, 255, 0.9);
        }

        .create-playlist input:focus {
            border-color: #1db954;
        }

        .create-playlist button {
            padding: 10px 24px;
            background-color: #1db954;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            transition: background-color 0.2s;
        }

        .create-playlist button:hover {
            background-color: #1ed760;
        }

        .playlist-list-section {
            margin: 30px 0;
        }

        .playlist-list-section h3 {
            color: #333;
            margin-bottom: 16px;
            font-size: 18px;
            font-weight: 600;
        }

        .playlist-item {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 12px 16px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            cursor: pointer;
            margin-bottom: 8px;
            transition: all 0.2s;
        }

        .playlist-item:hover {
            background-color: #f5f5f5;
            transform: translateX(2px);
        }

        .playlist-item.active {
            border-left: 4px solid #1db954;
            background-color: #f0f8f2;
        }

        .playlist-songs-section {
            margin: 30px 0;
        }

        .playlist-songs-section h3 {
            color: #333;
            margin-bottom: 16px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .song-list-empty {
            text-align: center;
            padding: 40px 0;
            color: #666;
            font-size: 16px;
        }

        .song-list-empty a {
            color: #1db954;
            text-decoration: none;
        }

        .song-list-empty a:hover {
            text-decoration: underline;
        }

        .song-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 16px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 4px;
            margin-bottom: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: background-color 0.2s;
        }

        .song-item:hover {
            background-color: #f8f8f8;
        }

        .song-cover img {
            width: 60px;
            height: 60px;
            border-radius: 4px;
            object-fit: cover;
        }

        .song-info {
            flex: 1;
        }

        .song-title {
            font-size: 16px;
            color: #333;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .song-meta {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }

        .song-actions {
            display: flex;
            gap: 8px;
        }

        .play-song-btn {
            padding: 6px 12px;
            background-color: #1db954;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .play-song-btn:hover {
            background-color: #1ed760;
        }

        .delete-song-btn {
            padding: 6px 12px;
            background-color: #dc3545;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .delete-song-btn:hover {
            background-color: #c82333;
        }

        .player {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(44, 44, 44, 0.95); 
            color: #e0e0e0;
            padding: 10px 20px;
            border-top: 1px solid #444;
            z-index: 9999;
            height: 60px;
            display: flex;
            align-items: center;
        }

        .player .container {
            display: flex;
            align-items: center;
            gap: 20px;
            width: 100%;
            background: transparent;
            backdrop-filter: none;
            box-shadow: none;
            padding: 0;
        }
        #current-song-title {
            min-width: 180px;
            font-size: 14px;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .core-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        .play-mode {
            position: relative;
        }

        .mode-toggle {
            background-color: #3a3a3a;
            border: none;
            color: #e0e0e0;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            height: 30px;
            transition: background-color 0.2s;
        }

        .mode-toggle::before {
            content: "⊟";
            font-size: 10px;
            color: #888;
        }

        .mode-dropdown {
            position: absolute;
            bottom: 100%;
            left: 0;
            background-color: #3a3a3a;
            border-radius: 4px;
            list-style: none;
            padding: 6px 0;
            margin: 5px 0 0 0;
            width: 120px;
            display: none;
            z-index: 10000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .mode-dropdown li {
            padding: 6px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .mode-dropdown li::before {
            content: "⊠";
            font-size: 10px;
            color: #888;
        }

        .play-mode:hover .mode-dropdown {
            display: block;
        }

        /* 按钮*/
        .play-controls-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .control-btn {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: none;
            background-color: #3a3a3a;
            color: #e0e0e0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            transition: background-color 0.2s;
        }

        .play-btn {
            width: 26px;
            height: 26px;
            background-color: #1db954;
            color: #fff;
            font-size: 12px;
        }

        .control-btn:hover {
            background-color: #484848;
        }

        .play-btn:hover {
            background-color: #1ed760;
        }

        /* 进度条 */
        .progress-area {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .progress-bar {
            flex: 1;
            height: 3px;
            background-color: #444;
            border-radius: 2px;
            cursor: pointer;
            position: relative;
            transition: background-color 0.2s;
        }

        .progress-bar:hover {
            background-color: #555;
        }

        .progress-fill {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background-color: #1db954;
            border-radius: 2px;
            width: 0%;
            transition: width 0.1s linear;
        }

        .time-text {
            font-size: 12px;
            color: #999;
            min-width: 40px;
            text-align: center;
        }

        /* 音量 */
        .volume-control {
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 100px;
        }

        .volume-bar {
            width: 50px;
            height: 3px;
            background-color: #444;
            border-radius: 2px;
            cursor: pointer;
            position: relative;
        }

        .volume-fill {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background-color: #1db954;
            border-radius: 2px;
            width: 80%;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>极简音符- 我的播放列表</h1>
            <div class="header-actions">
                <a href="index.php">首页</a>
                <a href="logout.php">登出 (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($createSuccess || $deleteSuccess): ?>
            <div class="alert alert-success">
                <?php echo $createSuccess ?: $deleteSuccess; ?>
            </div>
        <?php endif; ?>
        <?php if ($createError || $deleteError): ?>
            <div class="alert alert-error">
                <?php echo $createError ?: $deleteError; ?>
            </div>
        <?php endif; ?>

        <!-- 创建播放列表 -->
        <div class="create-playlist">
            <h3>创建新播放列表</h3>
            <form method="POST" action="playlist.php">
                <input 
                    type="text" 
                    name="playlist_name" 
                    placeholder="输入播放列表名称（如：我的宝藏歌单）..." 
                    required
                >
                <button type="submit">创建</button>
            </form>
        </div>

        <!-- 我的播放列表 -->
        <div class="playlist-list-section">
            <h3>我的播放列表</h3>
            <?php if (empty($playlists)): ?>
                <p style="color: #666; font-size: 16px;">暂无播放列表，先创建一个吧！</p>
            <?php else: ?>
                <?php foreach ($playlists as $pl): ?>
                    <div class="playlist-item <?php echo $pl['playlist_id'] == $playlistId ? 'active' : ''; ?>" 
                         onclick="location.href='playlist.php?playlist_id=<?php echo $pl['playlist_id']; ?>'">
                        <?php echo htmlspecialchars($pl['name']); ?>
                        <span style="color: #999; font-size: 12px; margin-left: 8px;">
                            创建于：<?php echo date('Y-m-d', strtotime($pl['created_at'])); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($currentPlaylist): ?>
            <div class="playlist-songs-section">
                <h3>
                    🎵 <?php echo htmlspecialchars($currentPlaylist['name']); ?> 
                    <span style="font-size: 14px; font-weight: normal; color: #666;">
                        （共 <?php echo count($playlistSongs); ?> 首歌曲）
                    </span>
                </h3>
                
                <?php if (empty($playlistSongs)): ?>
                    <div class="song-list-empty">
                        该播放列表暂无歌曲<br>
                        <a href="index.php" style="margin-top: 8px; display: inline-block;">去首页添加歌曲</a>
                    </div>
                <?php else: ?>
                    <div id="song-list-container">
                        <?php foreach ($playlistSongs as $song): ?>
                            <div class="song-item" data-ps-id="<?php echo $song['ps_id']; ?>">
                                <div class="song-cover">
                                    <img 
                                        src="<?php echo !empty($song['cover_path']) ? htmlspecialchars($song['cover_path']) : 'assets/images/default-cover.png'; ?>" 
                                        alt="<?php echo htmlspecialchars($song['title']); ?>"
                                    >
                                </div>
                                <div class="song-info">
                                    <h4 class="song-title"><?php echo htmlspecialchars($song['title']); ?></h4>
                                    <div class="song-meta">
                                        歌手：<?php echo htmlspecialchars($song['artist']); ?><br>
                                        专辑：<?php echo htmlspecialchars($song['album']); ?> | 时长：<?php echo htmlspecialchars($song['duration']); ?>
                                    </div>
                                </div>
                                <div class="song-actions">
                                    <button 
                                        class="play-song-btn"
                                        data-song-id="<?php echo $song['song_id']; ?>"
                                        data-song-title="<?php echo htmlspecialchars($song['title']); ?>"
                                        data-song-path="<?php echo htmlspecialchars($song['file_path']); ?>"
                                    >
                                        播放
                                    </button>
                                    <form method="POST" action="playlist.php?playlist_id=<?php echo $playlistId; ?>" style="display: inline;">
                                        <input type="hidden" name="delete_ps_id" value="<?php echo $song['ps_id']; ?>">
                                        <button type="submit" class="delete-song-btn" onclick="return confirm('确定删除这首歌曲吗？删除后不可恢复！')">
                                            删除
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 播放器 -->
    <div class="player">
        <div class="container">
            <div id="current-song-title">未选择歌曲</div>

            <div class="core-controls">
                <div class="play-mode">
                    <button class="mode-toggle" id="current-mode-btn">顺序播放</button>
                    <ul class="mode-dropdown" id="mode-list">
                        <li data-mode="random">随机播放</li>
                        <li data-mode="order">顺序播放</li>
                        <li data-mode="single">单曲循环</li>
                        <li data-mode="list">列表循环</li>
                    </ul>
                </div>

                <!-- 按钮-->
                <div class="play-controls-group">
                    <button class="control-btn" id="prev-btn">◀◀</button>
                    <button class="control-btn play-btn" id="play-btn">▶</button>
                    <button class="control-btn" id="next-btn">▶▶</button>
                </div>

                <!-- 进度条 -->
                <div class="progress-area">
                    <span class="time-text" id="current-time">00:00</span>
                    <div class="progress-bar" id="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <span class="time-text" id="total-time">00:00</span>
                </div>
                <!-- 音量 -->
                <div class="volume-control">
                    <span>🔊</span>
                    <div class="volume-bar" id="volume-bar">
                        <div class="volume-fill" id="volume-fill"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const audio = new Audio();
        let currentPlayMode = 'order';
        let isPlaying = false;
        let playQueue = [];
        let currentQueueIndex = 0;
        let currentSong = null;

        // DOM元素获取
        const dom = {
            currentModeBtn: document.getElementById('current-mode-btn'),
            modeList: document.getElementById('mode-list'),
            playBtn: document.getElementById('play-btn'),
            prevBtn: document.getElementById('prev-btn'),
            nextBtn: document.getElementById('next-btn'),
            progressBar: document.getElementById('progress-bar'),
            progressFill: document.getElementById('progress-fill'),
            currentTimeEl: document.getElementById('current-time'),
            totalTimeEl: document.getElementById('total-time'),
            currentSongTitle: document.getElementById('current-song-title'),
            volumeBar: document.getElementById('volume-bar'),
            volumeFill: document.getElementById('volume-fill'),
            playSongBtns: document.querySelectorAll('.play-song-btn')
        };

        // 初始化队列
        function initPlayQueue() {
            playQueue = [];
            dom.playSongBtns.forEach(btn => {
                playQueue.push({
                    id: btn.dataset.songId,
                    title: btn.dataset.songTitle,
                    path: btn.dataset.songPath
                });
            });
        }

        // 播放器
        function initPlayer() {
            initPlayQueue();

            // 播放模式切换
            dom.modeList.addEventListener('click', (e) => {
                if (e.target.tagName === 'LI') {
                    currentPlayMode = e.target.dataset.mode;
                    dom.currentModeBtn.textContent = e.target.textContent;
                }
            });

            // 播放/暂停切换
            dom.playBtn.addEventListener('click', togglePlay);

            dom.prevBtn.addEventListener('click', playPrevSong);
            dom.nextBtn.addEventListener('click', playNextSong);

            // 进度条点击调整播放进度
            dom.progressBar.addEventListener('click', setProgress);

            // 音量调节
            dom.volumeBar.addEventListener('click', setVolume);

            // 更新进度条
            audio.addEventListener('timeupdate', updateProgress);
            audio.addEventListener('loadedmetadata', () => {
                dom.totalTimeEl.textContent = formatTime(audio.duration);
            });
            audio.addEventListener('ended', handleSongEnd);

            // 绑定歌单内歌曲播放按钮
            dom.playSongBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const songId = btn.dataset.songId;
                    currentQueueIndex = playQueue.findIndex(song => song.id === songId);
                    loadSong(playQueue[currentQueueIndex]);
                    playSong();
                });
            });
            audio.volume = 0.8;
        }

        function loadSong(song) {
            if (!song) return;
            currentSong = song;
            audio.src = song.path;
            dom.currentSongTitle.textContent = song.title;
            dom.progressFill.style.width = '0%';
            dom.currentTimeEl.textContent = '00:00';
        }

        // 播放歌曲
        function playSong() {
            if (!currentSong && playQueue.length > 0) {
                currentQueueIndex = 0;
                loadSong(playQueue[0]);
            }
            audio.play();
            isPlaying = true;
            dom.playBtn.textContent = '❚❚';
        }

        // 暂停歌曲
        function pauseSong() {
            audio.pause();
            isPlaying = false;
            dom.playBtn.textContent = '▶';
        }

        // 播放/暂停切换
        function togglePlay() {
            isPlaying ? pauseSong() : playSong();
        }

        // 上一曲
        function playPrevSong() {
            if (playQueue.length === 0) return;
            switch (currentPlayMode) {
                case 'random':
                    currentQueueIndex = Math.floor(Math.random() * playQueue.length);
                    break;
                default:
                    currentQueueIndex = (currentQueueIndex - 1 + playQueue.length) % playQueue.length;
                    break;
            }
            loadSong(playQueue[currentQueueIndex]);
            if (isPlaying) audio.play();
        }

        // 下一曲
        function playNextSong() {
            if (playQueue.length === 0) return;
            switch (currentPlayMode) {
                case 'random':
                    currentQueueIndex = Math.floor(Math.random() * playQueue.length);
                    break;
                case 'single':
                    audio.currentTime = 0;
                    audio.play();
                    return;
                default:
                    currentQueueIndex = (currentQueueIndex + 1) % playQueue.length;
                    break;
            }
            loadSong(playQueue[currentQueueIndex]);
            if (isPlaying) audio.play();
        }

        // 点击进度条调整播放进度
        function setProgress(e) {
            if (!currentSong) return;
            const barWidth = dom.progressBar.clientWidth;
            const clickX = e.offsetX;
            const progressRatio = clickX / barWidth;
            const targetTime = progressRatio * audio.duration;
            
            audio.currentTime = targetTime;
            dom.progressFill.style.width = `${progressRatio * 100}%`;
            dom.currentTimeEl.textContent = formatTime(targetTime);
        }

        // 更新进度条和时间
        function updateProgress() {
            if (isNaN(audio.duration) || !currentSong) return;
            const progressRatio = audio.currentTime / audio.duration;
            dom.progressFill.style.width = `${progressRatio * 100}%`;
            dom.currentTimeEl.textContent = formatTime(audio.currentTime);
        }

        // 调节音量
        function setVolume(e) {
            const barWidth = dom.volumeBar.clientWidth;
            const clickX = e.offsetX;
            const volumeRatio = clickX / barWidth;
            audio.volume = volumeRatio;
            dom.volumeFill.style.width = `${volumeRatio * 100}%`;
        }

        // 结束
        function handleSongEnd() {
            playNextSong();
        }

        // 时间
        function formatTime(seconds) {
            if (isNaN(seconds)) return '00:00';
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // 初始化
        window.addEventListener('DOMContentLoaded', initPlayer);
    </script>
</body>
</html>