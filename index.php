<?php
session_start();
// Kết nối CSDL
include 'includes/connect_sql.php';
// Kết nối AI Helper (Đảm bảo bạn đã tạo file này theo hướng dẫn trước)
include 'includes/cohere_helper.php'; 

// --- HÀM LƯU LỊCH SỬ TRA CỨU ---
function luu_lich_su_tra_cuu($ket_noi, $id_user, $id_tuvung) {
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
    <link rel="stylesheet" href="./css/index.css">
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
                <span style="font-weight: 700; margin-right: 10px;">Hi, <?php echo htmlspecialchars($_SESSION['ten_nguoi_dung']); ?></span>
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
        if(isset($ket_noi)) {
            $sql_random = "SELECT * FROM tu_vung ORDER BY RAND() LIMIT 1";
            $result_random = $ket_noi->query($sql_random);
            if($result_random && $result_random->num_rows > 0){
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
                // --- BƯỚC 1: TÌM TRONG SQL ---
                $sql = "SELECT * FROM tu_vung WHERE ten_tu_vung = ?";
                $stmt = $ket_noi->prepare($sql);
                $stmt->bind_param("s", $tu_khoa);
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

                    if ($ai_data && isset($ai_data['nghia_tieng_viet'])) {
                        // Hiển thị Card AI (Màu tím)
                        hien_thi_card_tu_vung($ai_data, $ket_noi, $tu_khoa, true);

                        // Lưu AI vào SQL để lần sau không tốn tiền API nữa
                        try {
                            $stmt_ins = $ket_noi->prepare("INSERT INTO tu_vung (ten_tu_vung, phat_am, loai_tu, nghia_tieng_viet, vi_du) VALUES (?, ?, ?, ?, ?)");
                            $stmt_ins->bind_param("sssss", 
                                $ai_data['ten_tu_vung'], $ai_data['phat_am'], 
                                $ai_data['loai_tu'], $ai_data['nghia_tieng_viet'], $ai_data['vi_du']
                            );
                            $stmt_ins->execute();
                            $new_id = $ket_noi->insert_id;

                            // Lưu lịch sử cho từ mới này
                            if (isset($_SESSION['id_nguoi_dung']) && $new_id > 0) {
                                luu_lich_su_tra_cuu($ket_noi, $_SESSION['id_nguoi_dung'], $new_id);
                            }
                        } catch (Exception $e) { /* Bỏ qua lỗi insert */ }

                    } else {
                        // Không tìm thấy cả trong SQL lẫn AI
                        echo "
                        <div style='text-align:center; margin-top:50px;'>
                            <i class='fas fa-robot' style='font-size: 80px; color: #e5e5e5; margin-bottom: 20px;'></i>
                            <h2 style='color: #777;'>AI cũng bó tay rồi!</h2>
                            <p style='color: #999;'>Từ '<b>".htmlspecialchars($tu_khoa)."</b>' khó quá hoặc không tồn tại.</p>
                        </div>";
                    }
                }
            }
        }

        // HÀM HIỂN THỊ CARD (ĐỂ GỌN CODE)
        function hien_thi_card_tu_vung($row, $ket_noi, $tu_khoa, $is_ai = false) {
            // Logic yêu thích
            $da_thich = false;
            if (isset($_SESSION['id_nguoi_dung']) && isset($row['id_tuvung'])) {
                $id_user = $_SESSION['id_nguoi_dung'];
                $id_tu = $row['id_tuvung'];
                $check_sql = "SELECT id_dsyt FROM yeu_thich WHERE id_user = $id_user AND id_tuvung = $id_tu";
                $res_fav = $ket_noi->query($check_sql);
                if ($res_fav && $res_fav->num_rows > 0) $da_thich = true;
            }
            
            // Style AI: Viền tím
            $style = $is_ai ? "border: 2px solid #a29bfe; box-shadow: 0 8px 20px rgba(162, 155, 254, 0.2);" : "";
            ?>
            <div class="result-card" style="<?php echo $style; ?>">
                <?php if($is_ai): ?>
                    <div style="text-align:right; margin-bottom:5px;">
                        <span style="background:#a29bfe; color:white; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:bold;">✨ AI Generated</span>
                    </div>
                <?php endif; ?>

                <div class="word-header">
                    <span class="english-word"><?php echo htmlspecialchars($row['ten_tu_vung']); ?></span>
                    <i class="fas fa-volume-up btn-audio" onclick="docTu('<?php echo htmlspecialchars($row['ten_tu_vung']); ?>')"></i>
                    <?php if (!empty($row['phat_am'])): ?>
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
                    <?php if (isset($row['id_tuvung'])): 
                        $link_thich = "pages/xu_ly_yeu_thich.php?id_tuvung=" . $row['id_tuvung'] . "&tukhoa=" . urlencode($tu_khoa);
                    ?>
                        <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
                            <a href="<?php echo $link_thich; ?>" class="btn <?php echo $da_thich ? 'btn-green' : 'btn-outline'; ?>" style="font-size: 12px;">
                                <?php if ($da_thich): ?>
                                    <i class="fas fa-check"></i> ĐÃ LƯU TỪ
                                <?php else: ?>
                                    <i class="far fa-star"></i> LƯU TỪ NÀY
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <a href="pages/sign_in.php" class="btn btn-outline" onclick="return confirm('Đăng nhập để lưu từ nhé!');">
                                <i class="far fa-star"></i> LƯU TỪ
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button onclick="location.reload()" class="btn btn-outline" style="font-size: 12px;">
                            <i class="fas fa-sync"></i> TẢI LẠI ĐỂ LƯU
                        </button>
                    <?php endif; ?>
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

    <script>
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