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
$errors = [];

$stmt_courses = $pdo->prepare("SELECT id, course_name FROM college_course WHERE user_id = :user_id ORDER BY course_name ASC");
$stmt_courses->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_courses->execute();
$user_courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

$course_id = '';
$assignment_name = '';
$due_date = '';
$is_submitted = 0;
$related_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = trim($_POST['course_id'] ?? '');
    $assignment_name = trim($_POST['assignment_name'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    $is_submitted = isset($_POST['is_submitted']) ? 1 : 0;
    $related_link = trim($_POST['related_link'] ?? '');

    if (empty($course_id)) {
        $errors[] = "관련 수업을 선택해주세요.";
    }
    if (empty($assignment_name)) {
        $errors[] = "과제명은 필수 항목입니다.";
    }
    if (!empty($due_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
        $errors[] = "마감일 형식이 올바르지 않습니다. (YYYY-MM-DD)";
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO college_assignment (course_id, user_id, assignment_name, due_date, is_submitted, related_link) 
                    VALUES (:course_id, :user_id, :assignment_name, :due_date, :is_submitted, :related_link)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); 
            $stmt->bindParam(':assignment_name', $assignment_name, PDO::PARAM_STR);
            $stmt->bindParam(':due_date', $due_date, PDO::PARAM_STR);
            $stmt->bindParam(':is_submitted', $is_submitted, PDO::PARAM_BOOL);
            $stmt->bindParam(':related_link', $related_link, PDO::PARAM_STR);

            if ($stmt->execute()) {
                header('Location: /courses/index.php?status=assignment_success');
                exit;
            } else {
                $errors[] = "과제물 정보 등록에 실패했습니다. 다시 시도해주세요.";
            }
        } catch (PDOException $e) {
            $errors[] = "데이터베이스 오류가 발생했습니다. 관리자에게 문의해주세요.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 과제물 등록</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { margin-left: 260px; padding: 20px; width: calc(100% - 260px); padding-top: 100px; }
        .navbar { background-color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; }
        .navbar-user { font-size: 0.9rem; color: #505050; }
        .form-input, .form-select { width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); margin-top: 0.25rem; }
        .form-textarea { min-height: 100px; width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); margin-top: 0.25rem;}
        .form-label { font-weight: 600; color: #374151; }
        .form-checkbox { margin-top: 0.25rem; margin-right: 0.5rem; }
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
           <a href="/courses/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">수강 정보</a>
        </div>
        <div class="navbar-user">
            <div class="font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#808080] flex items-center">
            <?php if (isset($_SESSION['email'])): ?>
                <span><?php echo htmlspecialchars($_SESSION['email']); ?> 님 환영합니다.</span>
                <a href="/login/logout.php" title="로그아웃" class="ml-4 text-gray-600 hover:text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/></svg>
                </a>
            <?php else: ?>
                <a href="/login/index.php" class="text-primary hover:text-primary-hover">로그인</a>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <?php display_sidebar('/courses/index.php'); ?>
        <div class="main-content">
            <h1 class="text-2xl font-bold text-primary mb-6">새 과제물 등록</h1>

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

            <form action="/courses/create_assignment.php" method="POST" class="bg-white p-8 rounded-lg shadow-md space-y-6">
                <div>
                    <label for="course_id" class="form-label">관련 수업 <span class="text-red-500">*</span></label>
                    <select id="course_id" name="course_id" class="form-select" required>
                        <option value="">-- 수업 선택 --</option>
                        <?php foreach ($user_courses as $course_item): ?>
                            <option value="<?php echo htmlspecialchars($course_item['id']); ?>" <?php echo ($course_id == $course_item['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course_item['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="assignment_name" class="form-label">과제명 <span class="text-red-500">*</span></label>
                    <input type="text" id="assignment_name" name="assignment_name" class="form-input" value="<?php echo htmlspecialchars($assignment_name); ?>" required>
                </div>

                <div>
                    <label for="due_date" class="form-label">마감일</label>
                    <input type="date" id="due_date" name="due_date" class="form-input" value="<?php echo htmlspecialchars($due_date); ?>">
                </div>
                
                <div>
                    <label for="is_submitted" class="form-label inline-flex items-center">
                        <input type="checkbox" id="is_submitted" name="is_submitted" class="form-checkbox h-5 w-5 text-primary rounded border-gray-300 focus:ring-primary" value="1" <?php echo $is_submitted ? 'checked' : ''; ?>>
                        <span class="ml-2">제출 완료</span>
                    </label>
                </div>

                <div>
                    <label for="related_link" class="form-label">관련 링크/경로</label>
                    <textarea id="related_link" name="related_link" class="form-textarea" placeholder="예: https://github.com/user/repo 또는 제출 파일 경로"><?php echo htmlspecialchars($related_link); ?></textarea>
                </div>
                
                <div class="flex justify-end space-x-4">
                    <a href="/courses/index.php" class="btn btn-secondary">취소</a>
                    <button type="submit" class="btn btn-primary">등록하기</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>