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
$assignment_id = null;
$assignment = null;
$user_courses = [];

try {
    $stmt_courses = $pdo->prepare("SELECT id, course_name FROM college_course WHERE user_id = :user_id ORDER BY course_name ASC");
    $stmt_courses->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_courses->execute();
    $user_courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "강의 목록을 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
}


if (isset($_GET['id'])) {
    $assignment_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$assignment_id) {
        $errors[] = "유효하지 않은 과제 ID입니다.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM college_assignment WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $assignment_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$assignment) {
                $errors[] = "과제 정보를 찾을 수 없거나 수정 권한이 없습니다.";
            }
        } catch (PDOException $e) {
            $errors[] = "과제 정보를 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
} else {
    $errors[] = "과제 ID가 제공되지 않았습니다.";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $assignment) {
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
    if (!empty($due_date) && !DateTime::createFromFormat('Y-m-d', $due_date)) {
         if (!DateTime::createFromFormat('Y-m-d\TH:i', $due_date)) { // datetime-local 형식도 허용
            $errors[] = "제출 마감일 형식이 올바르지 않습니다. (YYYY-MM-DD 또는 YYYY-MM-DDTHH:MM)";
         }
    }
    if (!empty($related_link) && !filter_var($related_link, FILTER_VALIDATE_URL)) {
        $errors[] = "관련 링크가 유효한 URL 형식이 아닙니다.";
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE college_assignment SET 
                        course_id = :course_id, 
                        assignment_name = :assignment_name, 
                        due_date = :due_date, 
                        is_submitted = :is_submitted, 
                        related_link = :related_link 
                    WHERE id = :id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindParam(':assignment_name', $assignment_name, PDO::PARAM_STR);
            $stmt->bindParam(':due_date', $due_date, PDO::PARAM_STR);
            $stmt->bindParam(':is_submitted', $is_submitted, PDO::PARAM_INT);
            $stmt->bindParam(':related_link', $related_link, PDO::PARAM_STR);
            $stmt->bindParam(':id', $assignment_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $success_message = "과제 정보가 성공적으로 수정되었습니다.";
                $stmt_refresh = $pdo->prepare("SELECT * FROM college_assignment WHERE id = :id AND user_id = :user_id");
                $stmt_refresh->bindParam(':id', $assignment_id, PDO::PARAM_INT);
                $stmt_refresh->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_refresh->execute();
                $assignment = $stmt_refresh->fetch(PDO::FETCH_ASSOC);
            } else {
                $errors[] = "과제 정보 수정 중 오류가 발생했습니다.";
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
    <title>과제 수정</title>
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
            <h1 class="text-3xl font-bold mb-6 text-gray-800">과제 수정</h1>

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

            <?php if ($assignment): ?>
            <form action="edit_assignment.php?id=<?php echo htmlspecialchars($assignment_id); ?>" method="POST" class="space-y-6">
                <div>
                    <label for="course_id" class="block text-sm font-medium text-gray-700">관련 수업 <span class="text-red-500">*</span></label>
                    <select id="course_id" name="course_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">수업 선택</option>
                        <?php foreach ($user_courses as $user_course): ?>
                            <option value="<?php echo htmlspecialchars($user_course['id']); ?>" <?php echo ($assignment['course_id'] == $user_course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user_course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="assignment_name" class="block text-sm font-medium text-gray-700">과제명 <span class="text-red-500">*</span></label>
                    <input type="text" id="assignment_name" name="assignment_name" value="<?php echo htmlspecialchars($assignment['assignment_name'] ?? ''); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700">제출 마감일</label>
                    <input type="datetime-local" id="due_date" name="due_date" value="<?php echo !empty($assignment['due_date']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($assignment['due_date']))) : ''; ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="is_submitted" name="is_submitted" value="1" <?php echo ($assignment['is_submitted'] ?? 0) == 1 ? 'checked' : ''; ?> class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <label for="is_submitted" class="ml-2 block text-sm text-gray-900">제출 완료</label>
                </div>
                <div>
                    <label for="related_link" class="block text-sm font-medium text-gray-700">관련 링크 (예: 과제 제출 페이지)</label>
                    <input type="url" id="related_link" name="related_link" value="<?php echo htmlspecialchars($assignment['related_link'] ?? ''); ?>" placeholder="https://example.com" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        과제 정보 수정
                    </button>
                </div>
            </form>
            <?php elseif (empty($errors)): ?>
                <p class="text-center text-gray-500">과제 정보를 불러올 수 없습니다. ID를 확인해주세요.</p>
            <?php endif; ?>
             <div class="mt-6">
                <a href="/courses/index.php" class="text-indigo-600 hover:text-indigo-900">강의 및 과제 목록으로 돌아가기</a>
            </div>
        </div>
    </div>
</body>
</html>