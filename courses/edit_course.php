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
$success_message = '';
$course_id = null;
$course = null;

// GET 요청으로 course_id 받기
if (isset($_GET['id'])) {
    $course_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$course_id) {
        $errors[] = "유효하지 않은 강의 ID입니다.";
    } else {
        // 강의 정보 불러오기
        try {
            $stmt = $pdo->prepare("SELECT * FROM college_course WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $course_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) {
                $errors[] = "강의 정보를 찾을 수 없거나 수정 권한이 없습니다.";
            }
        } catch (PDOException $e) {
            $errors[] = "강의 정보를 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
} else {
    $errors[] = "강의 ID가 제공되지 않았습니다.";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $course) {
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

    if (!empty($semester) && !preg_match('/^\d{4}-[12]$/', $semester)) {
        $errors[] = "학기 형식이 올바르지 않습니다. (예: 2024-1)";
    }
    
    $valid_grades = ['A+', 'A', 'A0', 'B+', 'B', 'B0', 'C+', 'C', 'C0', 'D+', 'D', 'D0', 'F', 'P', 'NP', ''];
    if (!in_array(strtoupper($final_grade), $valid_grades)) {
        $errors[] = "유효하지 않은 학점입니다. 가능한 값: A+, A, B+, B, C+, C, D+, D, F, P, NP 또는 비워두기.";
    }


    if (empty($errors)) {
        try {
            $sql = "UPDATE college_course SET 
                        course_name = :course_name, 
                        professor_name = :professor_name, 
                        location = :location, 
                        major_field = :major_field, 
                        semester = :semester, 
                        exam_info = :exam_info, 
                        final_grade = :final_grade 
                    WHERE id = :id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':course_name', $course_name, PDO::PARAM_STR);
            $stmt->bindParam(':professor_name', $professor_name, PDO::PARAM_STR);
            $stmt->bindParam(':location', $location, PDO::PARAM_STR);
            $stmt->bindParam(':major_field', $major_field, PDO::PARAM_STR);
            $stmt->bindParam(':semester', $semester, PDO::PARAM_STR);
            $stmt->bindParam(':exam_info', $exam_info, PDO::PARAM_STR);
            $stmt->bindParam(':final_grade', $final_grade, PDO::PARAM_STR);
            $stmt->bindParam(':id', $course_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $success_message = "강의 정보가 성공적으로 수정되었습니다.";
                // 수정 후 최신 정보 다시 불러오기
                $stmt_refresh = $pdo->prepare("SELECT * FROM college_course WHERE id = :id AND user_id = :user_id");
                $stmt_refresh->bindParam(':id', $course_id, PDO::PARAM_INT);
                $stmt_refresh->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_refresh->execute();
                $course = $stmt_refresh->fetch(PDO::FETCH_ASSOC);
            } else {
                $errors[] = "강의 정보 수정 중 오류가 발생했습니다.";
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
    <title>강의 수정</title>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <script src="/tailwind.js"></script>
    <style>
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { margin-left: 260px; padding: 20px; width: calc(100% - 260px); padding-top: 100px; }
    </style>
</head>
<body class="content-wrapper">
    <?php display_sidebar('/courses/index.php'); ?>
    <div class="main-content p-8">
        <div class="container mx-auto bg-white p-8 rounded-lg shadow-md">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">강의 수정</h1>

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

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">성공:</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($course): ?>
            <form action="edit_course.php?id=<?php echo htmlspecialchars($course_id); ?>" method="POST" class="space-y-6">
                <div>
                    <label for="course_name" class="block text-sm font-medium text-gray-700">수업명 <span class="text-red-500">*</span></label>
                    <input type="text" id="course_name" name="course_name" value="<?php echo htmlspecialchars($course['course_name'] ?? ''); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="professor_name" class="block text-sm font-medium text-gray-700">교수명</label>
                    <input type="text" id="professor_name" name="professor_name" value="<?php echo htmlspecialchars($course['professor_name'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">강의실</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($course['location'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="major_field" class="block text-sm font-medium text-gray-700">분야 (예: 전공필수, 교양)</label>
                    <input type="text" id="major_field" name="major_field" value="<?php echo htmlspecialchars($course['major_field'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="semester" class="block text-sm font-medium text-gray-700">수강 학기 (예: 2024-1)</label>
                    <input type="text" id="semester" name="semester" value="<?php echo htmlspecialchars($course['semester'] ?? ''); ?>" placeholder="YYYY-S (예: 2024-1)" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="exam_info" class="block text-sm font-medium text-gray-700">시험 정보</label>
                    <textarea id="exam_info" name="exam_info" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($course['exam_info'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label for="final_grade" class="block text-sm font-medium text-gray-700">최종 학점 (예: A+, B0, P)</label>
                    <input type="text" id="final_grade" name="final_grade" value="<?php echo htmlspecialchars($course['final_grade'] ?? ''); ?>" placeholder="A+, B0, P 등" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        강의 정보 수정
                    </button>
                </div>
            </form>
            <?php elseif (empty($errors)): // 강의 정보는 없지만, ID 관련 오류 메시지가 없을 때 (초기 로딩 실패 등) ?>
                <p class="text-center text-gray-500">강의 정보를 불러올 수 없습니다. ID를 확인해주세요.</p>
            <?php endif; ?>
             <div class="mt-6">
                <a href="/courses/index.php" class="text-indigo-600 hover:text-indigo-900">강의 목록으로 돌아가기</a>
            </div>
        </div>
    </div>
</body>
</html>