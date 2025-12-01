<?php
session_start();
include 'includes/connect_sql.php'; 
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Từ Điển Online</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo"><i class="fas fa-book-open"></i> Wordik</a>
        
        <div class="nav-links">
            <a href="pages/words.php"><i class="fas fa-search"></i> Từ vựng</a>
            <a href="pages/the_loai.php"><i class="fas fa-layer-group"></i> Thể loại</a>
            <a href="pages/bai_thi.php"><i class="fas fa-clipboard-check"></i> Bài kiểm tra từ vựng</a>
            <a href="pages/lich_su_tra_cuu.php"><i class="fas fa-clipboard-check"></i> Lịch sử tra cứu</a>
        </div>

        <div class="user-menu">
            <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
                <span>Hi, <b><?php echo htmlspecialchars($_SESSION['ten_nguoi_dung']); ?></b></span>
                <a href="pages/sign_out.php" style="color: #dc3545;">
                    <i class="fas fa-sign-out-alt"></i> Thoát
                </a>
            <?php else: ?>  
                <a href="pages/dang_nhap.php">Đăng nhập</a>
                <a href="pages/dang_ky.php" style="background: #007bff; color: white; padding: 8px 15px; border-radius: 20px;">Đăng ký</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="main-container">
        
        <form action="" method="GET" class="search-box">
            <input type="text" name="tukhoa" placeholder="Nhập từ vựng tiếng Anh (VD: Apple, Run)..." 
                   value="<?php echo isset($_GET['tukhoa']) ? htmlspecialchars($_GET['tukhoa']) : ''; ?>" required>
            <button type="submit"><i class="fas fa-search"></i> Tra từ</button>
        </form>

        <?php
        if (isset($_GET['tukhoa']) && $_GET['tukhoa'] != '') {
            $tu_khoa = $_GET['tukhoa'];
            
            // 1. Tìm từ vựng (Lưu ý: Cột của bạn là 'ten_tu_vung')
            $sql = "SELECT * FROM tu_vung WHERE ten_tu_vung = ?";
            $stmt = $ket_noi->prepare($sql);
            $stmt->bind_param("s", $tu_khoa);
            $stmt->execute();
            $ket_qua = $stmt->get_result();

            if ($ket_qua && $ket_qua->num_rows > 0) {
                while ($row = $ket_qua->fetch_assoc()) {
                    
                    // 2. Logic kiểm tra yêu thích
                    $da_thich = false;
                    if (isset($_SESSION['id_nguoi_dung'])) {
                        $id_user = $_SESSION['id_nguoi_dung'];
                        $id_tu = $row['id_tuvung']; // Lưu ý: Cột của bạn là 'id_tuvung'
                        
                        // Kiểm tra trong bảng 'dsyt'
                        $check_sql = "SELECT id_dsyt FROM yeu_thich WHERE id_user = $id_user AND id_tuvung = $id_tu";
                        $res_fav = $ket_noi->query($check_sql);
                        if ($res_fav->num_rows > 0) {
                            $da_thich = true;
                        }
                    }
                    ?>

                    <div class="result-card">
                        <div class="word-header">
                            <span class="english-word"><?php echo htmlspecialchars($row['ten_tu_vung']); ?></span>
                            
                            <i class="fas fa-volume-up btn-audio" 
                               onclick="docTu('<?php echo htmlspecialchars($row['ten_tu_vung']); ?>')"></i>

                            <?php if(!empty($row['phat_am'])): ?>
                                <span class="pronounce"><?php echo htmlspecialchars($row['phat_am']); ?></span>
                            <?php endif; ?>

                            <?php if(!empty($row['loai_tu'])): ?>
                                <span class="word-type"><?php echo htmlspecialchars($row['loai_tu']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="meaning">
                            👉 <?php echo htmlspecialchars($row['nghia_tieng_viet']); ?>
                        </div>

                        <?php if(!empty($row['vi_du'])): ?>
                            <div class="example">
                                "<?php echo htmlspecialchars($row['vi_du']); ?>"
                            </div>
                        <?php endif; ?>

                        <?php 
                        $link_thich = "pages/xu_ly_yeu_thich.php?id_tuvung=" . $row['id_tuvung'] . "&tukhoa=" . urlencode($tu_khoa);
                        ?>
                        
                        <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
                            <a href="<?php echo $link_thich; ?>" class="btn-favorite <?php echo $da_thich ? 'fav-yes' : 'fav-no'; ?>">
                                <?php if ($da_thich): ?>
                                    <i class="fas fa-star" style="color: #ffc107;"></i> Đã lưu
                                <?php else: ?>
                                    <i class="far fa-star"></i> Lưu từ
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <a href="pages/sign_in.php" class="btn-favorite fav-no" onclick="return confirm('Đăng nhập để lưu từ nhé!');">
                                <i class="far fa-star"></i> Lưu từ
                            </a>
                        <?php endif; ?>

                    </div>

                    <?php
                }
            } else {
                echo "<div class='error-msg' style='text-align:center; color:#777; margin-top:30px;'>
                        Không tìm thấy từ '<b>" . htmlspecialchars($tu_khoa) . "</b>'.
                      </div>";
            }
        }
        ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Từ điển Anh - Việt Online. Code by Me.</p>
        <p>
            <a href="index.php">Trang chủ</a> | 
            <a href="#">Giới thiệu</a> | 
            <a href="#">Liên hệ</a>
        </p>
    </footer>

    <script>
        function docTu(tu_vung) {
            // Kiểm tra xem trình duyệt có hỗ trợ không
            if ('speechSynthesis' in window) {
                var msg = new SpeechSynthesisUtterance();
                msg.text = tu_vung;      // Từ cần đọc
                msg.lang = 'en-US';      // Giọng Anh - Mỹ (hoặc en-GB cho Anh-Anh)
                msg.rate = 0.9;          // Tốc độ đọc (1 là bình thường, 0.9 là chậm hơn tí cho dễ nghe)
                window.speechSynthesis.speak(msg);
            } else {
                alert("Trình duyệt của bạn không hỗ trợ phát âm!");
            }
        }
    </script>

</body>
</html>