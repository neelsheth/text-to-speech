<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Text to Speech App</title>
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
            max-width: 800px;
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
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .settings {
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🎤 Text to Speech Converter</h1>
        
        <form id="ttsForm">
            <div class="form-group">
                <label for="text">Enter your text:</label>
                <textarea id="text" name="text" placeholder="Type or paste your text here..." required></textarea>
            </div>

            <div class="settings">
                <div class="form-group">
                    <label for="voice">Voice:</label>
                    <select id="voice" name="voice">
                        <option value="">Loading voices...</option>
                    </select>
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

            <button type="submit" class="btn" id="convertBtn">
                🔊 Convert to Speech
            </button>

            <div class="loading" id="loading">
                ⏳ Generating audio...
            </div>

            <div id="message"></div>
        </form>
    </div>

    <script>
        // Check if browser supports Web Speech API
        if (!('speechSynthesis' in window)) {
            document.getElementById('message').innerHTML = '<div class="error">❌ Your browser does not support Text-to-Speech. Please use a modern browser like Chrome, Firefox, Safari, or Edge.</div>';
        }

        // Load available voices
        function loadVoices() {
            const voiceSelect = document.getElementById('voice');
            const voices = speechSynthesis.getVoices();
            
            voiceSelect.innerHTML = '<option value="">Default Voice</option>';
            
            voices.forEach((voice, index) => {
                const option = document.createElement('option');
                option.value = voice.voiceURI;
                option.textContent = `${voice.name} (${voice.lang})`;
                if (voice.default) {
                    option.textContent += ' - Default';
                }
                voiceSelect.appendChild(option);
            });
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

        // Form submission with browser TTS
        document.getElementById('ttsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const text = document.getElementById('text').value;
            const voiceSelect = document.getElementById('voice');
            const speed = parseFloat(document.getElementById('speed').value);
            const pitch = parseFloat(document.getElementById('pitch').value);
            const volume = parseFloat(document.getElementById('volume').value);
            
            const convertBtn = document.getElementById('convertBtn');
            const loading = document.getElementById('loading');
            const message = document.getElementById('message');
            
            if (!text.trim()) {
                message.innerHTML = '<div class="error">❌ Please enter some text to convert.</div>';
                return;
            }

            // Show loading state
            convertBtn.disabled = true;
            convertBtn.textContent = 'Speaking...';
            loading.style.display = 'block';
            loading.textContent = '🔊 Speaking...';
            message.innerHTML = '';

            try {
                // Stop any current speech
                speechSynthesis.cancel();

                // Create new speech synthesis utterance
                const utterance = new SpeechSynthesisUtterance(text);
                
                // Set voice if selected
                if (voiceSelect.value) {
                    const voices = speechSynthesis.getVoices();
                    const selectedVoice = voices.find(voice => voice.voiceURI === voiceSelect.value);
                    if (selectedVoice) {
                        utterance.voice = selectedVoice;
                    }
                }

                // Set speech parameters
                utterance.rate = speed;
                utterance.pitch = pitch;
                utterance.volume = volume;

                // Event handlers
                utterance.onstart = function() {
                    message.innerHTML = '<div class="success">🔊 Playing speech...</div>';
                };

                utterance.onend = function() {
                    convertBtn.disabled = false;
                    convertBtn.textContent = '🔊 Convert to Speech';
                    loading.style.display = 'none';
                    message.innerHTML = '<div class="success">✅ Speech completed!</div>';
                };

                utterance.onerror = function(event) {
                    convertBtn.disabled = false;
                    convertBtn.textContent = '🔊 Convert to Speech';
                    loading.style.display = 'none';
                    message.innerHTML = `<div class="error">❌ Speech error: ${event.error}</div>`;
                };

                // Start speaking
                speechSynthesis.speak(utterance);

            } catch (error) {
                convertBtn.disabled = false;
                convertBtn.textContent = '🔊 Convert to Speech';
                loading.style.display = 'none';
                message.innerHTML = `<div class="error">❌ Error: ${error.message}</div>`;
            }
        });

        // Add stop button functionality
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('ttsForm');
            const stopBtn = document.createElement('button');
            stopBtn.type = 'button';
            stopBtn.className = 'btn';
            stopBtn.style.background = 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)';
            stopBtn.style.marginTop = '10px';
            stopBtn.innerHTML = '⏹️ Stop Speech';
            stopBtn.onclick = function() {
                speechSynthesis.cancel();
                document.getElementById('convertBtn').disabled = false;
                document.getElementById('convertBtn').textContent = '🔊 Convert to Speech';
                document.getElementById('loading').style.display = 'none';
                document.getElementById('message').innerHTML = '<div class="success">⏹️ Speech stopped.</div>';
            };
            form.appendChild(stopBtn);
        });
    </script>
</body>
</html>
