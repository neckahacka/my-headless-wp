<?php
namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;
class WPAICG_Url
{
    const ORIGIN = 'https://api.openai.com';
    const API_VERSION = 'v1';
    const OPEN_AI_URL = self::ORIGIN . "/" . self::API_VERSION;

    /**
     * @deprecated
     * @param string $engine
     * @return string
     */
    public static function completionURL(string $engine): string
    {
        return self::OPEN_AI_URL . "/engines/$engine/completions";
    }

    /**
     * @return string
     */
    public static function completionsURL(): string
    {
        return self::OPEN_AI_URL . "/completions";
    }

    /**
     * @return string
     */
    public static function speechUrl(): string {
        return self::OPEN_AI_URL . "/audio/speech";
    }

    /**
     *
     * @return string
     */
    public static function editsUrl(): string
    {
        return self::OPEN_AI_URL . "/edits";
    }

    /**
     * @param string $engine
     * @return string
     */
    public static function searchURL(string $engine): string
    {
        return self::OPEN_AI_URL . "/engines/$engine/search";
    }

    /**
     * @param
     * @return string
     */
    public static function enginesUrl(): string
    {
        return self::OPEN_AI_URL . "/engines";
    }

    /**
     * @param string $engine
     * @return string
     */
    public static function engineUrl(string $engine): string
    {
        return self::OPEN_AI_URL . "/engines/$engine";
    }

    /**
     * @return string
     */
    public static function assistantsUrl(): string
    {
        return self::OPEN_AI_URL . "/assistants";
    }

        /**
     * @param string $assistant_id
     * @return string
     */
    public static function assistantUrl(string $assistant_id): string
    {
        return self::OPEN_AI_URL . "/assistants/{$assistant_id}";
    }

    /**
     * @return string
     */
    public static function threadsUrl(): string
    {
        return self::OPEN_AI_URL . "/threads";
    }

    /**
     * @param string $thread_id
     * @return string
     */
    public static function threadUrl(string $thread_id): string
    {
        return self::OPEN_AI_URL . "/threads/{$thread_id}";
    }

    /**
     * @param string $thread_id
     * @return string
     */
    public static function messagesUrl(string $thread_id): string
    {
        return self::OPEN_AI_URL . "/threads/{$thread_id}/messages";
    }

    /**
     * @param string $thread_id
     * @param string $message_id
     * @return string
     */
    public static function messageUrl(string $thread_id, string $message_id): string
    {
        return self::OPEN_AI_URL . "/threads/{$thread_id}/messages/{$message_id}";
    }

    /**
     * @param string $thread_id
     * @return string
     */
    public static function runsUrl(string $thread_id): string
    {
        return self::OPEN_AI_URL . "/threads/{$thread_id}/runs";
    }

    /**
     * @param string $thread_id
     * @param string $run_id
     * @return string
     */
    public static function runUrl(string $thread_id, string $run_id): string
    {
        return self::OPEN_AI_URL . "/threads/{$thread_id}/runs/{$run_id}";
    }

    /**
     * @param string $thread_id
     * @param string $run_id
     * @return string
     */
    public static function runStepsUrl(string $thread_id, string $run_id): string
    {
        return self::OPEN_AI_URL . "/threads/{$thread_id}/runs/{$run_id}/steps";
    }

    /**
     * @param
     * @return string
     */
    public static function classificationsUrl(): string
    {
        return self::OPEN_AI_URL . "/classifications";
    }

    /**
     * @param
     * @return string
     */
    public static function moderationUrl(): string
    {
        return self::OPEN_AI_URL . "/moderations";
    }

    /**
     * @param
     * @return string
     */
    public static function filesUrl(): string
    {
        return self::OPEN_AI_URL . "/files";
    }

    /**
     * @param
     * @return string
     */
    public static function fineTuneUrl(): string
    {
        return self::OPEN_AI_URL . "/fine_tuning/jobs";
    }

