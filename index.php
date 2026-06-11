<?php
header('Content-Type: application/x-mpegURL');
header('Access-Control-Allow-Origin: *'); // للسماح لبلوجر بالقراءة
header('Access-Control-Allow-Headers: *');

// بيانات الحماية المستخرجة
$referrer = "https://zz.depoooo.com/";
$user_agent = "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36";

// الرابط الأساسي لسيرفر البث (بدون اسم الملف) لدمج الروابط النسبية
$base_url = "https://1cup-live.s3.eu-west-3.amazonaws.com/max2/max2_240p/";

// تحديد الرابط المطلوب جلب محتواه (هل هو ملف m3u8 الأساسي أم قطعة فيديو .ts؟)
if (isset($_GET['file'])) {
    $target_url = $_GET['file'];
} else {
    $target_url = $base_url . "index.m3u8";
}

// دالة لجلب البيانات باستخدام cURL وتزوير الـ Headers
function fetch_url($url, $referrer, $user_agent) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_REFERER, $referrer);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // لتجنب مشاكل شهادات الـ SSL
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// جلب المحتوى
$response = fetch_url($target_url, $referrer, $user_agent);

// إذا كان المطلوب هو ملف m3u8 (قائمة التشغيل)، نحتاج لتعديل الروابط داخله
if (!isset($_GET['file']) || strpos($target_url, '.m3u8') !== false) {
    $current_script_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]";
    
    // تقسيم الملف السطور لتعديل روابط الـ .ts أو الـ m3u8 الفرعية
    $lines = explode("\n", $response);
    foreach ($lines as &$line) {
        $line = trim($line);
        // إذا كان السطر يحتوي على رابط أو اسم ملف فيديو ولا يبدأ بـ #
        if ($line && strpos($line, '#') !== 0) {
            // تحويل الرابط النسبي إلى رابط كامل إذا لزم الأمر
            if (strpos($line, 'http') !== 0) {
                $full_ts_url = $base_url . $line;
            } else {
                $full_ts_url = $line;
            }
            // إعادة توجيه رابط قطعة الفيديو ليمر عبر هذا السكربت أيضاً
            $line = $current_script_url . "?file=" . urlencode($full_ts_url);
        }
    }
    $response = implode("\n", $lines);
}

echo $response;
?>
