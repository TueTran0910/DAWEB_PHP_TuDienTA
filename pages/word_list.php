<?php
session_start();
include '../includes/connect_sql.php';

// --- PHẦN XỬ LÝ DATA ---
$limit = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// 1. Lấy tham số
$loai_tu_filter = isset($_GET['loai_tu']) ? $_GET['loai_tu'] : '';
$sort_option = isset($_GET['sort']) ? $_GET['sort'] : 'az';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : ''; 

// XÂY DỰNG CÂU TRUY VẤN ĐỘNG
$where_sql = " WHERE 1=1"; 
$params = [];
$types = "";

// Lọc theo loại từ
if ($loai_tu_filter != '') {
    $where_sql .= " AND loai_tu = ?";
    $params[] = $loai_tu_filter;
    $types .= "s";
}

// Lọc theo tìm kiếm (NÂNG CẤP: 2 CHIỀU)
if ($search_query != '') {
    // Tìm trong tên tiếng Anh HOẶC nghĩa tiếng Việt
    // Lưu ý: Phải để trong ngoặc đơn ( ... ) để không bị sai logic với các điều kiện khác
    $where_sql .= " AND (ten_tu_vung LIKE ? OR nghia_tieng_viet LIKE ?)";
    
    $search_param = "%" . $search_query . "%";
    $params[] = $search_param;
    $params[] = $search_param; // Thêm 2 lần cho 2 dấu ?
    $types .= "ss";            // Thêm 2 kiểu string
}

// 2. Đếm tổng số lượng
$sql_count = "SELECT COUNT(*) as total FROM tu_vung" . $where_sql;
$stmt_count = $ket_noi->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// 3. Truy vấn chính
$sql = "SELECT * FROM tu_vung" . $where_sql;

if ($sort_option == 'za') $sql .= " ORDER BY ten_tu_vung DESC";
else $sql .= " ORDER BY ten_tu_vung ASC";

$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $ket_noi->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// --- AJAX ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    renderWordList($result, $ket_noi, $loai_tu_filter, $page, $total_pages, $total_records, $sort_option, $search_query);
    exit(); 
}