    /**
     * @param
     * @return string
     */
    public static function chatUrl(): string
    {
        return self::OPEN_AI_URL . "/chat/completions";
    }

    /**
     * @param
     * @return string
     */
    public static function fineTuneModel(): string
    {
        return self::OPEN_AI_URL . "/models";
    }

    /**
     * @param
     * @return string
     */
    public static function answersUrl(): string
    {
        return self::OPEN_AI_URL . "/answers";
    }

    /**
     * @param
     * @return string
     */
    public static function imageUrl(): string
    {
        return self::OPEN_AI_URL . "/images";
    }

    /**
     * @param
     * @return string
     */
    public static function transcriptionsUrl(): string
    {
        return self::OPEN_AI_URL . "/audio/transcriptions";
    }

    /**
     * @param
     * @return string
     */
    public static function translationsUrl(): string
    {
        return self::OPEN_AI_URL . "/audio/translations";
    }

    /**
     * @param
     * @return string
     */
    public static function embeddings(): string
    {
        return self::OPEN_AI_URL . "/embeddings";
    }
}

if (!class_exists('\\WPAICG\\WPAICG_OpenAI')){
    class WPAICG_OpenAI
    {
        private  static $instance = null ;
        private $engine = "davinci";
        private $model = "text-davinci-003";

        public $temperature;
        public $max_tokens;
        public $top_p;
        public $frequency_penalty;
        public $presence_penalty;
        public $best_of;
        public $img_size;
        public $api_key;
        public $wpai_language;
        public $wpai_add_img;
        public $wpai_add_intro;
        public $wpai_add_conclusion;
        public $wpai_add_tagline;
        public $wpai_add_faq;
        public $wpai_add_keywords_bold;
        public $wpai_number_of_heading;
        public $wpai_modify_headings;
        public $wpai_heading_tag;
        public $wpai_writing_style;
        public $wpai_writing_tone;
        public $wpai_target_url;
        public $wpai_target_url_cta;
        public $wpai_cta_pos;


        private $headers;
        public $response;

        private $timeout = 200;
        private $stream_method;

        public static function get_instance()
        {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function openai()
        {
            global $wpdb;
            $wpaicgTable = $wpdb->prefix . 'wpaicg';
            $sql = $wpdb->prepare( 'SELECT * FROM ' . $wpaicgTable . ' where name=%s','wpaicg_settings' );
            $wpaicg_settings = $wpdb->get_row( $sql, ARRAY_A );
            if($wpaicg_settings && isset($wpaicg_settings['api_key']) && !empty($wpaicg_settings['api_key'])){
                add_action('http_api_curl', array($this, 'filterCurlForStream'));
                $this->headers = [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$wpaicg_settings['api_key'],
                ];
                unset($wpaicg_settings['ID']);
                unset($wpaicg_settings['name']);
                unset($wpaicg_settings['added_date']);
                unset($wpaicg_settings['modified_date']);
                foreach($wpaicg_settings as $key=>$wpaicg_setting){
                    $this->$key = $wpaicg_setting;
                }
                return $this;
            }
            else return false;
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

        /**
         * Create speech from text.
         * 
         * @param array $opts Options for speech generation.
         * @return bool|string
         */
        public function createSpeech(array $opts) {
            $url = WPAICG_Url::speechUrl();

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function listModels()
        {
            $url = WPAICG_Url::fineTuneModel();

            return $this->sendRequest($url, 'GET');
        }

        public function retrieveModel($model)
        {
            $model = "/$model";
            $url = WPAICG_Url::fineTuneModel() . $model;

            return $this->sendRequest($url, 'GET');
        }

        public function setResponse($content="")
        {
            $this->response = $content;
        }

        public function complete($opts)
        {
            $engine = $opts['engine'] ?? $this->engine;
            $url = WPAICG_Url::completionURL($engine);
            unset($opts['engine']);

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function completion($opts, $stream = null)
        {
            if ($stream != null && array_key_exists('stream', $opts)) {
                if (! $opts['stream']) {
                    throw new \Exception(
                        'Please provide a stream function.'
                    );
                }
                $this->stream_method = $stream;
            }

            $opts['model'] = $opts['model'] ?? $this->model;
            $url = WPAICG_Url::completionsURL();

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function chat($opts, $stream = null)
        {
            if ($stream != null && array_key_exists('stream', $opts)) {
                if (! $opts['stream']) {
                    throw new \Exception(
                        'Please provide a stream function.'
                    );
                }
                $this->stream_method = $stream;
            }

            $opts['model'] = $opts['model'] ?? $this->model;

            $url = WPAICG_Url::chatUrl();
            return $this->sendRequest($url, 'POST', $opts);
        }

        public function transcriptions($opts)
        {
            $url = WPAICG_Url::transcriptionsUrl();
            return $this->sendRequest($url, 'POST', $opts);
        }

        public function translations($opts)
        {
            $url = WPAICG_Url::translationsUrl();
            return $this->sendRequest($url, 'POST', $opts);
        }

        public function createEdit($opts)
        {
            $url = WPAICG_Url::editsUrl();

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function image($opts)
        {
            $url = WPAICG_Url::imageUrl() . "/generations";

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function imageEdit($opts)
        {
            $url = WPAICG_Url::imageUrl() . "/edits";

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function createImageVariation($opts)
        {
            $url = WPAICG_Url::imageUrl() . "/variations";

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function search($opts)
        {
            $engine = $opts['engine'] ?? $this->engine;
            $url = WPAICG_Url::searchURL($engine);
            unset($opts['engine']);

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function answer($opts)
        {
            $url = WPAICG_Url::answersUrl();
            return $this->sendRequest($url, 'POST', $opts);
        }

        public function classification($opts)
        {
            $url = WPAICG_Url::classificationsUrl();

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function moderation($opts)
        {
            $url = WPAICG_Url::moderationUrl();

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function uploadFile($opts)
        {
            $url = WPAICG_Url::filesUrl();

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function uploadAssitantFile($opts)
        {
            // add the purpose key to the $opts array
            $opts['purpose'] = 'vision';
            $url = WPAICG_Url::filesUrl();

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function retrieveFile($file_id)
        {
            $file_id = "/$file_id";
            $url = WPAICG_Url::filesUrl() . $file_id;

            return $this->sendRequest($url, 'GET');
        }

        public function retrieveFileContent($file_id)
        {
            $file_id = "/$file_id/content";
            $url = WPAICG_Url::filesUrl() . $file_id;

            return $this->sendRequest($url, 'GET');
        }

        public function deleteFile($file_id)
        {
            $file_id = "/$file_id";
            $url = WPAICG_Url::filesUrl() . $file_id;

            return $this->sendRequest($url, 'DELETE');
        }

        public function createFineTune($opts)
        {
            $url = WPAICG_Url::fineTuneUrl();

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function listFineTunes()
        {
            $url = WPAICG_Url::fineTuneUrl();

            return $this->sendRequest($url, 'GET');
        }

        public function retrieveFineTune($fine_tune_id)
        {
            $fine_tune_id = "/$fine_tune_id";
            $url = WPAICG_Url::fineTuneUrl() . $fine_tune_id;

            return $this->sendRequest($url, 'GET');
        }

        /**
         * @param $fine_tune_id
         * @return bool|string
         */
        public function cancelFineTune($fine_tune_id)
        {
            $fine_tune_id = "/$fine_tune_id/cancel";
            $url = WPAICG_Url::fineTuneUrl() . $fine_tune_id;

            return $this->sendRequest($url, 'POST');
        }

        /**
         * @param $fine_tune_id
         * @return bool|string
         */
        public function listFineTuneEvents($fine_tune_id)
        {
            $fine_tune_id = "/$fine_tune_id/events";
            $url = WPAICG_Url::fineTuneUrl() . $fine_tune_id;

            return $this->sendRequest($url, 'GET');
        }

        /**
         * @param $fine_tune_id
         * @return bool|string
         */
        public function deleteFineTune($fine_tune_id)
        {
            $fine_tune_id = "/$fine_tune_id";
            $url = WPAICG_Url::fineTuneModel() . $fine_tune_id;

            return $this->sendRequest($url, 'DELETE');
        }

        /**
         * @param
         * @return bool|string
         * @deprecated
         */
        public function engines()
        {
            $url = WPAICG_Url::enginesUrl();

            return $this->sendRequest($url, 'GET');
        }

        /**
         * @param $engine
         * @return bool|string
         * @deprecated
         */
        public function engine($engine)
        {
            $url = WPAICG_Url::engineUrl($engine);

            return $this->sendRequest($url, 'GET');
        }

        /**
         * @param $opts
         * @return bool|string
         */
        public function embeddings($opts)
        {
            $url = WPAICG_Url::embeddings();

            return $this->sendRequest($url, 'POST', $opts);
        }

        /**
         * @param int $timeout
         */
        public function setTimeout(int $timeout)
        {
            $this->timeout = $timeout;
        }

        private function setUpHeaders($beta_version = null) {
            global $wpdb;
            $wpaicgTable = $wpdb->prefix . 'wpaicg';
            $sql = $wpdb->prepare('SELECT * FROM ' . $wpaicgTable . ' WHERE name = %s', 'wpaicg_settings');
            $wpaicg_settings = $wpdb->get_row($sql, ARRAY_A);
            $api_key = isset($wpaicg_settings['api_key']) ? $wpaicg_settings['api_key'] : '';

            $this->headers['Authorization'] = 'Bearer ' . $api_key;
            $this->headers['Content-Type'] = 'application/json';

            if ($beta_version) {
                $this->headers['OpenAI-Beta'] = $beta_version;
            } else {
                unset($this->headers['OpenAI-Beta']);
            }
        }
        
        /**
         * List assistants.
         * @param array $query
         * @return bool|string
         */
        public function listAssistants($query = [])
        {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::assistantsUrl();

            // Add query parameters to the URL if they exist
            if (!empty($query)) {
                $url .= '?' . http_build_query($query);
            }

            return $this->sendRequest($url, 'GET');
        }

        /**
         * Get a specific assistant.
         * @param string $assistant_id
         * @return bool|string
         */
        public function getAssistant($assistant_id)
        {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::assistantUrl($assistant_id);

            return $this->sendRequest($url, 'GET');
        }


        /**
         * Delete an assistant.
         * @param string $assistant_id
         * @return bool|string
         */
        public function deleteAssistant($assistant_id) {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::assistantUrl($assistant_id);

            return $this->sendRequest($url, 'DELETE');
        }

        /**
         * Create an assistant.
         * @param array $assistant_data
         * @return bool|string
         */
        public function createAssistant($assistant_data) {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::assistantsUrl();

            return $this->sendRequest($url, 'POST', $assistant_data);
        }

        /**
         * Modify an assistant.
         * @param string $assistant_id
         * @param array $assistant_data
         * @return bool|string
         */
        public function modifyAssistant($assistant_id, $assistant_data) {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::assistantUrl($assistant_id);

            return $this->sendRequest($url, 'POST', $assistant_data);
        }
        
        public function create_body_for_file($file, $boundary)
        {
            // check for the 'vision' purpose along with 'assistants'
            if (isset($file['purpose'])) {
                if ($file['purpose'] === 'assistants') {
                    $filePurpose = 'assistants';
                } elseif ($file['purpose'] === 'vision') {
                    $filePurpose = 'vision';
                } else {
                    $filePurpose = 'fine-tune';
                }
            } else {
                $filePurpose = 'fine-tune';
            }
            $fields = array(
                'purpose' => $filePurpose,
                'file' => $file['filename']
            );

            $body = '';
            foreach ($fields as $name => $value) {
                $body .= "--$boundary\r\n";
                $body .= "Content-Disposition: form-data; name=\"$name\"";
                if ($name == 'file') {
                    $body .= "; filename=\"{$value}\"\r\n";
                    $body .= "Content-Type: application/json\r\n\r\n";
                    $body .= $file['data'] . "\r\n";
                } else {
                    $body .= "\r\n\r\n$value\r\n";
                }
            }
            $body .= "--$boundary--\r\n";
            return $body;
        }

        public function create_body_for_audio($file, $boundary, $fields)
        {
            $fields['file'] = $file['filename'];
            unset($fields['audio']);
            $body = '';
            foreach ($fields as $name => $value) {
                $body .= "--$boundary\r\n";
                $body .= "Content-Disposition: form-data; name=\"$name\"";
                if ($name == 'file') {
                    $body .= "; filename=\"{$value}\"\r\n";
                    $body .= "Content-Type: application/json\r\n\r\n";
                    $body .= $file['data'] . "\r\n";
                } else {
                    $body .= "\r\n\r\n$value\r\n";
                }
            }
            $body .= "--$boundary--\r\n";
            return $body;
        }

        public function listFiles()
        {
            $url = WPAICG_Url::filesUrl();

            return $this->sendRequest($url, 'GET');
        }

        private function handleO1Models(array $opts): array
        {
            // Get the o1 models list from the Util class
            $o1_models = \WPAICG\WPAICG_Util::get_instance()->o1_models;
        
            // If the model is in the o1 models list, adjust the parameters
            if (isset($opts['model']) && array_key_exists($opts['model'], $o1_models)) {
                // Use 'max_completion_tokens' instead of 'max_tokens'
                if (array_key_exists('max_tokens', $opts)) {
                    $opts['max_completion_tokens'] = $opts['max_tokens'];
                    unset($opts['max_tokens']);
                }
        
                // Set 'top_p' to the default value of 1 if it's not set correctly
                if (isset($opts['top_p']) && $opts['top_p'] != 1) {
                    $opts['top_p'] = 1;
                }
        
                // Set 'presence_penalty' to the default value of 0 if it's not set correctly
                if (isset($opts['presence_penalty']) && $opts['presence_penalty'] != 0) {
                    $opts['presence_penalty'] = 0;
                }
        
                // Set 'frequency_penalty' to the default value of 0 if it's not set correctly
                if (isset($opts['frequency_penalty']) && $opts['frequency_penalty'] != 0) {
                    $opts['frequency_penalty'] = 0;
                }
                // Set 'temperature' to the default value of 1 if it's not set correctly
                if (isset($opts['temperature']) && $opts['temperature'] != 1) {
                    $opts['temperature'] = 1;
                }

                // remove stop if exists
                if (isset($opts['stop'])) {
                    unset($opts['stop']);
                }
            }
        
            return $opts;
        }


        /**
         * Threads and Messages Methods
         */

        /**
         * Create a thread.
         * @param array $messages
         * @return bool|string
         */
        public function createThread($messages = [])
        {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::threadsUrl();
            $data = [];
            if (!empty($messages)) {
                $data['messages'] = $messages;
            }
            return $this->sendRequest($url, 'POST', $data);
        }

        /**
         * Get a thread.
         * @param string $thread_id
         * @return bool|string
         */
        public function getThread($thread_id)
        {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::threadUrl($thread_id);

            return $this->sendRequest($url, 'GET');
        }

        /**
         * Add a message to a thread.
         * @param string $thread_id
         * @param array $message_data
         * @return bool|string
         */
        public function addMessageToThread($thread_id, $message_data)
        {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::messagesUrl($thread_id);

            return $this->sendRequest($url, 'POST', $message_data);
        }

        /**
         * Get a specific message from a thread.
         * @param string $thread_id
         * @param string $message_id
         * @return bool|string
         */
        public function getMessage($thread_id, $message_id)
        {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::messageUrl($thread_id, $message_id);

            return $this->sendRequest($url, 'GET');
        }

        /**
         * List messages in a thread.
         * @param string $thread_id
         * @return bool|string
         */
        public function listMessages($thread_id)
        {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::messagesUrl($thread_id);

            return $this->sendRequest($url, 'GET');
        }

        /**
         * Runs Methods
         */

        /**
         * Create a run.
         * @param string $thread_id
         * @param array $run_data
         * @param callable|null $stream_method
         * @return bool|string
         */
        public function createRun($thread_id, $run_data, $stream_method = null)
        {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::runsUrl($thread_id);

            if (isset($run_data['stream']) && $run_data['stream']) {
                if (!is_callable($stream_method)) {
                    throw new \Exception('Please provide a stream function.');
                }
                $this->stream_method = $stream_method;
                $response = $this->sendRequestForAssistantStreaming($url, 'POST', $run_data);
                return $response;
            } else {
                return $this->sendRequest($url, 'POST', $run_data);
            }
        }

        /**
         * Get a run.
         * @param string $thread_id
         * @param string $run_id
         * @return bool|string
         */
        public function getRun($thread_id, $run_id)
        {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::runUrl($thread_id, $run_id);

            return $this->sendRequest($url, 'GET');
        }

        /**
         * List runs in a thread.
         * @param string $thread_id
         * @return bool|string
         */
        public function listRuns($thread_id)
        {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::runsUrl($thread_id);

            return $this->sendRequest($url, 'GET');
        }

        /**
         * List run steps belonging to a run.
         * @param string $thread_id
         * @param string $run_id
         * @param array $query Optional query parameters like limit, order, etc.
         * @return bool|string
         */
        public function listRunSteps($thread_id, $run_id, $query = [])
        {
            $this->setUpHeaders('assistants=v2');

            $url = WPAICG_Url::runStepsUrl($thread_id, $run_id);

            // Add query parameters to the URL if they exist
            if (!empty($query)) {
                $url .= '?' . http_build_query($query);
            }

            return $this->sendRequest($url, 'GET');
        }

        /**
         * @param string $url
         * @param string $method
         * @param array $opts
         * @return bool|string
         */
        private function sendRequest(string $url, string $method, array $opts = [])
        {
            // Handle model-specific adjustments (like for o1-mini)
            $opts = $this->handleO1Models($opts);

            $post_fields = json_encode($opts);
            // Check if the request is for text-to-speech
            if (array_key_exists('tts', $opts)) {
                // Retrieve API key from the database

                global $wpdb;
                $wpaicgTable = $wpdb->prefix . 'wpaicg';
                $sql = $wpdb->prepare('SELECT * FROM ' . $wpaicgTable . ' WHERE name = %s', 'wpaicg_settings');
                $wpaicg_settings = $wpdb->get_row($sql, ARRAY_A);
                $api_key = isset($wpaicg_settings['api_key']) ? $wpaicg_settings['api_key'] : '';

                // Add the Authorization header with the API key
                $this->headers['Authorization'] = 'Bearer ' . $api_key;
            }

            if (array_key_exists('file', $opts)) {
                $boundary = wp_generate_password(24, false);
                $this->headers['Content-Type'] = 'multipart/form-data; boundary='.$boundary;
                // check if purpose=vision exists in $opts
                if (isset($opts['purpose']) && $opts['purpose'] === 'vision') {
                    $opts['file']['purpose'] = 'vision';
                }
                $post_fields = $this->create_body_for_file($opts['file'], $boundary);
            }
            elseif (isset($opts['purpose']) && $opts['purpose'] === 'assistants') {
                $boundary = wp_generate_password(24, false);
                $this->headers['Content-Type'] = 'multipart/form-data; boundary='.$boundary;
                global $wpdb;
                $wpaicgTable = $wpdb->prefix . 'wpaicg';
                $sql = $wpdb->prepare('SELECT * FROM ' . $wpaicgTable . ' WHERE name = %s', 'wpaicg_settings');
                $wpaicg_settings = $wpdb->get_row($sql, ARRAY_A);
                $api_key = isset($wpaicg_settings['api_key']) ? $wpaicg_settings['api_key'] : '';

                // Add the Authorization header with the API key
                $this->headers['Authorization'] = 'Bearer ' . $api_key;
                $post_fields = $this->create_body_for_file(['filename' => $opts['filename'], 'data' => $opts['data'], 'purpose' => $opts['purpose']], $boundary);
            }
            elseif (array_key_exists('audio', $opts)) {
                $boundary = wp_generate_password(24, false);
                $this->headers['Content-Type'] = 'multipart/form-data; boundary='.$boundary;
                $post_fields = $this->create_body_for_audio($opts['audio'], $boundary, $opts);
            } else {
                $this->headers['Content-Type'] = 'application/json';
            }
            $stream = false;
            if (array_key_exists('stream', $opts) && $opts['stream']) {
                $stream = true;
            }
            $request_options = array(
                'timeout' => $this->timeout,
                'headers' => $this->headers,
                'method' => $method,
                'body' => $post_fields,
                'stream' => $stream
            );
            if($post_fields == '[]'){
                unset($request_options['body']);
            }

            $response = wp_remote_request($url,$request_options);

            if(is_wp_error($response)){
                return json_encode(array('error' => array('message' => $response->get_error_message())));
            }
            else{
                if ($stream){
                    return $this->response;
                }
                else{
                    return wp_remote_retrieve_body($response);
                }
            }
        }

        private function sendRequestForAssistantStreaming(string $url, string $method, array $opts = [])
        {
            // Handle model-specific adjustments
            $opts = $this->handleO1Models($opts);

            $post_fields = json_encode($opts);

            // initialize total_tokens
            $total_tokens = 0;
            $model = null;
            $response_text = ""; // initialize response_text

            // Ensure that we're handling streaming requests only
            if (!(array_key_exists('stream', $opts) && $opts['stream'])) {
                throw new \Exception('Streaming is not enabled in options.');
            }

            // Use cURL directly
            $curl = curl_init($url);

            $headers = [];
            foreach ($this->headers as $key => $value) {
                $headers[] = "$key: $value";
            }

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_fields);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$total_tokens, &$model, &$response_text) {
            
                // Process `thread.run.completed` to gather usage and model information
                if (strpos($data, 'event: thread.run.completed') !== false) {
                    $json_start = strpos($data, '{');
                    if ($json_start !== false) {
                        $json_part = substr($data, $json_start);
                        $event_data = json_decode($json_part, true);
                        if (isset($event_data['usage']['total_tokens'])) {
                            $total_tokens += $event_data['usage']['total_tokens'];
                        }
                        if (isset($event_data['model'])) {
                            $model = $event_data['model'];
                        }
                    }
                }
            
                // Process `thread.message.delta` to accumulate response text
                if (strpos($data, 'event: thread.message.delta') !== false) {
                    $json_start = strpos($data, '{');
                    if ($json_start !== false) {
                        $json_part = substr($data, $json_start);
                        $event_data = json_decode($json_part, true);
                        if (isset($event_data['delta']['content'][0]['text']['value'])) {
                            $response_text .= $event_data['delta']['content'][0]['text']['value'];
                        }
                    }
            
                    // Send `thread.message.delta` event to the client
                    echo $data;
                    flush();
                }
            
                // Pass through the `done` event directly to the client
                if (strpos($data, 'event: done') !== false) {
                    echo $data;
                    flush();
                }
            
                // For all other events, do nothing
                return strlen($data);
            });

            $exec = curl_exec($curl);
            if ($exec === false) {
                $error = curl_error($curl);
                curl_close($curl);
                return json_encode(array('error' => array('message' => $error)));
            } else {
                curl_close($curl);
                return json_encode(array(
                    'response_text' => $response_text,
                    'total_tokens' => $total_tokens,
                    'model' => $model
                ));
            }
        }

    }
}
