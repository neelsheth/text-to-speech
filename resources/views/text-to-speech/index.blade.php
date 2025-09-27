<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Text to Speech with Translation</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        textarea, select, input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        textarea:focus, select:focus, input:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
            margin-bottom: 10px;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn-secondary {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        }
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        .settings {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .language-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .loading {
            display: none;
            text-align: center;
            color: #667eea;
            font-weight: 600;
        }
        .error {
            color: #e74c3c;
            margin-top: 10px;
            padding: 10px;
            background: #fdf2f2;
            border-radius: 5px;
            border-left: 4px solid #e74c3c;
        }
        .success {
            color: #27ae60;
            margin-top: 10px;
            padding: 10px;
            background: #f2fdf5;
            border-radius: 5px;
            border-left: 4px solid #27ae60;
        }
        .translations-container {
            margin-top: 30px;
            display: none;
        }
        .translation-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .translation-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }
        .language-name {
            font-weight: 600;
            color: #495057;
            font-size: 18px;
        }
        .translated-text {
            color: #333;
            line-height: 1.6;
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .speak-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        .speak-btn:hover {
            opacity: 0.9;
        }
        .selected-languages {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .language-tag {
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .remove-lang {
            cursor: pointer;
            font-weight: bold;
        }
        .multi-select {
            height: 150px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌍 Text to Speech with Translation</h1>
        
        <form id="ttsForm">
            <div class="form-group">
                <label for="text">Enter your text (English):</label>
                <textarea id="text" name="text" placeholder="Type or paste your English text here..." required></textarea>
            </div>

            <div class="language-selection">
                <div class="form-group">
                    <label for="targetLanguages">Select target languages:</label>
                    <select id="targetLanguages" name="target_languages[]" multiple class="multi-select">
                        <option value="">Loading languages...</option>
                    </select>
                    <div class="selected-languages" id="selectedLanguages"></div>
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                        ✅ <strong>Powered by Free Translation APIs:</strong> Using MyMemory API and LibreTranslate for real-time translations. No API keys required!<br>
                        🔊 <strong>Voice Note:</strong> Hindi voices depend on your browser and operating system. Chrome typically has better language support.
                    </small>
                </div>

                <div class="form-group">
                    <button type="button" class="btn btn-secondary" id="translateBtn">
                        🌐 Translate Text
                    </button>
                </div>
            </div>

            <div class="settings">
                <div class="form-group">
                    <label for="voice">Voice:</label>
                    <select id="voice" name="voice">
                        <option value="">Loading voices...</option>
                    </select>
                    <small style="color: #666; font-size: 11px; margin-top: 5px; display: block;" id="voiceInfo">
                        Checking available voices...
                    </small>
                </div>

                <div class="form-group">
                    <label for="speed">Speed:</label>
                    <input type="range" id="speed" name="speed" min="0.25" max="4.0" step="0.25" value="1.0">
                    <span id="speedValue">1.0x</span>
                </div>
            </div>

            <div class="settings">
                <div class="form-group">
                    <label for="pitch">Pitch:</label>
                    <input type="range" id="pitch" name="pitch" min="0" max="2" step="0.1" value="1.0">
                    <span id="pitchValue">1.0</span>
                </div>

                <div class="form-group">
                    <label for="volume">Volume:</label>
                    <input type="range" id="volume" name="volume" min="0" max="1" step="0.1" value="1.0">
                    <span id="volumeValue">1.0</span>
                </div>
            </div>

            <button type="button" class="btn btn-danger" id="stopBtn">
                ⏹️ Stop Speech
            </button>

            <div class="loading" id="loading">
                ⏳ Processing...
            </div>

            <div id="message"></div>
        </form>

        <div class="translations-container" id="translationsContainer">
            <h2>📝 Translations</h2>
            <div id="translationsList"></div>
        </div>
    </div>

    <script>
        // Global variables
        let availableLanguages = {};
        let currentTranslations = {};
        
        // Check if browser supports Web Speech API
        if (!('speechSynthesis' in window)) {
            document.getElementById('message').innerHTML = '<div class="error">❌ Your browser does not support Text-to-Speech. Please use a modern browser like Chrome, Firefox, Safari, or Edge.</div>';
        } else {
            // Add helpful info about voice availability
            setTimeout(() => {
                const voiceInfo = document.getElementById('voiceInfo');
                if (voiceInfo && voiceInfo.textContent === 'Checking available voices...') {
                    voiceInfo.innerHTML = 'Loading voices... Different browsers have different voice support. Chrome typically has the most languages.';
                }
            }, 1000);
        }

        // Load available languages
        async function loadLanguages() {
            try {
                const response = await fetch('/languages');
                const data = await response.json();
                
                if (data.success) {
                    availableLanguages = data.languages;
                    const languageSelect = document.getElementById('targetLanguages');
                    languageSelect.innerHTML = '';
                    
                    Object.entries(availableLanguages).forEach(([code, name]) => {
                        const option = document.createElement('option');
                        option.value = code;
                        option.textContent = name;
                        languageSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading languages:', error);
                document.getElementById('message').innerHTML = '<div class="error">❌ Error loading languages: ' + error.message + '</div>';
            }
        }

        // Load available voices
        function loadVoices() {
            const voiceSelect = document.getElementById('voice');
            const voices = speechSynthesis.getVoices();
            
            voiceSelect.innerHTML = '<option value="">Default Voice</option>';
            
            // Sort voices by language for better organization
            const sortedVoices = voices.sort((a, b) => {
                if (a.lang < b.lang) return -1;
                if (a.lang > b.lang) return 1;
                return 0;
            });
            
            sortedVoices.forEach((voice, index) => {
                const option = document.createElement('option');
                option.value = voice.voiceURI;
                option.textContent = `${voice.name} (${voice.lang})`;
                if (voice.default) {
                    option.textContent += ' - Default';
                }
                voiceSelect.appendChild(option);
            });
            
            // Log available voices for debugging
            console.log('Available voices:', sortedVoices.map(v => ({ 
                name: v.name, 
                lang: v.lang, 
                default: v.default 
            })));
            
            // Show voice count
            const hindiVoices = voices.filter(v => v.lang.startsWith('hi'));
            const nonEnglishVoices = voices.filter(v => !v.lang.startsWith('en'));
            
            console.log(`Total voices: ${voices.length}`);
            console.log(`Hindi voices: ${hindiVoices.length}`);
            console.log(`Non-English voices: ${nonEnglishVoices.length}`);
            
            if (hindiVoices.length > 0) {
                console.log('Hindi voices found:', hindiVoices.map(v => v.name));
            } else {
                console.log('No Hindi voices found. Available languages:', [...new Set(voices.map(v => v.lang))]);
            }
            
            // Update voice info display
            const voiceInfo = document.getElementById('voiceInfo');
            const availableLanguages = [...new Set(voices.map(v => v.lang))].sort();
            const languageNames = {
                'en': 'English', 'es': 'Spanish', 'fr': 'French', 'de': 'German', 
                'hi': 'Hindi', 'ar': 'Arabic', 'zh': 'Chinese', 'ja': 'Japanese',
                'ko': 'Korean', 'ru': 'Russian', 'it': 'Italian', 'pt': 'Portuguese',
                'nl': 'Dutch', 'sv': 'Swedish', 'da': 'Danish', 'no': 'Norwegian',
                'fi': 'Finnish', 'pl': 'Polish', 'cs': 'Czech', 'sk': 'Slovak',
                'hu': 'Hungarian', 'ro': 'Romanian', 'bg': 'Bulgarian', 'hr': 'Croatian',
                'sl': 'Slovenian', 'et': 'Estonian', 'lv': 'Latvian', 'lt': 'Lithuanian',
                'mt': 'Maltese', 'el': 'Greek', 'tr': 'Turkish', 'he': 'Hebrew',
                'th': 'Thai', 'vi': 'Vietnamese', 'id': 'Indonesian', 'ms': 'Malay',
                'sw': 'Swahili', 'ur': 'Urdu', 'bn': 'Bengali', 'ta': 'Tamil',
                'te': 'Telugu', 'ml': 'Malayalam', 'kn': 'Kannada', 'gu': 'Gujarati',
                'pa': 'Punjabi', 'or': 'Odia', 'as': 'Assamese', 'ne': 'Nepali',
                'si': 'Sinhala', 'my': 'Myanmar', 'km': 'Khmer', 'lo': 'Lao',
                'ka': 'Georgian', 'hy': 'Armenian', 'az': 'Azerbaijani', 'kk': 'Kazakh',
                'ky': 'Kyrgyz', 'uz': 'Uzbek', 'tg': 'Tajik', 'mn': 'Mongolian',
                'be': 'Belarusian', 'uk': 'Ukrainian', 'mk': 'Macedonian', 'sq': 'Albanian',
                'eu': 'Basque', 'ca': 'Catalan', 'gl': 'Galician', 'is': 'Icelandic',
                'fa': 'Persian', 'ps': 'Pashto', 'sd': 'Sindhi', 'bo': 'Tibetan',
                'dz': 'Dzongkha', 'am': 'Amharic', 'ti': 'Tigrinya', 'so': 'Somali',
                'ha': 'Hausa', 'yo': 'Yoruba', 'ig': 'Igbo', 'zu': 'Zulu',
                'af': 'Afrikaans', 'cy': 'Welsh', 'ga': 'Irish', 'gd': 'Scottish Gaelic',
                'mt': 'Maltese', 'lb': 'Luxembourgish', 'rm': 'Romansh'
            };
            
            const languageList = availableLanguages.map(lang => {
                const name = languageNames[lang] || languageNames[lang.split('-')[0]] || lang;
                return `${name} (${lang})`;
            }).join(', ');
            
            voiceInfo.innerHTML = `Available languages: ${availableLanguages.length} total - ${languageList}`;
            
            // Highlight if Hindi is available
            if (hindiVoices.length > 0) {
                voiceInfo.innerHTML += `<br><strong style="color: #27ae60;">✅ Hindi voices available: ${hindiVoices.map(v => v.name).join(', ')}</strong>`;
            } else {
                voiceInfo.innerHTML += `<br><strong style="color: #e74c3c;">⚠️ No Hindi voices found. Text will be spoken in available language.</strong>`;
            }
        }

        // Update selected languages display
        function updateSelectedLanguages() {
            const select = document.getElementById('targetLanguages');
            const container = document.getElementById('selectedLanguages');
            const selectedOptions = Array.from(select.selectedOptions);
            
            container.innerHTML = '';
            selectedOptions.forEach(option => {
                const tag = document.createElement('div');
                tag.className = 'language-tag';
                tag.innerHTML = `
                    ${availableLanguages[option.value]} 
                    <span class="remove-lang" onclick="removeLanguage('${option.value}')">×</span>
                `;
                container.appendChild(tag);
            });
        }

        // Remove language from selection
        function removeLanguage(languageCode) {
            const select = document.getElementById('targetLanguages');
            const options = Array.from(select.options);
            const option = options.find(opt => opt.value === languageCode);
            if (option) {
                option.selected = false;
                updateSelectedLanguages();
            }
        }

        // Load voices when they become available
        if (speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = loadVoices;
        }

        // Slider updates
        const speedSlider = document.getElementById('speed');
        const speedValue = document.getElementById('speedValue');
        const pitchSlider = document.getElementById('pitch');
        const pitchValue = document.getElementById('pitchValue');
        const volumeSlider = document.getElementById('volume');
        const volumeValue = document.getElementById('volumeValue');
        
        speedSlider.addEventListener('input', function() {
            speedValue.textContent = this.value + 'x';
        });

        pitchSlider.addEventListener('input', function() {
            pitchValue.textContent = this.value;
        });

        volumeSlider.addEventListener('input', function() {
            volumeValue.textContent = this.value;
        });

        // Language selection change
        document.getElementById('targetLanguages').addEventListener('change', updateSelectedLanguages);

        // Translate text
        document.getElementById('translateBtn').addEventListener('click', async function() {
            const text = document.getElementById('text').value;
            const selectedLanguages = Array.from(document.getElementById('targetLanguages').selectedOptions).map(opt => opt.value);
            
            if (!text.trim()) {
                document.getElementById('message').innerHTML = '<div class="error">❌ Please enter some text to translate.</div>';
                return;
            }
            
            if (selectedLanguages.length === 0) {
                document.getElementById('message').innerHTML = '<div class="error">❌ Please select at least one target language.</div>';
                return;
            }

            const translateBtn = document.getElementById('translateBtn');
            const loading = document.getElementById('loading');
            const message = document.getElementById('message');
            
            translateBtn.disabled = true;
            translateBtn.textContent = '🌐 Translating...';
            loading.style.display = 'block';
            loading.textContent = '🌐 Translating text...';
            message.innerHTML = '';

            try {
                const formData = new FormData();
                formData.append('text', text);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                selectedLanguages.forEach(lang => {
                    formData.append('target_languages[]', lang);
                });

                const response = await fetch('/translate', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    currentTranslations = data.translations;
                    displayTranslations(data);
                    
                    // Check if there are any errors
                    if (Object.keys(data.errors).length > 0) {
                        message.innerHTML = `<div class="success">✅ Successfully translated to ${data.total_translations} language(s)!</div><div class="error">⚠️ Some translations failed: ${Object.keys(data.errors).join(', ')}</div>`;
                    } else {
                        message.innerHTML = `<div class="success">✅ Successfully translated to ${data.total_translations} language(s)!</div>`;
                    }
                    
                    // Check if any translations show service unavailable message
                    const hasServiceIssues = Object.values(data.translations).some(t => 
                        t.translated_text.includes('[Translation Service Temporarily Unavailable]')
                    );
                    
                    if (hasServiceIssues) {
                        message.innerHTML += '<div class="error">⚠️ Translation service is currently experiencing issues. Some translations may be limited.</div>';
                    }
                } else {
                    message.innerHTML = '<div class="error">❌ Translation failed. Please try again.</div>';
                }
            } catch (error) {
                message.innerHTML = `<div class="error">❌ Error: ${error.message}</div>`;
            } finally {
                translateBtn.disabled = false;
                translateBtn.textContent = '🌐 Translate Text';
                loading.style.display = 'none';
            }
        });

        // Display translations
        function displayTranslations(data) {
            const container = document.getElementById('translationsContainer');
            const list = document.getElementById('translationsList');
            
            list.innerHTML = '';
            
            Object.values(data.translations).forEach(translation => {
                const item = document.createElement('div');
                item.className = 'translation-item';
                
                const header = document.createElement('div');
                header.className = 'translation-header';
                header.innerHTML = `<div class="language-name">${translation.language_name}</div>`;
                
                const translatedText = document.createElement('div');
                translatedText.className = 'translated-text';
                translatedText.textContent = translation.translated_text;
                
                const speakBtn = document.createElement('button');
                speakBtn.className = 'speak-btn';
                speakBtn.innerHTML = `🔊 Speak (${translation.language_name})`;
                speakBtn.addEventListener('click', function() {
                    speakText(translation.translated_text, translation.language_code);
                });
                
                const speakEnglishBtn = document.createElement('button');
                speakEnglishBtn.className = 'speak-btn';
                speakEnglishBtn.innerHTML = '🔊 Speak (English)';
                speakEnglishBtn.addEventListener('click', function() {
                    speakText(data.original_text, 'en');
                });
                
                item.appendChild(header);
                item.appendChild(translatedText);
                item.appendChild(speakBtn);
                item.appendChild(speakEnglishBtn);
                list.appendChild(item);
            });
            
            container.style.display = 'block';
        }

        // Speak text
        function speakText(text, languageCode) {
            try {
                // Stop any current speech
                speechSynthesis.cancel();

                // Create new speech synthesis utterance
                const utterance = new SpeechSynthesisUtterance(text);
                
                // Get all available voices
                const voices = speechSynthesis.getVoices();
                console.log('Available voices:', voices.map(v => ({ name: v.name, lang: v.lang })));
                
                // Try to find a voice that matches the language
                let matchingVoice = null;
                let voiceInfo = '';
                
                // First, try exact language match
                matchingVoice = voices.find(voice => voice.lang === languageCode);
                if (matchingVoice) {
                    voiceInfo = `Using exact match: ${matchingVoice.name} (${matchingVoice.lang})`;
                } else {
                    // Try language family match (e.g., hi-IN, hi for Hindi)
                    matchingVoice = voices.find(voice => voice.lang.startsWith(languageCode));
                    if (matchingVoice) {
                        voiceInfo = `Using language family match: ${matchingVoice.name} (${matchingVoice.lang})`;
                    } else {
                        // Try alternative language codes
                        const alternativeCodes = {
                            'hi': ['hi-IN', 'hi-GB', 'hi-US', 'ur', 'ur-PK'], // Hindi alternatives
                            'es': ['es-ES', 'es-MX', 'es-AR', 'es-CO'], // Spanish alternatives
                            'fr': ['fr-FR', 'fr-CA', 'fr-BE'], // French alternatives
                            'de': ['de-DE', 'de-AT', 'de-CH'], // German alternatives
                            'ar': ['ar-SA', 'ar-EG', 'ar-AE'], // Arabic alternatives
                            'zh': ['zh-CN', 'zh-TW', 'zh-HK'], // Chinese alternatives
                            'ja': ['ja-JP'], // Japanese alternatives
                            'ko': ['ko-KR'], // Korean alternatives
                            'ru': ['ru-RU'], // Russian alternatives
                        };
                        
                        if (alternativeCodes[languageCode]) {
                            for (const altCode of alternativeCodes[languageCode]) {
                                matchingVoice = voices.find(voice => voice.lang === altCode);
                                if (matchingVoice) {
                                    voiceInfo = `Using alternative code: ${matchingVoice.name} (${matchingVoice.lang})`;
                                    break;
                                }
                            }
                        }
                    }
                }
                
                // If still no match, try to find any non-English voice
                if (!matchingVoice) {
                    matchingVoice = voices.find(voice => !voice.lang.startsWith('en') && voice.lang !== 'en');
                    if (matchingVoice) {
                        voiceInfo = `Using non-English voice: ${matchingVoice.name} (${matchingVoice.lang})`;
                    } else {
                        voiceInfo = 'Using default voice (English)';
                    }
                }
                
                // Set the voice
                if (matchingVoice) {
                    utterance.voice = matchingVoice;
                }
                
                console.log(`Speaking: "${text}" in ${languageCode} with voice: ${voiceInfo}`);

                // Set speech parameters
                const speed = parseFloat(document.getElementById('speed').value);
                const pitch = parseFloat(document.getElementById('pitch').value);
                const volume = parseFloat(document.getElementById('volume').value);
                
                utterance.rate = speed;
                utterance.pitch = pitch;
                utterance.volume = volume;

                // Event handlers
                utterance.onstart = function() {
                    document.getElementById('message').innerHTML = `<div class="success">🔊 Playing speech... (${voiceInfo})</div>`;
                };

                utterance.onend = function() {
                    document.getElementById('message').innerHTML = '<div class="success">✅ Speech completed!</div>';
                };

                utterance.onerror = function(event) {
                    document.getElementById('message').innerHTML = `<div class="error">❌ Speech error: ${event.error} (Voice: ${voiceInfo})</div>`;
                };

                // Start speaking
                speechSynthesis.speak(utterance);

            } catch (error) {
                document.getElementById('message').innerHTML = `<div class="error">❌ Error: ${error.message}</div>`;
            }
        }

        // Stop speech
        document.getElementById('stopBtn').addEventListener('click', function() {
            speechSynthesis.cancel();
            document.getElementById('message').innerHTML = '<div class="success">⏹️ Speech stopped.</div>';
        });

        // Initialize the app
        document.addEventListener('DOMContentLoaded', function() {
            loadLanguages();
            loadVoices();
        });
    </script>
</body>
</html>