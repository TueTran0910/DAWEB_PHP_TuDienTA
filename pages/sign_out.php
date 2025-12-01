<?php
session_start();
// Xóa hết mọi biến session
session_unset(); 
// Hủy session
session_destroy();

// Quay về trang chủ
header("Location: ../index.php");
exit();
?>