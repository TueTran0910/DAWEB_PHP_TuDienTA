<?php
session_start();
include '../includes/connect_sql.php';

$thong_bao = "";
$kieu_thong_bao = ""; // success hoặc error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // 1. Kiểm tra tài khoản có tồn tại không
    $check_sql = "SELECT id_user FROM nguoi_dung WHERE ten_dang_nhap = ?";
    $stmt = $ket_noi->prepare($check_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 2. Kiểm tra mật khẩu nhập lại
        if ($new_pass === $confirm_pass) {
            // 3. Mã hóa mật khẩu mới
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

            // 4. Cập nhật vào Database
            $update_sql = "UPDATE nguoi_dung SET mat_khau = ? WHERE ten_dang_nhap = ?";
            $stmt_update = $ket_noi->prepare($update_sql);
            $stmt_update->bind_param("ss", $hashed_password, $username);
            
            if ($stmt_update->execute()) {
                $thong_bao = "Đổi mật khẩu thành công! Bạn có thể đăng nhập ngay.";
                $kieu_thong_bao = "success";
            } else {
                $thong_bao = "Lỗi hệ thống, vui lòng thử lại sau.";
                $kieu_thong_bao = "error";
            }
        } else {
            $thong_bao = "Mật khẩu xác nhận không khớp!";
            $kieu_thong_bao = "error";
        }
    } else {
        $thong_bao = "Tên tài khoản không tồn tại!";
        $kieu_thong_bao = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu</title>
    <link rel="stylesheet" href="../css/register.css"> <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-box { margin-top: 50px; }
        .back-btn { display: inline-block; margin-top: 10px; color: #555; text-decoration: none;}
        .back-btn:hover { color: #000; text-decoration: underline; }
    </style>
</head>
<body>

<div class="form-box">
    <h2>Lấy Lại Mật Khẩu</h2>
    <p style="text-align:center; color:#666; font-size:0.9em; margin-bottom:20px;">
        Nhập tên đăng nhập của bạn để thiết lập mật khẩu mới.
    </p>

    <form action="" method="POST">
        <div class="form-group">
            <label>Tên đăng nhập của bạn</label>
            <input type="text" name="username" placeholder="Ví dụ: tuan123" required>
        </div>
        
        <div class="form-group">
            <label>Mật khẩu mới</label>
            <input type="password" name="new_password" placeholder="Nhập mật khẩu mới..." required minlength="6">
        </div>

        <div class="form-group">
            <label>Nhập lại mật khẩu mới</label>
            <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu..." required>
        </div>
        
        <button type="submit" class="btn-submit" style="background-color: #ff9800;">ĐỔI MẬT KHẨU</button>
    </form>
    
    <div class="back-link">
        <a href="sign_in.php" class="back-btn">← Quay lại đăng nhập</a>
    </div>
</div>

<?php if ($thong_bao != ""): ?>
<script>
    Swal.fire({
        icon: '<?php echo $kieu_thong_bao; ?>',
        title: 'Thông báo',
        text: '<?php echo $thong_bao; ?>',
        confirmButtonText: 'OK'
    }).then((result) => {
        <?php if ($kieu_thong_bao == 'success'): ?>
            window.location.href = 'sign_in.php';
        <?php endif; ?>
    });
</script>
<?php endif; ?>

</body>
</html>