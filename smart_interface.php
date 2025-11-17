<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البوت الذكي - الفريق أول / سامي</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            color: white;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .chat-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .chat-box {
            flex: 2;
            background: rgba(0,0,0,0.3);
            border-radius: 10px;
            padding: 20px;
            height: 500px;
            overflow-y: auto;
        }
        .status-panel {
            flex: 1;
            background: rgba(0,0,0,0.3);
            border-radius: 10px;
            padding: 20px;
        }
        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 10px;
            max-width: 80%;
        }
        .user-message {
            background: rgba(76, 175, 80, 0.3);
            margin-left: auto;
            text-align: left;
        }
        .bot-message {
            background: rgba(33, 150, 243, 0.3);
            margin-right: auto;
        }
        .system-message {
            background: rgba(255, 193, 7, 0.3);
            text-align: center;
            margin: 0 auto;
        }
        .input-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        input, select, button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        input {
            flex: 1;
        }
        button {
            background: #ff6b6b;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background: #ff5252;
        }
        button:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .mode-indicator {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin-left: 10px;
        }
        .commander { background: #f44336; }
        .brother { background: #4caf50; }
        .mentor { background: #2196f3; }
        .typing-indicator {
            display: none;
            padding: 10px;
            color: #ffd700;
            font-style: italic;
        }
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
            }
            .chat-box, .status-panel {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤖 البوت الذكي - الفريق أول / سامي</h1>
            <p>نظام ذكي واعي بذاكرة معرفية متكاملة</p>
        </div>

        <div class="chat-container">
            <div class="chat-box" id="chatBox">
                <div class="message system-message">
                    🟢 البوت الذكي متصل بالذاكرة المعرفية وجاهز للمحادثة
                </div>
                <div class="typing-indicator" id="typingIndicator">
                    البوت يكتب...
                </div>
            </div>
            
            <div class="status-panel">
                <h3>📊 حالة النظام</h3>
                <div id="statusInfo">
                    <p>⏳ جاري التحميل...</p>
                </div>
                
                <h3>🎯 أنماط المحادثة</h3>
                <select id="modeSelect">
                    <option value="auto">🔄 تلقائي</option>
                    <option value="commander">🎯 قائد</option>
                    <option value="brother">❤️ أخ</option>
                    <option value="mentor">🧠 مرشد</option>
                </select>
                
                <h3>🧠 الذاكرة النشطة</h3>
                <div id="memoryInfo">
                    <p>✅ يتصل تلقائياً بالـ API</p>
                    <p>✅ يحفظ كل المحادثات</p>
                    <p>✅ يتعرف على الإنجازات</p>
                    <p>✅ يولد ردوداً ذكية</p>
                </div>
                
                <button onclick="clearChat()" style="background: #ff4757; margin-top: 10px; width: 100%;">
                    🗑️ مسح المحادثة
                </button>
            </div>
        </div>

        <div class="input-group">
            <input type="text" id="messageInput" placeholder="اكتب رسالتك هنا..." onkeypress="handleKeyPress(event)">
            <button onclick="sendMessage()" id="sendButton">إرسال 🚀</button>
        </div>
    </div>

    <script>
        let conversationHistory = [];
        let isSending = false;
        
        function showTypingIndicator() {
            document.getElementById('typingIndicator').style.display = 'block';
        }
        
        function hideTypingIndicator() {
            document.getElementById('typingIndicator').style.display = 'none';
        }
        
        async function loadBotStatus() {
            try {
                const response = await fetch('smart_bot_engine.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ user_id: 'sami_hero' })
                });
                
                const data = await response.json();
                if (data.success) {
                    document.getElementById('statusInfo').innerHTML = `
                        <p>✅ API: متصل</p>
                        <p>👤 المستخدم: ${data.status.user_id}</p>
                        <p>💬 المحادثات: ${data.status.conversation_count}</p>
                        <p>🕒 آخر تحديث: ${data.status.last_context_update}</p>
                    `;
                }
            } catch (error) {
                console.error('Error loading status:', error);
            }
        }
        
        async function sendMessage() {
            if (isSending) return;
            
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            const mode = document.getElementById('modeSelect').value;
            
            if (!message) return;
            
            isSending = true;
            document.getElementById('sendButton').disabled = true;
            
            addMessage('user', message);
            messageInput.value = '';
            
            showTypingIndicator();
            
            try {
                const response = await fetch('smart_bot_engine.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        user_id: 'sami_hero',
                        message: message,
                        mode: mode
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`خطأ في الشبكة: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    addMessage('bot', data.response, data.mode);
                    
                    if (data.conversation_history) {
                        conversationHistory = data.conversation_history;
                    }
                    
                    loadBotStatus();
                } else {
                    addMessage('system', '❌ خطأ: ' + (data.error || 'غير معروف'));
                }
            } catch (error) {
                console.error('Error:', error);
                addMessage('system', '❌ خطأ في الاتصال: ' + error.message);
            } finally {
                hideTypingIndicator();
                isSending = false;
                document.getElementById('sendButton').disabled = false;
                messageInput.focus();
            }
        }
        
        function addMessage(role, message, mode = null) {
            const chatBox = document.getElementById('chatBox');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${role}-message`;
            
            let modeIndicator = '';
            if (mode) {
                modeIndicator = `<span class="mode-indicator ${mode}">${getModeText(mode)}</span>`;
            }
            
            messageDiv.innerHTML = `
                <strong>${getRoleText(role)}</strong>${modeIndicator}
                <div>${message}</div>
            `;
            
            chatBox.appendChild(messageDiv);
            chatBox.scrollTop = chatBox.scrollHeight;
        }
        
        function getRoleText(role) {
            const roles = {
                'user': '👤 أنت',
                'bot': '🤖 البوت',
                'system': '⚙️ النظام'
            };
            return roles[role] || role;
        }
        
        function getModeText(mode) {
            const modes = {
                'commander': 'قائد',
                'brother': 'أخ',
                'mentor': 'مرشد',
                'auto': 'تلقائي'
            };
            return modes[mode] || mode;
        }
        
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }
        
        function clearChat() {
            const chatBox = document.getElementById('chatBox');
            chatBox.innerHTML = `
                <div class="message system-message">
                    🟢 البوت الذكي متصل بالذاكرة المعرفية وجاهز للمحادثة
                </div>
                <div class="typing-indicator" id="typingIndicator">
                    البوت يكتب...
                </div>
            `;
            conversationHistory = [];
        }
        
        window.onload = function() {
            loadBotStatus();
            addMessage('system', '🔍 البوت يجلب أحدث البيانات من الذاكرة المعرفية...');
            document.getElementById('messageInput').focus();
        }
    </script>
</body>
</html>