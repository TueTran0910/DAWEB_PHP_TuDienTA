<?php
    $may_chu = "localhost";
    $ten_dang_nhap = "root";
    $mat_khau = "";
    $ten_csdl = "tudien_db";

    $ket_noi = new mysqli($may_chu, $ten_dang_nhap, $mat_khau, $ten_csdl);

    // Kiểm tra lỗi (Nếu lỗi thì dừng web và báo lỗi)
    if ($ket_noi->connect_error) {
        die("Kết nối thất bại: " . $ket_noi->connect_error);
    }
?>