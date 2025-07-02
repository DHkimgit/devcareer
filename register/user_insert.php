<?php
    $host = "localhost:3306";
    $username = "webadmin";
    $password_db = "Qwer1234!!@";
    $dbname = "devcareer";

    $email = $_POST['email'];
    $name = $_POST['name'];
    $password = $_POST['password'];

    $conn = mysqli_connect($host, $username, $password_db, $dbname);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $check_stmt = mysqli_prepare($conn, "SELECT email FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check_stmt, "s", $email);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);

    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        echo "<script>alert('이미 등록된 이메일입니다.'); window.location.href='index.php';</script>";
        mysqli_stmt_close($check_stmt);
        mysqli_close($conn);
        exit;
    }
    mysqli_stmt_close($check_stmt);

    $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (email, password_hash, name, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    mysqli_stmt_bind_param($insert_stmt, "sss", $email, $password, $name);

    if (mysqli_stmt_execute($insert_stmt)) {
        echo "<script>alert('회원가입이 완료되었습니다.'); window.location.href='/login';</script>";
    } else {
        echo "<script>alert('오류가 발생했습니다: " . mysqli_stmt_error($insert_stmt) . "'); window.location.href='index.php';</script>";
    }
    mysqli_stmt_close($insert_stmt);

    mysqli_close($conn);
?>