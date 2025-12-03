<?php
session_start();
include '../includes/connect_sql.php';

// Chặn người lạ
if (!isset($_SESSION['id_nguoi_dung'])) {
    die("Vui lòng đăng nhập! <a href='sign_in.php'>Đăng nhập ngay</a>");
}

$id_user = $_SESSION['id_nguoi_dung'];
$current_list_id = isset($_GET['list']) ? $_GET['list'] : 'all';

// --- LẤY DANH SÁCH USER ---
$result_lists = $ket_noi->query("SELECT * FROM danh_sach WHERE id_user = $id_user ORDER BY id_danh_sach DESC");

// --- LẤY TỪ VỰNG ---
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Xây dựng SQL lấy từ
if ($current_list_id == 'all') {
    $sql_words = "SELECT tv.*, yt.id_dsyt, ds.ten_danh_sach 
                  FROM tu_vung tv
                  JOIN yeu_thich yt ON tv.id_tuvung = yt.id_tuvung
                  LEFT JOIN danh_sach ds ON yt.id_danh_sach = ds.id_danh_sach
                  WHERE yt.id_user = ? ORDER BY yt.id_dsyt DESC LIMIT ? OFFSET ?";
    $stmt = $ket_noi->prepare($sql_words);
    $stmt->bind_param("iii", $id_user, $limit, $offset);
    
    // Đếm tổng cho "Tất cả"
    $count_res = $ket_noi->query("SELECT COUNT(*) as total FROM yeu_thich WHERE id_user = $id_user");
} else {
    $sql_words = "SELECT tv.*, yt.id_dsyt, ds.ten_danh_sach 
                  FROM tu_vung tv
                  JOIN yeu_thich yt ON tv.id_tuvung = yt.id_tuvung
                  LEFT JOIN danh_sach ds ON yt.id_danh_sach = ds.id_danh_sach
                  WHERE yt.id_user = ? AND yt.id_danh_sach = ? ORDER BY yt.id_dsyt DESC LIMIT ? OFFSET ?";
    $stmt = $ket_noi->prepare($sql_words);
    $stmt->bind_param("iiii", $id_user, $current_list_id, $limit, $offset);

    // Đếm tổng cho list cụ thể
    $count_res = $ket_noi->query("SELECT COUNT(*) as total FROM yeu_thich WHERE id_user = $id_user AND id_danh_sach = $current_list_id");
}

