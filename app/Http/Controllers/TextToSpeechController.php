<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GuzzleHttp\Client;
use Stichoza\GoogleTranslate\GoogleTranslate;

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
                
                // Check if translation actually changed the text
                if ($translatedText === $text) {
                    // Try alternative translation method
                    $translatedText = $this->getAlternativeTranslation($text, $targetLang);
                }
                
                $translations[$targetLang] = [
                    'language_code' => $targetLang,
                    'language_name' => $this->getLanguageName($targetLang),
                    'translated_text' => $translatedText
                ];
            } catch (\Exception $e) {
                $errors[$targetLang] = 'Translation failed: ' . $e->getMessage();
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
        try {
            $tr = new GoogleTranslate($sourceLang, $targetLang);
            
            // Set options to try to bypass detection
            $tr->setOptions([
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ]);
            
            return $tr->translate($text);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getAlternativeTranslation($text, $targetLang)
    {
        // Simple translation mapping for common words/phrases as fallback
        $translations = [
            'hi' => [
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
                'welcome' => 'स्वागत है'
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
                'welcome' => 'bienvenido'
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
                'welcome' => 'bienvenue'
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
                'welcome' => 'willkommen'
            ]
        ];

        $textLower = strtolower(trim($text));
        
        if (isset($translations[$targetLang][$textLower])) {
            return $translations[$targetLang][$textLower];
        }

        // If no direct match, return a message indicating translation service issue
        return "[Translation Service Temporarily Unavailable] " . $text . " → Please try again later or use a different translation service.";
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
