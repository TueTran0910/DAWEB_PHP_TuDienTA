<?php
session_start();
include '../includes/connect_sql.php';
include '../includes/cohere_helper.php'; 

// Chặn người chưa đăng nhập
if (!isset($_SESSION['id_nguoi_dung'])) {
    header("Location: sign_in.php");
    exit();
}

$id_user = $_SESSION['id_nguoi_dung'];
$list_id = isset($_GET['list']) ? $_GET['list'] : 'all';

// --- 1. LẤY TỪ VỰNG TỪ DATABASE (TỐI ĐA 20 TỪ) ---
if ($list_id == 'all') {
    $sql = "SELECT tv.ten_tu_vung FROM tu_vung tv 
            JOIN yeu_thich yt ON tv.id_tuvung = yt.id_tuvung 
            WHERE yt.id_user = $id_user ORDER BY RAND() LIMIT 20";
} else {
    $sql = "SELECT tv.ten_tu_vung FROM tu_vung tv 
            JOIN yeu_thich yt ON tv.id_tuvung = yt.id_tuvung 
            WHERE yt.id_user = $id_user AND yt.id_danh_sach = ? 
            ORDER BY RAND() LIMIT 20";
}

$stmt = $ket_noi->prepare($sql);
if ($list_id != 'all') {
    $stmt->bind_param("i", $list_id);
}
$stmt->execute();
$result = $stmt->get_result();

$words_array = [];
while($row = $result->fetch_assoc()) {
    $words_array[] = $row['ten_tu_vung'];
}

// Kiểm tra: Cần ít nhất 4 từ để tạo đáp án trắc nghiệm
if (count($words_array) < 4) {
    echo "<script>
        alert('Bạn cần ít nhất 4 từ vựng trong danh sách để tạo bài thi!');
        window.location.href = 'tu_yeu_thich.php';
    </script>";
    exit();
}

// --- 2. GỌI AI & XỬ LÝ FALLBACK ---
$quiz_data = tao_bai_kiem_tra($words_array);
$source_type = 'AI'; // Mặc định là AI

