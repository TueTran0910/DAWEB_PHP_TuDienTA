<?php
session_start();
// Kết nối CSDL
include 'includes/connect_sql.php';
// Kết nối AI Helper
include 'includes/cohere_helper.php';

// --- HÀM LƯU LỊCH SỬ TRA CỨU ---
function luu_lich_su_tra_cuu($ket_noi, $id_user, $id_tuvung)
{
    // 1. Lấy từ vừa tra gần nhất của user này
    $check = $ket_noi->query("SELECT id_tuvung FROM lich_su WHERE id_user = $id_user ORDER BY thoi_gian_tra DESC LIMIT 1");
    $last_id = ($check && $check->num_rows > 0) ? $check->fetch_assoc()['id_tuvung'] : 0;

    // 2. Nếu từ hiện tại KHÁC từ vừa tra thì mới lưu (tránh spam F5)
    if ($last_id != $id_tuvung) {
        $stmt = $ket_noi->prepare("INSERT INTO lich_su (id_user, id_tuvung, thoi_gian_tra) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $id_user, $id_tuvung);
        $stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wordik - Học từ vựng vui nhộn</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="./css/index.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <nav class="navbar">
        <a href="index.php" class="logo"><i class="fas fa-feather-alt"></i> Wordik</a>

        <div class="nav-links">
            <a href="./pages/word_list.php">KHO TỪ VỰNG</a>
            <a href="./pages/word_history.php">LỊCH SỬ</a>
            <a href="./pages/tu_yeu_thich.php">DANH SÁCH TỪ VỰNG YÊU THÍCH</a>
        </div>

        <div class="user-menu">
            <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
                <span class="user-name" style="font-weight: 700; margin-right: 10px;">Hi, <?php echo htmlspecialchars($_SESSION['ten_nguoi_dung']); ?></span>
                <a href="pages/sign_out.php" class="btn btn-outline" style="border-color: #dc3545; color: #dc3545; box-shadow: 0 4px 0 #bd2130;">THOÁT</a>
            <?php else: ?>
                <a href="pages/sign_in.php" class="btn btn-outline">ĐĂNG NHẬP</a>
                <a href="pages/register.php" class="btn btn-primary">BẮT ĐẦU</a>
            <?php endif; ?>
        </div>
    </nav>

    <?php if (!isset($_GET['tukhoa']) || $_GET['tukhoa'] == ''): ?>

        <div class="hero-container">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>

            <div class="hero-wrapper">
                <div class="hero-text-side">
                    <span class="hero-badge">👋 Chào mừng bạn đến với Wordik</span>
                    <h1 class="hero-title">
                        Học từ vựng <span class="highlight-green">Hiệu Quả</span><br>
                        và hoàn toàn <span class="highlight-blue">Miễn Phí</span>
                    </h1>
                    <p class="hero-desc">
                        Tra cứu nhanh chóng, lưu từ vựng yêu thích và ôn tập mỗi ngày với các bài kiểm tra thú vị. Xây dựng vốn từ vựng vững chắc ngay hôm nay!
                    </p>

                    <form action="" method="GET" class="search-form" style="box-shadow: 0 10px 20px rgba(0,0,0,0.05);">
                        <input type="text" name="tukhoa" class="search-input" placeholder="Nhập từ tiếng Anh (VD: Galaxy)..." required>
                        <button type="submit" class="btn btn-green" style="padding: 0 30px; font-size: 16px;">
                            <i class="fas fa-search"></i> TRA NGAY
                        </button>
                    </form>

                    <div class="tags-container" style="justify-content: flex-start;">
                        <span style="font-size: 13px; color: #999; display: flex; align-items: center;">Gợi ý:</span>
                        <a href="?tukhoa=Education" class="tag-chip">🏫 Education</a>
                        <a href="?tukhoa=Technology" class="tag-chip">💻 Technology</a>
                        <a href="?tukhoa=Food" class="tag-chip">🍔 Food</a>
                    </div>
                </div>

                <div class="hero-visual-side">
                    <i class="fas fa-book-reader main-icon"></i>
                    <i class="fas fa-star floating-item f-item-1"></i>
                    <i class="fas fa-bolt floating-item f-item-2"></i>
                    <i class="fas fa-heart floating-item f-item-3"></i>
                </div>
            </div>
        </div>

        <div class="features-grid">
            <div class="feature-item">
                <i class="fas fa-fire feature-icon"></i>
                <h3>Siêu Tốc độ</h3>
                <p style="color: #777;">Tra từ cực nhanh với gợi ý thông minh và phát âm chuẩn bản xứ.</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-brain feature-icon"></i>
                <h3>Ghi nhớ lâu</h3>
                <p style="color: #777;">Phương pháp học lặp lại ngắt quãng giúp bạn nhớ từ vựng mãi mãi.</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-mobile-alt feature-icon"></i>
                <h3>Học mọi nơi</h3>
                <p style="color: #777;">Tương thích hoàn hảo trên điện thoại, máy tính bảng và desktop.</p>
            </div>
        </div>

        <?php
        // RANDOM TỪ VỰNG MỖI NGÀY
        if (isset($ket_noi)) {
            $sql_random = "SELECT * FROM tu_vung ORDER BY RAND() LIMIT 1";
            $result_random = $ket_noi->query($sql_random);
            if ($result_random && $result_random->num_rows > 0) {
                $daily_word = $result_random->fetch_assoc();
        ?>
                <div class="daily-section">
                    <div class="daily-banner"></div>
                    <div class="daily-content">
                        <span class="daily-label"><i class="fas fa-sun"></i> TỪ VỰNG CỦA HÔM NAY</span>
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                            <div>
                                <h2 style="font-size: 32px; color: #3c3c3c; margin-bottom: 5px;">
                                    <?php echo htmlspecialchars($daily_word['ten_tu_vung']); ?>
                                </h2>
                                <p style="color: #777; font-size: 18px;">
                                    <?php echo htmlspecialchars($daily_word['nghia_tieng_viet']); ?>
                                </p>
                            </div>
                            <a href="?tukhoa=<?php echo urlencode($daily_word['ten_tu_vung']); ?>" class="btn btn-outline" style="margin-top: 10px;">
                                XEM CHI TIẾT
                            </a>
                        </div>
                    </div>
                </div>
        <?php
            }
        }
        ?>

    <?php endif; ?>


    <div style="max-width: 800px; margin: 0 auto; padding: 20px; flex: 1;">

        <?php if (isset($_GET['tukhoa']) && $_GET['tukhoa'] != ''): ?>
            <div class="search-container">
                <form action="" method="GET" class="search-form">
                    <input type="text" name="tukhoa" class="search-input"
                        placeholder="Tìm từ khác..."
                        value="<?php echo htmlspecialchars($_GET['tukhoa']); ?>" required>
                    <button type="submit" class="btn btn-green">TRA CỨU</button>
                </form>

                <div class="tags-container">
                    <a href="?tukhoa=Education" class="tag-chip">🏫 Education</a>
                    <a href="?tukhoa=Technology" class="tag-chip">💻 Technology</a>
                    <a href="?tukhoa=Travel" class="tag-chip">✈️ Travel</a>
                    <a href="?tukhoa=Food" class="tag-chip">🍔 Food</a>
                </div>
            </div>
        <?php endif; ?>

        <?php
        if (isset($_GET['tukhoa']) && $_GET['tukhoa'] != '') {
            $tu_khoa = trim($_GET['tukhoa']);

            if (isset($ket_noi)) {
                // 1. Câu lệnh SQL có 2 dấu ? (cho Tiếng Anh và Tiếng Việt)
                $sql = "SELECT * FROM tu_vung WHERE ten_tu_vung LIKE ? OR nghia_tieng_viet LIKE ?";

                $stmt = $ket_noi->prepare($sql);

                // 2. Chuẩn bị từ khóa (thêm % để tìm gần đúng)
                $param_search = "%" . $tu_khoa . "%";

                // 3. QUAN TRỌNG: "ss" nghĩa là 2 chuỗi, và $param_search phải được điền 2 lần
                $stmt->bind_param("ss", $param_search, $param_search);

                $stmt->execute();
                $ket_qua = $stmt->get_result();

                // NẾU CÓ TRONG SQL
                if ($ket_qua && $ket_qua->num_rows > 0) {
                    while ($row = $ket_qua->fetch_assoc()) {
                        // Lưu lịch sử
                        if (isset($_SESSION['id_nguoi_dung'])) {
                            luu_lich_su_tra_cuu($ket_noi, $_SESSION['id_nguoi_dung'], $row['id_tuvung']);
                        }

                        // Hiển thị Card
                        hien_thi_card_tu_vung($row, $ket_noi, $tu_khoa, false);
                    }
                }
                // NẾU KHÔNG CÓ -> HỎI AI
                else {
                    $ai_data = tra_tu_cohere($tu_khoa);

                    // Lấy dữ liệu để kiểm tra
                    $nghia = isset($ai_data['nghia_tieng_viet']) ? trim($ai_data['nghia_tieng_viet']) : '';

                    // ĐIỀU KIỆN CHẶT CHẼ:
                    // 1. Phải có dữ liệu
                    // 2. Nghĩa không được là "N/A"
                    // 3. Nghĩa không được chứa chữ "Không tìm thấy"
                    $hop_le = false;
                    if ($ai_data && !empty($nghia)) {
                        if (strtoupper($nghia) !== 'N/A' && stripos($nghia, 'Không tìm thấy') === false) {
                            $hop_le = true;
                        }
                    }

                    if ($hop_le) {
                        // --- TỪ HỢP LỆ -> HIỆN CARD VÀ LƯU ---
                        hien_thi_card_tu_vung($ai_data, $ket_noi, $tu_khoa, true);

                        // Code lưu vào DB (Giữ nguyên)
                        try {
                            $stmt_ins = $ket_noi->prepare("INSERT INTO tu_vung (ten_tu_vung, phat_am, loai_tu, nghia_tieng_viet, vi_du) VALUES (?, ?, ?, ?, ?)");
                            $stmt_ins->bind_param("sssss", $ai_data['ten_tu_vung'], $ai_data['phat_am'], $ai_data['loai_tu'], $ai_data['nghia_tieng_viet'], $ai_data['vi_du']);
                            $stmt_ins->execute();
                            $new_id = $ket_noi->insert_id;
                            if (isset($_SESSION['id_nguoi_dung']) && $new_id > 0) {
                                luu_lich_su_tra_cuu($ket_noi, $_SESSION['id_nguoi_dung'], $new_id);
                            }
                        } catch (Exception $e) {
                        }
                    } else {
                        // --- TỪ TÀO LAO / KHÔNG TÌM THẤY ---
                        // Chỉ hiện thông báo text, KHÔNG gọi hàm hiển thị card -> Không có nút nào hiện ra cả
        ?>
                        <div style="text-align:center; margin-top:60px; color: #777;">
                            <div style="font-size: 50px; margin-bottom: 15px; opacity: 0.3;"><i class="fas fa-search"></i></div>
                            <h3 style="font-weight: 600;">Không tìm thấy từ "<?php echo htmlspecialchars($tu_khoa); ?>"</h3>
                            <p style="font-size: 14px;">Từ này không tồn tại hoặc hệ thống chưa cập nhật.</p>
                        </div>
            <?php
                    }
                }
            }
        }

        // HÀM HIỂN THỊ CARD (ĐỂ GỌN CODE)
        function hien_thi_card_tu_vung($row, $ket_noi, $tu_khoa, $is_ai = false)
        {
            // ... (Giữ nguyên phần logic kiểm tra yêu thích ở đầu hàm) ...
            $da_thich = false;
            if (isset($_SESSION['id_nguoi_dung']) && isset($row['id_tuvung'])) {
                // ... code cũ ...
            }

            // --- THÊM ĐOẠN KIỂM TRA NÀY ---
            $nghia = trim($row['nghia_tieng_viet']);
            $phat_am = trim($row['phat_am']);

            // Kiểm tra xem có nên hiện nút không
            $hien_nut_luu = (!empty($nghia) && strtoupper($nghia) !== 'N/A' && stripos($nghia, 'Không tìm thấy') === false);
            $hien_nut_loa = (!empty($phat_am) && strtoupper($phat_am) !== 'N/A');

            $style = $is_ai ? "border: 2px solid #a29bfe; box-shadow: 0 8px 20px rgba(162, 155, 254, 0.2);" : "";
            ?>
            <div class="result-card" style="<?php echo $style; ?>">
                <div class="word-header">
                    <span class="english-word"><?php echo htmlspecialchars($row['ten_tu_vung']); ?></span>

                    <?php if ($hien_nut_loa): ?>
                        <i class="fas fa-volume-up btn-audio" onclick="docTu('<?php echo htmlspecialchars($row['ten_tu_vung']); ?>')"></i>
                        <span style="color: #999;">/<?php echo htmlspecialchars($row['phat_am']); ?>/</span>
                    <?php endif; ?>

                    <?php if (!empty($row['loai_tu'])): ?>
                        <span class="word-type"><?php echo htmlspecialchars($row['loai_tu']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="meaning">
                    <span style="color: var(--duo-green); margin-right: 10px;">NGHĨA LÀ:</span>
                    <?php echo htmlspecialchars($row['nghia_tieng_viet']); ?>
                </div>

                <?php if (!empty($row['vi_du'])): ?>
                    <div class="example">
                        <i class="fas fa-quote-left"></i> <?php echo htmlspecialchars($row['vi_du']); ?>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 20px; text-align: right;">
                    <?php if ($hien_nut_luu): ?>

                        <?php if (isset($row['id_tuvung'])): ?>
                            <button onclick="openSaveModal(<?php echo $row['id_tuvung']; ?>)" class="btn <?php echo $da_thich ? 'btn-green' : 'btn-outline'; ?>">
                                Lưu từ
                            </button>
                        <?php else: ?>
                            <button onclick="location.reload()" class="btn btn-outline" style="font-size: 12px;">
                                <i class="fas fa-sync"></i> TẢI LẠI ĐỂ LƯU
                            </button>
                        <?php endif; ?>

                    <?php endif; // Kết thúc if hien_nut_luu 
                    ?>
                </div>
            </div>
        <?php
        }
        ?>
    </div>

    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-col">
                <a href="#" class="logo" style="margin-bottom: 20px;"><i class="fas fa-feather-alt"></i> Wordik</a>
                <p style="color: #999; font-size: 14px; line-height: 1.6;">
                    Nền tảng học từ vựng tiếng Anh miễn phí, vui nhộn và hiệu quả.
                </p>
            </div>
            <div class="footer-col">
                <h4>Về chúng tôi</h4>
                <a href="#">Giới thiệu</a>
                <a href="#">Phương pháp học</a>
            </div>
            <div class="footer-col">
                <h4>Hỗ trợ</h4>
                <a href="#">Hướng dẫn</a>
                <a href="#">Liên hệ</a>
            </div>
            <div class="footer-col">
                <h4>Mạng xã hội</h4>
                <a href="#"><i class="fab fa-facebook"></i> Facebook</a>
                <a href="#"><i class="fab fa-youtube"></i> Youtube</a>
            </div>
        </div>
        <div class="copyright">
            © 2025 Wordik. Code by Từ Quốc Tuấn và Trần Thiên Tuệ.
        </div>
    </footer>

    <style>
        /* Mặc định ẩn trên Desktop */
        .mobile-nav {
            display: none;
        }
    </style>

    <div class="mobile-nav">
        <a href="index.php" class="mobile-nav-item">
            <i class="fas fa-home"></i> Trang chủ
        </a>
        <a href="./pages/word_list.php" class="mobile-nav-item">
            <i class="fas fa-book"></i> Kho từ
        </a>
        <a href="./pages/tu_yeu_thich.php" class="mobile-nav-item">
            <i class="fas fa-heart"></i> Yêu thích
        </a>
        <a href="./pages/word_history.php" class="mobile-nav-item">
            <i class="fas fa-history"></i> Lịch sử
        </a>
    </div>

    <div class="mobile-nav">
        <a href="index.php" class="mobile-nav-item">
            <i class="fas fa-home"></i>
            <span>Trang chủ</span>
        </a>
        <a href="./pages/word_list.php" class="mobile-nav-item">
            <i class="fas fa-book"></i>
            <span>Kho từ</span>
        </a>
        <a href="./pages/tu_yeu_thich.php" class="mobile-nav-item">
            <i class="fas fa-heart"></i>
            <span>Yêu thích</span>
        </a>
        <a href="./pages/word_history.php" class="mobile-nav-item">
            <i class="fas fa-history"></i>
            <span>Lịch sử</span>
        </a>
    </div>

    <script>
        async function openSaveModal(id_tuvung) {
            try {
                // 1. Gọi Ajax lấy danh sách
                const response = await fetch('ajax_save_word.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=get_lists'
                });

                // Kiểm tra nếu file ajax bị lỗi HTML (ví dụ lỗi include sai đường dẫn)
                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error("Lỗi từ server:", text);
                    Swal.fire('Lỗi Hệ Thống', 'Không thể kết nối đến file xử lý (Kiểm tra Console F12)', 'error');
                    return;
                }

                if (result.status === 'error') {
                    Swal.fire('Thông báo', result.message, 'error');
                    return;
                }

                // 2. Tạo HTML cho dropdown
                let options = `<option value="new">+ Tạo danh sách mới...</option>`;
                if (result.data && result.data.length > 0) {
                    options += `<optgroup label="Danh sách của bạn">`;
                    // Lưu ý: Đảm bảo tên cột khớp với database (id_danh_sach, ten_danh_sach)
                    result.data.forEach(l => {
                        options += `<option value="${l.id_danh_sach}">${l.ten_danh_sach}</option>`;
                    });
                    options += `</optgroup>`;
                }

                // 3. Hiển thị Popup
                const {
                    value: formValues
                } = await Swal.fire({
                    title: 'Lưu từ vựng',
                    html: `
                    <p style="text-align:left; margin-bottom:5px; font-weight:600;">Chọn danh sách lưu trữ:</p>
                    <select id="swal-list" class="swal2-input" style="margin-top:5px;">${options}</select>
                    <input id="swal-new" class="swal2-input" placeholder="Nhập tên danh sách mới..." style="display:none; margin-top:10px;">
                `,
                    showCancelButton: true,
                    confirmButtonText: 'Lưu ngay',
                    cancelButtonText: 'Hủy',
                    confirmButtonColor: '#2ecc71',
                    didOpen: () => {
                        const select = document.getElementById('swal-list');
                        const input = document.getElementById('swal-new');

                        // Nếu có danh sách cũ, chọn cái đầu tiên
                        if (result.data.length > 0) {
                            select.value = result.data[0].id_danh_sach;
                        } else {
                            // Nếu chưa có danh sách nào, hiện ô nhập mới luôn
                            input.style.display = 'block';
                        }

                        // Xử lý ẩn hiện ô nhập tên mới
                        select.onchange = () => {
                            if (select.value === 'new') {
                                input.style.display = 'block';
                                input.focus();
                            } else {
                                input.style.display = 'none';
                            }
                        };
                    },
                    preConfirm: () => {
                        const select = document.getElementById('swal-list');
                        const input = document.getElementById('swal-new');

                        let mode = select.value === 'new' ? 'new' : 'existing';
                        let val = select.value === 'new' ? input.value : select.value;

                        if (mode === 'new' && !val.trim()) {
                            Swal.showValidationMessage('Vui lòng nhập tên danh sách mới');
                            return false;
                        }
                        return {
                            mode: mode,
                            val: val
                        };
                    }
                });

                // 4. Gửi yêu cầu lưu nếu người dùng bấm OK
                if (formValues) {
                    const saveRes = await fetch('ajax_save_word.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=save_word&id_tuvung=${id_tuvung}&mode=${formValues.mode}&list_val=${encodeURIComponent(formValues.val)}`
                    });

                    const saveResult = await saveRes.json();

                    if (saveResult.status === 'saved' || saveResult.status === 'removed') {
                        await Swal.fire({
                            icon: 'success',
                            title: saveResult.message,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        // QUAN TRỌNG: Tải lại trang để cập nhật nút "Đã lưu"
                        location.reload();
                    } else {
                        Swal.fire('Lỗi', saveResult.message, 'error');
                    }
                }

            } catch (error) {
                console.error(error);
                Swal.fire('Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!', 'error');
            }
        }

        // Hàm đọc từ (giữ nguyên)
        function docTu(tu_vung) {
            if ('speechSynthesis' in window) {
                var msg = new SpeechSynthesisUtterance();
                msg.text = tu_vung;
                msg.lang = 'en-US';
                msg.rate = 0.8;
                window.speechSynthesis.speak(msg);
            } else {
                alert("Trình duyệt không hỗ trợ âm thanh.");
            }
        }
    </script>

</body>

</html>