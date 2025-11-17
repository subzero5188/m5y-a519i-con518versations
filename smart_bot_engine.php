<?php
require_once 'intelligent_bot.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$api_url = 'https://mrsam.kesug.com/military_bot_api.php'; // ⚠️ غير هذا

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'بيانات JSON غير صالحة'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = $input['user_id'] ?? 'sami_hero';
$message = $input['message'] ?? '';
$mode = $input['mode'] ?? 'auto';

$user_id = filter_var($user_id, FILTER_SANITIZE_STRING);
$message = trim(filter_var($message, FILTER_SANITIZE_STRING));
$mode = filter_var($mode, FILTER_SANITIZE_STRING);

try {
    $bot = new IntelligentBot($api_url, $user_id);
    
    if (empty($message)) {
        echo json_encode([
            'success' => true,
            'type' => 'bot_status',
            'status' => $bot->getBotStatus(),
            'message' => '🟢 البوت الذكي متصل ويعمل - أرسل رسالة لبدء المحادثة',
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $result = $bot->processMessage($message, $mode);
        
        echo json_encode([
            'success' => true,
            'type' => 'bot_response',
            'response' => $result['response'],
            'mode' => $result['mode'],
            'context_used' => $result['context_used'],
            'conversation_history' => $bot->getConversationHistory(),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log("Bot Engine Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'فشل في تشغيل البوت: ' . $e->getMessage(),
        'suggestion' => 'تأكد من إعدادات API وصحة قاعدة البيانات'
    ], JSON_UNESCAPED_UNICODE);
}
?>