<?php
session_start();
include '../includes/connect_sql.php';

if (isset($_GET['id_dsyt']) && isset($_SESSION['id_nguoi_dung'])) {
    $id_dsyt = $_GET['id_dsyt'];
    $current_list = isset($_GET['list']) ? $_GET['list'] : 'all';
    
    // Xóa khỏi bảng yeu_thich
    $stmt = $ket_noi->prepare("DELETE FROM yeu_thich WHERE id_dsyt = ? AND id_user = ?");
    $stmt->bind_param("ii", $id_dsyt, $_SESSION['id_nguoi_dung']);
    $stmt->execute();
    
    // Quay lại trang danh sách đang đứng
    header("Location: tu_yeu_thich.php?list=" . $current_list);
} else {
    header("Location: tu_yeu_thich.php");
}
?>