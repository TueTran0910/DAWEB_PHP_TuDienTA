<?php
session_start();
include '../includes/connect_sql.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id_nguoi_dung'])) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn chưa đăng nhập!']); exit();
}

$id_user = $_SESSION['id_nguoi_dung'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

// 1. LẤY DANH SÁCH LIST CỦA USER
if ($action == 'get_lists') {
    $sql = "SELECT * FROM danh_sach WHERE id_user = ?";
    $stmt = $ket_noi->prepare($sql);
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $lists = [];
    while ($row = $result->fetch_assoc()) {
        $lists[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $lists]);
    exit();
}

// 2. LƯU TỪ (HOẶC TẠO LIST MỚI RỒI LƯU)
if ($action == 'save_word') {
    $id_tuvung = $_POST['id_tuvung'];
    $mode = $_POST['mode']; // 'new' (tạo mới) hoặc 'existing' (có sẵn)
    $list_val = $_POST['list_val']; // Tên list mới hoặc ID list cũ

    $id_danh_sach = 0;

    // Nếu chọn tạo list mới
    if ($mode == 'new') {
        $ten_ds = trim($list_val);
        if ($ten_ds == "") {
            echo json_encode(['status' => 'error', 'message' => 'Tên danh sách trống!']); exit();
        }
        // Tạo list mới
        $stmt = $ket_noi->prepare("INSERT INTO danh_sach (id_user, ten_danh_sach) VALUES (?, ?)");
        $stmt->bind_param("is", $id_user, $ten_ds);
        if ($stmt->execute()) {
            $id_danh_sach = $stmt->insert_id; // Lấy ID vừa tạo
        }
    } else {
        $id_danh_sach = $list_val; // Dùng ID list có sẵn
    }

    // Kiểm tra đã lưu chưa
    $check = $ket_noi->query("SELECT id_dsyt FROM yeu_thich WHERE id_user=$id_user AND id_tuvung=$id_tuvung AND id_danh_sach=$id_danh_sach");
    
    if ($check->num_rows > 0) {
        // Có rồi -> Xóa (Bỏ thích)
        $ket_noi->query("DELETE FROM yeu_thich WHERE id_user=$id_user AND id_tuvung=$id_tuvung AND id_danh_sach=$id_danh_sach");
        echo json_encode(['status' => 'removed', 'message' => 'Đã xóa khỏi danh sách']);
    } else {
        // Chưa có -> Thêm
        $stmt = $ket_noi->prepare("INSERT INTO yeu_thich (id_user, id_tuvung, id_danh_sach) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $id_user, $id_tuvung, $id_danh_sach);
        $stmt->execute();
        echo json_encode(['status' => 'saved', 'message' => 'Đã lưu thành công!']);
    }
    exit();
}
if ($action == 'rename_list') {
    $id_list = $_POST['id_list'];
    $new_name = trim($_POST['new_name']);

    if ($new_name == "") {
        echo json_encode(['status' => 'error', 'message' => 'Tên không được để trống!']); exit();
    }

    $stmt = $ket_noi->prepare("UPDATE danh_sach SET ten_danh_sach = ? WHERE id_danh_sach = ? AND id_user = ?");
    $stmt->bind_param("sii", $new_name, $id_list, $id_user);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Đã đổi tên thành công!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống!']);
    }
    exit();
}

// --- 4. XÓA DANH SÁCH ---
if ($action == 'delete_list') {
    $id_list = $_POST['id_list'];

    // Xóa danh sách (Các từ trong bảng yeu_thich liên kết với list này sẽ tự mất nếu bạn đã set Foreign Key CASCADE)
    // Nếu chưa set CASCADE, ta xóa thủ công cho chắc:
    $ket_noi->query("DELETE FROM yeu_thich WHERE id_danh_sach = $id_list AND id_user = $id_user");
    
    $stmt = $ket_noi->prepare("DELETE FROM danh_sach WHERE id_danh_sach = ? AND id_user = ?");
    $stmt->bind_param("ii", $id_list, $id_user);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Đã xóa danh sách!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không thể xóa danh sách này!']);
    }
    exit();
}

// --- 5. TẠO DANH SÁCH MỚI (RỖNG) ---
if ($action == 'create_list_only') {
    $new_name = trim($_POST['new_name']);

    if ($new_name == "") {
        echo json_encode(['status' => 'error', 'message' => 'Tên danh sách không được trống!']); exit();
    }

    // Thêm vào bảng danh_sach
    $stmt = $ket_noi->prepare("INSERT INTO danh_sach (id_user, ten_danh_sach) VALUES (?, ?)");
    $stmt->bind_param("is", $id_user, $new_name);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Đã tạo danh sách mới!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi: ' . $ket_noi->error]);
    }
    exit();
}
?>