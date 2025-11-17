
<?php
// إعدادات قاعدة البيانات - غير هذه القيم حسب استضافتك
define('DB_HOST', 'localhost');
define('DB_NAME', 'if0_40417373_cyber_warrior');
define('DB_USER', 'if0_40417373'); // ⚠️ غير هذا
define('DB_PASS', 'Root518X'); // ⚠️ غير هذا
define('SITE_URL', 'https://mrsam.kesug.com'); // ⚠️ غير هذا

// الاتصال بقاعدة البيانات مع معالجة أخطاء محسنة
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    error_log("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
    die(json_encode(['error' => 'فشل الاتصال بقاعدة البيانات. راجع الإعدادات.']));
}

// إعدادات إضافية للسلامة
date_default_timezone_set('Asia/Riyadh');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// نظام الرتب
$ranks_system = [
    'soldier' => 0,
    'first_soldier' => 15,
    'corporal' => 40,
    'sergeant' => 75,
    'lieutenant' => 120,
    'captain' => 200,
    'major' => 300,
    'colonel' => 450,
    'brigadier' => 650,
    'general' => 900
];

// ترجمة الرتب للعربية
$ranks_arabic = [
    'soldier' => 'جندي',
    'first_soldier' => 'جندي أول',
    'corporal' => 'عريف',
    'sergeant' => 'رقيب',
    'lieutenant' => 'ملازم',
    'captain' => 'نقيب',
    'major' => 'رائد',
    'colonel' => 'مقدم',
    'brigadier' => 'عقيد',
    'general' => 'عميد'
];
?>