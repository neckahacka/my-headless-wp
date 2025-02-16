<?php
namespace WPAICG;

if (!defined('ABSPATH')) {
    exit;
}

class WPAZUREAICG_Url
{

    private static $azure_api_url;
    private static $deployment_name;
    private static $api_version;
    private static $image_api_version;
    private static $deployment_name_embedding;
    private static $api_version_embedding;
    private static $finetune_version;

    public function __construct()
    {
        // Fetching values from wp_options table
        self::$azure_api_url = get_option('wpaicg_azure_endpoint', ''); // Default to an empty string if not set
        self::$deployment_name = get_option('wpaicg_azure_deployment', ''); // Default to an empty string if not set
        self::$deployment_name_embedding = get_option('wpaicg_azure_embeddings', "text-embedding-ada-002"); // Default to "text-embedding-ada-002" if not set

        // Static values
        self::$api_version_embedding = 'api-version=2023-05-15';
        self::$api_version = 'api-version=2023-03-15-preview';
        self::$image_api_version = 'api-version=2023-06-01-preview';
        self::$finetune_version = 'api-version=2023-05-15';
    }


    /**
     *
     * @return string
     */
    public static function editsUrl(): string
    {
        return self::$azure_api_url . "/edits";
    }

    /**
     * @param string $engine
     * @return string
     */
    public static function searchURL(string $engine): string
    {
        return self::$azure_api_url . "/engines/$engine/search";
    }

    /**
     * @param
     * @return string
     */
    public static function enginesUrl(): string
    {
        return self::$azure_api_url . "/engines";
    }

    /**
     * @param string $engine
     * @return string
     */
    public static function engineUrl(string $engine): string
    {
        return self::$azure_api_url . "/engines/$engine";
    }

    /**
     * @param
     * @return string
     */
    public static function classificationsUrl(): string
    {
        return self::$azure_api_url . "/classifications";
    }

    /**
     * @param
     * @return string
     */
    public static function moderationUrl(): string
    {
        return self::$azure_api_url . "/moderations";
    }

    /**
     * @param
     * @return string
     */
    public static function filesUrl(): string
    {
        return self::$azure_api_url . "/files" . "?" . self::$finetune_version;
    }

    /**
     * @param
     * @return string
     */
    public static function chatUrl(): string
    {
        return self::$azure_api_url . "openai/deployments/" . self::$deployment_name . "/chat/completions?" . self::$api_version;
    }

    /**
     * @param
     * @return string
     */
    public static function answersUrl(): string
    {
        return self::$azure_api_url . "/answers";
    }

    /**
     * @param
     * @return string
     */
    public static function imageUrl(): string
    {
        return self::$azure_api_url . "openai/images/generations:submit?" . self::$image_api_version;
    }

    /**
     * @param
     * @return string
     */
    public static function transcriptionsUrl(): string
    {
        return self::$azure_api_url . "/audio/transcriptions";
    }

    /**
     * @param
     * @return string
     */
    public static function transaltionsUrl(): string
    {
        return self::$azure_api_url . "/audio/translations";
    }

    /**
     * @param
     * @return string
     */
    public static function embeddings(): string
    {
        return self::$azure_api_url . "openai/deployments/" . self::$deployment_name_embedding . "/embeddings?" . self::$api_version_embedding;
    }
    
}