$stmt->execute();
$result_words = $stmt->get_result();
$total_records = $count_res->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Lấy tên danh sách hiện tại để hiển thị tiêu đề
$current_list_name = "Tất cả từ vựng";
if ($current_list_id != 'all') {
    $res_name = $ket_noi->query("SELECT ten_danh_sach FROM danh_sach WHERE id_danh_sach = $current_list_id");
    if ($res_name->num_rows > 0) {
        $current_list_name = $res_name->fetch_assoc()['ten_danh_sach'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sổ Tay Từ Vựng</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/word_list.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* CSS RIÊNG CHO TRANG NÀY */
        .sidebar { background: #f8f9fa; border-right: 1px solid #eee; }
        
        .list-group-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 15px; margin-bottom: 8px; background: white;
            border-radius: 8px; color: #555; text-decoration: none;
            transition: 0.2s; border: 1px solid transparent;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .list-group-item:hover { transform: translateX(5px); border-color: #007bff; color: #007bff; }
        .list-group-item.active { background: #e3f2fd; color: #0056b3; border-color: #b3e5fc; font-weight: 700; }
        
        .list-actions { display: none; gap: 5px; }
        .list-group-item:hover .list-actions { display: flex; } 
        
        .action-btn {
            border: none; background: none; cursor: pointer; padding: 4px; border-radius: 4px; transition: 0.2s;
        }
        .btn-edit:hover { background: #ffc107; color: white; }
        .btn-del:hover { background: #dc3545; color: white; }

        .create-btn {
            width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 8px;
            font-weight: bold; cursor: pointer; margin-top: 15px; display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: 0.2s;
        }
        .create-btn:hover { background: #218838; }

        /* --- PHẦN HEADER MỚI CÓ NÚT REVIEW --- */
        .page-header {
            background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); margin-bottom: 20px;
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .header-left {
            display: flex; align-items: center; gap: 15px;
        }

        .btn-review {
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            color: white; padding: 10px 20px; border-radius: 30px;
            text-decoration: none; font-weight: 700;
            box-shadow: 0 4px 10px rgba(108, 92, 231, 0.3);
            transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-review:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(108, 92, 231, 0.4);
            background: linear-gradient(135deg, #5b4cc4, #8e84e8);
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="../index.php" class="logo"><i class="fas fa-feather-alt"></i> Wordik</a>
        <div class="nav-links">
            <a href="word_list.php">KHO TỪ VỰNG</a>
            <a href="lich_su_tra_cuu.php">LỊCH SỬ</a>
            <a href="tu_yeu_thich.php">DANH SÁCH TỪ VỰNG YÊU THÍCH</a>
        </div>
        <div class="user-menu">
            <span style="font-weight: 700; margin-right: 10px;">Hi, <?php echo htmlspecialchars($_SESSION['ten_nguoi_dung']); ?></span>
            <a href="sign_out.php" class="btn btn-outline" style="border-color: #dc3545; color: #dc3545;">THOÁT</a>
        </div>
    </nav>

    <div class="layout-container">
        
        <aside class="sidebar">
            <div class="sidebar-title"><i class="fas fa-folder-open"></i> Danh Sách Của Tôi</div>
            
            <a href="tu_yeu_thich.php?list=all" class="list-group-item <?php echo $current_list_id == 'all' ? 'active' : ''; ?>">
                <span><i class="fas fa-layer-group"></i> Tất cả từ vựng</span>
            </a>

            <?php while ($lst = $result_lists->fetch_assoc()): ?>
                <div class="list-group-item <?php echo $current_list_id == $lst['id_danh_sach'] ? 'active' : ''; ?>">
                    <a href="tu_yeu_thich.php?list=<?php echo $lst['id_danh_sach']; ?>" style="text-decoration:none; color:inherit; flex:1; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-book"></i> <?php echo htmlspecialchars($lst['ten_danh_sach']); ?>
                    </a>
                    
                    <div class="list-actions">
                        <button class="action-btn btn-edit" onclick="renameList(<?php echo $lst['id_danh_sach']; ?>, '<?php echo htmlspecialchars($lst['ten_danh_sach']); ?>')" title="Đổi tên">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="action-btn btn-del" onclick="deleteList(<?php echo $lst['id_danh_sach']; ?>)" title="Xóa danh sách">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>

            <button class="create-btn" onclick="createNewList()">
                <i class="fas fa-plus-circle"></i> Tạo danh sách mới
            </button>
        </aside>

        <main class="content">
            <div class="page-header">
                <div class="header-left">
                    <h2 style="margin: 0; color: #2d3436;">
                        <?php echo htmlspecialchars($current_list_name); ?>
                    </h2>
                    <span style="background: #e3f2fd; color: #007bff; padding: 5px 12px; border-radius: 20px; font-weight: 700; font-size: 14px;">
                        <?php echo $total_records; ?> từ
                    </span>
                </div>

                <div>
                    <?php if ($total_records >= 4): ?>
                        <a href="review.php?list=<?php echo $current_list_id; ?>" class="btn-review">
                            <i class="fas fa-robot"></i> Ôn tập cùng AI
                        </a>
                    <?php else: ?>
                         <span style="color: #999; font-size: 0.9rem; font-style: italic;">
                            <i class="fas fa-info-circle"></i> Thêm >4 từ để dùng AI
                         </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($result_words->num_rows > 0): ?>
                <?php while ($row = $result_words->fetch_assoc()): ?>
                    <div class="word-card">
                        <div class="card-top">
                            <div>
                                <span class="word-title"><?php echo htmlspecialchars($row['ten_tu_vung']); ?></span>
                                <div class="pronunciation">
                                    <span>/<?php echo htmlspecialchars($row['phat_am']); ?>/</span>
                                    <button class="btn-sound" onclick="docTu('<?php echo htmlspecialchars($row['ten_tu_vung']); ?>')"><i class="fas fa-volume-up"></i></button>
                                </div>
                            </div>
                            <?php if(!empty($row['ten_danh_sach'])): ?>
                                <span class="word-pos" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba;">
                                    <i class="fas fa-folder"></i> <?php echo htmlspecialchars($row['ten_danh_sach']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="meaning-box">👉 <?php echo htmlspecialchars($row['nghia_tieng_viet']); ?></div>
                        
                        <?php if(!empty($row['vi_du'])): 
                             $tu_can_tim = $row['ten_tu_vung'];
                             $vi_du_hien_thi = preg_replace('/(' . preg_quote($tu_can_tim, '/') . ')/i', '<b style="color:#2d3436;background-color:#fff3cd;padding:0 2px;">$1</b>', htmlspecialchars($row['vi_du']));
                        ?>
                            <div class="example-box">"<?php echo $vi_du_hien_thi; ?>"</div>
                        <?php endif; ?>

                        <div style="text-align: right; margin-top: 10px;">
                            <a href="xoa_tu_khoi_list.php?id_dsyt=<?php echo $row['id_dsyt']; ?>&list=<?php echo $current_list_id; ?>" 
                               class="btn btn-outline" style="border-color:#dc3545; color:#dc3545;" 
                               onclick="return confirm('Bạn muốn xóa từ này?');">
                                <i class="fas fa-trash-alt"></i> Xóa
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>

                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="tu_yeu_thich.php?list=<?php echo $current_list_id; ?>&page=<?php echo $i; ?>" class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>" style="text-decoration:none; display:flex; justify-content:center; align-items:center;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>

            <?php else: ?>
                <div class="empty-state">
                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="100" style="opacity:0.5; margin-bottom:15px;">
                    <p>Danh sách này chưa có từ vựng nào.</p>
                    <a href="word_list.php" class="btn btn-green">Đi thêm từ ngay</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // 1. Đọc từ
        function docTu(tu_vung) {
            if ('speechSynthesis' in window) {
                var msg = new SpeechSynthesisUtterance(); msg.text = tu_vung; msg.lang = 'en-US'; window.speechSynthesis.speak(msg);
            }
        }

        // 2. Tạo danh sách mới
        async function createNewList() {
            // Hiện Popup nhập tên
            const { value: listName } = await Swal.fire({
                title: 'Tạo danh sách mới',
                input: 'text',
                inputPlaceholder: 'Nhập tên danh sách (VD: Ôn thi)...',
                showCancelButton: true,
                confirmButtonText: 'Tạo ngay',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#28a745',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Bạn chưa nhập tên danh sách!'
                    }
                }
            });

            // Nếu người dùng đã nhập tên và bấm Tạo
            if (listName) {
                // Gửi yêu cầu lên Server
                const formData = new FormData();
                formData.append('action', 'create_list_only'); // Gọi action mới chúng ta vừa thêm
                formData.append('new_name', listName);

                const res = await fetch('ajax_save_word.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload()); // Tải lại trang để thấy list mới bên trái
                } else {
                    Swal.fire('Lỗi', data.message, 'error');
                }
            }
        }

        // 3. Đổi tên danh sách
        async function renameList(id, oldName) {
            const { value: newName } = await Swal.fire({
                title: 'Đổi tên danh sách',
                input: 'text',
                inputValue: oldName,
                showCancelButton: true,
                confirmButtonText: 'Lưu thay đổi'
            });

            if (newName && newName !== oldName) {
                const formData = new FormData();
                formData.append('action', 'rename_list');
                formData.append('id_list', id);
                formData.append('new_name', newName);

                const res = await fetch('ajax_save_word.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.status === 'success') {
                    Swal.fire('Thành công', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Lỗi', data.message, 'error');
                }
            }
        }

        // 4. Xóa danh sách
        function deleteList(id) {
            Swal.fire({
                title: 'Bạn chắc chắn chứ?',
                text: "Toàn bộ từ trong danh sách này cũng sẽ bị xóa khỏi mục Yêu Thích!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Vâng, xóa nó!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete_list');
                    formData.append('id_list', id);

                    const res = await fetch('ajax_save_word.php', { method: 'POST', body: formData });
                    const data = await res.json();

                    if (data.status === 'success') {
                        Swal.fire('Đã xóa!', data.message, 'success').then(() => {
                            window.location.href = 'tu_yeu_thich.php?list=all';
                        });
                    } else {
                        Swal.fire('Lỗi', data.message, 'error');
                    }
                }
            })
        }
    </script>
</body>
</html>