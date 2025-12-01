<?php
include '../includes/connect_sql.php'; 

$thong_bao = "";
$loi = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    // 1. Kiểm tra rỗng
    if (empty($user) || empty($email) || empty($pass) || empty($confirm_pass)) {
        $loi = "Vui lòng nhập đầy đủ thông tin!";
    }
    // 2. Kiểm tra định dạng Email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loi = "Email không hợp lệ! (Ví dụ đúng: ten@gmail.com)";
    }
    // 3. Kiểm tra độ dài mật khẩu (Ví dụ tối thiểu 6 ký tự)
    elseif (strlen($pass) < 6) {
        $loi = "Mật khẩu phải có ít nhất 6 ký tự!";
    }
    // 4. Kiểm tra mật khẩu nhập lại có khớp không
    elseif ($pass !== $confirm_pass) {
        $loi = "Mật khẩu nhập lại không khớp!";
    }
    // 5. Kiểm tra tên đăng nhập (Không chứa ký tự đặc biệt, chỉ chữ và số)
    elseif (!preg_match("/^[a-zA-Z0-9]*$/", $user)) {
        $loi = "Tên đăng nhập chỉ được chứa chữ cái và số, không dấu!";
    }
    else {
        // --- NẾU KHÔNG CÓ LỖI GÌ THÌ MỚI ĐỤNG VÀO DATABASE ---

        // Kiểm tra xem Username HOẶC Email đã tồn tại chưa
        // Dùng câu lệnh SQL an toàn (Prepared Statement) để tránh hack SQL Injection
        // (Đây là kỹ thuật nâng cao hơn chút, nhưng an toàn tuyệt đối)
        $sql_check = "SELECT id_user FROM nguoi_dung WHERE ten_dang_nhap = ? OR email = ?";
        $stmt = $ket_noi->prepare($sql_check);
        $stmt->bind_param("ss", $user, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $loi = "Tên đăng nhập hoặc Email này đã được sử dụng!";
        } else {
            // Mã hóa mật khẩu
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
            
            // Thêm vào database
            $sql_insert = "INSERT INTO nguoi_dung (ten_dang_nhap, mat_khau, email) VALUES (?, ?, ?)";
            $stmt_insert = $ket_noi->prepare($sql_insert);
            $stmt_insert->bind_param("sss", $user, $pass_hash, $email);
            
            if ($stmt_insert->execute()) {
                $thong_bao = "Đăng ký thành công! <a href='sign_in.php'>Đăng nhập ngay</a>";
                $user = $email = ""; 
            } else {
                $loi = "Lỗi hệ thống: " . $ket_noi->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký Tài Khoản</title>
    <link rel="stylesheet" href="../css/register.css">
</head>
<body>

<div class="form-box">
    <h2>Đăng Ký</h2>
    
    <?php if ($thong_bao != ""): ?>
        <div class="message success"><?php echo $thong_bao; ?></div>
    <?php endif; ?>
    
    <?php if ($loi != ""): ?>
        <div class="message error"><?php echo $loi; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label>Tên đăng nhập</label>
            <input type="text" name="username" value="<?php echo isset($user) ? $user : ''; ?>" placeholder="Nhập tên user..." required>
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo isset($email) ? $email : ''; ?>" placeholder="Nhập email..." required>
        </div>

        <div class="form-group">
            <label>Mật khẩu</label>
            <input type="password" name="password" placeholder="Tối thiểu 6 ký tự" required>
        </div>

        <div class="form-group">
            <label>Nhập lại mật khẩu</label>
            <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu trên" required>
        </div>
        
        <button type="submit" class="btn-submit">ĐĂNG KÝ TÀI KHOẢN</button>
    </form>
    
    <div class="back-link">
        <a href="../index.php">← Quay về trang chủ</a> | 
        <a href="sign_in.php">Đã có tài khoản?</a>
    </div>
</div>

</body>
</html>