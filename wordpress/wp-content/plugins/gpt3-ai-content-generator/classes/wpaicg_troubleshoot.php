<?php
namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('\\WPAICG\\WPAICG_TroubleShoot')) {
    class WPAICG_TroubleShoot
    {
        private static $instance = null;

        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            add_action('wp_ajax_wpaicg_troubleshoot_add_vector',[$this,'wpaicg_troubleshoot_add_vector']);
            add_action('wp_ajax_wpaicg_troubleshoot_delete_vector',[$this,'wpaicg_troubleshoot_delete_vector']);
            add_action('wp_ajax_wpaicg_troubleshoot_search',[$this,'wpaicg_troubleshoot_search']);
            add_action('wp_ajax_wpaicg_troubleshoot_save',[$this,'wpaicg_troubleshoot_save']);
            add_action('wp_ajax_wpaicg_troubleshoot_connect_qdrant', [$this, 'wpaicg_troubleshoot_connect_qdrant']);
            add_action('wp_ajax_wpaicg_troubleshoot_show_collections', [$this, 'wpaicg_troubleshoot_show_collections']);
            add_action('wp_ajax_wpaicg_troubleshoot_get_collection_details', [$this, 'wpaicg_troubleshoot_get_collection_details']);
            add_action('wp_ajax_wpaicg_troubleshoot_delete_collection', [$this, 'wpaicg_troubleshoot_delete_collection']);
            add_action('wp_ajax_wpaicg_troubleshoot_create_collection', [$this, 'wpaicg_troubleshoot_create_collection']);
            add_action('wp_ajax_wpaicg_troubleshoot_add_vector_qdrant', [$this, 'wpaicg_troubleshoot_add_vector_qdrant']);
            add_action('wp_ajax_wpaicg_troubleshoot_delete_vector_qdrant', [$this, 'wpaicg_troubleshoot_delete_vector_qdrant']);
            add_action('wp_ajax_wpaicg_troubleshoot_search_qdrant', [$this, 'wpaicg_troubleshoot_search_qdrant']);

        }

        public function wpaicg_troubleshoot_create_collection() {
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                die(esc_html__('Nonce verification failed', 'gpt3-ai-content-generator'));
            }
        
            $collectionName = sanitize_text_field($_POST['collection_name']);
            $apiKey = get_option('wpaicg_qdrant_api_key', '');
            $dimension = 1536;

            if ($wpaicg_provider === 'Google') {
                $dimension = 768;
            } 
            $endpoint = get_option('wpaicg_qdrant_endpoint', '') . '/collections/' . $collectionName;
        
            $response = wp_remote_request($endpoint, [
                'method' => 'PUT',
                'headers' => [
                    'api-key' => $apiKey,
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'vectors' => [
                        'distance' => 'Cosine',
                        'size' => $dimension
                    ],
                ])
            ]);
        
            if (is_wp_error($response)) {
                echo json_encode(['error' => $response->get_error_message()]);
            } else {
                echo wp_remote_retrieve_body($response);
            }
        
            die();
        }

        public function wpaicg_troubleshoot_delete_collection() {
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                die(esc_html__('Nonce verification failed', 'gpt3-ai-content-generator'));
            }
        
            $collectionName = sanitize_text_field($_POST['collection_name']);
            $apiKey = get_option('wpaicg_qdrant_api_key', '');
            $endpoint = get_option('wpaicg_qdrant_endpoint', '') . '/collections/' . $collectionName;
        
            $response = wp_remote_request($endpoint, [
                'method' => 'DELETE',
                'headers' => ['api-key' => $apiKey]
            ]);
        
            if (is_wp_error($response)) {
                echo json_encode(['error' => $response->get_error_message()]);
            } else {
                echo wp_remote_retrieve_body($response);
            }
        
            die();
        }

        public function wpaicg_troubleshoot_get_collection_details() {
            // Verify nonce for security
            if ( ! wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce') ) {
                die(esc_html__('Nonce verification failed', 'gpt3-ai-content-generator'));
            }
        
            $collectionName = sanitize_text_field($_POST['collection_name']);
            $apiKey = get_option('wpaicg_qdrant_api_key', '');
            $endpoint = get_option('wpaicg_qdrant_endpoint', '') . "/collections/{$collectionName}";
        
            $response = wp_remote_get($endpoint, [
                'headers' => ['api-key' => $apiKey]
            ]);
        
            if (is_wp_error($response)) {
                echo json_encode(['error' => $response->get_error_message()]);
            } else {
                echo wp_remote_retrieve_body($response);
            }
        
            die();
        }
        

        public function wpaicg_troubleshoot_show_collections() {
            // Verify nonce for security
            if ( ! wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce') ) {
                die(esc_html__('Nonce verification failed', 'gpt3-ai-content-generator'));
            }
        
            $apiKey = get_option('wpaicg_qdrant_api_key', '');
            $endpoint = get_option('wpaicg_qdrant_endpoint', '') . '/collections';
        
            $response = wp_remote_get($endpoint, [
                'headers' => ['api-key' => $apiKey]
            ]);
        
            if (is_wp_error($response)) {
                echo json_encode(['error' => $response->get_error_message()]);
            } else {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $collections = array_column($body['result']['collections'], 'name');
                echo json_encode($collections);
            }
        
            die();
        }
        
        public function wpaicg_troubleshoot_connect_qdrant()
        {
            // nonce verification
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                die(esc_html__('Nonce verification failed','gpt3-ai-content-generator'));
            }

            $apiKey = sanitize_text_field($_POST['api_key']);
            $endpoint = sanitize_text_field($_POST['endpoint']);

            // Save Qdrant API key and endpoint
            update_option('wpaicg_qdrant_api_key', $apiKey);
            update_option('wpaicg_qdrant_endpoint', $endpoint);

            // Sample cURL request to Qdrant - replace with actual PHP cURL request
            $response = wp_remote_get($endpoint, [
                'headers' => ['api-key' => $apiKey]
            ]);

            if (is_wp_error($response)) {
                echo 'Error: ' . $response->get_error_message();
            } else {
                echo 'Response: ' . wp_remote_retrieve_body($response);
            }

            die();
        }

        public function wpaicg_troubleshoot_save()
        {
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                die(esc_html__('Nonce verification failed','gpt3-ai-content-generator'));
            }
            if(!current_user_can('manage_options')){
                die(esc_html__('You do not have permission for this action.','gpt3-ai-content-generator'));
            }
            $key = sanitize_text_field($_REQUEST['key']);
            $value = sanitize_text_field($_REQUEST['value']);
            if(in_array($key,array(
                'wpaicg_troubleshoot_pinecone_api',
                'wpaicg_openai_trouble_api'
            ))) {
                update_option($key, $value);
            }
        }

        public function wpaicg_troubleshoot_search_qdrant() {
            // 1. Check nonce
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                wp_send_json_error( __('Nonce verification failed','gpt3-ai-content-generator'), 403 );
            }
        
            // 2. Check capability (only allow if the user has the Troubleshoot capability or manage_options)
            if ( ! current_user_can('wpaicg_embeddings_troubleshoot') && ! current_user_can('manage_options') ) {
                wp_send_json_error( __('Insufficient permissions','gpt3-ai-content-generator'), 403 );
            }
        
            // 3. Build and validate the endpoint URL
            $collectionName = sanitize_text_field($_POST['collection_name']);
            $baseEndpoint   = isset($_POST['endpoint']) ? wp_unslash($_POST['endpoint']) : '';
            $baseEndpoint   = esc_url_raw($baseEndpoint);
            if ( empty($baseEndpoint) || ! wp_http_validate_url($baseEndpoint) ) {
                wp_send_json_error( __('Invalid or empty base Qdrant endpoint','gpt3-ai-content-generator') );
            }
            // Construct the final URL for searching
            $url = trailingslashit($baseEndpoint) . 'collections/' . $collectionName . '/points/search';
        
            // OPTIONAL: block private/loopback addresses
            $urlParts = parse_url($url);
            if ( ! $this->is_valid_public_host( $urlParts['host'] ?? '' ) ) {
                wp_send_json_error( __('Requests to private or invalid hosts are not allowed','gpt3-ai-content-generator') );
            }
        
            // 4. Prepare request
            $api_key = get_option('wpaicg_qdrant_api_key', '');
            $query   = stripslashes($_POST['query']);
            $response = wp_remote_post(
                $url,
                array(
                    'method'  => 'POST',
                    'headers' => array(
                        'api-key'      => $api_key,
                        'Content-Type' => 'application/json',
                    ),
                    'body'    => $query,
                    'timeout' => 15,
                )
            );
        
            // 5. Handle response
            if ( is_wp_error($response) ) {
                wp_send_json_error( $response->get_error_message() );
            } else {
                wp_send_json_success( wp_remote_retrieve_body($response) );
            }
        }
        

        public function wpaicg_troubleshoot_add_vector()
        {
            // 1. Check nonce
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                wp_send_json_error( __('Nonce verification failed','gpt3-ai-content-generator'), 403 );
            }
        
            // 2. Check capability (only allow if the user has the Troubleshoot capability or manage_options)
            if ( ! current_user_can('wpaicg_embeddings_troubleshoot') && ! current_user_can('manage_options') ) {
                wp_send_json_error( __('Insufficient permissions','gpt3-ai-content-generator'), 403 );
            }
        
            // 3. Validate the requested environment URL
            $url = isset($_REQUEST['environment']) ? esc_url_raw( wp_unslash($_REQUEST['environment']) ) : '';
            if ( empty($url) || ! wp_http_validate_url( $url ) ) {
                wp_send_json_error( __('Invalid or empty URL','gpt3-ai-content-generator') );
            }
        
            // OPTIONAL: Additional checks to prevent SSRF to private IPs
            // parse_url() to examine host
            $url_parts = parse_url($url);
            if ( ! $this->is_valid_public_host( $url_parts['host'] ?? '' ) ) {
                wp_send_json_error( __('Requests to private or invalid hosts are not allowed','gpt3-ai-content-generator') );
            }
        
            // 4. Prepare safe remote request data
            $api_key = sanitize_text_field($_REQUEST['api_key']);
            $headers = array(
                'Api-Key'       => $api_key,
                'Content-Type'  => 'application/json',
            );
            $vectors = isset($_REQUEST['data']) ? wp_unslash($_REQUEST['data']) : '';
            // If 'data' is JSON, we can decode and re-encode it, or at least we do some minimal checks.
        
            // 5. Use wp_safe_remote_post
            $response = wp_remote_post( 
                $url,
                array(
                    'headers' => $headers,
                    'body'    => $vectors,
                    'timeout' => 15, // set an explicit timeout to avoid long-hangs
                )
            );
        
            // 6. Check for errors
            if ( is_wp_error($response) ) {
                wp_send_json_error( $response->get_error_message() );
            } else {
                // Return the response from the external server if needed
                $body = wp_remote_retrieve_body($response);
                wp_send_json_success( $body );
            }
        }
        
        /**
         * Helper function to prevent calls to internal or loopback addresses.
         * 
         * We can block 127.x.x.x, 10.x.x.x, 192.168.x.x, etc. 
         * or do a DNS lookup and block private ranges. 
         */
        private function is_valid_public_host( $hostname ) {
            // Quick exit if it's obviously empty
            if ( empty($hostname) ) {
                return false;
            }
        
            // Get IP(s) from the domain
            $ip_list = gethostbynamel( $hostname );
            if ( ! is_array($ip_list) || empty($ip_list) ) {
                // could not resolve
                return false;
            }
        
            // Check each resolved IP to ensure it's not in private/reserved ranges
            foreach ($ip_list as $ip) {
                if (
                    // IPv4 private ranges:
                    filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
                ) {
                    // It's in a local or reserved range => block it
                    return false;
                }
            }
        
            return true;
        }

        public function wpaicg_troubleshoot_add_vector_qdrant()
        {
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                die(esc_html__('Nonce verification failed', 'gpt3-ai-content-generator'));
            }
        
            $endpoint = sanitize_text_field($_REQUEST['endpoint']) . '/collections/' . sanitize_text_field($_REQUEST['collection_name']) . '/points?wait=true';
            // get api key from wpaicg_qdrant_api_key options
            $api_key = get_option('wpaicg_qdrant_api_key', '');

            $vectors = str_replace("\\", '', sanitize_text_field($_REQUEST['data']));
        
            $response = wp_remote_request($endpoint, array(
                'method'    => 'PUT',
                'headers' => ['api-key' => $api_key, 
                              'Content-Type' => 'application/json'],
                'body'      => $vectors,
            ));
        
            if (is_wp_error($response)) {
                die($response->get_error_message());
            } else {
                echo wp_remote_retrieve_body($response);
                die();
            }
        }

        public function wpaicg_troubleshoot_delete_vector_qdrant() {
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                die(esc_html__('Nonce verification failed', 'gpt3-ai-content-generator'));
            }
        
            $endpoint = sanitize_text_field($_REQUEST['endpoint']) . '/collections/' . sanitize_text_field($_REQUEST['collection_name']) . '/points/delete?wait=true';
            $api_key = get_option('wpaicg_qdrant_api_key', '');
            $points = str_replace("\\", '', sanitize_text_field($_REQUEST['data']));
        
            $response = wp_remote_request($endpoint, array(
                'method' => 'POST',
                'headers' => ['api-key' => $api_key, 'Content-Type' => 'application/json'],
                'body' => $points,
            ));
        
            if (is_wp_error($response)) {
                die($response->get_error_message());
            } else {
                echo wp_remote_retrieve_body($response);
                die();
            }
        }
        
        public function wpaicg_troubleshoot_search()
        {
            // 1. Check nonce
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                wp_send_json_error( __('Nonce verification failed','gpt3-ai-content-generator'), 403 );
            }
        
            // 2. Check capability
            if ( ! current_user_can('wpaicg_embeddings_troubleshoot') && ! current_user_can('manage_options') ) {
                wp_send_json_error( __('Insufficient permissions','gpt3-ai-content-generator'), 403 );
            }
        
            // 3. Validate environment URL
            $url = isset($_REQUEST['environment']) ? wp_unslash($_REQUEST['environment']) : '';
            $url = esc_url_raw($url);
            if ( empty($url) || ! wp_http_validate_url($url) ) {
                wp_send_json_error( __('Invalid or empty URL','gpt3-ai-content-generator') );
            }
        
            // OPTIONAL: block private IP
            $urlParts = parse_url($url);
            if ( ! $this->is_valid_public_host( $urlParts['host'] ?? '' ) ) {
                wp_send_json_error( __('Requests to private or invalid hosts are not allowed','gpt3-ai-content-generator') );
            }
        
            // 4. Prepare request
            $api_key = sanitize_text_field($_REQUEST['api_key']);
            $headers = array(
                'Api-Key'       => $api_key,
                'Content-Type'  => 'application/json',
            );
            $data = str_replace("\\", '', sanitize_text_field($_REQUEST['data']));
        
            // 5. Use wp_safe_remote_post
            $response = wp_remote_post(
                $url,
                array(
                    'headers' => $headers,
                    'body'    => $data,
                    'timeout' => 15,
                )
            );
        
            // 6. Handle response
            if ( is_wp_error($response) ) {
                wp_send_json_error( $response->get_error_message() );
            } else {
                wp_send_json_success( wp_remote_retrieve_body($response) );
            }
        }

        public function wpaicg_troubleshoot_delete_vector()
        {
            // 1. Check nonce
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                wp_send_json_error( __('Nonce verification failed','gpt3-ai-content-generator'), 403 );
            }
        
            // 2. Check capability
            if ( ! current_user_can('wpaicg_embeddings_troubleshoot') && ! current_user_can('manage_options') ) {
                wp_send_json_error( __('Insufficient permissions','gpt3-ai-content-generator'), 403 );
            }
        
            // 3. Validate environment URL
            $url = isset($_REQUEST['environment']) ? wp_unslash($_REQUEST['environment']) : '';
            $url = esc_url_raw($url);
            if ( empty($url) || ! wp_http_validate_url($url) ) {
                wp_send_json_error( __('Invalid or empty URL','gpt3-ai-content-generator') );
            }
        
            // OPTIONAL: block private IP addresses
            $urlParts = parse_url($url);
            if ( ! $this->is_valid_public_host( $urlParts['host'] ?? '' ) ) {
                wp_send_json_error( __('Requests to private or invalid hosts are not allowed','gpt3-ai-content-generator') );
            }
        
            // 4. Prepare request
            $api_key = get_option('wpaicg_pinecone_api','');
            $headers = array(
                'Api-Key'      => $api_key,
                'Content-Type' => 'application/json',
            );
            $data = str_replace("\\", '', sanitize_text_field($_REQUEST['data']));
        
            // 5. Use wp_safe_remote_post
            $response = wp_remote_post(
                $url,
                array(
                    'headers' => $headers,
                    'body'    => $data,
                    'timeout' => 15,
                )
            );
        
            // 6. Handle response
            if ( is_wp_error($response) ) {
                wp_send_json_error( $response->get_error_message() );
            } else {
                wp_send_json_success( wp_remote_retrieve_body($response) );
            }
        }
    }
    WPAICG_TroubleShoot::get_instance();
}
