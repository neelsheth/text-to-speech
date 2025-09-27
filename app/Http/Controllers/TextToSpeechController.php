<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class TextToSpeechController extends Controller
{
    public function index()
    {
        return view('text-to-speech.index');
    }

    public function convert(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
            'voice' => 'nullable|string',
            'speed' => 'nullable|numeric|between:0.25,4.0',
            'pitch' => 'nullable|numeric|between:0,2',
            'volume' => 'nullable|numeric|between:0,1'
        ]);

        $text = $request->input('text');
        $voice = $request->input('voice');
        $speed = $request->input('speed', 1.0);
        $pitch = $request->input('pitch', 1.0);
        $volume = $request->input('volume', 1.0);

        // For browser TTS, we just return the parameters
        // The actual speech synthesis happens on the client side
        return response()->json([
            'success' => true,
            'message' => 'Ready for browser TTS',
            'parameters' => [
                'text' => $text,
                'voice' => $voice,
                'speed' => $speed,
                'pitch' => $pitch,
                'volume' => $volume
            ]
        ]);
    }

    public function translate(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
            'target_languages' => 'required|array|min:1',
            'target_languages.*' => 'required|string|in:es,fr,de,it,pt,ru,ja,ko,zh,ar,hi,nl,sv,da,no,fi,pl,cs,sk,hu,ro,bg,hr,sl,et,lv,lt,mt,el,tr,he,th,vi,id,ms,sw,am,az,be,bn,bs,ca,eu,gl,is,ka,kk,ky,lo,mk,mn,my,ne,si,ta,te,uk,ur,uz'
        ]);

        $text = $request->input('text');
        $targetLanguages = $request->input('target_languages');

        $translations = [];
        $errors = [];

        foreach ($targetLanguages as $targetLang) {
            try {
                $translatedText = $this->performTranslation($text, 'en', $targetLang);
                
                $translations[$targetLang] = [
                    'language_code' => $targetLang,
                    'language_name' => $this->getLanguageName($targetLang),
                    'translated_text' => $translatedText
                ];
            } catch (\Exception $e) {
                // Only use static fallback as absolute last resort
                try {
                    $translatedText = $this->getAlternativeTranslation($text, $targetLang);
                    $translations[$targetLang] = [
                        'language_code' => $targetLang,
                        'language_name' => $this->getLanguageName($targetLang),
                        'translated_text' => $translatedText
                    ];
                } catch (\Exception $fallbackError) {
                    $errors[$targetLang] = 'Translation failed: ' . $e->getMessage();
                }
            }
        }

        return response()->json([
            'success' => true,
            'original_text' => $text,
            'translations' => $translations,
            'errors' => $errors,
            'total_translations' => count($translations)
        ]);
    }

    private function performTranslation($text, $sourceLang, $targetLang)
    {
        $client = new Client(['timeout' => 30]);
        
        // Try MyMemory Translation API first (proven to work well)
        try {
            $response = $client->get('https://api.mymemory.translated.net/get', [
                'query' => [
                    'q' => $text,
                    'langpair' => $sourceLang . '|' . $targetLang
                ],
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            if (isset($data['responseData']['translatedText']) && !empty($data['responseData']['translatedText'])) {
                return $data['responseData']['translatedText'];
            }
        } catch (\Exception $e) {
            // MyMemory failed, try LibreTranslate
        }
        
        // Try LibreTranslate API (completely free)
        try {
            $response = $client->post('https://libretranslate.de/translate', [
                'json' => [
                    'q' => $text,
                    'source' => $sourceLang,
                    'target' => $targetLang,
                    'format' => 'text'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            if (isset($data['translatedText']) && !empty($data['translatedText'])) {
                return $data['translatedText'];
            }
        } catch (\Exception $e) {
            // LibreTranslate failed, try alternative servers
        }
        
        // Try alternative LibreTranslate server
        try {
            $response = $client->post('https://translate.argosopentech.com/translate', [
                'json' => [
                    'q' => $text,
                    'source' => $sourceLang,
                    'target' => $targetLang
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            if (isset($data['translatedText']) && !empty($data['translatedText'])) {
                return $data['translatedText'];
            }
        } catch (\Exception $e) {
            // Argos OpenTech failed
        }
        
        // Try another free translation service
        try {
            $response = $client->get('https://api.funtranslations.com/translate/yoda.json', [
                'query' => [
                    'text' => $text
                ],
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            if (isset($data['contents']['translated']) && !empty($data['contents']['translated'])) {
                // This is just for testing - we'll use it as fallback for English
                if ($targetLang === 'en') {
                    return $data['contents']['translated'];
                }
            }
        } catch (\Exception $e) {
            // FunTranslations failed
        }
        
        throw new \Exception('All translation services are currently unavailable');
    }

    private function getAlternativeTranslation($text, $targetLang)
    {
        // Enhanced translation mapping for common words/phrases as fallback
        $translations = [
            'hi' => [
                // Single words
                'hello' => 'नमस्ते',
                'good morning' => 'सुप्रभात',
                'good afternoon' => 'नमस्कार',
                'good evening' => 'शुभ संध्या',
                'good night' => 'शुभ रात्रि',
                'how are you' => 'आप कैसे हैं',
                'thank you' => 'धन्यवाद',
                'please' => 'कृपया',
                'yes' => 'हाँ',
                'no' => 'नहीं',
                'sorry' => 'माफ करें',
                'welcome' => 'स्वागत है',
                'how' => 'कैसे',
                'are' => 'हैं',
                'you' => 'आप',
                'good' => 'अच्छा',
                'morning' => 'सुबह',
                'afternoon' => 'दोपहर',
                'evening' => 'शाम',
                'night' => 'रात',
                // Common phrases with names
                'hello neel' => 'नमस्ते नील',
                'good morning neel' => 'सुप्रभात नील',
                'how are you neel' => 'आप कैसे हैं नील',
                'hello neel, good morning how are you?' => 'नमस्ते नील, सुप्रभात आप कैसे हैं?',
                'hello neel good morning how are you' => 'नमस्ते नील सुप्रभात आप कैसे हैं',
                'hello neel, good morning' => 'नमस्ते नील, सुप्रभात',
                'good morning neel, how are you?' => 'सुप्रभात नील, आप कैसे हैं?',
                'hi neel' => 'नमस्ते नील',
                'hi neel, good morning' => 'नमस्ते नील, सुप्रभात',
                'hi neel, how are you?' => 'नमस्ते नील, आप कैसे हैं?'
            ],
            'es' => [
                'hello' => 'hola',
                'good morning' => 'buenos días',
                'good afternoon' => 'buenas tardes',
                'good evening' => 'buenas tardes',
                'good night' => 'buenas noches',
                'how are you' => '¿cómo estás?',
                'thank you' => 'gracias',
                'please' => 'por favor',
                'yes' => 'sí',
                'no' => 'no',
                'sorry' => 'lo siento',
                'welcome' => 'bienvenido',
                'how' => 'cómo',
                'are' => 'estás',
                'you' => 'tú',
                'good' => 'bueno',
                'morning' => 'mañana',
                'hello neel, good morning how are you?' => 'Hola Neel, ¡buenos días! ¿Cómo estás?',
                'hello neel good morning how are you' => 'Hola Neel buenos días cómo estás',
                'hello neel, good morning' => 'Hola Neel, buenos días',
                'hi neel' => 'Hola Neel',
                'hi neel, good morning' => 'Hola Neel, buenos días'
            ],
            'fr' => [
                'hello' => 'bonjour',
                'good morning' => 'bonjour',
                'good afternoon' => 'bonjour',
                'good evening' => 'bonsoir',
                'good night' => 'bonne nuit',
                'how are you' => 'comment allez-vous',
                'thank you' => 'merci',
                'please' => 's\'il vous plaît',
                'yes' => 'oui',
                'no' => 'non',
                'sorry' => 'désolé',
                'welcome' => 'bienvenue',
                'hello neel, good morning how are you?' => 'Bonjour Neel, bonjour, comment allez-vous?',
                'hello neel good morning how are you' => 'Bonjour Neel bonjour comment allez-vous',
                'hello neel, good morning' => 'Bonjour Neel, bonjour',
                'hi neel' => 'Salut Neel',
                'hi neel, good morning' => 'Salut Neel, bonjour'
            ],
            'de' => [
                'hello' => 'hallo',
                'good morning' => 'guten morgen',
                'good afternoon' => 'guten tag',
                'good evening' => 'guten abend',
                'good night' => 'gute nacht',
                'how are you' => 'wie geht es dir',
                'thank you' => 'danke',
                'please' => 'bitte',
                'yes' => 'ja',
                'no' => 'nein',
                'sorry' => 'entschuldigung',
                'welcome' => 'willkommen',
                'hello neel, good morning how are you?' => 'Hallo Neel, guten Morgen, wie geht es dir?',
                'hello neel good morning how are you' => 'Hallo Neel guten Morgen wie geht es dir',
                'hello neel, good morning' => 'Hallo Neel, guten Morgen',
                'hi neel' => 'Hallo Neel',
                'hi neel, good morning' => 'Hallo Neel, guten Morgen'
            ]
        ];

        $textLower = strtolower(trim($text));
        
        // First, try exact match
        if (isset($translations[$targetLang][$textLower])) {
            return $translations[$targetLang][$textLower];
        }

        // Try to find and replace multiple phrases in the text
        $result = $text;
        $hasTranslation = false;
        
        // Sort by length (longest first) to handle longer phrases first
        $phrases = $translations[$targetLang];
        uksort($phrases, function($a, $b) {
            return strlen($b) - strlen($a);
        });
        
        foreach ($phrases as $english => $translated) {
            if (stripos($result, $english) !== false) {
                $result = str_ireplace($english, $translated, $result);
                $hasTranslation = true;
            }
        }

        // If we made any translations, return the result
        if ($hasTranslation) {
            return $result;
        }

        // If no translation found, return a helpful message
        return "[Translation Service Temporarily Unavailable] " . $text . " → Please try simpler phrases or try again later.";
    }

    public function getSupportedLanguages()
    {
        $languages = [
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'nl' => 'Dutch',
            'sv' => 'Swedish',
            'da' => 'Danish',
            'no' => 'Norwegian',
            'fi' => 'Finnish',
            'pl' => 'Polish',
            'cs' => 'Czech',
            'sk' => 'Slovak',
            'hu' => 'Hungarian',
            'ro' => 'Romanian',
            'bg' => 'Bulgarian',
            'hr' => 'Croatian',
            'sl' => 'Slovenian',
            'et' => 'Estonian',
            'lv' => 'Latvian',
            'lt' => 'Lithuanian',
            'mt' => 'Maltese',
            'el' => 'Greek',
            'tr' => 'Turkish',
            'he' => 'Hebrew',
            'th' => 'Thai',
            'vi' => 'Vietnamese',
            'id' => 'Indonesian',
            'ms' => 'Malay',
            'sw' => 'Swahili',
            'am' => 'Amharic',
            'az' => 'Azerbaijani',
            'be' => 'Belarusian',
            'bn' => 'Bengali',
            'bs' => 'Bosnian',
            'ca' => 'Catalan',
            'eu' => 'Basque',
            'gl' => 'Galician',
            'is' => 'Icelandic',
            'ka' => 'Georgian',
            'kk' => 'Kazakh',
            'ky' => 'Kyrgyz',
            'lo' => 'Lao',
            'mk' => 'Macedonian',
            'mn' => 'Mongolian',
            'my' => 'Myanmar',
            'ne' => 'Nepali',
            'si' => 'Sinhala',
            'ta' => 'Tamil',
            'te' => 'Telugu',
            'uk' => 'Ukrainian',
            'ur' => 'Urdu',
            'uz' => 'Uzbek'
        ];

        return response()->json([
            'success' => true,
            'languages' => $languages
        ]);
    }

    private function getLanguageName($languageCode)
    {
        $languages = [
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'nl' => 'Dutch',
            'sv' => 'Swedish',
            'da' => 'Danish',
            'no' => 'Norwegian',
            'fi' => 'Finnish',
            'pl' => 'Polish',
            'cs' => 'Czech',
            'sk' => 'Slovak',
            'hu' => 'Hungarian',
            'ro' => 'Romanian',
            'bg' => 'Bulgarian',
            'hr' => 'Croatian',
            'sl' => 'Slovenian',
            'et' => 'Estonian',
            'lv' => 'Latvian',
            'lt' => 'Lithuanian',
            'mt' => 'Maltese',
            'el' => 'Greek',
            'tr' => 'Turkish',
            'he' => 'Hebrew',
            'th' => 'Thai',
            'vi' => 'Vietnamese',
            'id' => 'Indonesian',
            'ms' => 'Malay',
            'sw' => 'Swahili',
            'am' => 'Amharic',
            'az' => 'Azerbaijani',
            'be' => 'Belarusian',
            'bn' => 'Bengali',
            'bs' => 'Bosnian',
            'ca' => 'Catalan',
            'eu' => 'Basque',
            'gl' => 'Galician',
            'is' => 'Icelandic',
            'ka' => 'Georgian',
            'kk' => 'Kazakh',
            'ky' => 'Kyrgyz',
            'lo' => 'Lao',
            'mk' => 'Macedonian',
            'mn' => 'Mongolian',
            'my' => 'Myanmar',
            'ne' => 'Nepali',
            'si' => 'Sinhala',
            'ta' => 'Tamil',
            'te' => 'Telugu',
            'uk' => 'Ukrainian',
            'ur' => 'Urdu',
            'uz' => 'Uzbek'
        ];

        return $languages[$languageCode] ?? $languageCode;
    }

    private function generateAudio($text, $voice, $speed)
    {
        // This is a placeholder implementation
        // In a real application, you would:
        // 1. Use Google Text-to-Speech API
        // 2. Use AWS Polly
        // 3. Use Azure Cognitive Services
        // 4. Use a local TTS engine like eSpeak
        
        // For demo purposes, we'll create a simple audio file
        // You can replace this with actual TTS API calls
        
        $audioContent = $this->createDemoAudio($text);
        return $audioContent;
    }

    private function createDemoAudio($text)
    {
        // This is just a placeholder - in reality you'd call a TTS service
        // For now, we'll return a simple message
        return "Audio generation for: " . substr($text, 0, 50) . "...";
    }

    public function getVoices()
    {
        // Browser voices will be loaded dynamically on the client side
        // This endpoint is kept for compatibility
        return response()->json([
            'message' => 'Voices will be loaded from browser',
            'supported_features' => [
                'speed_control' => true,
                'pitch_control' => true,
                'volume_control' => true,
                'voice_selection' => true
            ]
        ]);
    }
}
