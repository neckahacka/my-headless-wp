<?php
namespace WPAICG;

if ( ! defined( 'ABSPATH' ) ) exit;

if(!class_exists('\\WPAICG\\WPAICG_OpenAI_Speech')) {
    class WPAICG_OpenAI_Speech
    {
        private static $instance = null;

        public static function get_instance()
        {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            add_action('wp_ajax_wpaicg_openai_speech', [$this, 'generate_speech']);
            add_action('wp_ajax_nopriv_wpaicg_openai_speech', [$this, 'generate_speech']);
        }

        public function generate_speech()
        {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-chatbox')) {
                wp_send_json_error('Invalid nonce');
            }
            $text = isset($_POST['text']) ? sanitize_text_field($_POST['text']) : '';
            $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : 'tts-1'; // Default model
            $voice = isset($_POST['voice']) ? sanitize_text_field($_POST['voice']) : 'alloy'; // Default voice
            $output_format = isset($_POST['output_format']) ? sanitize_text_field($_POST['output_format']) : 'mp3';
            $speed = isset($_POST['speed']) ? sanitize_text_field($_POST['speed']) : '1.0'; // Default speed

            $openAI = WPAICG_OpenAI::get_instance();
            $opts = [
                'tts' => true, // Indicate that this is a TTS request
                'model' => $model,
                'input' => $text,
                'voice' => $voice,
                'response_format' => $output_format,
                'speed' => $speed
            ];

            $audioData = $openAI->createSpeech($opts); // Get the audio data

            // Set the content type header based on the selected output format
            switch ($output_format) {
                case 'opus':
                    header('Content-Type: audio/opus');
                    break;
                case 'aac':
                    header('Content-Type: audio/aac');
                    break;
                case 'flac':
                    header('Content-Type: audio/flac');
                    break;
                default:
                    header('Content-Type: audio/mpeg'); // Default to MP3
            }

            // Process the result and return appropriate response
            echo $audioData;
            exit;
        }
    }
    WPAICG_OpenAI_Speech::get_instance();
}
