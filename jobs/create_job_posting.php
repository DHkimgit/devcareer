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

$errors = [];
$company_name = '';
$job_title = '';
$required_skills = '';
$preferred_skills = '';
$hiring_process = '';
$deadline_or_period = '';
$coding_test_info = '';
$posting_url = '';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $job_title = trim($_POST['job_title'] ?? '');
    $required_skills = trim($_POST['required_skills'] ?? '');
    $preferred_skills = trim($_POST['preferred_skills'] ?? '');
    $hiring_process = trim($_POST['hiring_process'] ?? '');
    $deadline_or_period = trim($_POST['deadline_or_period'] ?? '');
    $coding_test_info = trim($_POST['coding_test_info'] ?? '');
    $posting_url = trim($_POST['posting_url'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($company_name)) {
        $errors[] = "회사명은 필수 항목입니다.";
    }
    if (!empty($posting_url) && !filter_var($posting_url, FILTER_VALIDATE_URL)) {
        $errors[] = "유효한 공고 URL을 입력해주세요.";
    }

    if (empty($errors)) {
        try {
            $pdo = getPDO();
            $sql = "INSERT INTO job_posting (user_id, company_name, job_title, required_skills, preferred_skills, hiring_process, deadline_or_period, coding_test_info, posting_url, notes, orgainzation_id) 
                    VALUES (:user_id, :company_name, :job_title, :required_skills, :preferred_skills, :hiring_process, :deadline_or_period, :coding_test_info, :posting_url, :notes, NULL)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':company_name', $company_name, PDO::PARAM_STR);
            $stmt->bindParam(':job_title', $job_title, PDO::PARAM_STR);
            $stmt->bindParam(':required_skills', $required_skills, PDO::PARAM_STR);
            $stmt->bindParam(':preferred_skills', $preferred_skills, PDO::PARAM_STR);
            $stmt->bindParam(':hiring_process', $hiring_process, PDO::PARAM_STR);
            $stmt->bindParam(':deadline_or_period', $deadline_or_period, PDO::PARAM_STR);
            $stmt->bindParam(':coding_test_info', $coding_test_info, PDO::PARAM_STR);
            $stmt->bindParam(':posting_url', $posting_url, PDO::PARAM_STR);
            $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);

            if ($stmt->execute()) {
                header('Location: /jobs/index.php?status=success');
                exit;
            } else {
                $errors[] = "채용 공고 등록에 실패했습니다. 다시 시도해주세요.";
            }
        } catch (PDOException $e) {
            $errors[] = "데이터베이스 오류: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 채용 공고 등록</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { margin-left: 260px; padding: 20px; width: calc(100% - 260px); padding-top: 100px; }
        .navbar { background-color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; }
        .navbar-user { font-size: 0.9rem; color: #505050; }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            margin-top: 0.25rem;
        }
        .form-textarea {
            min-height: 100px;
        }
        .form-label {
            font-weight: 600;
            color: #374151;
        }
        .btn { padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; transition: background-color 0.3s ease; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #5F43FF; color: white; }
        .btn-primary:hover { background-color: #8243FF; }
        .btn-secondary { background-color: #6B7280; color: white; }
        .btn-secondary:hover { background-color: #4B5563; }
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
        <div class="flex items-center">
            <div class="navbar-brand">devcareer</div>
            <span class="ml-[106px] mx-4 text-gray-400">|</span>
            <a href="/main/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">홈</a>
            <a href="/jobs/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">채용 공고</a>
        </div>
        <div class="navbar-user">
            <div class="font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#808080] flex items-center">
            <?php if (isset($_SESSION['email'])): ?>
                <span><?php echo htmlspecialchars($_SESSION['email']); ?> 님 환영합니다.</span>
                <a href="/login/logout.php" title="로그아웃" class="ml-4 text-gray-600 hover:text-primary">로그아웃 아이콘</a>
            <?php else: ?>
                <a href="/login/index.php" class="text-primary hover:text-primary-hover">로그인</a>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <?php display_sidebar('/jobs/index.php'); ?>
        <div class="main-content">
            <h1 class="text-2xl font-bold text-primary mb-6">새 채용 공고 등록</h1>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">오류 발생:</strong>
                    <ul class="mt-2 list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/jobs/create_job_posting.php" method="POST" class="bg-white p-8 rounded-lg shadow-md space-y-6">
                <div>
                    <label for="company_name" class="form-label">회사명 <span class="text-red-500">*</span></label>
                    <input type="text" id="company_name" name="company_name" class="form-input" value="<?php echo htmlspecialchars($company_name); ?>" required>
                </div>

                <div>
                    <label for="job_title" class="form-label">채용 직군</label>
                    <input type="text" id="job_title" name="job_title" class="form-input" value="<?php echo htmlspecialchars($job_title); ?>">
                </div>

                <div>
                    <label for="required_skills" class="form-label">기술 스택 (필수)</label>
                    <textarea id="required_skills" name="required_skills" class="form-input form-textarea"><?php echo htmlspecialchars($required_skills); ?></textarea>
                </div>

                <div>
                    <label for="preferred_skills" class="form-label">기술 스택 (우대)</label>
                    <textarea id="preferred_skills" name="preferred_skills" class="form-input form-textarea"><?php echo htmlspecialchars($preferred_skills); ?></textarea>
                </div>

                <div>
                    <label for="hiring_process" class="form-label">채용 과정</label>
                    <textarea id="hiring_process" name="hiring_process" class="form-input form-textarea"><?php echo htmlspecialchars($hiring_process); ?></textarea>
                </div>
                
                <div>
                    <label for="deadline_or_period" class="form-label">채용 시기 / 마감일</label>
                    <input type="text" id="deadline_or_period" name="deadline_or_period" class="form-input" value="<?php echo htmlspecialchars($deadline_or_period); ?>" placeholder="예: 2025-12-31 또는 상시채용">
                </div>

                <div>
                    <label for="coding_test_info" class="form-label">코딩테스트 정보</label>
                    <textarea id="coding_test_info" name="coding_test_info" class="form-input form-textarea"><?php echo htmlspecialchars($coding_test_info); ?></textarea>
                </div>

                <div>
                    <label for="posting_url" class="form-label">공고 URL</label>
                    <input type="url" id="posting_url" name="posting_url" class="form-input" value="<?php echo htmlspecialchars($posting_url); ?>" placeholder="https://example.com/careers/job">
                </div>
                
                <div>
                    <label for="notes" class="form-label">메모</label>
                    <textarea id="notes" name="notes" class="form-input form-textarea"><?php echo htmlspecialchars($notes); ?></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="/jobs/index.php" class="btn btn-secondary">취소</a>
                    <button type="submit" class="btn btn-primary">등록하기</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>