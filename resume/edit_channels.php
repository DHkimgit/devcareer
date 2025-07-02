<?php
session_start();
require_once __DIR__ . '/../components/sidebar/sidebar.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
    header('Location: /login/index.php');
    exit;
} else {
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['user_id'];
        $_SESSION['username'] = isset($_COOKIE['username']) ? $_COOKIE['username'] : 'User';
    }
    $user_id = $_SESSION['user_id'];
}

$pdo = getPDO();
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add_channel') {
                $platform_name = trim($_POST['platform_name']);
                $url = trim($_POST['url']);

                if (empty($platform_name) || empty($url)) {
                    $error_message = "플랫폼 이름과 URL을 모두 입력해주세요.";
                } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                    $error_message = "유효한 URL을 입력해주세요.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO resume_user_channel (user_id, platform_name, url) VALUES (:user_id, :platform_name, :url)");
                    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindParam(':platform_name', $platform_name, PDO::PARAM_STR);
                    $stmt->bindParam(':url', $url, PDO::PARAM_STR);
                    if ($stmt->execute()) {
                        $success_message = "채널이 성공적으로 추가되었습니다.";
                    } else {
                        $error_message = "채널 추가 중 오류가 발생했습니다.";
                    }
                }
            } elseif ($_POST['action'] === 'delete_channel' && isset($_POST['channel_id'])) {
                $channel_id_to_delete = intval($_POST['channel_id']);
                $stmt = $pdo->prepare("DELETE FROM resume_user_channel WHERE id = :channel_id AND user_id = :user_id");
                $stmt->bindParam(':channel_id', $channel_id_to_delete, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                if ($stmt->execute()) {
                    if ($stmt->rowCount() > 0) {
                        $success_message = "채널이 성공적으로 삭제되었습니다.";
                    } else {
                        $error_message = "채널을 삭제할 수 없거나 이미 삭제된 채널입니다.";
                    }
                } else {
                    $error_message = "채널 삭제 중 오류가 발생했습니다.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "데이터베이스 오류: " . $e->getMessage();
        }
    }
}

$current_channels = [];
try {
    $stmt_channels = $pdo->prepare("SELECT id, platform_name, url FROM resume_user_channel WHERE user_id = :user_id ORDER BY platform_name");
    $stmt_channels->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_channels->execute();
    $current_channels = $stmt_channels->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "채널 정보를 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>채널 관리 - 나의 이력서</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { 
            font-family: 'Pretendard', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f7fb;
        }
        .content-wrapper { 
            display: flex; 
        }
        .main-content { 
            flex-grow: 1; 
            padding: 2rem; 
            margin-left: 280px; /* 사이드바 너비 */
            background-color: #ffffff;
        }
        .form-input, .form-select {
            border-color: #e2e8f0;
        }
        .form-input:focus, .form-select:focus {
            border-color: #5F43FF;
            box-shadow: 0 0 0 2px rgba(95, 67, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <?php display_sidebar('/resume/index.php');?>
        <main class="main-content">
            <div class="container mx-auto px-4 py-8">
                <h1 class="text-3xl font-bold mb-8 text-gray-800">채널 관리</h1>

                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                <?php endif; ?>

                <!-- 새 채널 추가 폼 -->
                <div class="bg-white shadow-lg rounded-lg px-8 pt-6 pb-8 mb-10">
                    <h2 class="text-xl font-semibold text-gray-700 mb-6">새 채널 추가</h2>
                    <form action="edit_channels.php" method="POST">
                        <input type="hidden" name="action" value="add_channel">
                        <div class="mb-4">
                            <label for="platform_name" class="block text-gray-700 text-sm font-medium mb-2">플랫폼 이름</label>
                            <input type="text" name="platform_name" id="platform_name" class="form-input shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring" placeholder="예: GitHub, Blog, LinkedIn" required>
                        </div>
                        <div class="mb-6">
                            <label for="url" class="block text-gray-700 text-sm font-medium mb-2">URL</label>
                            <input type="url" name="url" id="url" class="form-input shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring" placeholder="https://example.com/username" required>
                        </div>
                        <div class="flex items-center justify-start">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition-colors">
                                채널 추가
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 기존 채널 목록 -->
                <div class="bg-white shadow-lg rounded-lg px-8 pt-6 pb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-6">등록된 채널 목록</h2>
                    <?php if (empty($current_channels)): ?>
                        <p class="text-gray-600">등록된 채널이 없습니다.</p>
                    <?php else: ?>
                        <ul class="space-y-4">
                            <?php foreach ($current_channels as $channel): ?>
                                <li class="flex items-center justify-between p-4 border border-gray-200 rounded-md hover:bg-gray-50">
                                    <div>
                                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($channel['platform_name']); ?>:</span>
                                        <a href="<?php echo htmlspecialchars($channel['url']); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 hover:underline ml-2 break-all">
                                            <?php echo htmlspecialchars($channel['url']); ?>
                                        </a>
                                    </div>
                                    <form action="edit_channels.php" method="POST" onsubmit="return confirm('정말로 이 채널을 삭제하시겠습니까?');">
                                        <input type="hidden" name="action" value="delete_channel">
                                        <input type="hidden" name="channel_id" value="<?php echo $channel['id']; ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 font-semibold py-1 px-3 rounded-md text-sm transition-colors">
                                            삭제
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                 <div class="mt-8 text-center">
                    <a href="/resume/index.php" class="inline-block bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-6 rounded-md focus:outline-none focus:shadow-outline transition-colors">
                        이력서로 돌아가기
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>