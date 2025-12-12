<?php
// FILE: includes/cohere_helper.php

// HÀM 1: TRA TỪ (Dùng cho trang tìm kiếm)
function tra_tu_cohere($tu_khoa) {
    $apiKey = 'RSOYigrH1dJ3nSNX2p5rEgLAaW2PdLu1ZlHJGMOK'; 
    $modelName = 'command-r-08-2024'; 

    // --- CÂU LỆNH PROMPT MỚI (HỖ TRỢ 2 CHIỀU) ---
    $prompt = "Bạn là từ điển song ngữ thông minh. Hãy xử lý từ khóa: '$tu_khoa'.
    
    Quy tắc xử lý:
    1. Nếu '$tu_khoa' là Tiếng Anh: Hãy giải nghĩa sang Tiếng Việt.
    2. Nếu '$tu_khoa' là Tiếng Việt: Hãy tìm từ vựng Tiếng Anh tương ứng chuẩn nhất (Ví dụ: 'Con mèo' -> 'Cat').
    
    Yêu cầu trả về JSON duy nhất với cấu trúc CỐ ĐỊNH (Không thay đổi tên key):
    {
        \"ten_tu_vung\": \"(Luôn là từ Tiếng Anh gốc)\",
        \"phat_am\": \"(Phiên âm IPA của từ Tiếng Anh)\",
        \"loai_tu\": \"(Loại từ: n, v, adj...)\",
        \"nghia_tieng_viet\": \"(Nghĩa tiếng Việt ngắn gọn)\",
        \"vi_du\": \"(Một câu ví dụ tiếng Anh chứa từ đó)\"
    }";

    return goi_api_cohere_v2($apiKey, $modelName, $prompt);
}


// HÀM 2: TẠO BÀI KIỂM TRA (Dùng cho trang Review)
function tao_bai_kiem_tra($ds_tu_vung) {
    $apiKey = 'RSOYigrH1dJ3nSNX2p5rEgLAaW2PdLu1ZlHJGMOK';
    
    $modelName = 'command-r-08-2024'; 

    $text_list = implode(", ", $ds_tu_vung);
    $prompt = "Dựa trên danh sách từ vựng: [$text_list]. 
    Hãy tạo chính xác 10 câu hỏi trắc nghiệm (Multiple Choice).
    
    Yêu cầu ĐỊNH DẠNG JSON KHẮT KHE:
    1. Trả về một Mảng JSON (Array) gồm 10 đối tượng.
    2. Tuyệt đối KHÔNG dùng markdown (```json), KHÔNG viết lời dẫn.
    3. Cấu trúc mỗi câu:
       [
         {
           \"question\": \"Câu hỏi tiếng Anh về nghĩa hoặc cách dùng từ\",
           \"options\": [\"Đáp án A\", \"Đáp án B\", \"Đáp án C\", \"Đáp án D\"],
           \"correct_index\": 0, (Số thứ tự đáp án đúng là 0, 1, 2 hoặc 3)
           \"explanation\": \"Giải thích ngắn gọn tại sao đúng bằng Tiếng Việt\"
         }
         ... (làm đủ 10 câu)
       ]";

    return goi_api_cohere_v2($apiKey, $modelName, $prompt);
}

// HÀM GỌI API V2 (Dùng chung)
function goi_api_cohere_v2($key, $model, $prompt) {
    $url = 'https://api.cohere.com/v2/chat';
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.3 // Độ sáng tạo thấp để JSON chuẩn
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    // Bỏ qua SSL (để chạy được trên XAMPP/Localhost)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        die('Lỗi cURL: ' . curl_error($ch));
    }
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Kiểm tra lỗi từ Server Cohere
    if ($http_code !== 200) {
        echo "<div style='color:red; border:1px solid red; padding:10px; background:#fff0f0;'>";
        echo "<h3>Lỗi API (Mã $http_code)</h3>";
        echo "<strong>Phản hồi từ server:</strong> ";
        print_r($response);
        echo "</div>";
        die();
    }

    $result = json_decode($response, true);

    // Lấy nội dung trả về từ cấu trúc V2
    if (isset($result['message']['content'][0]['text'])) {
        $raw = $result['message']['content'][0]['text'];
        // Làm sạch chuỗi JSON
        $raw = str_replace(['```json', '```'], '', $raw);
        return json_decode(trim($raw), true);
    }

    return null;
}
?>