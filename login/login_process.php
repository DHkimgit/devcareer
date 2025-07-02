<?php
session_start();

$host = "localhost:3306";
$username_db = "webadmin";
$password_db = "Qwer1234!!@";
$dbname = "devcareer";

$conn = mysqli_connect($host, $username_db, $password_db, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, email, password_hash FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($password == $user['password_hash']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            
            setcookie("user_id", $user['id'], time() + (86400 * 30), "/");
            setcookie("email", $user['email'], time() + (86400 * 30), "/");

            header("Location: /main/");
            exit();
        } else {
            header("Location: index.php?error=이메일 또는 비밀번호가 잘못되었습니다.");
            exit();
        }
    } else {
        header("Location: index.php?error=이메일 또는 비밀번호가 잘못되었습니다.");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>