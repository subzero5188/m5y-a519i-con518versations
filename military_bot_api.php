<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'بيانات JSON غير صالحة'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $input['action'] ?? 'get_context';
$user_id = $input['user_id'] ?? 'sami_hero';
$message = $input['message'] ?? '';

$user_id = filter_var($user_id, FILTER_SANITIZE_STRING);
$action = filter_var($action, FILTER_SANITIZE_STRING);

switch($action) {
    case 'get_context':
        echo getFullContext($user_id);
        break;
    case 'save_conversation':
        echo saveConversation($user_id, $message, $input['bot_response'], $input['mode']);
        break;
    case 'update_progress':
        echo updateProgress($user_id, $input['progress_data']);
        break;
    case 'add_knowledge':
        echo addKnowledgeMemory($user_id, $input['memory_data']);
        break;
    case 'get_knowledge':
        echo getKnowledgeMap($user_id, $input['category'] ?? 'all');
        break;
    case 'get_stats':
        echo getUserStats($user_id);
        break;
    case 'test_connection':
        echo json_encode(['success' => true, 'message' => 'API يعمل بشكل صحيح'], JSON_UNESCAPED_UNICODE);
        break;
    default:
        echo json_encode(['error' => 'إجراء غير معروف: ' . $action], JSON_UNESCAPED_UNICODE);
}

function getFullContext($user_id) {
    global $pdo, $ranks_arabic;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $stmt = $pdo->prepare("INSERT INTO users (user_id, current_rank, total_wings) VALUES (?, 'soldier', 25)");
        $stmt->execute([$user_id]);
        $user = ['user_id' => $user_id, 'current_rank' => 'soldier', 'total_wings' => 25];
    }
    
    $stmt = $pdo->prepare("SELECT * FROM conversations WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM daily_progress WHERE user_id = ? ORDER BY progress_date DESC LIMIT 7");
    $stmt->execute([$user_id]);
    $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM knowledge_map WHERE user_id = ? ORDER BY importance_level DESC, created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $knowledge = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $context = buildIntelligentContext($user, $conversations, $progress, $knowledge, $ranks_arabic);
    
    return json_encode([
        'success' => true,
        'user' => [
            'user_id' => $user['user_id'],
            'current_rank' => $ranks_arabic[$user['current_rank']] ?? $user['current_rank'],
            'total_wings' => $user['total_wings']
        ],
        'context' => $context,
        'conversations' => $conversations,
        'progress' => $progress,
        'knowledge' => $knowledge
    ], JSON_UNESCAPED_UNICODE);
}

function buildIntelligentContext($user, $conversations, $progress, $knowledge, $ranks_arabic) {
    $arabic_rank = $ranks_arabic[$user['current_rank']] ?? $user['current_rank'];
    
    $context = "🎯 **الذاكرة الشاملة للبطل {$user['user_id']}**\n\n";
    $context .= "🪖 **الرتبة الحالية:** {$arabic_rank}\n";
    $context .= "🏆 **الأجناح المجموعة:** {$user['total_wings']}\n\n";
    
    if (!empty($knowledge)) {
        $context .= "📚 **آخر الإنجازات:**\n";
        foreach(array_slice($knowledge, 0, 3) as $memory) {
            $context .= "• {$memory['title']}\n";
        }
        $context .= "\n";
    }
    
    if (!empty($progress)) {
        $latest = $progress[0];
        $context .= "📊 **آخر تقدم:** " . date('Y-m-d', strtotime($latest['progress_date'])) . "\n";
        if ($latest['study_hours']) {
            $context .= "⏱️ **ساعات الدراسة:** {$latest['study_hours']} ساعة\n";
        }
        if ($latest['performance_rating']) {
            $context .= "⭐ **الأداء:** {$latest['performance_rating']}\n";
        }
        $context .= "\n";
    }
    
    $context .= "💡 **تعليمات للبوت:** استخدم هذه المعلومات لتقديم رد شخصي وذكي، تذكر تاريخ المستخدم وإنجازاته.";
    
    return $context;
}

