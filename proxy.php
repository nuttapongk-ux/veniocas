<?php
// ไฟล์ proxy.php สำหรับส่งข้อมูลหา GoFive API แบบข้ามโดเมน

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Ocp-Apim-Subscription-Key");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$target_path = isset($_GET['path']) ? $_GET['path'] : '';
if (empty($target_path)) {
    http_response_code(400);
    echo json_encode(["error" => "No path specified"]);
    exit;
}

$url = "https://api.gofive.co.th/" . $target_path;
$post_data = file_get_contents('php://input');

$headers = [];
// ตรวจสอบฟังก์ชัน getallheaders เนื่องจากบาง Server อาจไม่มี
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

foreach (getallheaders() as $name => $value) {
    $name_lower = strtolower($name);
    // ยกเว้น Header บางตัวเพื่อไม่ให้ไปกระทบการทำงานของระบบปลายทาง
    if (!in_array($name_lower, ['host', 'origin', 'referer', 'content-length', 'connection', 'accept-encoding'])) {
        $headers[] = "$name: $value";
    }
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(["error" => curl_error($ch)]);
} else {
    http_response_code($httpcode);
    header("Content-Type: application/json");
    echo $response;
}
curl_close($ch);
?>
