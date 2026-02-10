<?php
/**
 * Saint Porphyrius - Quiz AI Handler
 * Handles AI integration for quiz content processing and question generation
 * Uses OpenAI GPT-4o for reliable, accurate Christian content processing
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Quiz_AI {
    
    private static $instance = null;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {}
    
    /**
     * Get the API key from settings
     */
    private function get_api_key() {
        $quiz = SP_Quiz::get_instance();
        $settings = $quiz->get_settings();
        return $settings['openai_api_key'];
    }
    
    /**
     * Get the AI model from settings
     */
    private function get_model() {
        $quiz = SP_Quiz::get_instance();
        $settings = $quiz->get_settings();
        return $settings['ai_model'] ?: 'gpt-4o';
    }
    
    /**
     * Make an API call to OpenAI
     */
    private function call_api($messages, $max_tokens = 8000, $temperature = 0.3) {
        $api_key = $this->get_api_key();
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'مفتاح OpenAI API غير مُعد. يرجى إعداده في إعدادات الاختبارات.');
        }
        
        $body = array(
            'model'       => $this->get_model(),
            'messages'    => $messages,
            'max_tokens'  => $max_tokens,
            'temperature' => $temperature,
            'response_format' => array('type' => 'json_object'),
        );
        
        $response = wp_remote_post($this->api_url, array(
            'timeout' => 120,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode($body),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code !== 200) {
            $error_msg = isset($body['error']['message']) ? $body['error']['message'] : 'خطأ غير معروف من API';
            return new WP_Error('api_error', $error_msg);
        }
        
        $content = $body['choices'][0]['message']['content'] ?? '';
        $tokens_used = $body['usage']['total_tokens'] ?? 0;
        
        $parsed = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'فشل في تحليل استجابة AI: ' . json_last_error_msg());
        }
        
        return array(
            'data'   => $parsed,
            'tokens' => $tokens_used,
            'model'  => $this->get_model(),
        );
    }
    
    /**
     * Extract YouTube video ID from URL
     */
    public function extract_youtube_id($url) {
        $patterns = array(
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    /**
     * Get YouTube transcript using a free transcript API
     * Falls back to video metadata if transcript unavailable
     */
    public function get_youtube_transcript($youtube_url) {
        $video_id = $this->extract_youtube_id($youtube_url);
        if (!$video_id) {
            return new WP_Error('invalid_url', 'رابط YouTube غير صالح');
        }
        
        // Try to get transcript via YouTube Transcript API (using a popular free endpoint)
        $transcript_url = 'https://yt-api.p.rapidapi.com/video/transcript?videoId=' . $video_id;
        
        // First try: Use oEmbed to at least get title/description
        $oembed_url = 'https://www.youtube.com/oembed?url=' . urlencode($youtube_url) . '&format=json';
        $oembed_response = wp_remote_get($oembed_url, array('timeout' => 15));
        
        $video_title = '';
        if (!is_wp_error($oembed_response)) {
            $oembed_data = json_decode(wp_remote_retrieve_body($oembed_response), true);
            $video_title = $oembed_data['title'] ?? '';
        }
        
        return array(
            'video_id'    => $video_id,
            'title'       => $video_title,
            'transcript'  => null, // Will be provided by admin or extracted via AI
            'embed_url'   => 'https://www.youtube.com/embed/' . $video_id,
        );
    }
    
    /**
     * Process and format content using AI
     * Takes raw admin input and creates nicely formatted educational content
     */
    public function process_content($content_id, $admin_notes = '') {
        $quiz = SP_Quiz::get_instance();
        $content = $quiz->get_content($content_id);
        
        if (!$content) {
            return new WP_Error('not_found', 'المحتوى غير موجود');
        }
        
        // Build the input text for AI
        $input_text = $content->raw_input;
        
        // If YouTube, include transcript if available
        if ($content->youtube_url && $content->youtube_transcript) {
            $input_text .= "\n\n--- محتوى الفيديو ---\n" . $content->youtube_transcript;
        }
        
        $system_prompt = "أنت خبير في المحتوى المسيحي الأرثوذكسي. مهمتك هي تنسيق وتحسين المحتوى التعليمي المسيحي المقدم لك.

القواعد الصارمة:
1. استخدم فقط المعلومات الموجودة في المحتوى المقدم - لا تضف أي معلومات خارجية
2. حافظ على الدقة اللاهوتية والكتابية
3. نسق المحتوى بشكل تعليمي واضح مع عناوين فرعية
4. اذكر الآيات الكتابية بشكل دقيق
5. استخدم اللغة العربية الفصحى
6. لا تحرف أو تغير أي معنى من المحتوى الأصلي

أعد المحتوى بصيغة JSON التالية:
{
    \"formatted_content\": \"المحتوى المنسق بصيغة HTML (استخدم h3, h4, p, ul, li, blockquote, strong, em)\",
    \"summary\": \"ملخص قصير للمحتوى في 2-3 جمل\",
    \"key_points\": [\"النقطة الرئيسية 1\", \"النقطة الرئيسية 2\", ...]
}";

        $user_prompt = "قم بتنسيق المحتوى التعليمي المسيحي التالي بشكل جميل ومنظم:\n\n";
        $user_prompt .= "العنوان: " . $content->title_ar . "\n\n";
        $user_prompt .= "المحتوى:\n" . $input_text;
        
        if ($admin_notes) {
            $user_prompt .= "\n\n--- تعليمات إضافية من المسؤول ---\n" . $admin_notes;
        }
        
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt),
        );
        
        $result = $this->call_api($messages, 4000, 0.3);
        
        if (is_wp_error($result)) {
            $quiz->log_ai_action($content_id, 'format_content', $user_prompt, '', $this->get_model(), 0, 'error', $result->get_error_message());
            return $result;
        }
        
        // Save formatted content
        $formatted = $result['data']['formatted_content'] ?? '';
        
        $quiz->update_content($content_id, array(
            'ai_formatted_content' => $formatted,
            'ai_model'             => $result['model'],
            'ai_generation_prompt' => $user_prompt,
        ));
        
        // Log the action
        $quiz->log_ai_action($content_id, 'format_content', $user_prompt, $result['data'], $result['model'], $result['tokens']);
        
        return array(
            'formatted_content' => $formatted,
            'summary'          => $result['data']['summary'] ?? '',
            'key_points'       => $result['data']['key_points'] ?? array(),
            'tokens_used'      => $result['tokens'],
        );
    }
    
    /**
     * Generate quiz questions from content using AI
     */
    public function generate_questions($content_id, $num_questions = 50, $admin_instructions = '') {
        $quiz = SP_Quiz::get_instance();
        $content = $quiz->get_content($content_id);
        
        if (!$content) {
            return new WP_Error('not_found', 'المحتوى غير موجود');
        }
        
        // Use formatted content if available, otherwise raw input
        $source_text = $content->ai_formatted_content ?: $content->raw_input;
        
        if ($content->youtube_url && $content->youtube_transcript) {
            $source_text .= "\n\n--- محتوى الفيديو ---\n" . $content->youtube_transcript;
        }
        
        $system_prompt = "أنت خبير في إنشاء اختبارات مسيحية أرثوذكسية تعليمية. مهمتك هي إنشاء أسئلة اختبار بناءً على المحتوى المقدم فقط.

القواعد الصارمة:
1. أنشئ الأسئلة بناءً على المحتوى المقدم فقط - لا تستخدم أي معلومات خارجية
2. كل سؤال يجب أن يكون له إجابة واضحة ومحددة من المحتوى
3. تجنب الأسئلة الغامضة أو المبهمة
4. نوع بين مستويات الصعوبة (سهل، متوسط، صعب)
5. رتب الأسئلة بترتيب منطقي يتبع سياق المحتوى
6. كل سؤال يجب أن يحتوي على 4 خيارات (اختيار من متعدد) أو صح/خطأ
7. اجعل الخيارات الخاطئة معقولة ولكن واضحة الخطأ
8. أضف شرحاً مختصراً لكل إجابة صحيحة
9. استخدم اللغة العربية الفصحى
10. لا تكرر الأسئلة أو تسأل نفس المعلومة بصياغات مختلفة

أعد الأسئلة بصيغة JSON التالية:
{
    \"questions\": [
        {
            \"question\": \"نص السؤال\",
            \"type\": \"multiple_choice\",
            \"options\": [
                {\"text\": \"الخيار الأول\", \"is_correct\": true},
                {\"text\": \"الخيار الثاني\", \"is_correct\": false},
                {\"text\": \"الخيار الثالث\", \"is_correct\": false},
                {\"text\": \"الخيار الرابع\", \"is_correct\": false}
            ],
            \"correct_answer_index\": 0,
            \"explanation\": \"شرح الإجابة الصحيحة\",
            \"difficulty\": \"easy\"
        }
    ]
}

ملاحظة مهمة: correct_answer_index هو رقم فهرس الإجابة الصحيحة (يبدأ من 0)";

        $user_prompt = sprintf("أنشئ %d سؤال اختبار متنوع بناءً على المحتوى التالي:\n\n", $num_questions);
        $user_prompt .= "العنوان: " . $content->title_ar . "\n\n";
        $user_prompt .= "المحتوى:\n" . $source_text;
        
        if ($admin_instructions) {
            $user_prompt .= "\n\n--- تعليمات إضافية من المسؤول ---\n" . $admin_instructions;
        }
        
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt),
        );
        
        // Use higher token limit for question generation
        $result = $this->call_api($messages, 16000, 0.4);
        
        if (is_wp_error($result)) {
            $quiz->log_ai_action($content_id, 'generate_questions', $user_prompt, '', $this->get_model(), 0, 'error', $result->get_error_message());
            return $result;
        }
        
        $questions = $result['data']['questions'] ?? array();
        
        if (empty($questions)) {
            return new WP_Error('no_questions', 'لم يتم إنشاء أي أسئلة. يرجى المحاولة مرة أخرى.');
        }
        
        // Validate and fix correct_answer_index
        foreach ($questions as &$q) {
            // Find correct answer from options
            $correct_found = false;
            foreach ($q['options'] as $idx => $opt) {
                if (!empty($opt['is_correct'])) {
                    $q['correct_answer_index'] = $idx;
                    $correct_found = true;
                    break;
                }
            }
            if (!$correct_found) {
                $q['correct_answer_index'] = 0;
            }
        }
        unset($q);
        
        // Log the action
        $quiz->log_ai_action($content_id, 'generate_questions', $user_prompt, $result['data'], $result['model'], $result['tokens']);
        
        return array(
            'questions'   => $questions,
            'count'       => count($questions),
            'tokens_used' => $result['tokens'],
        );
    }
    
    /**
     * Full pipeline: process content + generate questions
     */
    public function process_and_generate($content_id, $admin_notes = '', $num_questions = 50) {
        $quiz = SP_Quiz::get_instance();
        
        // Update status to processing
        $quiz->update_content($content_id, array('status' => 'ai_processing'));
        
        // Step 1: Process/format content
        $content_result = $this->process_content($content_id, $admin_notes);
        if (is_wp_error($content_result)) {
            $quiz->update_content($content_id, array('status' => 'draft'));
            return $content_result;
        }
        
        // Step 2: Generate questions
        $questions_result = $this->generate_questions($content_id, $num_questions, $admin_notes);
        if (is_wp_error($questions_result)) {
            $quiz->update_content($content_id, array('status' => 'draft'));
            return $questions_result;
        }
        
        // Step 3: Save questions to database
        $quiz->delete_questions($content_id); // Remove any existing questions
        $saved = $quiz->save_questions($content_id, $questions_result['questions']);
        
        // Step 4: Update status to ready for review
        $quiz->update_content($content_id, array('status' => 'ai_ready'));
        
        return array(
            'content'         => $content_result,
            'questions'       => $questions_result['questions'],
            'questions_saved' => $saved,
            'total_tokens'    => ($content_result['tokens_used'] ?? 0) + ($questions_result['tokens_used'] ?? 0),
        );
    }
    
    /**
     * Regenerate just the questions with updated instructions
     */
    public function regenerate_questions($content_id, $admin_instructions = '', $num_questions = 50) {
        $quiz = SP_Quiz::get_instance();
        
        // Generate new questions
        $result = $this->generate_questions($content_id, $num_questions, $admin_instructions);
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Replace existing questions
        $quiz->delete_questions($content_id);
        $saved = $quiz->save_questions($content_id, $result['questions']);
        
        // Reset to review status
        $quiz->update_content($content_id, array(
            'status'      => 'ai_ready',
            'admin_notes' => $admin_instructions,
        ));
        
        // Log regeneration
        $quiz->log_ai_action($content_id, 'regenerate', $admin_instructions, $result, $this->get_model(), $result['tokens_used']);
        
        return array(
            'questions'       => $result['questions'],
            'questions_saved' => $saved,
            'tokens_used'     => $result['tokens_used'],
        );
    }
}
