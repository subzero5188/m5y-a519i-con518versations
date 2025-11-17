<?php
class IntelligentBot {
    private $api_url;
    private $user_id;
    private $context;
    private $conversation_history = [];
    
    public function __construct($api_url, $user_id = 'sami_hero') {
        if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
            throw new Exception('رابط API غير صالح');
        }
        
        $this->api_url = $api_url;
        $this->user_id = filter_var($user_id, FILTER_SANITIZE_STRING);
        $this->initializeBot();
    }
    
    private function initializeBot() {
        try {
            $this->context = $this->callAPI('get_context', ['user_id' => $this->user_id]);
            
            if ($this->context && $this->context['success']) {
                $this->addToConversation('system', '✅ البوت متصل بالذاكرة المعرفية وبدأ الجلسة');
                $this->saveMemory('system', 'بداية_gلسة', 'البوت بدأ جلسة جديدة مع اتصال كامل بالذاكرة');
            } else {
                throw new Exception('فشل في جلب السياق من API');
            }
        } catch (Exception $e) {
            error_log("Bot initialization error: " . $e->getMessage());
            $this->addToConversation('system', '⚠️ تحذير: هناك مشكلة في الاتصال بالذاكرة');
        }
    }
    
    public function processMessage($user_message, $mode = 'auto') {
        if ($mode === 'auto') {
            $mode = $this->detectMode($user_message);
        }
        
        $this->updateContext();
        
        $bot_response = $this->generateResponse($user_message, $mode);
        
        $this->saveConversation($user_message, $bot_response, $mode);
        
        $this->autoUpdateProgress($user_message, $bot_response);
        
        return [
            'response' => $bot_response,
            'mode' => $mode,
            'context_used' => $this->getContextSummary(),
            'memory_updated' => true
        ];
    }
    
    private function detectMode($message) {
        $training_keywords = ['تدريب', 'مهمة', 'تمرين', 'اختبار', 'رتبة', 'جناح', 'شهادة'];
        $support_keywords = ['مساعدة', 'مشكلة', 'تعبان', 'متضايق', 'دعم', 'نصيحة'];
        $technical_keywords = ['كود', 'برمجة', 'سكريبت', 'api', 'قاعدة بيانات'];
        
        $message_lower = strtolower($message);
        
        foreach($training_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) return 'commander';
        }
        
        foreach($support_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) return 'brother';
        }
        
        foreach($technical_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) return 'mentor';
        }
        
        return 'commander';
    }
    
    private function updateContext() {
        $new_context = $this->callAPI('get_context', ['user_id' => $this->user_id]);
        if ($new_context['success']) {
            $this->context = $new_context;
        }
    }
    
    private function generateResponse($user_message, $mode) {
        $context_summary = $this->getContextSummary();
        
        $base_response = $this->getBaseResponse($mode);
        $personalized_part = $this->getPersonalizedResponse($user_message, $context_summary);
        $memory_reference = $this->getMemoryReference($context_summary);
        
        return $base_response . $personalized_part . $memory_reference;
    }
    
    private function getContextSummary() {
        if (!$this->context['success']) return 'لا توجد بيانات سابقة';
        
        $user = $this->context['user'];
        $knowledge = $this->context['knowledge'] ?? [];
        $progress = $this->context['progress'] ?? [];
        
        $summary = "المستخدم: {$user['user_id']} | الرتبة: {$user['current_rank']} | الأجناح: {$user['total_wings']}";
        
        if (!empty($knowledge)) {
            $latest_knowledge = $knowledge[0];
            $summary .= " | آخر إنجاز: {$latest_knowledge['title']}";
        }
        
        if (!empty($progress)) {
            $latest_progress = $progress[0];
            $summary .= " | آخر نشاط: {$latest_progress['progress_date']}";
        }
        
        return $summary;
    }
    
    private function getBaseResponse($mode) {
        $responses = [
            'commander' => "🎯 الفريق أول / سامي: \n",
            'brother' => "❤️ الأخ سامي: \n", 
            'mentor' => "🧠 المرشد سامي: \n"
        ];
        
        return $responses[$mode] ?? $responses['commander'];
    }
    
    private function getPersonalizedResponse($user_message, $context) {
        if (strpos($context, 'جندي') !== false && strpos($user_message, 'مبتدئ') !== false) {
            return "أرى أنك في بداية الرحلة! هذا ممتاز 🌟 تذكر أن كل الخبراء بدأوا من حيث أنت الآن.\n\n";
        }
        
        if (strpos($context, 'رقيب') !== false && strpos($user_message, 'تقدم') !== false) {
            return "تقدمك ملحوظ يا بطل! 🚀 من جندي إلى رقيب في وقت قياسي.\n\n";
        }
        
        if (strpos($user_message, 'تعب') !== false || strpos($user_message, 'إرهاق') !== false) {
            return "أعلم أن الطريق صعب، لكن الأبطال مثل الجبال - كلما زاد الضغط زاد الارتفاع! 🏔️\n\n";
        }
        
        return "أنا هنا لمساعدتك في رحلتك السيبرانية. ";
    }
    
    private function getMemoryReference($context) {
        $memory_triggers = [
            'آخر إنجاز' => "أتذكر إنجازك الأخير وكان رائعاً! ",
            'آخر نشاط' => "نشاطك المستمر يظهر التزامك العالي. ",
            'الأجناح' => "أرى أن مجموع أجناحك ينمو بسرعة! "
        ];
        
        foreach($memory_triggers as $trigger => $response) {
            if (strpos($context, $trigger) !== false) {
                return $response;
            }
        }
        
        return "ذاكرتي المعرفية متصلة وأتذكر كل تقدمك. ";
    }
    
    private function autoUpdateProgress($user_message, $bot_response) {
        $achievements = $this->detectAchievements($user_message);
        
        if (!empty($achievements)) {
            foreach($achievements as $achievement) {
                $this->saveMemory('achievement', $achievement['title'], $achievement['description']);
                $this->callAPI('update_progress', [
                    'user_id' => $this->user_id,
                    'progress_data' => [
                        'tasks' => [$achievement['title']],
                        'wings_earned' => $achievement['wings'] ?? 5,
                        'performance' => 'ممتاز',
                        'study_hours' => 1
                    ]
                ]);
            }
        }
        
        $this->updateStudyTime();
    }
    
    private function detectAchievements($message) {
        $achievements = [];
        $message_lower = trim(strtolower($message));
        
        $achievement_patterns = [
            '/انتهيت من (.+)/' => ['title' => 'إكمال مهمة: $1', 'wings' => 10],
            '/حللت (?:تحدي|مشكلة) (.+)/' => ['title' => 'حل تحدي: $1', 'wings' => 8],
            '/تعلمت (.+)/' => ['title' => 'اكتساب مهارة: $1', 'wings' => 7],
            '/اجتزت (.+)/' => ['title' => 'اجتياز اختبار: $1', 'wings' => 15],
            '/انتهت (.+)/' => ['title' => 'إنهاء مرحلة: $1', 'wings' => 12],
            '/حصلت على شهادة (.+)/' => ['title' => 'الحصول على شهادة: $1', 'wings' => 25],
            '/اكملت (.+) غرفة/' => ['title' => 'إكمال غرفة: $1', 'wings' => 15]
        ];
        
        foreach($achievement_patterns as $pattern => $achievement) {
            if (preg_match($pattern, $message_lower, $matches)) {
                $title = str_replace('$1', $matches[1] ?? '', $achievement['title']);
                $achievements[] = [
                    'title' => $title,
                    'description' => "تم إنجاز: {$title} - من خلال المحادثة",
                    'wings' => $achievement['wings']
                ];
            }
        }
        
        return $achievements;
    }
    
    private function updateStudyTime() {
        $this->callAPI('update_progress', [
            'user_id' => $this->user_id,
            'progress_data' => [
                'tasks' => ['محادثة تدريبية'],
                'wings_earned' => 2,
                'performance' => 'جيد',
                'study_hours' => 0.5
            ]
        ]);
    }
    
    private function saveConversation($user_message, $bot_response, $mode) {
        $this->callAPI('save_conversation', [
            'user_id' => $this->user_id,
            'message' => $user_message,
            'bot_response' => $bot_response,
            'mode' => $mode
        ]);
        
        $this->addToConversation('user', $user_message);
        $this->addToConversation('bot', $bot_response);
    }
    
    private function saveMemory($category, $title, $description) {
        $this->callAPI('add_knowledge', [
            'user_id' => $this->user_id,
            'memory_data' => [
                'category' => $category,
                'title' => $title,
                'description' => $description,
                'skills_learned' => 'تواصل, تذكر, تحليل',
                'confidence_level' => 8,
                'importance_level' => 3
            ]
        ]);
    }
    
    private function callAPI($action, $data) {
        $post_data = array_merge(['action' => $action], $data);
        $json_data = json_encode($post_data, JSON_UNESCAPED_UNICODE);
        
        if ($json_data === false) {
            throw new Exception('فشل في ترميز بيانات JSON');
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->api_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($json_data),
                'User-Agent: IntelligentBot/1.0'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception('فشل في الاتصال بالـ API: ' . $curl_error);
        }
        
        if ($http_code !== 200) {
            throw new Exception('استجابة غير صالحة من API: ' . $http_code);
        }
        
        $decoded_response = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('استجابة JSON غير صالحة من API');
        }
        
        return $decoded_response;
    }
    
    private function addToConversation($role, $message) {
        $this->conversation_history[] = [
            'role' => $role,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if (count($this->conversation_history) > 50) {
            array_shift($this->conversation_history);
        }
    }
    
    public function getConversationHistory() {
        return $this->conversation_history;
    }
    
    public function getBotStatus() {
        return [
            'api_connected' => !empty($this->context),
            'user_id' => $this->user_id,
            'conversation_count' => count($this->conversation_history),
            'last_context_update' => date('Y-m-d H:i:s'),
            'memory_entries' => count($this->context['knowledge'] ?? [])
        ];
    }
}
?>