// Nếu AI trả về null (lỗi), dùng dữ liệu mẫu
if (!$quiz_data) {
    $source_type = 'FALLBACK';
    
    // Bộ câu hỏi dự phòng (10 câu)
    $quiz_data = [
        [ "question" => "Which word means 'Hello'?", "options" => ["Xin chào", "Tạm biệt", "Cảm ơn", "Xin lỗi"], "correct_index" => 0, "explanation" => "Hello là lời chào phổ biến." ],
        [ "question" => "What is the meaning of 'Apple'?", "options" => ["Quả Cam", "Quả Táo", "Quả Chuối", "Quả Dưa"], "correct_index" => 1, "explanation" => "Apple nghĩa là quả Táo." ],
        [ "question" => "Choose the synonym for 'Happy'", "options" => ["Sad", "Angry", "Joyful", "Tired"], "correct_index" => 2, "explanation" => "Joyful đồng nghĩa với Happy (Vui vẻ)." ],
        [ "question" => "What is the past tense of 'Go'?", "options" => ["Goes", "Gone", "Went", "Going"], "correct_index" => 2, "explanation" => "Quá khứ của Go là Went." ],
        [ "question" => "Which is a color?", "options" => ["Blue", "Table", "Run", "Fast"], "correct_index" => 0, "explanation" => "Blue (Xanh dương) là một màu sắc." ],
        [ "question" => "Translate 'Thank you'", "options" => ["Xin lỗi", "Cảm ơn", "Làm ơn", "Không có chi"], "correct_index" => 1, "explanation" => "Thank you nghĩa là Cảm ơn." ],
        [ "question" => "Which word is a verb?", "options" => ["Cat", "House", "Run", "Beautiful"], "correct_index" => 2, "explanation" => "Run (Chạy) là động từ." ],
        [ "question" => "What is the opposite of 'Big'?", "options" => ["Large", "Huge", "Small", "Tall"], "correct_index" => 2, "explanation" => "Trái nghĩa với Big (To) là Small (Nhỏ)." ],
        [ "question" => "Complete: '___ name is John.'", "options" => ["I", "My", "Me", "Mine"], "correct_index" => 1, "explanation" => "My name is... (Tên của tôi là...)" ],
        [ "question" => "Which animal says 'Meow'?", "options" => ["Dog", "Cat", "Cow", "Duck"], "correct_index" => 1, "explanation" => "Con mèo (Cat) kêu Meow." ]
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ôn tập cùng AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* GIAO DIỆN STYLE DUOLINGO */
        body {
            font-family: 'Nunito', sans-serif; background-color: #f0f2f5; 
            display: flex; justify-content: center; align-items: center; 
            min-height: 100vh; margin: 0; color: #3c3c3c;
        }

        .quiz-container {
            background: white; width: 100%; max-width: 600px; padding: 30px; 
            border-radius: 20px; box-shadow: 0 8px 0 #e5e5e5; border: 2px solid #e5e5e5; 
            position: relative;
        }

        /* Header & Thanh tiến trình */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .close-btn { color: #afafaf; font-size: 1.5rem; cursor: pointer; transition: 0.2s; }
        .close-btn:hover { color: #3c3c3c; }

        .progress-container { flex: 1; margin: 0 20px; background: #e5e5e5; height: 16px; border-radius: 10px; overflow:hidden;}
        .progress-fill { background: #58cc02; height: 100%; width: 0%; transition: width 0.4s ease; border-radius: 10px; }

        /* HUY HIỆU AI / FALLBACK */
        .badge-ai {
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            color: white; padding: 5px 12px; border-radius: 20px;
            font-size: 0.8rem; font-weight: 800; box-shadow: 0 4px 10px rgba(108, 92, 231, 0.3);
        }
        .badge-fallback {
            background: #ffc107; color: #333; padding: 5px 12px; border-radius: 20px;
            font-size: 0.8rem; font-weight: 800;
        }

        /* Câu hỏi & Đáp án */
        .question-text { font-size: 1.5rem; font-weight: 700; color: #3c3c3c; margin-bottom: 30px; line-height: 1.4; }
        .options-grid { display: grid; gap: 15px; }
        
        .option-btn {
            background: white; border: 2px solid #e5e5e5; border-radius: 16px; 
            padding: 15px 20px; font-size: 1.1rem; font-weight: 600; color: #4b4b4b; 
            cursor: pointer; text-align: left; box-shadow: 0 4px 0 #e5e5e5; 
            transition: 0.1s; position: relative; top: 0;
        }
        .option-btn:hover { background: #f7f7f7; border-color: #d1d1d1; }
        .option-btn:active { top: 4px; box-shadow: none; }

        /* Trạng thái đúng/sai */
        .option-btn.correct { background: #d7ffb8; border-color: #58cc02; color: #58cc02; box-shadow: 0 4px 0 #46a302; }
        .option-btn.wrong { background: #ffdfe0; border-color: #ff4b4b; color: #ff4b4b; box-shadow: 0 4px 0 #ea2b2b; }

        /* Hộp giải thích */
        .feedback-box {
            margin-top: 25px; padding: 20px; border-radius: 16px; display: none; animation: slideUp 0.3s ease;
        }
        .feedback-box.correct { background: #d7ffb8; border: 2px solid #58cc02; color: #58cc02; }
        .feedback-box.wrong { background: #ffdfe0; border: 2px solid #ff4b4b; color: #ff4b4b; }

        .next-btn {
            background: #58cc02; color: white; border: none; padding: 15px 30px;
            width: 100%; font-size: 1.1rem; font-weight: 800; text-transform: uppercase;
            border-radius: 16px; box-shadow: 0 4px 0 #46a302; cursor: pointer;
            margin-top: 20px; display: none;
        }
        .next-btn:active { transform: translateY(4px); box-shadow: none; }

        /* Màn hình kết quả */
        .result-screen { text-align: center; display: none; }
        .score-circle {
            width: 150px; height: 150px; border-radius: 50%; border: 8px solid #ffc800;
            display: flex; justify-content: center; align-items: center; margin: 20px auto;
            font-size: 3.5rem; font-weight: 800; color: #ffc800; background: #fffdf0;
        }
        .score-label { font-size: 1.2rem; color: #777; font-weight: 700; text-transform: uppercase; }

        /* Loading */
        #loading-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: white; z-index: 999; display: flex; flex-direction: column;
            justify-content: center; align-items: center;
        }
        .loader {
            border: 5px solid #f3f3f3; border-top: 5px solid #58cc02;
            border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite;
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div id="loading-overlay">
        <div class="loader"></div>
        <h3 style="margin-top: 20px; color: #58cc02;">
            <?php echo ($source_type == 'AI') ? 'AI đang soạn đề...' : 'Đang tải dữ liệu mẫu...'; ?>
        </h3>
    </div>

    <div class="quiz-container">
        
        <div id="quiz-screen">
            <div class="header">
                <a href="tu_yeu_thich.php" class="close-btn"><i class="fas fa-times"></i></a>
                
                <div class="progress-container">
                    <div class="progress-fill" id="progress-bar"></div>
                </div>

                <?php if ($source_type == 'AI'): ?>
                    <span class="badge-ai"><i class="fas fa-bolt"></i> AI Generated</span>
                <?php else: ?>
                    <span class="badge-fallback"><i class="fas fa-exclamation-triangle"></i> Dữ liệu mẫu</span>
                <?php endif; ?>
            </div>

            <div style="text-align: right; margin-bottom: 10px; font-weight: bold; color: #58cc02;">
                Câu <span id="q-idx">1</span>/<span id="q-total">10</span>
            </div>

            <div class="question-text" id="question-text"></div>
            <div class="options-grid" id="options-area"></div>
            
            <div class="feedback-box" id="feedback"></div>
            
            <button class="next-btn" id="next-btn" onclick="nextQuestion()">Tiếp tục</button>
        </div>

        <div id="result-screen" class="result-screen">
            <h2 style="color: #58cc02;">Hoàn thành!</h2>
            
            <p class="score-label">Điểm số (Thang 10)</p>
            <div class="score-circle">
                <span id="score-point">0</span>
            </div>
            
            <p style="color: #777; margin-bottom: 30px;">
                Bạn trả lời đúng <b id="right-answers">0</b>/<b id="total-questions">0</b> câu.
            </p>
            
            <div style="display: grid; gap: 10px;">
                <a href="tu_yeu_thich.php" class="option-btn" style="text-align: center; color: #1cb0f6; border-color: #1cb0f6; box-shadow: 0 4px 0 #1899d6;">
                    <i class="fas fa-list"></i> Về danh sách
                </a>
                <button onclick="location.reload()" class="option-btn" style="text-align: center; background: #58cc02; color: white; border-color: #58cc02; box-shadow: 0 4px 0 #46a302;">
                    <i class="fas fa-redo"></i> Làm bài khác
                </button>
            </div>
        </div>

    </div>

    <script>
        const questions = <?php echo isset($quiz_data) ? json_encode($quiz_data) : '[]'; ?>;
        
        window.onload = function() {
            document.getElementById('loading-overlay').style.display = 'none';
            if(questions.length > 0) {
                document.getElementById('q-total').innerText = questions.length;
                loadQuestion();
            }
        };

        let currentIdx = 0;
        let score = 0;
        let isAnswered = false;

        function loadQuestion() {
            isAnswered = false;
            let q = questions[currentIdx];

            // Reset UI
            document.getElementById('feedback').style.display = 'none';
            document.getElementById('next-btn').style.display = 'none';
            document.getElementById('question-text').innerText = q.question;
            document.getElementById('q-idx').innerText = currentIdx + 1;
            
            // Render Đáp án
            let html = '';
            q.options.forEach((opt, index) => {
                html += `<button class="option-btn" onclick="checkAnswer(${index}, this)">${opt}</button>`;
            });
            document.getElementById('options-area').innerHTML = html;

            // Update Progress Bar
            let percent = (currentIdx / questions.length) * 100;
            document.getElementById('progress-bar').style.width = percent + '%';
        }

        function checkAnswer(selectedIdx, btn) {
            if (isAnswered) return;
            isAnswered = true;

            let q = questions[currentIdx];
            let feedback = document.getElementById('feedback');
            let allBtns = document.querySelectorAll('.option-btn');

            if (selectedIdx == q.correct_index) {
                // Đúng
                score++;
                btn.classList.add('correct');
                feedback.className = "feedback-box correct";
                feedback.innerHTML = `<strong><i class="fas fa-check-circle"></i> Chính xác!</strong><br>${q.explanation}`;
            } else {
                // Sai
                btn.classList.add('wrong');
                allBtns[q.correct_index].classList.add('correct'); // Hiện đáp án đúng
                feedback.className = "feedback-box wrong";
                feedback.innerHTML = `<strong><i class="fas fa-times-circle"></i> Sai rồi!</strong><br>${q.explanation}`;
            }

            feedback.style.display = 'block';
            document.getElementById('next-btn').style.display = 'block';
        }

        function nextQuestion() {
            currentIdx++;
            if (currentIdx < questions.length) {
                loadQuestion();
            } else {
                showResult();
            }
        }

        function showResult() {
            document.getElementById('quiz-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'block';
            document.getElementById('progress-bar').style.width = '100%';

            // TÍNH ĐIỂM THANG 10
            let totalQ = questions.length;
            let finalScore = (score / totalQ) * 10;
            
            // Làm tròn 1 chữ số thập phân (VD: 8.5)
            finalScore = Math.round(finalScore * 10) / 10; 

            document.getElementById('score-point').innerText = finalScore;
            document.getElementById('right-answers').innerText = score;
            document.getElementById('total-questions').innerText = totalQ;
        }
    </script>
</body>
</html>