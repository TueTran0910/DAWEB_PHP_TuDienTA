<?php
session_start();
// Lưu ý đường dẫn include
include '../includes/connect_sql.php';

// --- PHẦN XỬ LÝ DATA (Dùng chung cho cả load thường và AJAX) ---
$limit = 5; // Số từ mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$loai_tu_filter = isset($_GET['loai_tu']) ? $_GET['loai_tu'] : '';

// 1. Đếm tổng số record để phân trang
$sql_count = "SELECT COUNT(*) as total FROM tu_vung";
if ($loai_tu_filter != '') {
    $sql_count .= " WHERE loai_tu = ?";
}
$stmt_count = $ket_noi->prepare($sql_count);
if ($loai_tu_filter != '') {
    $stmt_count->bind_param("s", $loai_tu_filter);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// 2. Lấy danh sách từ vựng
$sql = "SELECT * FROM tu_vung";
if ($loai_tu_filter != '') {
    $sql .= " WHERE loai_tu = ?";
}
$sql .= " LIMIT ? OFFSET ?";
$stmt = $ket_noi->prepare($sql);
if ($loai_tu_filter != '') {
    $stmt->bind_param("sii", $loai_tu_filter, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// --- [QUAN TRỌNG] LOGIC AJAX ---
// Nếu có tham số ?ajax=1, chỉ in ra danh sách từ rồi DỪNG (exit)
// Không in ra header, footer hay html bao quanh
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    renderWordList($result, $ket_noi, $loai_tu_filter, $page, $total_pages, $total_records);
    exit(); 
}

// --- HÀM RENDER HTML (Để tái sử dụng) ---
function renderWordList($result, $ket_noi, $loai_tu_filter, $page, $total_pages, $total_records) {
    ?>
    <h2 style="margin-bottom: 20px; color: #3c3c3c;">
        <?php echo $loai_tu_filter == '' ? 'Tất cả từ vựng' : 'Đang lọc: ' . htmlspecialchars($loai_tu_filter); ?>
        <span style="font-size: 14px; color: #999; font-weight: normal;">(<?php echo $total_records; ?> từ)</span>
    </h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php 
            $da_thich = false;
            if (isset($_SESSION['id_nguoi_dung'])) {
                $id_user = $_SESSION['id_nguoi_dung'];
                $id_tu = $row['id_tuvung'];
                $check_sql = "SELECT id_dsyt FROM yeu_thich WHERE id_user = $id_user AND id_tuvung = $id_tu";
                $res_fav = $ket_noi->query($check_sql);
                if ($res_fav && $res_fav->num_rows > 0) $da_thich = true;
            }
            ?>
            <div class="word-card">
                <div class="card-top">
                    <div>
                        <span class="word-title"><?php echo htmlspecialchars($row['ten_tu_vung']); ?></span>
                        <div class="pronunciation">
                            <span>/<?php echo htmlspecialchars($row['phat_am']); ?>/</span>
                            <button class="btn-sound" onclick="docTu('<?php echo htmlspecialchars($row['ten_tu_vung']); ?>')">
                                <i class="fas fa-volume-up"></i>
                            </button>
                        </div>
                    </div>
                    <span class="word-pos"><?php echo htmlspecialchars($row['loai_tu']); ?></span>
                </div>

                <div class="meaning-box">👉 <?php echo htmlspecialchars($row['nghia_tieng_viet']); ?></div>
                <?php if(!empty($row['vi_du'])): ?>
                    <div class="example-box">"<?php echo htmlspecialchars($row['vi_du']); ?>"</div>
                <?php endif; ?>

                <div style="text-align: right; margin-top: 10px;">
                    <?php 
                    $link_thich = "xu_ly_yeu_thich.php?id_tuvung=" . $row['id_tuvung'] . "&redirect=words";
                    if($loai_tu_filter) $link_thich .= "&loai_tu=" . urlencode($loai_tu_filter);
                    $link_thich .= "&page=" . $page;
                    ?>
                    <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
                        <a href="<?php echo $link_thich; ?>" class="btn <?php echo $da_thich ? 'btn-green' : 'btn-outline'; ?>">
                            <?php if ($da_thich): echo '<i class="fas fa-check"></i> Đã lưu'; else: echo '<i class="far fa-star"></i> Lưu từ'; endif; ?>
                        </a>
                    <?php else: ?>
                        <a href="sign_in.php" class="btn btn-outline" onclick="return confirm('Đăng nhập để lưu từ nhé!');">
                            <i class="far fa-star"></i> Lưu từ
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>

        <!-- PHÂN TRANG (Dùng class pagination-btn để JS bắt sự kiện) -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <button class="page-btn" onclick="loadPage(<?php echo ($page - 1); ?>)"><i class="fas fa-chevron-left"></i></button>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <button class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>" onclick="loadPage(<?php echo $i; ?>)">
                    <?php echo $i; ?>
                </button>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <button class="page-btn" onclick="loadPage(<?php echo ($page + 1); ?>)"><i class="fas fa-chevron-right"></i></button>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-box-open" style="font-size: 60px; margin-bottom: 20px;"></i>
            <p>Không tìm thấy từ vựng nào.</p>
        </div>
    <?php endif; 
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kho Từ Vựng - Wordik</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css//word_list.css">
    
</head>
<body>

    <nav class="navbar">
        <a href="../index.php" class="logo"><i class="fas fa-feather-alt"></i> Wordik</a>
        <div class="nav-links">
            <a href="words.php" class="active">KHO TỪ VỰNG</a>
            <a href="bai_thi.php">LUYỆN TẬP</a>
            <a href="lich_su_tra_cuu.php">LỊCH SỬ</a>
        </div>
        <div class="user-menu">
            <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
                <span style="font-weight: 700; margin-right: 10px;">Hi, <?php echo htmlspecialchars($_SESSION['ten_nguoi_dung']); ?></span>
                <a href="sign_out.php" class="btn btn-outline" style="border-color: #dc3545; color: #dc3545; box-shadow: 0 4px 0 #bd2130;">THOÁT</a>
            <?php else: ?>  
                <a href="sign_in.php" class="btn btn-outline">ĐĂNG NHẬP</a>
                <a href="register.php" class="btn btn-green">BẮT ĐẦU</a> 
            <?php endif; ?>
        </div>
    </nav>

    <div class="layout-container">
        <!-- SIDEBAR BỘ LỌC (RADIO BUTTONS) -->
        <aside class="sidebar">
            <div class="sidebar-title"><i class="fas fa-filter"></i> Lọc theo loại</div>
            <form id="filterForm">
                <!-- Radio Tất cả -->
                <div class="radio-item">
                    <input type="radio" id="type_all" name="loai_tu" value="" class="radio-input" <?php echo $loai_tu_filter == '' ? 'checked' : ''; ?> onchange="loadPage(1)">
                    <label for="type_all" class="radio-label">
                        <span>Tất cả</span> <i class="fas fa-layer-group"></i>
                    </label>
                </div>
                <!-- Radio Danh từ -->
                <div class="radio-item">
                    <input type="radio" id="type_noun" name="loai_tu" value="Danh từ" class="radio-input" <?php echo $loai_tu_filter == 'Danh từ' ? 'checked' : ''; ?> onchange="loadPage(1)">
                    <label for="type_noun" class="radio-label">
                        <span>Danh từ</span> <i class="fas fa-cube"></i>
                    </label>
                </div>
                <!-- Radio Động từ -->
                <div class="radio-item">
                    <input type="radio" id="type_verb" name="loai_tu" value="Động từ" class="radio-input" <?php echo $loai_tu_filter == 'Động từ' ? 'checked' : ''; ?> onchange="loadPage(1)">
                    <label for="type_verb" class="radio-label">
                        <span>Động từ</span> <i class="fas fa-running"></i>
                    </label>
                </div>
                <!-- Radio Tính từ -->
                <div class="radio-item">
                    <input type="radio" id="type_adj" name="loai_tu" value="Tính từ" class="radio-input" <?php echo $loai_tu_filter == 'Tính từ' ? 'checked' : ''; ?> onchange="loadPage(1)">
                    <label for="type_adj" class="radio-label">
                        <span>Tính từ</span> <i class="fas fa-star"></i>
                    </label>
                </div>
                <!-- Radio Giới từ -->
                <div class="radio-item">
                    <input type="radio" id="type_prep" name="loai_tu" value="Giới từ" class="radio-input" <?php echo $loai_tu_filter == 'Giới từ' ? 'checked' : ''; ?> onchange="loadPage(1)">
                    <label for="type_prep" class="radio-label">
                        <span>Giới từ</span> <i class="fas fa-random"></i>
                    </label>
                </div>
            </form>
        </aside>

        <!-- KHU VỰC HIỂN THỊ DANH SÁCH (Sẽ reload bằng JS) -->
        <main class="content" style="position: relative; min-height: 400px;">
            <div class="loading-overlay" id="loadingOverlay">
                <i class="fas fa-spinner fa-spin" style="margin-right: 10px;"></i> Đang tải...
            </div>
            
            <div id="wordListContainer">
                <?php renderWordList($result, $ket_noi, $loai_tu_filter, $page, $total_pages, $total_records); ?>
            </div>
        </main>
    </div>

    <!-- JAVASCRIPT XỬ LÝ -->
    <script>
        // 1. Hàm đọc từ vựng
        function docTu(tu_vung) {
            if ('speechSynthesis' in window) {
                var msg = new SpeechSynthesisUtterance();
                msg.text = tu_vung; msg.lang = 'en-US'; msg.rate = 0.8;
                window.speechSynthesis.speak(msg);
            } else { alert("Trình duyệt không hỗ trợ âm thanh."); }
        }

        // 2. Hàm AJAX Load dữ liệu
        function loadPage(pageNumber) {
            // Lấy loại từ đang được chọn từ Radio Button
            const radios = document.getElementsByName('loai_tu');
            let selectedType = '';
            for (const radio of radios) {
                if (radio.checked) {
                    selectedType = radio.value;
                    break;
                }
            }

            // Hiện loading
            document.getElementById('loadingOverlay').style.display = 'flex';

            // Gọi AJAX về chính file này nhưng thêm ?ajax=1
            const url = `words.php?ajax=1&page=${pageNumber}&loai_tu=${encodeURIComponent(selectedType)}`;

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    // Thay thế nội dung cũ bằng nội dung mới
                    document.getElementById('wordListContainer').innerHTML = html;
                    
                    // Ẩn loading
                    document.getElementById('loadingOverlay').style.display = 'none';

                    // Scroll nhẹ lên đầu danh sách cho dễ nhìn
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                })
                .catch(err => {
                    console.error('Lỗi tải trang:', err);
                    alert('Có lỗi xảy ra khi tải dữ liệu.');
                    document.getElementById('loadingOverlay').style.display = 'none';
                });
        }
    </script>
</body>
</html>