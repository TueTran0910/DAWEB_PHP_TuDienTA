<?php
session_start(); // Bắt buộc phải có dòng này đầu tiên để tạo "thẻ bài"
include '../includes/connect_sql.php';

$loi = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    // 1. Kiểm tra tài khoản có tồn tại không
    // Lưu ý: Lấy cả id_user, ten_dang_nhap, mat_khau
    $sql = "SELECT id_user, ten_dang_nhap, mat_khau FROM nguoi_dung WHERE ten_dang_nhap = ?";
    
    // Dùng Prepared Statement cho bảo mật
    $stmt = $ket_noi->prepare($sql);
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // 2. So sánh mật khẩu nhập vào với mật khẩu mã hóa trong DB
        if (password_verify($pass, $row['mat_khau'])) {
            // --- ĐĂNG NHẬP THÀNH CÔNG ---
            
            // Lưu thông tin vào SESSION
            $_SESSION['id_nguoi_dung'] = $row['id_user']; // QUAN TRỌNG: dùng id_user
            $_SESSION['ten_nguoi_dung'] = $row['ten_dang_nhap'];
            
            // Chuyển hướng về trang chủ
            header("Location: ../index.php"); 
            exit();
        } else {
            $loi = "Mật khẩu không đúng!";
        }
    } else {
        $loi = "Tên đăng nhập không tồn tại!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập</title>
    <link rel="stylesheet" href="../css/register.css">
</head>
<body>

<div class="form-box">
    <h2>Đăng Nhập</h2>
    
    <?php if ($loi != ""): ?>
        <div class="message error"><?php echo $loi; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label>Tên đăng nhập</label>
            <input type="text" name="username" placeholder="Nhập tên tài khoản..." required>
        </div>
        
        <div class="form-group">
            <label>Mật khẩu</label>
            <input type="password" name="password" placeholder="Nhập mật khẩu..." required>
        </div>
        
        <button type="submit" class="btn-submit">ĐĂNG NHẬP</button>
    </form>
    
    <div class="back-link">
        <a href="../index.php">← Về trang chủ</a> | 
        <a href="register.php">Chưa có tài khoản?</a>
    </div>
</div>

</body>
</html>