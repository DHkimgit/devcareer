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

// 학점 평균 계산 함수
function calculate_gpa($grades) {
    $grade_points = [
        'A+' => 4.5, 'A' => 4.0, 'A0' => 4.0,
        'B+' => 3.5, 'B' => 3.0, 'B0' => 3.0,
        'C+' => 2.5, 'C' => 2.0, 'C0' => 2.0,
        'D+' => 1.5, 'D' => 1.0, 'D0' => 1.0,
        'F' => 0.0
    ];
    $total_points = 0;
    $valid_grades_count = 0;

    foreach ($grades as $grade_item) {
        $grade = strtoupper(trim($grade_item['final_grade']));
        if (isset($grade_points[$grade])) {
            $total_points += $grade_points[$grade];
            $valid_grades_count++;
        }
    }

    if ($valid_grades_count > 0) {
        return round($total_points / $valid_grades_count, 2);
    }
    return 0; 
}

$stmt_grades = $pdo->prepare("SELECT final_grade FROM college_course WHERE user_id = :user_id AND final_grade IS NOT NULL AND final_grade != ''");
$stmt_grades->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_grades->execute();
$user_grades = $stmt_grades->fetchAll(PDO::FETCH_ASSOC);
$average_gpa = calculate_gpa($user_grades);

$stmt_courses = $pdo->prepare("SELECT * FROM college_course WHERE user_id = :user_id ORDER BY semester DESC, course_name ASC");
$stmt_courses->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_courses->execute();
$courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

