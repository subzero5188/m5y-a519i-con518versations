<?php
class AdvancedBotIntegration {
    private $api_url;
    private $user_id;
    
    public function __construct($api_url, $user_id) {
        if (empty($api_url) || empty($user_id)) {
            throw new InvalidArgumentException('API URL و user_id مطلوبان');
        }
        
        $this->api_url = $api_url;
        $this->user_id = $user_id;
    }
    
    public function integrateWithPlatform($platform, $message) {
        $allowed_platforms = ['tryhackme', 'rootme', 'discord', 'telegram', 'generic'];
        
        if (!in_array($platform, $allowed_platforms)) {
            throw new InvalidArgumentException('المنصة غير مدعومة: ' . $platform);
        }
        
        $integration_method = 'integrate' . ucfirst($platform);
        
        if (method_exists($this, $integration_method)) {
            return $this->$integration_method($message);
        } else {
            return $this->processGenericMessage($message);
        }
    }
    
    private function integrateTryhackme($message) {
        $patterns = [
            '/انتهيت من غرفة (.+)/i' => ['title' => 'إكمال غرفة TryHackMe: $1', 'wings' => 15],
            '/بدأت غرفة (.+)/i' => ['title' => 'بدء غرفة TryHackMe: $1', 'wings' => 5],
            '/حصلت على نقطة في (.+)/i' => ['title' => 'اكتساب نقاط في: $1', 'wings' => 8]
        ];
        
        foreach ($patterns as $pattern => $achievement) {
            if (preg_match($pattern, $message, $matches)) {
                $title = str_replace('$1', $matches[1], $achievement['title']);
                $this->autoSaveAchievement($title, $message, $achievement['wings']);
                return "🎉 مبروك على إكمال الغرفة! تمت إضافة {$achievement['wings']} جناح لرصيدك.";
            }
        }
        
        return null;
    }
    
    private function integrateRootme($message) {
        $patterns = [
            '/حللت تحدي (.+)/i' => ['title' => 'حل تحدي RootMe: $1', 'wings' => 10],
            '/انتهيت من تحدي (.+)/i' => ['title' => 'إنهاء تحدي RootMe: $1', 'wings' => 12]
        ];
        
        foreach ($patterns as $pattern => $achievement) {
            if (preg_match($pattern, $message, $matches)) {
                $title = str_replace('$1', $matches[1], $achievement['title']);
                $this->autoSaveAchievement($title, $message, $achievement['wings']);
                return "🔥 رائع! حل التحديات يطور مهاراتك. تم إضافة {$achievement['wings']} أجناح.";
            }
        }
        
        return null;
    }
    
    private function processGenericMessage($message) {
        return "🤖 تم استقبال رسالتك من المنصة العامة. البوت الذكي سيتعامل معها.";
    }
    
    private function autoSaveAchievement($title, $description, $wings) {
        $this->callAPI('add_knowledge', [
            'user_id' => $this->user_id,
            'memory_data' => [
                'category' => 'achievement',
                'title' => $title,
                'description' => $description,
                'skills_learned' => 'حل المشكلات, تحليل التحديات',
                'confidence_level' => 9,
                'importance_level' => 4
            ]
        ]);
        
        $this->callAPI('update_progress', [
            'user_id' => $this->user_id,
            'progress_data' => [
                'tasks' => [$title],
                'wings_earned' => $wings,
                'performance' => 'ممتاز',
                'study_hours' => 2
            ]
        ]);
    }
    
    private function callAPI($action, $data) {
        $post_data = array_merge(['action' => $action], $data);
        $json_data = json_encode($post_data, JSON_UNESCAPED_UNICODE);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->api_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8']
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

// مثال للاستخدام:
// $integration = new AdvancedBotIntegration('https://yourdomain.com/military_bot_api.php', 'sami_hero');
// $result = $integration->integrateWithPlatform('tryhackme', 'انتهيت من غرفة Basic Pentesting');
?>