function saveConversation($user_id, $user_message, $bot_response, $mode) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO conversations (user_id, user_message, bot_response, mode) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $user_message, $bot_response, $mode]);
        
        return json_encode(['success' => true, 'message' => 'تم حفظ المحادثة'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        return json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function updateProgress($user_id, $progress_data) {
    global $pdo;
    
    try {
        if (!isset($progress_data['wings_earned'])) {
            throw new Exception('قيمة wings_earned مطلوبة');
        }
        
        $stmt = $pdo->prepare("INSERT INTO daily_progress (user_id, progress_date, tasks_completed, wings_earned, performance_rating, study_hours) VALUES (?, CURDATE(), ?, ?, ?, ?)");
        
        $success = $stmt->execute([
            $user_id, 
            $progress_data['tasks'] ?? 'مهام عامة',
            $progress_data['wings_earned'],
            $progress_data['performance'] ?? 'جيد',
            $progress_data['study_hours'] ?? 0
        ]);
        
        if (!$success) {
            throw new Exception('فشل في حفظ التقدم');
        }
        
        $stmt = $pdo->prepare("UPDATE users SET total_wings = total_wings + ?, last_active = NOW() WHERE user_id = ?");
        $stmt->execute([$progress_data['wings_earned'], $user_id]);
        
        $new_rank = checkPromotion($user_id);
        
        return json_encode([
            'success' => true, 
            'message' => 'تم تحديث التقدم',
            'new_rank' => $new_rank
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        return json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

function checkPromotion($user_id) {
    global $pdo, $ranks_system, $ranks_arabic;
    
    $stmt = $pdo->prepare("SELECT total_wings FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $wings = $stmt->fetchColumn();
    
    $new_rank = 'soldier';
    foreach($ranks_system as $rank => $required_wings) {
        if ($wings >= $required_wings) {
            $new_rank = $rank;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE users SET current_rank = ? WHERE user_id = ?");
    $stmt->execute([$new_rank, $user_id]);
    
    return $ranks_arabic[$new_rank] ?? $new_rank;
}

function addKnowledgeMemory($user_id, $memory_data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO knowledge_map (user_id, category, title, description, skills_learned, challenges_faced, solutions_applied, results_achieved, confidence_level, date_completed, time_spent, resources_used, next_steps, importance_level, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $user_id,
            $memory_data['category'] ?? 'skill',
            $memory_data['title'] ?? '',
            $memory_data['description'] ?? '',
            $memory_data['skills_learned'] ?? '',
            $memory_data['challenges_faced'] ?? '',
            $memory_data['solutions_applied'] ?? '',
            $memory_data['results_achieved'] ?? '',
            $memory_data['confidence_level'] ?? 5,
            $memory_data['date_completed'] ?? date('Y-m-d'),
            $memory_data['time_spent'] ?? 0,
            $memory_data['resources_used'] ?? '',
            $memory_data['next_steps'] ?? '',
            $memory_data['importance_level'] ?? 1,
            $memory_data['tags'] ?? ''
        ]);
        
        return json_encode(['success' => true, 'message' => 'تم إضافة الذكرى المعرفية'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        return json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function getKnowledgeMap($user_id, $category) {
    global $pdo;
    
    if ($category === 'all') {
        $stmt = $pdo->prepare("SELECT * FROM knowledge_map WHERE user_id = ? ORDER BY importance_level DESC, created_at DESC");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM knowledge_map WHERE user_id = ? AND category = ? ORDER BY importance_level DESC, created_at DESC");
        $stmt->execute([$user_id, $category]);
    }
    
    $knowledge = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return json_encode([
        'success' => true,
        'knowledge' => $knowledge,
        'count' => count($knowledge)
    ], JSON_UNESCAPED_UNICODE);
}

function getUserStats($user_id) {
    global $pdo, $ranks_arabic;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as skills_count FROM knowledge_map WHERE user_id = ? AND category = 'skill'");
    $stmt->execute([$user_id]);
    $skills_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as projects_count FROM knowledge_map WHERE user_id = ? AND category = 'project'");
    $stmt->execute([$user_id]);
    $projects_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT SUM(study_hours) as total_hours FROM daily_progress WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_hours = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT current_rank, total_wings FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return json_encode([
        'success' => true,
        'stats' => [
            'المهارات المكتسبة' => $skills_count ?: 0,
            'المشاريع المكتملة' => $projects_count ?: 0,
            'إجمالي ساعات الدراسة' => $total_hours ?: 0,
            'الرتبة الحالية' => $ranks_arabic[$user['current_rank']] ?? $user['current_rank'],
            'الأجناح المجموعة' => $user['total_wings']
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>