$course_ids = array_column($courses, 'id');
$assignments = [];
if (!empty($course_ids)) {
    $placeholders = implode(',', array_fill(0, count($course_ids), '?'));
    $sql_assignments = "SELECT ca.*, cc.course_name 
                        FROM college_assignment ca
                        JOIN college_course cc ON ca.course_id = cc.id
                        WHERE ca.user_id = ? AND ca.course_id IN ($placeholders)
                        ORDER BY ca.due_date ASC, ca.assignment_name ASC";
    
    $stmt_assignments = $pdo->prepare($sql_assignments);
    $params = array_merge([$user_id], $course_ids);
    $stmt_assignments->execute($params);
    $assignments = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>나의 수강 정보</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { margin-left: 260px; padding: 20px; width: calc(100% - 260px); padding-top: 100px; }
        .navbar { background-color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; }
        .navbar-user { font-size: 0.9rem; color: #505050; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; transition: background-color 0.3s ease; text-decoration: none; display: inline-block; }
        .btn-sm { padding: 0.1rem 0.3rem; font-size: 0.65rem; border-radius: 0.2rem; } /* padding과 font-size를 줄였습니다. */
        .btn-primary { background-color: #5F43FF; color: white; }
        .btn-primary:hover { background-color: #8243FF; }
        .btn-edit { background-color: #3b82f6; color: white; }
        .btn-edit:hover { background-color: #2563eb; }
        .btn-delete { background-color: #ef4444; color: white; }
        .btn-delete:hover { background-color: #dc2626; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 0.875rem; }
        th { background-color: #f8f9fa; font-weight: 600; color: #333; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        tr:hover { background-color: #e9ecef; }
        .table-responsive { overflow-x: auto; }
        .gpa-display { background-color: #5F43FF; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .gpa-display h2 { font-size: 1.25rem; margin-bottom: 0.5rem; }
        .gpa-display p { font-size: 2rem; font-weight: bold; }
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
            <a href="/main/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house-door-fill mr-2" viewBox="0 0 16 16"><path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.505a.5.5 0 0 0 .5.5h2.5a.5.5 0 0 0 .5-.5v-4.09c0-.29-.12-.55-.32-.73l-6-5.5a.5.5 0 0 0-.64 0l-6 5.5c-.2.18-.32.44-.32.73V14a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5z"/><path d="M7.293 1.293a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.646a.5.5 0 1 1-.708-.708L7.293 1.293z"/></svg>
                홈
            </a>
             <a href="/projects/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-folder-fill mr-2" viewBox="0 0 16 16"><path d="M9.828 3h3.982a2 2 0 0 1 1.992 2.181l-.637 7A2 2 0 0 1 13.174 14H2.826a2 2 0 0 1-1.991-1.819l-.637-7a1.99 1.99 0 0 1 .342-1.31L.5 3a2 2 0 0 1 2-2h3.672a2 2 0 0 1 1.414.586l.828.828A2 2 0 0 0 9.828 3zm-8.322.12C1.72 3.042 1.95 3 2.19 3h5.396l-.707-.707A1 1 0 0 0 6.172 2H2.5a1 1 0 0 0-1 .981l.006.139z"/></svg>
                프로젝트
            </a>
            <a href="/blog/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square mr-2" viewBox="0 0 16 16">
                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                </svg>
                블로그
            </a>
            <a href="/jobs/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-briefcase-fill mr-2" viewBox="0 0 16 16">
                    <path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v1.384l7.614 2.03a1.5 1.5 0 0 0 .772 0L16 5.884V4.5A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1h-3zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M0 12.5A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5V6.85L8.129 8.947a.5.5 0 0 1-.258 0L0 6.85v5.65z"/>
                </svg>
                채용 공고
            </a>
            <a href="/courses/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-book-fill mr-2" viewBox="0 0 16 16">
                    <path d="M8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
                </svg>
                수강 정보
            </a>
            <a href="/cover-letter/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text-fill mr-2" viewBox="0 0 16 16">
                    <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1M4.5 9a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4.5 11a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4.5 13a.5.5 0 0 1 0-1h4a.5.5 0 0 1 0 1z"/>
                </svg>
                자기소개서
            </a>
            <a href="/algorithms/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text-fill mr-2" viewBox="0 0 16 16">
                    <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1M4.5 9a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4.5 11a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4.5 13a.5.5 0 0 1 0-1h4a.5.5 0 0 1 0 1z"/>
                </svg>
                알고리즘
            </a>
            <a href="/resume/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-person-fill mr-2" viewBox="0 0 16 16"><path d="M12 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm-1 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm-3 4c2.623 0 4.146.826 5 1.755V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-1.245C3.854 11.825 5.377 11 8 11z"/></svg>
                이력서
            </a>
        </div>
        <div class="navbar-user">
             <div class="font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#808080] flex items-center">
            <?php if (isset($_SESSION['email'])): ?>
                <span><?php echo htmlspecialchars($_SESSION['email']); ?> 님 환영합니다.</span>
                <a href="/login/logout.php" title="로그아웃" class="ml-4 text-gray-600 hover:text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                        <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                    </svg>
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
            <div class="gpa-display">
                <h2>나의 전체 학점 평균</h2>
                <p><?php echo htmlspecialchars($average_gpa); ?> / 4.5</p>
            </div>

            <div class="mb-10">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-primary">수강 수업 목록</h2>
                    <a href="/courses/create_course.php" class="btn btn-primary">수업 추가하기</a>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md table-responsive">
                    <?php if (count($courses) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>수업명</th>
                                    <th>교수명</th>
                                    <th>수업 위치</th>
                                    <th>전공 분야</th>
                                    <th>학기</th>
                                    <th>시험 정보</th>
                                    <th>최종 학점</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                        <td><?php echo htmlspecialchars($course['professor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($course['location']); ?></td>
                                        <td><?php echo htmlspecialchars($course['major_field']); ?></td>
                                        <td><?php echo htmlspecialchars($course['semester']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($course['exam_info'])); ?></td>
                                        <td><?php echo htmlspecialchars($course['final_grade']); ?></td>
                                        <td>
                                            <a href="/courses/edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-edit mr-1">수정</a>
                                            <button onclick="deleteCourse(<?php echo $course['id']; ?>, this)" class="btn btn-sm btn-delete">삭제</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center text-gray-500 py-5">등록된 수업 정보가 없습니다.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-primary">과제물 목록</h2>
                    <a href="/courses/create_assignment.php" class="btn btn-primary">과제물 추가하기</a>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md table-responsive">
                    <?php if (count($assignments) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>과제명</th>
                                    <th>관련 수업</th>
                                    <th>마감일</th>
                                    <th>제출 여부</th>
                                    <th>관련 링크/경로</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['assignment_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['course_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['due_date']); ?></td>
                                        <td><?php echo $assignment['is_submitted'] ? '제출 완료' : '미제출'; ?></td>
                                        <td>
                                            <?php if (!empty($assignment['related_link'])): ?>
                                                <?php if (filter_var($assignment['related_link'], FILTER_VALIDATE_URL)): ?>
                                                    <a href="<?php echo htmlspecialchars($assignment['related_link']); ?>" target="_blank" class="text-blue-600 hover:underline">링크 보기</a>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($assignment['related_link']); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="/courses/edit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-edit mr-1">수정</a>
                                            <button onclick="deleteAssignment(<?php echo $assignment['id']; ?>, this)" class="btn btn-sm btn-delete">삭제</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center text-gray-500 py-5">등록된 과제물 정보가 없습니다.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        async function deleteCourse(courseId, buttonElement) {
            if (confirm('정말로 이 수업을 삭제하시겠습니까? 관련된 과제물도 함께 삭제될 수 있습니다.')) {
                try {
                    const response = await fetch('/courses/delete_course.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${courseId}`
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert('수업이 삭제되었습니다.');
                        // 행을 테이블에서 제거
                        const row = buttonElement.closest('tr');
                        row.parentNode.removeChild(row);
                        // 또는 페이지 새로고침: location.reload();
                    } else {
                        alert('수업 삭제에 실패했습니다: ' + (result.message || '알 수 없는 오류'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('수업 삭제 중 오류가 발생했습니다.');
                }
            }
        }

        async function deleteAssignment(assignmentId, buttonElement) {
            if (confirm('정말로 이 과제물을 삭제하시겠습니까?')) {
                try {
                    const response = await fetch('/courses/delete_assignment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${assignmentId}`
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert('과제물이 삭제되었습니다.');
                        // 행을 테이블에서 제거
                        const row = buttonElement.closest('tr');
                        row.parentNode.removeChild(row);
                        // 또는 페이지 새로고침: location.reload();
                    } else {
                        alert('과제물 삭제에 실패했습니다: ' + (result.message || '알 수 없는 오류'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('과제물 삭제 중 오류가 발생했습니다.');
                }
            }
        }
    </script>
</body>
</html>