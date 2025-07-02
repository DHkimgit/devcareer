<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
    header('Location: /login/index.php');
    exit;
} else {
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['user_id'];
        $_SESSION['username'] = isset($_COOKIE['username']) ? $_COOKIE['username'] : 'User';
        $_SESSION['email'] = isset($_COOKIE['email']) ? $_COOKIE['email'] : '';
    }
    $user_id = $_SESSION['user_id'];
    $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '사용자';
    $user_email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '';
}

$pdo = getPDO();

$stmt_check = $pdo->prepare("SELECT id FROM resume_user_profile WHERE user_id = :user_id LIMIT 1");
$stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_check->execute();
if ($stmt_check->fetch()) {
    header('Location: /resume/index.php');
    exit;
}

$resume_title = '';
$contact_email = $user_email;
$phone_number = '';
$introduction = '';
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $resume_title = trim($_POST['resume_title']);
    $contact_email = trim($_POST['contact_email']);
    $phone_number = trim($_POST['phone_number']);
    $introduction = trim($_POST['introduction']);

    if (empty($resume_title)) {
        $errors[] = "이력서 제목을 입력해주세요.";
    }
    if (empty($contact_email)) {
        $errors[] = "연락 이메일을 입력해주세요.";
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "유효한 이메일 주소를 입력해주세요.";
    }
    if (empty($introduction)) {
        $errors[] = "자기소개를 입력해주세요.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO resume_user_profile (user_id, resume_title, contact_email, phone_number, introduction) VALUES (:user_id, :resume_title, :contact_email, :phone_number, :introduction)");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':resume_title', $resume_title, PDO::PARAM_STR);
            $stmt->bindParam(':contact_email', $contact_email, PDO::PARAM_STR);
            $stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
            $stmt->bindParam(':introduction', $introduction, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                header('Location: /resume/index.php');
                exit;
            } else {
                $errors[] = "이력서 생성 중 오류가 발생했습니다. 다시 시도해주세요.";
            }
        } catch (PDOException $e) {
            $errors[] = "이력서 생성 오류가 발생했습니다.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 이력서 작성</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; background-color: #f7f7fb; }
        .navbar { background-color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; }
        .main-container { padding-top: 80px; display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 80px); }
        .form-card { background-color: white; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); width: 100%; max-width: 600px; }
        .form-title { font-size: 1.75rem; font-weight: 600; color: #333; margin-bottom: 1.5rem; text-align: center; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #4A5568; }
        .form-input, .form-textarea { width: 100%; padding: 0.75rem; border: 1px solid #CBD5E0; border-radius: 0.375rem; box-sizing: border-box; }
        .form-input:focus, .form-textarea:focus { border-color: #5F43FF; outline: none; box-shadow: 0 0 0 2px rgba(95, 67, 255, 0.2); }
        .form-textarea { min-height: 100px; resize: vertical; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 0.375rem; font-weight: 600; transition: background-color 0.3s ease; text-decoration: none; display: inline-block; font-size: 1rem; cursor: pointer; border: none; }
        .btn-primary { background-color: #5F43FF; color: white; }
        .btn-primary:hover { background-color: #8243FF; }
        .error-messages { background-color: #FED7D7; color: #C53030; border: 1px solid #FC8181; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem; }
        .error-messages ul { margin: 0; padding-left: 1.25rem; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 'pretendard': ['Pretendard', 'sans-serif'], },
                    colors: { 'primary': '#5F43FF', 'primary-hover': '#8243FF', }
                }
            }
        }
    </script>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand ml-4">devcareer</div>
        <div class="mr-4">
            <a href="/resume/index.php" class="text-gray-600 hover:text-primary">이력서 목록으로</a>
        </div>
    </div>

    <div class="main-container">
        <div class="form-card">
            <h1 class="form-title">새 이력서 작성</h1>

            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <p>다음 오류를 수정해주세요:</p>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/resume/create_resume.php" method="POST">
                <div class="form-group">
                    <label for="resume_title" class="form-label">이력서 제목</label>
                    <input type="text" id="resume_title" name="resume_title" class="form-input" value="<?php echo htmlspecialchars($resume_title); ?>" placeholder="예: 홍길동의 웹 개발자 이력서" required>
                </div>
                <div class="form-group">
                    <label for="contact_email" class="form-label">연락 이메일</label>
                    <input type="email" id="contact_email" name="contact_email" class="form-input" value="<?php echo htmlspecialchars($contact_email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone_number" class="form-label">연락처 (선택)</label>
                    <input type="tel" id="phone_number" name="phone_number" class="form-input" value="<?php echo htmlspecialchars($phone_number); ?>" placeholder="예: 010-1234-5678">
                </div>
                <div class="form-group">
                    <label for="introduction" class="form-label">간단한 자기소개 (2-5줄)</label>
                    <textarea id="introduction" name="introduction" class="form-textarea" placeholder="자신을 간략하게 소개해주세요." required><?php echo htmlspecialchars($introduction); ?></textarea>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">이력서 생성</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>