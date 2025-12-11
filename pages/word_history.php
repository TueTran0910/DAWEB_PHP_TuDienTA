<?php
session_start();
include '../includes/connect_sql.php';

// Chặn người lạ
if (!isset($_SESSION['id_nguoi_dung'])) {
    header("Location: sign_in.php");
    exit();
}

$id_user = $_SESSION['id_nguoi_dung'];

// --- XỬ LÝ BỘ LỌC NGÀY ---
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Câu lệnh SQL chuẩn: JOIN bảng lịch sử với bảng từ vựng
$sql = "SELECT ls.id_ls, ls.thoi_gian_tra, tv.ten_tu_vung, tv.nghia_tieng_viet 
        FROM lich_su ls
        JOIN tu_vung tv ON ls.id_tuvung = tv.id_tuvung
        WHERE ls.id_user = ?";

$params = [$id_user];
$types = "i";

// Nếu có chọn ngày lọc
if (!empty($date_from) && !empty($date_to)) {
    $sql .= " AND ls.thoi_gian_tra BETWEEN ? AND ?";
    $params[] = $date_from . " 00:00:00"; // Bắt đầu ngày
    $params[] = $date_to . " 23:59:59";   // Kết thúc ngày
    $types .= "ss";
}

$sql .= " ORDER BY ls.thoi_gian_tra DESC"; // Mới nhất lên đầu

$stmt = $ket_noi->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử tra cứu</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/word_list.css"> <style>
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        
        /* CSS Bộ lọc */
        .filter-bar {
            background: white; padding: 20px; border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 25px;
            display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;
        }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-weight: 700; font-size: 0.9rem; color: #555; }
        .date-input {
            padding: 10px; border: 2px solid #eee; border-radius: 8px; font-family: 'Nunito', sans-serif;
        }
        .btn-filter {
            background: #58cc02; color: white; border: none; padding: 10px 25px;
            border-radius: 8px; font-weight: 700; cursor: pointer; height: 42px;
            box-shadow: 0 4px 0 #46a302; transition: 0.2s;
        }
        .btn-filter:active { transform: translateY(4px); box-shadow: none; }
        .btn-clear {
            background: #f1f1f1; color: #777; padding: 10px 20px; border-radius: 8px;
            font-weight: 700; text-decoration: none; display: flex; align-items: center; height: 42px;
            box-sizing: border-box;
        }

        /* Danh sách lịch sử */
        .history-item {
            background: white; border-bottom: 1px solid #f0f0f0; padding: 15px 20px;
            display: flex; justify-content: space-between; align-items: center;
            transition: 0.2s;
        }
        .history-item:first-child { border-top-left-radius: 12px; border-top-right-radius: 12px; }
        .history-item:last-child { border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; border-bottom: none; }
        .history-item:hover { background: #f9fdfc; }

        .word-main { font-size: 1.2rem; font-weight: 800; color: #2d3436; }
        .word-mean { font-size: 0.95rem; color: #636e72; margin-top: 4px; }
        .time-stamp { font-size: 0.85rem; color: #b2bec3; display: flex; align-items: center; gap: 5px; }
        .btn-view {
            color: #1cb0f6; border: 2px solid #1cb0f6; padding: 6px 15px;
            border-radius: 20px; font-weight: 700; font-size: 0.85rem;
            text-decoration: none; transition: 0.2s;
        }
        .btn-view:hover { background: #1cb0f6; color: white; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="../index.php" class="logo" style="margin-left: 20px;">Wordik</a>
        <div class="nav-links">
            <a href="word_list.php">Kho từ vựng</a>
            <a href="word_history.php" style="color: #58cc02; border-bottom: 2px solid #58cc02;">Lịch sử</a>
            <a href="tu_yeu_thich.php">Danh sách từ vựng yêu thích</a>
            
        </div>
        <div class="user-menu" style="margin-right: 20px;">
            <a href="../index.php" class="btn-outline">Trang chủ</a>
        </div>
    </nav>

    <div class="container">
        <h2 style="color: #2d3436; margin-bottom: 20px;">🕒 Lịch sử tra cứu</h2>

        <form method="GET" class="filter-bar">
            <div class="form-group">
                <label>Từ ngày:</label>
                <input type="date" name="date_from" class="date-input" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            <div class="form-group">
                <label>Đến ngày:</label>
                <input type="date" name="date_to" class="date-input" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Lọc</button>
            
            <?php if(!empty($date_from) || !empty($date_to)): ?>
                <a href="word_history.php" class="btn-clear">Xóa lọc</a>
            <?php endif; ?>
        </form>

        <?php if ($result->num_rows > 0): ?>
            <div style="box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-radius: 12px;">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="history-item">
                        <div>
                            <div class="word-main"><?php echo htmlspecialchars($row['ten_tu_vung']); ?></div>
                            <div class="word-mean"><?php echo htmlspecialchars($row['nghia_tieng_viet']); ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div class="time-stamp" style="margin-bottom: 8px; justify-content: flex-end;">
                                <i class="far fa-clock"></i> 
                                <?php echo date("H:i - d/m/Y", strtotime($row['thoi_gian_tra'])); ?>
                            </div>
                            <a href="../index.php?tukhoa=<?php echo urlencode($row['ten_tu_vung']); ?>" class="btn-view">
                                Tra lại <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 50px; color: #999;">
                <i class="fas fa-history" style="font-size: 3rem; opacity: 0.3; margin-bottom: 15px;"></i>
                <p>Không có lịch sử tra cứu nào trong khoảng thời gian này.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>