// --- HÀM RENDER HTML ---
function renderWordList($result, $ket_noi, $loai_tu_filter, $page, $total_pages, $total_records, $sort_option, $search_query) {
    ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h2 style="color: #3c3c3c; margin: 0; font-size: 1.5rem;">
            <?php 
                if ($search_query != '') echo '🔍 Kết quả cho: "' . htmlspecialchars($search_query) . '"';
                else echo $loai_tu_filter == '' ? 'Tất cả từ vựng' : 'Lọc: ' . htmlspecialchars($loai_tu_filter); 
            ?>
            <span style="font-size: 14px; color: #999; font-weight: normal;">(<?php echo $total_records; ?> từ)</span>
        </h2>
        
        <div class="sort-box" style="display: flex; align-items: center; gap: 8px;">
            <label for="sortSelect" style="font-weight: 600; color: #555; font-size: 14px;">Sắp xếp:</label>
            <select id="sortSelect" onchange="loadPage(1)" style="padding: 8px 15px; border-radius: 8px; border: 1px solid #ddd; outline: none; cursor: pointer;">
                <option value="az" <?php echo $sort_option == 'az' ? 'selected' : ''; ?>>A → Z</option>
                <option value="za" <?php echo $sort_option == 'za' ? 'selected' : ''; ?>>Z → A</option>
            </select>
        </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php 
            $da_thich = false;
            $ten_list_da_luu = "";
            if (isset($_SESSION['id_nguoi_dung'])) {
                $id_user = $_SESSION['id_nguoi_dung'];
                $id_tu = isset($row['id_tuvung']) ? $row['id_tuvung'] : $row['id'];
                $check_sql = "SELECT ds.ten_danh_sach FROM yeu_thich yt 
                              LEFT JOIN danh_sach ds ON yt.id_danh_sach = ds.id_danh_sach
                              WHERE yt.id_user = $id_user AND yt.id_tuvung = $id_tu LIMIT 1";
                $res_fav = $ket_noi->query($check_sql);
                if ($res_fav && $res_fav->num_rows > 0) {
                    $da_thich = true;
                    $row_fav = $res_fav->fetch_assoc();
                    $ten_list_da_luu = $row_fav['ten_danh_sach'] ? $row_fav['ten_danh_sach'] : "Chưa phân loại";
                }
            }
            $id_tu_chuan = isset($row['id_tuvung']) ? $row['id_tuvung'] : $row['id'];
            ?>
            <div class="word-card">
                <div class="card-top">
                    <div>
                        <span class="word-title">
                            <?php 
                                // Highlight từ khóa tìm kiếm trong Tên Tiếng Anh
                                if($search_query != '') {
                                    echo preg_replace('/(' . preg_quote($search_query, '/') . ')/i', '<span style="background:#fff3cd;">$1</span>', htmlspecialchars($row['ten_tu_vung']));
                                } else {
                                    echo htmlspecialchars($row['ten_tu_vung']); 
                                }
                            ?>
                        </span>
                        <div class="pronunciation">
                            <span>/<?php echo htmlspecialchars($row['phat_am']); ?>/</span>
                            <button class="btn-sound" onclick="docTu('<?php echo htmlspecialchars($row['ten_tu_vung']); ?>')">
                                <i class="fas fa-volume-up"></i>
                            </button>
                        </div>
                    </div>
                    <span class="word-pos"><?php echo htmlspecialchars($row['loai_tu']); ?></span>
                </div>

                <div class="meaning-box">👉 
                    <?php 
                        // Highlight từ khóa tìm kiếm trong Nghĩa Tiếng Việt (MỚI)
                        if($search_query != '') {
                            echo preg_replace('/(' . preg_quote($search_query, '/') . ')/i', '<span style="background:#fff3cd;">$1</span>', htmlspecialchars($row['nghia_tieng_viet']));
                        } else {
                            echo htmlspecialchars($row['nghia_tieng_viet']);
                        }
                    ?>
                </div>
                
                <?php if(!empty($row['vi_du'])): 
                     $tu_can_tim = $row['ten_tu_vung'];
                     $vi_du_hien_thi = preg_replace('/(' . preg_quote($tu_can_tim, '/') . ')/i', '<b style="color:#2d3436;background-color:#fff3cd;padding:0 2px;">$1</b>', htmlspecialchars($row['vi_du']));
                ?>
                    <div class="example-box">"<?php echo $vi_du_hien_thi; ?>"</div>
                <?php endif; ?>

                <div style="text-align: right; margin-top: 10px;">
                    <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
                        <button onclick="openSaveModal(<?php echo $id_tu_chuan; ?>)" class="btn <?php echo $da_thich ? 'btn-green' : 'btn-outline'; ?>">
                            <?php if ($da_thich): ?>
                                <i class="fas fa-check"></i> Đã lưu: <?php echo htmlspecialchars($ten_list_da_luu); ?>
                            <?php else: ?>
                                <i class="far fa-star"></i> Lưu từ
                            <?php endif; ?>
                        </button>
                    <?php else: ?>
                        <a href="sign_in.php" class="btn btn-outline" onclick="return confirm('Đăng nhập để lưu từ nhé!');">
                            <i class="far fa-star"></i> Lưu từ
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <button type="button" class="page-btn" onclick="loadPage(1)" title="Trang đầu"><i class="fas fa-angle-double-left"></i></button>
                <button type="button" class="page-btn" onclick="loadPage(<?php echo ($page - 1); ?>)" title="Trang trước"><i class="fas fa-angle-left"></i></button>
            <?php endif; ?>

            <?php
            $visible_pages = 5;
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            if ($total_pages > $visible_pages) {
                if ($page <= 3) $end = $visible_pages;
                if ($page > $total_pages - 2) $start = $total_pages - 4;
            } else {
                $start = 1; $end = $total_pages;
            }
            for ($i = $start; $i <= $end; $i++): 
                if($i > 0 && $i <= $total_pages): ?>
                <button type="button" class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>" onclick="loadPage(<?php echo $i; ?>)"><?php echo $i; ?></button>
            <?php endif; endfor; ?>

            <?php if ($page < $total_pages): ?>
                <button type="button" class="page-btn" onclick="loadPage(<?php echo ($page + 1); ?>)" title="Trang sau"><i class="fas fa-angle-right"></i></button>
                <button type="button" class="page-btn" onclick="loadPage(<?php echo $total_pages; ?>)" title="Trang cuối"><i class="fas fa-angle-double-right"></i></button>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="empty-state"><i class="fas fa-search" style="font-size: 60px; color: #ddd; margin-bottom: 10px;"></i><p style="color:#777;">Không tìm thấy từ vựng nào.</p></div>
    <?php endif; 
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kho Từ Vựng - Wordik</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/word_list.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .search-wrapper { position: relative; margin-bottom: 25px; }
        .search-input-main { width: 100%; padding: 15px 50px 15px 20px; border-radius: 12px; border: 2px solid #e5e5e5; font-size: 16px; font-family: 'Nunito', sans-serif; outline: none; transition: all 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .search-input-main:focus { border-color: #1cb0f6; box-shadow: 0 0 0 4px rgba(28, 176, 246, 0.1); }
        .search-btn-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #afafaf; font-size: 20px; cursor: pointer; padding: 5px; }
        .search-btn-icon:hover { color: #1cb0f6; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="../index.php" class="logo"><i class="fas fa-feather-alt"></i> Wordik</a>
        <div class="nav-links">
            <a href="word_list.php" class="active">KHO TỪ VỰNG</a>
            <a href="word_history.php">LỊCH SỬ</a>
            <a href="tu_yeu_thich.php">DANH SÁCH TƯ VỰNG YÊU THÍCH</a>
        </div>
        <div class="user-menu">
            <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
                <span style="font-weight: 700; margin-right: 10px;">Hi, <?php echo htmlspecialchars($_SESSION['ten_nguoi_dung']); ?></span>
                <a href="sign_out.php" class="btn btn-outline" style="border-color: #dc3545; color: #dc3545;">THOÁT</a>
            <?php else: ?>  
                <a href="sign_in.php" class="btn btn-outline">ĐĂNG NHẬP</a>
                <a href="register.php" class="btn btn-green">BẮT ĐẦU</a> 
            <?php endif; ?>
        </div>
    </nav>

    <div class="layout-container">
        <aside class="sidebar" style="max-height: 80vh; overflow-y: auto;">
            <div class="sidebar-title"><i class="fas fa-filter"></i> Lọc theo loại</div>
            <form id="filterForm">
                <div class="radio-item"><input type="radio" id="type_all" name="loai_tu" value="" class="radio-input" <?php echo $loai_tu_filter == '' ? 'checked' : ''; ?> onchange="loadPage(1)"><label for="type_all" class="radio-label"><span>Tất cả</span> <i class="fas fa-layer-group"></i></label></div>
                <div class="radio-item"><input type="radio" id="type_noun" name="loai_tu" value="Danh từ" class="radio-input" onchange="loadPage(1)"><label for="type_noun" class="radio-label"><span>Danh từ</span> <i class="fas fa-cube"></i></label></div>
                <div class="radio-item"><input type="radio" id="type_verb" name="loai_tu" value="Động từ" class="radio-input" onchange="loadPage(1)"><label for="type_verb" class="radio-label"><span>Động từ</span> <i class="fas fa-running"></i></label></div>
                <div class="radio-item"><input type="radio" id="type_adj" name="loai_tu" value="Tính từ" class="radio-input" onchange="loadPage(1)"><label for="type_adj" class="radio-label"><span>Tính từ</span> <i class="fas fa-star"></i></label></div>
                <div class="radio-item"><input type="radio" id="type_prep" name="loai_tu" value="Giới từ" class="radio-input" onchange="loadPage(1)"><label for="type_prep" class="radio-label"><span>Giới từ</span> <i class="fas fa-random"></i></label></div>
                <div class="radio-item"><input type="radio" id="type_pronoun" name="loai_tu" value="Đại từ" class="radio-input" onchange="loadPage(1)"><label for="type_pronoun" class="radio-label"><span>Đại từ</span> <i class="fas fa-user-tag"></i></label></div>
                <div class="radio-item"><input type="radio" id="type_adverb" name="loai_tu" value="Trạng từ" class="radio-input" onchange="loadPage(1)"><label for="type_adverb" class="radio-label"><span>Trạng từ</span> <i class="fas fa-bolt"></i></label></div>
                <div class="radio-item"><input type="radio" id="type_conj" name="loai_tu" value="Liên từ" class="radio-input" onchange="loadPage(1)"><label for="type_conj" class="radio-label"><span>Liên từ</span> <i class="fas fa-link"></i></label></div>
                <div class="radio-item"><input type="radio" id="type_det" name="loai_tu" value="Từ hạn định" class="radio-input" onchange="loadPage(1)"><label for="type_det" class="radio-label"><span>Từ hạn định</span> <i class="fas fa-hand-point-right"></i></label></div>
                <div class="radio-item"><input type="radio" id="type_inter" name="loai_tu" value="Thán từ" class="radio-input" onchange="loadPage(1)"><label for="type_inter" class="radio-label"><span>Thán từ</span> <i class="fas fa-exclamation-circle"></i></label></div>
            </form>
        </aside>

        <main class="content" style="position: relative; min-height: 400px;">
            <div class="search-wrapper">
                <input type="text" id="searchInput" class="search-input-main" placeholder="Nhập từ vựng hoặc nghĩa tiếng Việt..." onkeyup="handleSearch()">
                <i class="fas fa-search search-btn-icon" onclick="loadPage(1)"></i>
            </div>

            <div class="loading-overlay" id="loadingOverlay"><i class="fas fa-spinner fa-spin"></i></div>
            <div id="wordListContainer">
                <?php renderWordList($result, $ket_noi, $loai_tu_filter, $page, $total_pages, $total_records, $sort_option, $search_query); ?>
            </div>
        </main>
    </div>

    <script>
        let searchTimeout;
        function handleSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { loadPage(1); }, 500); 
        }

        function docTu(tu_vung) {
            if ('speechSynthesis' in window) {
                var msg = new SpeechSynthesisUtterance(); msg.text = tu_vung; msg.lang = 'en-US'; window.speechSynthesis.speak(msg);
            } else { alert("Trình duyệt không hỗ trợ âm thanh."); }
        }

        async function openSaveModal(id_tuvung) {
             const response = await fetch('ajax_save_word.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=get_lists' });
            const result = await response.json();
            if(result.status === 'error') { Swal.fire('Lỗi', result.message, 'error'); return; }

            let options = `<option value="new">+ Tạo danh sách mới...</option>`;
            if(result.data.length > 0) {
                options += `<optgroup label="Danh sách của bạn">`;
                result.data.forEach(l => { options += `<option value="${l.id_danh_sach}">${l.ten_danh_sach}</option>`; });
                options += `</optgroup>`;
            }

            const { value: formValues } = await Swal.fire({
                title: 'Lưu vào danh sách',
                html: `<p style="margin-bottom:5px; text-align:left;">Chọn danh sách:</p><select id="swal-list" class="swal2-input" style="margin:0 0 15px 0;">${options}</select><input id="swal-new" class="swal2-input" placeholder="Nhập tên danh sách mới..." style="display:block; margin:0;">`,
                showCancelButton: true, confirmButtonText: 'Lưu ngay',
                didOpen: () => {
                    const select = document.getElementById('swal-list');
                    const input = document.getElementById('swal-new');
                    if(result.data.length > 0) { select.value = result.data[0].id_danh_sach; input.style.display = 'none'; }
                    select.onchange = () => { input.style.display = (select.value === 'new') ? 'block' : 'none'; if(select.value === 'new') input.focus(); };
                },
                preConfirm: () => {
                    const select = document.getElementById('swal-list');
                    const input = document.getElementById('swal-new');
                    return { mode: select.value === 'new' ? 'new' : 'existing', val: select.value === 'new' ? input.value : select.value }
                }
            });

            if (formValues) {
                if(formValues.mode === 'new' && !formValues.val) { Swal.fire('Lỗi', 'Tên danh sách không được để trống', 'warning'); return; }
                const saveRes = await fetch('ajax_save_word.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `action=save_word&id_tuvung=${id_tuvung}&mode=${formValues.mode}&list_val=${encodeURIComponent(formValues.val)}` });
                const saveResult = await saveRes.json();
                if(saveResult.status === 'saved' || saveResult.status === 'removed') {
                    Swal.fire({ icon: 'success', title: saveResult.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    setTimeout(() => loadPage(<?php echo $page; ?>), 1000); 
                } else { Swal.fire('Lỗi', saveResult.message, 'error'); }
            }
        }

        function loadPage(pageNumber) {
            const radios = document.getElementsByName('loai_tu');
            let selectedType = '';
            for (const radio of radios) { if (radio.checked) { selectedType = radio.value; break; } }
            const sortVal = document.getElementById('sortSelect') ? document.getElementById('sortSelect').value : 'az';
            const searchVal = document.getElementById('searchInput').value;

            document.getElementById('loadingOverlay').style.display = 'flex';
            const url = `word_list.php?ajax=1&page=${pageNumber}&loai_tu=${encodeURIComponent(selectedType)}&sort=${sortVal}&q=${encodeURIComponent(searchVal)}`;

            fetch(url).then(response => response.text()).then(html => {
                document.getElementById('wordListContainer').innerHTML = html;
                document.getElementById('loadingOverlay').style.display = 'none';
                if(document.activeElement.id !== 'searchInput') window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    </script>
</body>
</html>