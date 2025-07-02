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

$profile = null;
try {
    $stmt = $pdo->prepare("SELECT resume_title, contact_email, phone_number, introduction FROM resume_user_profile WHERE user_id = :user_id LIMIT 1");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        $error_message = "이력서 프로필을 찾을 수 없습니다. 먼저 이력서를 생성해주세요.";
    }
} catch (PDOException $e) {
    $error_message = "프로필 정보를 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
    $profile = ['resume_title' => '', 'contact_email' => '', 'phone_number' => '', 'introduction' => ''];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $profile) {
    $resume_title = trim($_POST['resume_title']);
    $contact_email = trim($_POST['contact_email']);
    $phone_number = trim($_POST['phone_number']);
    $introduction = trim($_POST['introduction']);

    if (empty($resume_title)) {
        $error_message = "이력서 제목을 입력해주세요.";
    } elseif (empty($contact_email)) {
        $error_message = "연락 이메일을 입력해주세요.";
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "유효한 이메일 주소를 입력해주세요.";
    } elseif (empty($introduction)) {
        $error_message = "자기소개를 입력해주세요.";
    }

    if (empty($error_message)) {
        try {
            $update_stmt = $pdo->prepare("UPDATE resume_user_profile SET resume_title = :resume_title, contact_email = :contact_email, phone_number = :phone_number, introduction = :introduction, updated_at = NOW() WHERE user_id = :user_id");
            $update_stmt->bindParam(':resume_title', $resume_title, PDO::PARAM_STR);
            $update_stmt->bindParam(':contact_email', $contact_email, PDO::PARAM_STR);
            $update_stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
            $update_stmt->bindParam(':introduction', $introduction, PDO::PARAM_STR);
            $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($update_stmt->execute()) {
                $success_message = "프로필 정보가 성공적으로 업데이트되었습니다.";
                $profile['resume_title'] = $resume_title;
                $profile['contact_email'] = $contact_email;
                $profile['phone_number'] = $phone_number;
                $profile['introduction'] = $introduction;
            } else {
                $error_message = "프로필 정보 업데이트 중 오류가 발생했습니다.";
            }
        } catch (PDOException $e) {
            $error_message = "데이터베이스 오류: " . $e->getMessage();
        }
    } else {
        $profile['resume_title'] = $resume_title;
        $profile['contact_email'] = $contact_email;
        $profile['phone_number'] = $phone_number;
        $profile['introduction'] = $introduction;
    }
}

if (is_null($profile)) {
    $profile = ['resume_title' => '', 'contact_email' => '', 'phone_number' => '', 'introduction' => ''];
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>프로필 편집 - 나의 이력서</title>
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
            margin-left: 280px;
            background-color: #ffffff;
        }
        .form-input, .form-textarea {
            border-color: #e2e8f0;
        }
        .form-input:focus, .form-textarea:focus {
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
                <h1 class="text-3xl font-bold mb-8 text-gray-800">기본 프로필 편집</h1>

                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_message) && !$profile):?>
                     <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                        <p><a href="/resume/create_resume.php" class="font-semibold underline">이력서 생성 페이지로 이동</a></p>
                    </div>
                <?php elseif (!empty($error_message)):?>
                     <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($profile):?>
                <form action="edit_profile.php" method="POST" class="bg-white shadow-xl rounded-lg px-8 pt-6 pb-8 mb-4">
                    <div class="mb-6">
                        <label for="resume_title" class="block text-gray-700 text-sm font-medium mb-2">이력서 제목</label>
                        <input type="text" name="resume_title" id="resume_title" class="form-input shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring" value="<?php echo htmlspecialchars($profile['resume_title']); ?>" placeholder="예: 홍길동의 웹 개발자 이력서" required>
                    </div>
                    <div class="mb-6">
                        <label for="contact_email" class="block text-gray-700 text-sm font-medium mb-2">연락 이메일</label>
                        <input type="email" name="contact_email" id="contact_email" class="form-input shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring" value="<?php echo htmlspecialchars($profile['contact_email']); ?>" required>
                    </div>
                    <div class="mb-6">
                        <label for="phone_number" class="block text-gray-700 text-sm font-medium mb-2">연락처 (선택)</label>
                        <input type="tel" name="phone_number" id="phone_number" class="form-input shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring" value="<?php echo htmlspecialchars($profile['phone_number']); ?>" placeholder="예: 010-1234-5678">
                    </div>
                    <div class="mb-8">
                        <label for="introduction" class="block text-gray-700 text-sm font-medium mb-2">간단한 자기소개 (2-5줄)</label>
                        <textarea name="introduction" id="introduction" rows="4" class="form-textarea shadow-sm appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring" placeholder="자신을 간략하게 소개해주세요." required><?php echo htmlspecialchars($profile['introduction']); ?></textarea>
                    </div>
                    <div class="flex items-center justify-start">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-md focus:outline-none focus:shadow-outline transition-colors">
                            프로필 저장
                        </button>
                        <a href="/resume/index.php" class="ml-4 inline-block align-baseline font-semibold text-sm text-gray-600 hover:text-gray-800">
                            이력서로 돌아가기
                        </a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>