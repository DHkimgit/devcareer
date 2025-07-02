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
$course_name = '';
$professor_name = '';
$location = '';
$major_field = '';
$semester = '';
$exam_info = '';
$final_grade = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_name = trim($_POST['course_name'] ?? '');
    $professor_name = trim($_POST['professor_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $major_field = trim($_POST['major_field'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $exam_info = trim($_POST['exam_info'] ?? '');
    $final_grade = trim($_POST['final_grade'] ?? '');

    if (empty($course_name)) {
        $errors[] = "수업명은 필수 항목입니다.";
    }

    //학기 형식 검사
    if (!empty($semester) && !preg_match('/^\d{4}-[12]$/', $semester)) {
        $errors[] = "학기 형식이 올바르지 않습니다. (예: 2024-1)";
    }

    // 학점 형식 검사
    $valid_grades = ['A+', 'A', 'A0', 'B+', 'B', 'B0', 'C+', 'C', 'C0', 'D+', 'D', 'D0', 'F', 'P', 'NP', ''];
    if (!empty($final_grade) && !in_array(strtoupper($final_grade), array_map('strtoupper', $valid_grades))) {
        $errors[] = "유효한 학점을 입력해주세요. (예: A+, B0, C, P 등)";
    }


    if (empty($errors)) {
        try {
            $pdo = getPDO();
            $sql = "INSERT INTO college_course (user_id, course_name, professor_name, location, major_field, semester, exam_info, final_grade) 
                    VALUES (:user_id, :course_name, :professor_name, :location, :major_field, :semester, :exam_info, :final_grade)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':course_name', $course_name, PDO::PARAM_STR);
            $stmt->bindParam(':professor_name', $professor_name, PDO::PARAM_STR);
            $stmt->bindParam(':location', $location, PDO::PARAM_STR);
            $stmt->bindParam(':major_field', $major_field, PDO::PARAM_STR);
            $stmt->bindParam(':semester', $semester, PDO::PARAM_STR);
            $stmt->bindParam(':exam_info', $exam_info, PDO::PARAM_STR);
            $stmt->bindParam(':final_grade', $final_grade, PDO::PARAM_STR);

            if ($stmt->execute()) {
                header('Location: /courses/index.php?status=course_success');
                exit;
            } else {
                $errors[] = "수업 정보 등록에 실패했습니다. 다시 시도해주세요.";
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
    <title>새 수업 정보 등록</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { margin-left: 260px; padding: 20px; width: calc(100% - 260px); padding-top: 100px; }
        .navbar { background-color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; }
        .navbar-user { font-size: 0.9rem; color: #505050; }
        .form-input { width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); margin-top: 0.25rem; }
        .form-textarea { min-height: 100px; }
        .form-label { font-weight: 600; color: #374151; }
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
            <h1 class="text-2xl font-bold text-primary mb-6">새 수업 정보 등록</h1>

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

            <form action="/courses/create_course.php" method="POST" class="bg-white p-8 rounded-lg shadow-md space-y-6">
                <div>
                    <label for="course_name" class="form-label">수업명 <span class="text-red-500">*</span></label>
                    <input type="text" id="course_name" name="course_name" class="form-input" value="<?php echo htmlspecialchars($course_name); ?>" required>
                </div>

                <div>
                    <label for="professor_name" class="form-label">교수명</label>
                    <input type="text" id="professor_name" name="professor_name" class="form-input" value="<?php echo htmlspecialchars($professor_name); ?>">
                </div>

                <div>
                    <label for="location" class="form-label">수업 위치</label>
                    <input type="text" id="location" name="location" class="form-input" value="<?php echo htmlspecialchars($location); ?>">
                </div>

                <div>
                    <label for="major_field" class="form-label">전공 분야</label>
                    <input type="text" id="major_field" name="major_field" class="form-input" value="<?php echo htmlspecialchars($major_field); ?>" placeholder="예: 컴퓨터공학, 소프트웨어">
                </div>
                
                <div>
                    <label for="semester" class="form-label">학기</label>
                    <input type="text" id="semester" name="semester" class="form-input" value="<?php echo htmlspecialchars($semester); ?>" placeholder="예: 2024-1">
                </div>

                <div>
                    <label for="exam_info" class="form-label">시험 정보</label>
                    <textarea id="exam_info" name="exam_info" class="form-input form-textarea"><?php echo htmlspecialchars($exam_info); ?></textarea>
                </div>

                <div>
                    <label for="final_grade" class="form-label">최종 학점</label>
                    <input type="text" id="final_grade" name="final_grade" class="form-input" value="<?php echo htmlspecialchars($final_grade); ?>" placeholder="예: A+, B0, P (입력하지 않아도 됩니다)">
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