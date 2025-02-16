<?php

namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('\\WPAICG\\WPAICG_ElevenLabs')) {
    class WPAICG_ElevenLabs
    {
        private static $instance = null;
        public $url = 'https://api.elevenlabs.io/v1/';
        public $api_key;
        public $voice;
        public $type;
        public $voices = array(
            '21m00Tcm4TlvDq8ikWAM' => 'Rachel',
            'AZnzlk1XvdvUeBnXmlld' => 'Domi',
            'EXAVITQu4vr4xnSDxMaL' => 'Bella',
            'ErXwobaYiN019PkySvjV' => 'Antoni',
            'MF3mGyEYCl7XYWbV9V6O' => 'Elli',
            'TxGEqnHWrfWFTfGW9XjX' => 'Josh',
            'VR6AewLTigWG4xSOukaG' => 'Arnold',
            'pNInz6obpgDQGcFmaJgB' => 'Adam',
            'yoZ06aMxZJJ28mfd3POQ' => 'Sam'
        );

        public $model;
        public $models = array(
            'eleven_multilingual_v1' => 'Eleven Multilingual v1',
            'eleven_monolingual_v1' => 'Eleven English v1'
        );
        
        public $stream_method = null;

        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            $this->init();
            $voices = get_option('wpaicg_elevenlabs_voices',[]);
            if($voices && is_array($voices) && count($voices)){
                $this->voices = $voices;
            }

            $models = get_option('wpaicg_elevenlabs_models', []);
            if ($models && is_array($models) && count($models)) {
                $this->models = $models;
            }

            add_action('http_api_curl', array($this, 'filterCurlForStream'));
            add_action('wp_ajax_wpaicg_text_to_speech', [$this,'wpaicg_text_to_speech']);
            add_action('wp_ajax_nopriv_wpaicg_text_to_speech', [$this,'wpaicg_text_to_speech']);
            add_action('wp_ajax_wpaicg_speech_error_log', [$this,'wpaicg_speech_error_log']);
            add_action('wp_ajax_nopriv_wpaicg_speech_error_log', [$this,'wpaicg_speech_error_log']);
            add_action('wp_ajax_wpaicg_sync_voices', [$this,'wpaicg_sync_voices']);
            add_action('wp_ajax_wpaicg_sync_models', [$this,'wpaicg_sync_models']);
        }

        public function wpaicg_sync_voices(){
            $result = array('status' => 'error', 'message' => __('Missing parameters','gpt3-ai-content-generator'));
            if(!current_user_can('manage_options')){
                $wpaicg_result['message'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg_save_ai_engine_nonce' ) ) {
                $result['message'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
            }
            else{
                $sync = $this->wpaicg_load_voices();
                if($sync === true){
                    $result = array('status' => 'success', 'message' => __('Voices synced successfully','gpt3-ai-content-generator'));
                }
                else{
                    $result['message'] = $sync;
                }
            }
            wp_send_json($result);
        }

        public function wpaicg_load_voices()
        {
            if(!empty($this->api_key)){
                $response = wp_remote_get($this->url.'voices', array(
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'xi-api-key' => $this->api_key
                    )
                ));
                if(!is_wp_error($response)){
                    $body = json_decode(wp_remote_retrieve_body($response),true);
                    if($body && is_array($body) && isset($body['voices']) && is_array($body['voices'])){
                        $option_voices = [];
                        foreach($body['voices'] as $voice){
                            $option_voices[$voice['voice_id']] = $voice['name'];
                        }
                        $this->voices = $option_voices;
                        update_option('wpaicg_elevenlabs_voices', $option_voices);
                        return true;
                    }
                    else{
                        return $body['detail']['message'];
                    }
                }
                else return $response->get_error_message();
            }
        }

        public function wpaicg_sync_models(){
            $result = array('status' => 'error', 'message' => __('Missing parameters','gpt3-ai-content-generator'));
            if(!current_user_can('manage_options')){
                $wpaicg_result['message'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg_save_ai_engine_nonce' ) ) {
                $result['message'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
            }
            else{
                $sync = $this->wpaicg_load_models();
                if($sync === true){
                    $result = array('status' => 'success', 'message' => __('Models synced successfully','gpt3-ai-content-generator'));
                }
                else{
                    if (is_string($sync)) {
                        $result['message'] = $sync;
                    } else {
                        $result['message'] = __('Error syncing models', 'gpt3-ai-content-generator');
                    }
                }
            }
            wp_send_json($result);
        }

        public function wpaicg_load_models()
        {
            if(!empty($this->api_key)){
                $response = wp_remote_get($this->url.'models', array(
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'xi-api-key' => $this->api_key
                    )
                ));
                
                if(!is_wp_error($response)){
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    
                    if ($body && is_array($body)) {
                        // Check for an error message in the response
                        if (isset($body['detail']) && isset($body['detail']['status']) && $body['detail']['status'] === 'invalid_api_key') {
                            return $body['detail']['message'];
                        }

                        $option_models = [];
                        foreach($body as $model) {
                            if (isset($model['model_id']) && isset($model['name'])) {
                                $option_models[$model['model_id']] = $model['name'];
                            }
                        }
                        
                        // Check if models were processed
                        if (!empty($option_models)) {
                            $this->models = $option_models; // Store models in the class property
                            update_option('wpaicg_elevenlabs_models', $option_models); // Update the WordPress option
                            return true;
                        } else {
                            return 'No valid models found in the API response.';
                        }
                    } else {
                        return 'Unexpected response from API.';
                    }
                }
                else {
                    return $response->get_error_message();
                }
            }
            return 'API Key is not set';
        }

        
        
          

        public function fetch_voice_settings($voice_id) {
            if(empty($this->api_key)) {
                return false;
            }
        
            $response = wp_remote_get($this->url . 'voices/' . $voice_id . '/settings', array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'xi-api-key' => $this->api_key
                )
            ));
        
            if(is_wp_error($response)) {
                // Optionally, log the error or handle it.
                return false;
            }
        
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if(isset($body['stability']) && isset($body['similarity_boost']) && isset($body['style']) && isset($body['use_speaker_boost'])) {
                return $body; // Return the settings
            }
        
            return false; // Failed to fetch or parse the voice settings.
        }
        
        

        public function wpaicg_speech_error_log()
        {
            global $wpdb;
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-chatbox' ) ) {
                die(esc_html__('Nonce verification failed','gpt3-ai-content-generator'));
            }
            if(
                isset($_REQUEST['message'])
                && !empty($_REQUEST['message'])
                && isset($_REQUEST['log_id'])
                && !empty($_REQUEST['log_id'])
            ){
                $log_id = sanitize_text_field($_REQUEST['log_id']);
                $message = sanitize_text_field($_REQUEST['message']);
                $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.'wpaicg_chatlogs'." WHERE id=%d", $log_id));
                if($log){
                    $logs = json_decode($log->data,true);
                    if($logs && is_array($logs) && count($logs)){
                        $lastLog = count($logs)-1;
                        $logs[$lastLog]['message'] .= "\n".$message;
                    }
                    $wpdb->update($wpdb->prefix.'wpaicg_chatlogs', array(
                        'data' => json_encode($logs),
                        'created_at' => time()
                    ), array(
                        'id' => $log_id
                    ));
                }
            }
            exit;
        }

        public function wpaicg_text_to_speech()
        {
            $result = array('detail' => array('status' => 'error', 'message' => __('Missing parameters','gpt3-ai-content-generator')));

            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-chatbox' ) ) {
                $result['detail']['message'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
            }
            elseif(isset($_REQUEST['message']) && !empty($_REQUEST['message'])) {
                $voice = isset($_REQUEST['voice']) && !empty($_REQUEST['voice']) ? sanitize_text_field($_REQUEST['voice']) : '21m00Tcm4TlvDq8ikWAM';
                $message = sanitize_text_field($_REQUEST['message']);
                $model = isset($_REQUEST['elevenlabs_model']) && !empty($_REQUEST['elevenlabs_model']) ? sanitize_text_field($_REQUEST['elevenlabs_model']) : 'eleven_monolingual_v1'; // Added the model line
                $result = $this->stream($voice, $message, $model); // Added the model parameter
            }

            if(is_array($result)){
                wp_send_json($result);
            }
            else {
                echo $result;
                die();
            }
        }


        public function filterCurlForStream($handle)
        {
            if ($this->stream_method !== null){
                curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($handle, CURLOPT_WRITEFUNCTION, function ($curl_info, $data) {
                    return call_user_func($this->stream_method, $this, $data);
                });
            }
        }

        public function init()
        {
            $api_key = get_option('wpaicg_elevenlabs_api','');
            $this->api_key = $api_key;
            return $this;
        }

        public function stream($voice, $text, $model)
        {
            if(empty($this->api_key)) {
                return array('detail' => array('status' => 'missing_api', 'message' => __('Missing ElevenLabs API keys', 'gpt3-ai-content-generator')));
            } else {
                $text = str_replace("\\", '', $text);

                // Fetch voice settings dynamically
                $voice_settings = $this->fetch_voice_settings($voice);
                if(!$voice_settings) {
                    // Fallback to default settings if fetching fails.
                    $voice_settings = array(
                        'stability' => 0.5,
                        'similarity_boost' => 0.75,
                        'style' => 0.5,
                        'use_speaker_boost' => true
                    );
                }

                $response = wp_remote_post($this->url . 'text-to-speech/' . $voice . '/stream', array(
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'xi-api-key' => $this->api_key
                    ),
                    'body' => json_encode(array(
                        'text' => $text,
                        'model_id' => $model, 
                        'voice_settings' => $voice_settings
                    )),
                    'timeout' => 1000
                ));
        
                if(is_wp_error($response)) {
                    return array('detail' => array('status' => 'error', 'message' => $response->get_error_message()));
                } else {
                    return wp_remote_retrieve_body($response);
                }
            }
        }
        
    }
    WPAICG_ElevenLabs::get_instance();
}
