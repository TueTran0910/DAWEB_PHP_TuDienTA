<?php

include '../includes/connect_sql.php';
$ma_bi_mat = "admin";

if (!isset($_GET['key']) || $_GET['key'] != $ma_bi_mat) {
    die("<h2 style='color:red; text-align:center;'>⛔ Sai mã bảo mật! Bạn không được phép chạy file này.</h2>");
}
$duong_dan_file = "./dulieu.txt";

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tool Nhập Dữ Liệu</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .log-box { background: white; max-width: 800px; margin: 0 auto; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: green; border-bottom: 1px solid #eee; padding: 5px 0; }
        .skipped { color: orange; border-bottom: 1px solid #eee; padding: 5px 0; }
        .error { color: red; padding: 5px 0; }
    </style>
</head>
<body>

<div class="log-box">
    <h2 style="text-align: center; color: #007bff;">🚀 Tool Nạp Dữ Liệu Tự Động</h2>
    
    <div style="height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fafafa;">
    <?php
    if (!file_exists($duong_dan_file)) {
        echo "<p class='error'>❌ Không tìm thấy file <b>dulieu.txt</b> trong thư mục data.</p>";
    } else {
        $file = fopen($duong_dan_file, "r");
        $them_moi = 0;
        $bo_qua = 0;

        $sql_check = "SELECT id_tuvung FROM tu_vung WHERE ten_tu_vung = ? AND loai_tu = ?";
        $stmt_check = $ket_noi->prepare($sql_check);

        $sql_insert = "INSERT INTO tu_vung (ten_tu_vung, phat_am, loai_tu, nghia_tieng_viet, vi_du) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $ket_noi->prepare($sql_insert);

        while (!feof($file)) {
            $dong = fgets($file);
            if (trim($dong) == "") continue;

            $mang = explode("|", $dong);

            if (count($mang) >= 4) {
                $ten_tu = trim($mang[0]);
                $phat_am = trim($mang[1]);
                $loai_tu = trim($mang[2]);
                $nghia = trim($mang[3]);
                $vi_du = isset($mang[4]) ? trim($mang[4]) : "";

                // Kiểm tra trùng
                $stmt_check->bind_param("ss", $ten_tu, $loai_tu);
                $stmt_check->execute();
                $res_check = $stmt_check->get_result();

                if ($res_check->num_rows > 0) {
                    echo "<div class='skipped'>⚠️ Bỏ qua: <b>$ten_tu</b> - Đã có.</div>";
                    $bo_qua++;
                } else {
                    $stmt_insert->bind_param("sssss", $ten_tu, $phat_am, $loai_tu, $nghia, $vi_du);
                    if ($stmt_insert->execute()) {
                        echo "<div class='success'>✅ Đã thêm: <b>$ten_tu</b></div>";
                        $them_moi++;
                    }
                }
            }
        }
        fclose($file);
        
        echo "<hr><h3>KẾT QUẢ: Thêm mới ($them_moi) | Bỏ qua ($bo_qua)</h3>";
    }
    ?>
    </div>
    
    <div style="text-align:center; margin-top:20px;">
        <a href="../index.php" style="text-decoration:none; background:#007bff; color:white; padding:10px 20px; border-radius:5px;">Về trang chủ</a>
    </div>
</div>

</body>
</html>