if (!class_exists('\\WPAICG\\WPAICG_AzureAI')) {
    class WPAICG_AzureAI
    {
        private static $instance = null;
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
            if (is_null(self::$instance)) {
                self::$instance = new self();
                $AzureUrlCheckObj = new WPAZUREAICG_Url();
            }
            return self::$instance;
        }

        public function azureai()
        {
            // Fetch the Azure API key from wp_options table
            $azure_api_key = get_option('wpaicg_azure_api_key', ''); // Default to an empty string if not set
            
            if (!empty($azure_api_key)) {
                add_action('http_api_curl', array($this, 'filterCurlForStream'));
                $this->headers = [
                    'Content-Type' => 'application/json',
                    'api-key' => $azure_api_key,
                ];
        
                global $wpdb;
                $wpaicgTable = $wpdb->prefix . 'wpaicg';
                $sql = $wpdb->prepare('SELECT * FROM ' . $wpaicgTable . ' where name=%s', 'wpaicg_settings');
                $wpaicg_settings = $wpdb->get_row($sql, ARRAY_A);
        
                if ($wpaicg_settings) {
                    unset($wpaicg_settings['ID']);
                    unset($wpaicg_settings['name']);
                    unset($wpaicg_settings['added_date']);
                    unset($wpaicg_settings['modified_date']);
                    
                    foreach ($wpaicg_settings as $key => $wpaicg_setting) {
                        $this->$key = $wpaicg_setting;
                    }
                }
        
                return $this;
            } else {
                return false;
            }
        }
        

        public function filterCurlForStream($handle)
        {
            if ($this->stream_method !== null) {
                curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($handle, CURLOPT_WRITEFUNCTION, function ($curl_info, $data) {
                    return call_user_func($this->stream_method, $this, $data);
                });
            }
        }

        public function setResponse($content = "")
        {
            $this->response = $content;
        }

        public function chat($opts, $stream = null)
        {
            if ($stream != null && array_key_exists('stream', $opts)) {
                if (!$opts['stream']) {
                    throw new \Exception(
                        'Please provide a stream function.'
                    );
                }
                $this->stream_method = $stream;
            }

            $opts['model'] = $opts['model'] ?? $this->model;

            $url = WPAZUREAICG_Url::chatUrl();

            if (isset($opts['isAzure']) && $opts['isAzure']) {
                // Pattern modification logic for Azure
                $model = $opts['model'] ?? $this->model;
                $pattern = "/(\/deployments\/)[^\/]+/";
                $replacement = "$1" . urlencode($model);
                $url = preg_replace($pattern, $replacement, $url);
                unset($opts['isAzure']);
            }        
            return $this->sendRequest($url, 'POST', $opts);
        }

        public function transcriptions($opts)
        {
            $url = WPAZUREAICG_Url::transcriptionsUrl();
            return $this->sendRequest($url, 'POST', $opts);
        }

        public function translations($opts)
        {
            $url = WPAZUREAICG_Url::translationsUrl();
            return $this->sendRequest($url, 'POST', $opts);
        }

        public function createEdit($opts)
        {
            $url = WPAZUREAICG_Url::editsUrl();

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function image($opts)
        {
            $url = WPAZUREAICG_Url::imageUrl();
            return $this->sendRequest($url, 'POST', $opts, true);
        }

        public function imageEdit($opts)
        {
            $url = WPAZUREAICG_Url::imageUrl() . "/edits";

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function createImageVariation($opts)
        {
            $url = WPAZUREAICG_Url::imageUrl() . "/variations";

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function search($opts)
        {
            $engine = $opts['engine'] ?? $this->engine;
            $url = WPAZUREAICG_Url::searchURL($engine);
            unset($opts['engine']);

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function answer($opts)
        {
            $url = WPAZUREAICG_Url::answersUrl();
            return $this->sendRequest($url, 'POST', $opts);
        }

        public function classification($opts)
        {
            $url = WPAZUREAICG_Url::classificationsUrl();

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function moderation($opts)
        {
            $url = WPAZUREAICG_Url::moderationUrl();

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function uploadFile($opts)
        {
            $url = WPAZUREAICG_Url::filesUrl();

            return $this->sendRequest($url, 'POST', $opts);
        }

        public function retrieveFile($file_id)
        {
            $file_id = "/$file_id";
            $url = WPAZUREAICG_Url::filesUrl() . $file_id;

            return $this->sendRequest($url, 'GET');
        }

        public function retrieveFileContent($file_id)
        {
            $file_id = "/$file_id/content";
            $url = WPAZUREAICG_Url::filesUrl() . $file_id;

            return $this->sendRequest($url, 'GET');
        }

        public function deleteFile($file_id)
        {
            $file_id = "/$file_id";
            $url = WPAZUREAICG_Url::filesUrl() . $file_id;

            return $this->sendRequest($url, 'DELETE');
        }

        /**
         * @param
         * @return bool|string
         * @deprecated
         */
        public function engines()
        {
            $url = WPAZUREAICG_Url::enginesUrl();

            return $this->sendRequest($url, 'GET');
        }

        /**
         * @param $engine
         * @return bool|string
         * @deprecated
         */
        public function engine($engine)
        {
            $url = WPAZUREAICG_Url::engineUrl($engine);

            return $this->sendRequest($url, 'GET');
        }

        /**
         * @param $opts
         * @return bool|string
         */
        public function embeddings($opts)
        {
            $url = WPAZUREAICG_Url::embeddings();

            return $this->sendRequest($url, 'POST', $opts);
        }

        /**
         * @param int $timeout
         */
        public function setTimeout(int $timeout)
        {
            $this->timeout = $timeout;
        }

        public function create_body_for_file($file, $boundary)
        {
            $fields = array(
                'purpose' => 'fine-tune',
                'file' => $file['filename'],
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
            $url = WPAZUREAICG_Url::filesUrl();

            return $this->sendRequest($url, 'GET');
        }

        public function wpaicg_azure_images($image_result_url, $get_request_options)
        {
            $images = array();

            try {
                $response_data = wp_remote_request($image_result_url, $get_request_options);
                $response = wp_remote_retrieve_body($response_data);

                if (is_wp_error($response)) {
                    $images = $response->get_error_message();
                } else {
                    $body = json_decode($response, true);

                    if ($body['status'] == 'succeeded') {
                        $images = json_encode($body['result']);
                    } elseif ($body['status'] == 'running' || $body['status'] == 'notRunning') {
                        $images = $this->wpaicg_azure_images($image_result_url, $get_request_options);
                    } elseif ($body['status'] == 'failed') {
                        $images = json_encode($body);
                    } else {
                        $images = esc_html__('Something went wrong', 'gpt3-ai-content-generator');
                    }
                }
            } catch (\Exception $exception) {
                $images = $exception->getMessage();
            }

            return $images;
        }

        /**
         * @param string $url
         * @param string $method
         * @param array $opts
         * @return bool|string
         */
        private function sendRequest(string $url, string $method, array $opts = [], $isDalle = false)
        {
            $post_fields = json_encode($opts);
            if (array_key_exists('file', $opts)) {
                $boundary = wp_generate_password(24, false);
                $this->headers['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;
                $post_fields = $this->create_body_for_file($opts['file'], $boundary);
            } elseif (array_key_exists('audio', $opts)) {
                $boundary = wp_generate_password(24, false);
                $this->headers['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;
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
                'stream' => $stream,
            );

            if ($post_fields == '[]') {
                unset($request_options['body']);
            }



            $response = wp_remote_request($url, $request_options);

            $responseData = wp_remote_retrieve_body($response);
            $responseData = json_decode($responseData);

            if (is_wp_error($response)) {
                return json_encode(array('error' => array('message' => $response->get_error_message())));
            } else if (isset($responseData->error) && $responseData->error != "") {
                return json_encode($responseData);
            } else {
                if ($stream) {
                    return $this->response;
                } else {
                    if ($isDalle) {
                        $image_result_url = wp_remote_retrieve_header($response, 'operation-location');
                        $method = "GET";

                        $get_request_options = array(
                            'timeout' => $this->timeout,
                            'headers' => $this->headers,
                            'method' => $method,
                            'stream' => $stream,
                        );

                        $response = $this->wpaicg_azure_images($image_result_url, $get_request_options);
                        return $response;
                        
                    } else {
                        return wp_remote_retrieve_body($response);
                    }
                }
            }
        }
    }
}
