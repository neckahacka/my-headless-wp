<?php
namespace WPAICG;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\\WPAICG\\WPAICG_FineTune')) {
    class WPAICG_FineTune
    {
        private static $instance = null;
        public $wpaicg_max_file_size = 10485760;

        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            add_action('wp_ajax_wpaicg_finetune_upload', [$this, 'wpaicg_finetune_upload']);
            add_action('wp_ajax_wpaicg_get_finetune_file', [$this, 'wpaicg_get_finetune_file']);
            add_action('wp_ajax_wpaicg_get_finetune', [$this, 'wpaicg_get_finetune']);
            add_action('wp_ajax_wpaicg_create_finetune', [$this, 'wpaicg_create_finetune']);
            add_action('wp_ajax_wpaicg_finetune_events', [$this, 'wpaicg_finetune_events']);
            add_action('wp_ajax_wpaicg_delete_finetune_file', [$this, 'wpaicg_delete_finetune_file']);
            add_action('wp_ajax_wpaicg_delete_finetune', [$this, 'wpaicg_delete_finetune']);
            add_action('wp_ajax_wpaicg_cancel_finetune', [$this, 'wpaicg_cancel_finetune']);
            add_action('wp_ajax_wpaicg_other_finetune', [$this, 'wpaicg_other_finetune']);
            add_action('wp_ajax_wpaicg_fetch_finetunes', [$this, 'wpaicg_finetunes']);
            add_action('wp_ajax_wpaicg_fetch_finetune_files', [$this, 'wpaicg_files']);
            add_action('wp_ajax_wpaicg_download', [$this, 'wpaicg_download']);
            add_action('wp_ajax_wpaicg_create_finetune_modal', [$this, 'wpaicg_create_finetune_modal']);
            add_action('wp_ajax_wpaicg_data_converter_count', [$this, 'wpaicg_data_converter_count']);
            add_action('wp_ajax_wpaicg_data_converter', [$this, 'wpaicg_data_converter']);
            add_action('wp_ajax_wpaicg_upload_convert', [$this, 'wpaicg_upload_convert']);
            add_action('wp_ajax_wpaicg_data_insert', [$this, 'wpaicg_data_insert']);
            // ajax_pagination_finetune
            add_action('wp_ajax_ajax_pagination_finetune', [$this, 'ajax_pagination_finetune']);
            add_action('wp_ajax_ajax_pagination_training', [$this, 'ajax_pagination_training']);
            // wpaicg_fetch_google_models
            add_action('wp_ajax_wpaicg_fetch_google_models', [$this, 'wpaicg_fetch_google_models']);

            add_filter('mime_types', function ($mime_types) {
                $mime_types['jsonl'] = 'application/octet-stream';
                return $mime_types;
            });
            add_action('wp_ajax_aipower_fetch_openai_models', [$this, 'aipower_fetch_openai_models']);
            add_action('wp_ajax_aipower_sync_google_models', [$this, 'aipower_sync_google_models']);
        }

        // Function to sync Google models
        public function aipower_sync_google_models()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            $api_key = get_option('wpaicg_google_model_api_key');
            if (empty($api_key)) {
                wp_send_json_error(array('message' => esc_html__('Google API key is not configured. Please enter your Google API key first.', 'gpt3-ai-content-generator')));
                return;
            }

            $google_ai = WPAICG_Google::get_instance();
            $model_list = $google_ai->listModels(); // Fetch the models using your existing logic

            if (is_wp_error($model_list)) {
                wp_send_json_error(array('message' => $model_list->get_error_message()));
                return;
            }

            if (isset($model_list['error'])) {
                wp_send_json_error(array('message' => $model_list['error']['message']));
                return;
            }

            update_option('wpaicg_google_model_list', $model_list); // Save the fetched models
            // if wpaicg_google_default_model options not exist or empty then set it to gemini-pro
            if (!get_option('wpaicg_google_default_model')) {
                update_option('wpaicg_google_default_model', 'gemini-pro');
            }
            wp_send_json_success(array('message' => esc_html__('Google models synced successfully.', 'gpt3-ai-content-generator'), 'models' => $model_list));
        }

        public function wpaicg_fetch_google_models()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json(['status' => 'error', 'msg' => esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator')]);
            }
        
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                wp_send_json(['status' => 'error', 'msg' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')]);
            }
        
            $api_key = get_option('wpaicg_google_model_api_key');
            if (empty($api_key)) {
                wp_send_json(['status' => 'error', 'msg' => 'Google API key is not configured. Please enter your Google API key in the settings and save it first.']);
            }
        
            $google_ai = WPAICG_Google::get_instance();
            $model_list = $google_ai->listModels();
        
            if (is_wp_error($model_list)) {
                wp_send_json(['status' => 'error', 'msg' => $model_list->get_error_message()]);
            }

            // Check if the response is an error response from the Google API
            if (isset($model_list['error'])) {
                $api_error_msg = $model_list['error']['message'];
                wp_send_json(['status' => 'error', 'msg' => $api_error_msg]);
            }
        
            update_option('wpaicg_google_model_list', $model_list);
            wp_send_json(['status' => 'success', 'msg' => 'Models updated successfully']);
        }

        public function wpaicgUploadOpenAI($file, $open_ai)
        {
            $model = isset($_POST['model']) && !empty($_POST['model']) ? sanitize_text_field($_POST['model']) : 'gpt-3.5-turbo';
            $name = isset($_POST['custom']) && !empty($_POST['custom']) ? sanitize_title($_POST['custom']) : '';
            $result = $open_ai->uploadFile(array(
                'file' => array(
                    'data' => file_get_contents($file),
                    'filename' => basename($file),
                ),
            ));
            $result = json_decode($result);
            if (isset($result->error)) {
                return trim($result->error->message);
            } else {
                $wpaicg_file_id = wp_insert_post(array(
                    'post_title' => $result->id,
                    'post_date' => date('Y-m-d H:i:s', $result->created_at),
                    'post_status' => 'publish',
                    'post_type' => 'wpaicg_file',
                ));
                if (!is_wp_error($wpaicg_file_id)) {
                    add_post_meta($wpaicg_file_id, 'wpaicg_filename', $result->filename);
                    add_post_meta($wpaicg_file_id, 'wpaicg_purpose', $result->purpose);
                    add_post_meta($wpaicg_file_id, 'wpaicg_model', $model);
                    add_post_meta($wpaicg_file_id, 'wpaicg_custom_name', $name);
                    add_post_meta($wpaicg_file_id, 'wpaicg_file_size', $result->bytes);
                } else {
                    return $wpaicg_file_id->get_error_message();
                }
                return 'success';
            }
        }

        public function wpaicg_data_insert()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            $wpaicg_file_generation = false;
            if ($_POST['model'] === 'gpt-3.5-turbo') {
                if (
                    isset($_POST['messages'])
                    && !empty($_POST['messages'])
                ) {
                    $message = isset($_POST['messages']) ? $_POST['messages'] : array();
                    foreach ($message as $role => $content) {
                        $data[$role]['role'] = $content['role'];
                        $data[$role]['content'] = sanitize_text_field($content['content']);
                    }
                    $data = array(
                        'messages' => $data,
                    );
                    $wpaicg_file_generation = true;
                }
            } else {
                if (
                    isset($_POST['prompt'])
                    && !empty($_POST['prompt'])
                    && isset($_POST['completion'])
                    && !empty($_POST['completion'])
                ) {
                    $data = array(
                        'prompt' => sanitize_text_field($_POST['prompt']) . ' ->',
                        'completion' => strip_tags(sanitize_text_field($_POST['completion'])),
                    );
                    $wpaicg_file_generation = true;
                }
            }

            if ($wpaicg_file_generation) {
                $file = isset($_POST['file']) && !empty($_POST['file']) ? sanitize_text_field($_POST['file']) : md5(time()) . '.jsonl';
                $wpaicg_json_file = fopen(wp_upload_dir()['basedir'] . '/' . $file, "a");
                fwrite($wpaicg_json_file, json_encode($data) . PHP_EOL);
                fclose($wpaicg_json_file);
                $wpaicg_result['file'] = $file;
                $wpaicg_result['status'] = 'success';
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_upload_convert()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (
                isset($_POST['file'])
                && !empty($_POST['file'])
            ) {
                $filename = sanitize_text_field($_POST['file']);
                $line = isset($_POST['line']) && !empty($_POST['line']) ? sanitize_text_field($_POST['line']) : 0;
                $index = isset($_POST['index']) && !empty($_POST['index']) ? sanitize_text_field($_POST['index']) : 1;
                $file = wp_upload_dir()['basedir'] . '/' . $filename;
                if (file_exists($file)) {
                    $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                    $open_ai = WPAICG_OpenAI::get_instance()->openai();
                    // if provider not openai then assing azure to $open_ai
                    if ($wpaicg_provider != 'OpenAI') {
                        $open_ai = WPAICG_AzureAI::get_instance()->azureai();
                    }
                    if (!$open_ai) {
                        $wpaicg_result['msg'] = esc_html__('Missing API Setting', 'gpt3-ai-content-generator');
                    } else {
                        $wpaicg_lines = file($file);
                        $wpaicg_file_size = filesize($file);
                        if ($wpaicg_file_size < $this->wpaicg_max_file_size) {
                            $result = $this->wpaicgUploadOpenAI($file, $open_ai);
                            $wpaicg_result['next'] = 'DONE';
                        } else {
                            $filename = str_replace('.jsonl', '', $filename);
                            $filename = $filename . '-' . $index . '.jsonl';
                            try {
                                $split_file = wp_upload_dir()['basedir'] . '/' . $filename;
                                $wpaicg_json_file = fopen($split_file, "a");
                                $wpaicg_content = '';
                                for ($i = $line; $i <= count($wpaicg_lines); $i++) {
                                    if ($i == count($wpaicg_lines)) {
                                        $wpaicg_content .= $wpaicg_lines[$i];
                                        $wpaicg_result['next'] = 'DONE';
                                    } else {
                                        if (mb_strlen($wpaicg_content, '8bit') > $this->wpaicg_max_file_size) {
                                            $wpaicg_result['next'] = $i + 1;
                                            break;
                                        } else {
                                            $wpaicg_content .= $wpaicg_lines[$i];
                                        }
                                    }
                                }
                                fwrite($wpaicg_json_file, $wpaicg_content);
                                fclose($wpaicg_json_file);
                                $result = $this->wpaicgUploadOpenAI($split_file, $open_ai);
                                unlink($split_file);
                            } catch (\Exception $exception) {
                                $result = $exception->getMessage();
                            }
                        }
                        if ($result == 'success') {
                            $wpaicg_result['status'] = 'success';
                        } else {
                            $wpaicg_result['msg'] = $result;
                        }
                    }
                } else {
                    $wpaicg_result['msg'] = esc_html__('The file has been removed', 'gpt3-ai-content-generator');
                }

            } else {
                $wpaicg_result['msg'] = esc_html__('The file does not exist or removed', 'gpt3-ai-content-generator');
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_data_converter_count()
        {
            global $wpdb;
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg_data_converter_count')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (isset($_POST['data']) && is_array($_POST['data']) && count($_POST['data'])) {
                $types = \WPAICG\wpaicg_util_core()->sanitize_text_or_array_field($_POST['data']);
                $commaDelimitedPlaceholders = implode(',', array_fill(0, count($types), '%s'));
                $sql = $wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->posts . " WHERE post_status='publish' AND post_type IN ($commaDelimitedPlaceholders)", $types);
                $wpaicg_result['count'] = $wpdb->get_var($sql);
                $wpaicg_result['status'] = 'success';
                $wpaicg_result['types'] = $types;
            } else {
                $wpaicg_result['msg'] = esc_html__('Please select least one data to convert', 'gpt3-ai-content-generator');
            }

            wp_send_json($wpaicg_result);
        }

        public function wpaicg_data_converter()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            global $wpdb;
            if (
                isset($_POST['types'])
                && is_array($_POST['types'])
                && count($_POST['types'])
                && isset($_POST['per_page'])
                && !empty($_POST['per_page'])
                && isset($_POST['total'])
                && !empty($_POST['total'])
            ) {
                $types = \WPAICG\wpaicg_util_core()->sanitize_text_or_array_field($_POST['types']);
                $wpaicg_total = sanitize_text_field($_POST['total']);
                $wpaicg_per_page = sanitize_text_field($_POST['per_page']);
                $wpaicg_page = isset($_POST['page']) && !empty($_POST['page']) ? sanitize_text_field($_POST['page']) : 1;
                if (isset($_POST['file']) && !empty($_POST['file'])) {
                    $wpaicg_file = sanitize_text_field($_POST['file']);
                } else {
                    $wpaicg_file = md5(time()) . '.jsonl';
                }
                if (isset($_POST['id']) && !empty($_POST['id'])) {
                    $wpaicg_convert_id = sanitize_text_field($_POST['id']);
                } else {
                    $wpaicg_convert_id = wp_insert_post(array(
                        'post_title' => $wpaicg_file,
                        'post_type' => 'wpaicg_convert',
                        'post_status' => 'publish',
                    ));
                }
                try {
                    $wpaicg_json_file = fopen(wp_upload_dir()['basedir'] . '/' . $wpaicg_file, "a");
                    $wpaicg_content = '';
                    $wpaicg_offset = ($wpaicg_page * $wpaicg_per_page) - $wpaicg_per_page;
                    $sql = $wpdb->prepare("SELECT post_title, post_content FROM " . $wpdb->posts . " WHERE post_status='publish' AND post_type IN ('" . implode("','", $types) . "') ORDER BY post_date ASC LIMIT %d,%d", $wpaicg_offset, $wpaicg_per_page);
                    $wpaicg_data = $wpdb->get_results($sql);
                    if ($wpaicg_data && is_array($wpaicg_data) && count($wpaicg_data)) {
                        foreach ($wpaicg_data as $item) {
                            $data = array(
                                "prompt" => $item->post_title . ' ->',
                                "completion" => strip_tags($item->post_content),
                            );
                            fwrite($wpaicg_json_file, json_encode($data) . PHP_EOL);
                        }
                    }
                    fclose($wpaicg_json_file);
                    $wpaicg_max_page = ceil($wpaicg_total / $wpaicg_per_page);
                    if ($wpaicg_max_page == $wpaicg_page) {
                        $wpaicg_result['next_page'] = 'DONE';
                        wp_update_post(array(
                            'ID' => $wpaicg_convert_id,
                            'post_modified' => date('Y-m-d H:i:s'),
                        ));
                    } else {
                        $wpaicg_result['next_page'] = $wpaicg_page + 1;
                    }
                    $wpaicg_result['file'] = $wpaicg_file;
                    $wpaicg_result['id'] = $wpaicg_convert_id;
                    $wpaicg_result['status'] = 'success';
                } catch (\Exception $exception) {
                    $wpaicg_result['msg'] = $exception->getMessage();
                }
            } else {
                $wpaicg_result['msg'] = esc_html__('Please select least one data to convert', 'gpt3-ai-content-generator');
            }

            wp_send_json($wpaicg_result);
        }

        public function wpaicg_create_finetune_modal()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            $models = $this->wpaicg_get_models();
            if (is_array($models)) {
                $wpaicg_result['status'] = 'success';
                $wpaicg_result['data'] = $models;
            } else {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = $models;
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_get_models()
        {
            $result = false;
            $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
            $open_ai = WPAICG_OpenAI::get_instance()->openai();
            // if provider not openai then assing azure to $open_ai
            if ($wpaicg_provider != 'OpenAI') {
                $open_ai = WPAICG_AzureAI::get_instance()->azureai();
            }
            if ($open_ai) {
                $result = $open_ai->listModels();
                $json_parse = json_decode($result);
                if (isset($json_parse->error)) {
                    return $json_parse->error->message;
                } elseif (isset($json_parse->data) && is_array($json_parse->data) && count($json_parse->data)) {
                    $result = array();
                    foreach ($json_parse->data as $item) {
                        if ($item->owned_by != 'openai' && $item->owned_by != 'system' && $item->owned_by != 'openai-dev' && $item->owned_by != 'openai-internal') {
                            $result[] = $item->id;
                        }
                    }
                    if (count($result)) {
                        update_option('wpaicg_custom_models', $result);
                    }
                }
            }
            return $result;
        }

        public function wpaicg_download()
        {
            $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
            $open_ai = WPAICG_OpenAI::get_instance()->openai();
            // if provider not openai then assing azure to $open_ai
            if ($wpaicg_provider != 'OpenAI') {
                $open_ai = WPAICG_AzureAI::get_instance()->azureai();
            }
            if (!current_user_can('manage_options')) {
                echo esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                exit;
            }
            if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
                $id = sanitize_text_field($_REQUEST['id']);
                if (!$open_ai) {
                    echo 'Missing API Setting';
                } else {
                    $result = $open_ai->retrieveFileContent($id);
                    $json_parse = json_decode($result);
                    if (isset($json_parse->error)) {
                        echo esc_html($json_parse->error->message);
                    } else {
                        $filename = $id . '.csv';
                        header('Content-Type: application/csv');
                        header('Content-Disposition: attachment; filename="' . $filename . '";');
                        $f = fopen('php://output', 'w');
                        $lines = explode("\n", $result);
                        foreach ($lines as $line) {
                            $line = explode(';', $line);
                            fputcsv($f, $line, ';');
                        }
                    }
                }
            }
            die();
        }

        public function wpaicg_create_finetune()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $wpaicg_file = get_post(sanitize_text_field($_POST['id']));
                if ($wpaicg_file) {
                    $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                    $open_ai = WPAICG_OpenAI::get_instance()->openai();
                    // if provider not openai then assing azure to $open_ai
                    if ($wpaicg_provider != 'OpenAI') {
                        $open_ai = WPAICG_AzureAI::get_instance()->azureai();
                    }
                    if (!$open_ai) {
                        $wpaicg_result['msg'] = esc_html__('Missing API Setting', 'gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }
                    $model = get_post_meta($wpaicg_file->ID, 'wpaicg_model', true);
                    $suffix = get_post_meta($wpaicg_file->ID, 'wpaicg_custom_name', true);
                    $dataSend = [
                        'training_file' => $wpaicg_file->post_title,
                    ];
                    if (isset($_POST['model']) && !empty($_POST['model'])) {
                        $dataSend['model'] = sanitize_text_field($_POST['model']);
                    } else {
                        $dataSend['model'] = $model;
                        $dataSend['suffix'] = $suffix;
                    }
                    if (empty($dataSend['model'])) {
                        $dataSend['model'] = 'gpt-3.5-turbo';
                    }
                    $result = $open_ai->createFineTune($dataSend);
                    if (!empty($result->error)) {
                        $wpaicg_result['msg'] = $result->error->message;
                        wp_send_json($wpaicg_result);
                    }
                    $result = json_decode($result);
                    update_post_meta($wpaicg_file->ID, 'wpaicg_fine_tune', $result->id);
                    $wpaicg_file_id = wp_insert_post(array(
                        'post_title' => $result->id,
                        'post_date' => date('Y-m-d H:i:s', $result->created_at),
                        'post_status' => 'publish',
                        'post_type' => 'wpaicg_finetune',
                    ));
                    add_post_meta($wpaicg_file_id, 'wpaicg_model', $result->model);
                    if (isset($result->updated_at)) {
                        add_post_meta($wpaicg_file_id, 'wpaicg_updated_at', date('Y-m-d H:i:s', $result->updated_at));
                    }                    
                    add_post_meta($wpaicg_file_id, 'wpaicg_name', $result->fine_tuned_model);
                    add_post_meta($wpaicg_file_id, 'wpaicg_org', $result->organization_id);
                    add_post_meta($wpaicg_file_id, 'wpaicg_status', $result->status);
                    $wpaicg_result = [
                        'status' => 'success',
                        'msg' => esc_html__('Fine tuning job created successfully.', 'gpt3-ai-content-generator'),
                        'data' => $result
                    ];

                } else {
                    $wpaicg_result['msg'] = esc_html__('File not found', 'gpt3-ai-content-generator');
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_finetune_upload()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (isset($_FILES['file']) && empty($_FILES['file']['error'])) {
                $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                $open_ai = WPAICG_OpenAI::get_instance()->openai();
                // if provider not openai then assing azure to $open_ai
                if ($wpaicg_provider != 'OpenAI') {
                    $open_ai = WPAICG_AzureAI::get_instance()->azureai();
                }
                if (!$open_ai) {
                    $wpaicg_result['msg'] = esc_html__('Missing API Setting', 'gpt3-ai-content-generator');
                    wp_send_json($wpaicg_result);
                }
                $file_name = sanitize_file_name(basename($_FILES['file']['name']));
                $filetype = wp_check_filetype($file_name);
                if ($filetype['ext'] !== 'jsonl') {
                    $wpaicg_result['msg'] = esc_html__('Only files with the jsonl extension are supported', 'gpt3-ai-content-generator');
                    wp_send_json($wpaicg_result);
                }
                $tmp_file = $_FILES['file']['tmp_name'];
                $c_file = $tmp_file;
                $purpose = isset($_POST['purpose']) && !empty($_POST['purpose']) ? sanitize_text_field($_POST['purpose']) : 'fine-tune';
                $model = isset($_POST['model']) && !empty($_POST['model']) ? sanitize_text_field($_POST['model']) : 'gpt-3.5-turbo';
                $name = isset($_POST['name']) && !empty($_POST['name']) ? sanitize_title($_POST['name']) : '';
                $result = $open_ai->uploadFile(array(
                    'file' => array(
                        'data' => file_get_contents($tmp_file),
                        'filename' => basename($_FILES['file']['name']),
                    ),
                ));
                $result = json_decode($result);
                if (isset($result->error)) {
                    $wpaicg_result['msg'] = $result->error->message;
                } else {
                    $wpaicg_file_id = wp_insert_post(array(
                        'post_title' => $result->id,
                        'post_date' => date('Y-m-d H:i:s', get_date_from_gmt(date('Y-m-d H:i:s', $result->created_at), 'U')),
                        'post_status' => 'publish',
                        'post_type' => 'wpaicg_file',
                    ));
                    if (!is_wp_error($wpaicg_file_id)) {
                        $wpaicg_result['status'] = 'success';
                        add_post_meta($wpaicg_file_id, 'wpaicg_filename', $result->filename);
                        add_post_meta($wpaicg_file_id, 'wpaicg_purpose', $result->purpose);
                        add_post_meta($wpaicg_file_id, 'wpaicg_model', $model);
                        add_post_meta($wpaicg_file_id, 'wpaicg_custom_name', $name);
                        add_post_meta($wpaicg_file_id, 'wpaicg_file_size', $result->bytes);
                    } else {
                        $wpaicg_result['msg'] = $wpaicg_file_id->get_error_message();
                    }
                }
            } else {
                $wpaicg_result['msg'] = esc_html__('File upload required', 'gpt3-ai-content-generator');
            }

            wp_send_json($wpaicg_result);
        }

        public function wpaicg_get_finetune_file()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $wpaicg_file = get_post(sanitize_text_field($_POST['id']));
                if ($wpaicg_file) {
                    $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                    $open_ai = WPAICG_OpenAI::get_instance()->openai();
                    // if provider not openai then assing azure to $open_ai
                    if ($wpaicg_provider != 'OpenAI') {
                        $open_ai = WPAICG_AzureAI::get_instance()->azureai();
                    }
                    if (!$open_ai) {
                        $wpaicg_result['msg'] = esc_html__('Missing API Setting', 'gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }
                    $result = $open_ai->retrieveFileContent($wpaicg_file->post_title);
                    $json_parse = json_decode($result);
                    if (isset($json_parse->error)) {
                        $wpaicg_result['msg'] = $json_parse->error->message;
                    } else {
                        $wpaicg_result['status'] = 'success';
                        $wpaicg_result['data'] = $result;
                    }
                } else {
                    $wpaicg_result['msg'] = esc_html__('File not found', 'gpt3-ai-content-generator');
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_finetune_events()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $wpaicg_file = get_post(sanitize_text_field($_POST['id']));
                if ($wpaicg_file) {
                    $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                    $open_ai = WPAICG_OpenAI::get_instance()->openai();
                    // if provider not openai then assing azure to $open_ai
                    if ($wpaicg_provider != 'OpenAI') {
                        $open_ai = WPAICG_AzureAI::get_instance()->azureai();
                    }
                    if (!$open_ai) {
                        $wpaicg_result['msg'] = esc_html__('Missing API Setting', 'gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }
                    $result = $open_ai->retrieveFineTune($wpaicg_file->post_title);
                    $result = json_decode($result);
                    if (isset($result->error)) {
                        $wpaicg_result['msg'] = $result->error->message;
                    } else {
                        $wpaicg_result['status'] = 'success';
                        $wpaicg_result['data'] = $result->events;
                    }
                } else {
                    $wpaicg_result['msg'] = esc_html__('Fine Tune not found', 'gpt3-ai-content-generator');
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_get_finetune()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $wpaicg_file = get_post(sanitize_text_field($_POST['id']));
                if ($wpaicg_file) {
                    $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                    $open_ai = WPAICG_OpenAI::get_instance()->openai();
                    // if provider not openai then assing azure to $open_ai
                    if ($wpaicg_provider != 'OpenAI') {
                        $open_ai = WPAICG_AzureAI::get_instance()->azureai();
                    }
                    if (!$open_ai) {
                        $wpaicg_result['msg'] = esc_html__('Missing API Setting', 'gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }
                    $result = $open_ai->retrieveFineTune($wpaicg_file->post_title);
                    $result = json_decode($result);
                    if (isset($result->error)) {
                        $wpaicg_result['msg'] = $result->error->message;
                    } else {
                        $wpaicg_result['status'] = 'success';
                        $wpaicg_result['data'] = $result;
                    }
                } else {
                    $wpaicg_result['msg'] = esc_html__('Fine Tune not found', 'gpt3-ai-content-generator');
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_other_finetune()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (
                isset($_POST['id'])
                && !empty($_POST['id'])
                && isset($_POST['type'])
                && !empty($_POST['type'])
                && in_array($_POST['type'], array('hyperparameters', 'result_files', 'training_file', 'events'))
            ) {
                $wpaicg_type = sanitize_text_field($_POST['type']);

                $wpaicg_file = get_post(sanitize_text_field($_POST['id']));
                if ($wpaicg_file) {
                    $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                    $open_ai = WPAICG_OpenAI::get_instance()->openai();
                    // if provider not openai then assing azure to $open_ai
                    if ($wpaicg_provider != 'OpenAI') {
                        $open_ai = WPAICG_AzureAI::get_instance()->azureai();
                    }
                    if (!$open_ai) {
                        $wpaicg_result['msg'] = esc_html__('Missing API Setting', 'gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }

                    if ($wpaicg_type === 'events') {
                        $result = $open_ai->listFineTuneEvents($wpaicg_file->post_title);
                        $wpaicg_type = 'data';
                    } else {
                        $result = $open_ai->retrieveFineTune($wpaicg_file->post_title);
                    }

                    $result = json_decode($result);

                    if (isset($result->error)) {
                        $wpaicg_result['msg'] = $result->error->message;
                    } elseif (isset($result->$wpaicg_type)) {
                        $wpaicg_data = $result->$wpaicg_type;

                        if ($wpaicg_type === 'data') {
                            $wpaicg_type = 'events';
                        } else if ($wpaicg_type === 'hyperparameters') {
                            $wpaicg_type = 'hyperparams';
                        } else if ($wpaicg_type === 'result_files') {
                            if (isset($wpaicg_data->error)) {
                                $wpaicg_result['msg'] = $wpaicg_data->error->message;
                            } else {
                                $resultFiles = [];
                                if ($wpaicg_data) {
                                    foreach ($wpaicg_data as $key => $val) {
                                        $resultData = $open_ai->retrieveFile($val);
                                        $wpaicg_res = json_decode($resultData);
                                        $resultFiles[] = $wpaicg_res;
                                    }
                                    $wpaicg_data = $resultFiles;
                                }
                            }
                        } else if ($wpaicg_type === 'training_file') {

                            $resultFiles = [];
                            $resultData = $open_ai->retrieveFile($wpaicg_data);
                            $wpaicg_res = json_decode($resultData);
                            $resultFiles[] = $wpaicg_res;
                            $wpaicg_data = $resultFiles;
                            $wpaicg_type = 'training_files';
                        }

                        ob_start();
                        include WPAICG_PLUGIN_DIR . 'admin/views/finetune/' . $wpaicg_type . '.php';
                        $wpaicg_result['html'] = ob_get_clean();
                        $wpaicg_result['status'] = 'success';
                    }
                } else {
                    $wpaicg_result['msg'] = esc_html__('Fine Tune not found', 'gpt3-ai-content-generator');
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_delete_finetune_file()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $wpaicg_file = get_post(sanitize_text_field($_POST['id']));
                if ($wpaicg_file) {
                    $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                    $open_ai = WPAICG_OpenAI::get_instance()->openai();
                    // if provider not openai then assing azure to $open_ai
                    if ($wpaicg_provider != 'OpenAI') {
                        $open_ai = WPAICG_AzureAI::get_instance()->azureai();
                    }
                    if (!$open_ai) {
                        $wpaicg_result['msg'] = esc_html__('Missing API Setting', 'gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }
                    $result = $open_ai->deleteFile($wpaicg_file->post_title);
                    $result = json_decode($result);
                    if (isset($result->error)) {
                        $wpaicg_result['msg'] = $result->error->message;
                    } else {
                        wp_delete_post($wpaicg_file->ID);
                        $wpaicg_result['status'] = 'success';
                    }
                } else {
                    $wpaicg_result['msg'] = esc_html__('File not found', 'gpt3-ai-content-generator');
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_delete_finetune()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $wpaicg_file = get_post(sanitize_text_field($_POST['id']));
                if ($wpaicg_file) {
                    $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                    $open_ai = WPAICG_OpenAI::get_instance()->openai();
                    // if provider not openai then assing azure to $open_ai
                    if ($wpaicg_provider != 'OpenAI') {
                        $open_ai = WPAICG_AzureAI::get_instance()->azureai();
                    }
                    if (!$open_ai) {
                        $wpaicg_result['msg'] = esc_html__('Missing API Setting', 'gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }
                    $ft_model = get_post_meta($wpaicg_file->ID, 'wpaicg_name', true);
                    if (!empty($ft_model)) {
                        $result = $open_ai->deleteFineTune($ft_model);
                        $result = json_decode($result);
                        if (isset($result->error)) {
                            $wpaicg_result['msg'] = $result->error->message;
                        } else {
                            update_post_meta($wpaicg_file->ID, 'wpaicg_deleted', '1');
                            $wpaicg_result['status'] = 'success';
                        }
                    } else {
                        $wpaicg_result['msg'] = esc_html__('That model does not exist', 'gpt3-ai-content-generator');
                    }
                } else {
                    $wpaicg_result['msg'] = esc_html__('File not found', 'gpt3-ai-content-generator');
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_cancel_finetune()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $wpaicg_file = get_post(sanitize_text_field($_POST['id']));
                if ($wpaicg_file) {
                    $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                    $open_ai = WPAICG_OpenAI::get_instance()->openai();
                    // if provider not openai then assing azure to $open_ai
                    if ($wpaicg_provider != 'OpenAI') {
                        $open_ai = WPAICG_AzureAI::get_instance()->azureai();
                    }
                    if (!$open_ai) {
                        $wpaicg_result['msg'] = esc_html__('Missing API Setting', 'gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }
                    $result = $open_ai->cancelFineTune($wpaicg_file->post_title);
                    if (!empty($result->error)) {
                        $wpaicg_result['msg'] = $result->error->message;
                        wp_send_json($wpaicg_result);
                    }
                    $result = json_decode($result, true); // Decode as associative array
                    // Update or add post meta based on the status field from the response
                    if (isset($result['status'])) {
                        update_post_meta($wpaicg_file->ID, 'wpaicg_status', $result['status']);
                        $wpaicg_result = [
                            'status' => 'success',
                            'msg' => esc_html__('Fine-tuning job status updated successfully.', 'gpt3-ai-content-generator'),
                            'data' => $result
                        ];
                    } else {
                        $wpaicg_result['msg'] = esc_html__('Status field missing in the response.', 'gpt3-ai-content-generator');
                    }
                } else {
                    $wpaicg_result['msg'] = esc_html__('File not found', 'gpt3-ai-content-generator');
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function aipower_fetch_openai_models() {
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Fetch the models from OpenAI
            $gpt4_models = \WPAICG\WPAICG_Util::get_instance()->openai_gpt4_models;
            $gpt35_models = \WPAICG\WPAICG_Util::get_instance()->openai_gpt35_models;
        
            // Call the API to list fine-tuned models
            $result = WPAICG_OpenAI::get_instance()->openai()->listFineTunes();
            $result = json_decode($result);
        
            $custom_models = array();
        
            if (isset($result->error)) {
                // Handle the error
                wp_send_json_error(array('message' => $result->error->message));
                return;
            } else {
                if (isset($result->data) && is_array($result->data) && count($result->data)) {
                    foreach ($result->data as $item) {
                        if ($item->status == 'succeeded' && !empty($item->fine_tuned_model)) {
                            $custom_models[] = $item->fine_tuned_model;
                        }
                    }
                }
            }
        
            // Update the 'wpaicg_custom_models' option in the database
            update_option('wpaicg_custom_models', $custom_models);
        
            // Check if 'wpaicg_ai_model' option exists and has a value
            $ai_model_option = get_option('wpaicg_ai_model', '');
        
            if (empty($ai_model_option)) {
                // If the option does not exist or is empty, update it with 'gpt-3.5-turbo'
                update_option('wpaicg_ai_model', 'gpt-3.5-turbo');
            }
        
            // Retrieve custom models from the updated option
            $custom_models_serialized = get_option('wpaicg_custom_models', '');
            $custom_models = maybe_unserialize($custom_models_serialized);
        
            // Fetch assistants using the existing listAssistants method
            $assistants_response = WPAICG_OpenAI::get_instance()->listAssistants(); // This should return JSON
        
            $assistants_data = json_decode($assistants_response, true);
        
            if (isset($assistants_data['error'])) {
                wp_send_json_error(array('message' => $assistants_data['error']['message']));
                return;
            } else {
                if (isset($assistants_data['data']) && is_array($assistants_data['data']) && count($assistants_data['data'])) {
                    $assistants = array();
                    foreach ($assistants_data['data'] as $assistant) {
                        if (isset($assistant['id'])) {
                            $assistants[] = array(
                                'assistant_id' => sanitize_text_field($assistant['id']),
                                'name' => isset($assistant['name']) ? sanitize_text_field($assistant['name']) : 'Unnamed Assistant'
                            );
                        }
                    }
                    // Update the 'wpaicg_assistants' option with the fetched assistants
                    update_option('wpaicg_assistants', $assistants);
                } else {
                    // No assistants found, set to empty
                    $assistants = array();
                    update_option('wpaicg_assistants', $assistants);
                }
            }
        
            // Return success with the OpenAI models and assistants
            wp_send_json_success([
                'gpt35_models'   => $gpt35_models,
                'gpt4_models'    => $gpt4_models,
                'custom_models'  => $custom_models,
                'assistants'     => $assistants // array of assistant objects
            ]);
        }
        
        
        public function wpaicg_finetunes()
        {
            global $wpdb;
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
            $open_ai = WPAICG_OpenAI::get_instance()->openai();
            // if provider not openai then assing azure to $open_ai
            if ($wpaicg_provider == 'Azure') {
                $open_ai = WPAICG_AzureAI::get_instance()->azureai();
            } else {
                $open_ai = WPAICG_OpenAI::get_instance()->openai();
            }
            
            if (!$open_ai) {
                $wpaicg_result['msg'] = esc_html__('Missing API Setting', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            $result = $open_ai->listFineTunes();
            $result = json_decode($result);
            if (isset($result->error)) {
                $wpaicg_result['msg'] = $result->error->message;
            } else {
                if (isset($result->data) && is_array($result->data) && count($result->data)) {
                    $wpaicg_result['status'] = 'success';
                    $wpaicgExist = array();
                    $finetone_models = array();
                    foreach ($result->data as $item) {
                        $wpaicgExist[] = $item->id;
                        $wpaicg_check = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->posts . " WHERE post_type='wpaicg_finetune' AND post_title=%s", $item->id));
                        if (!$wpaicg_check) {
                            $wpaicg_file_id = wp_insert_post(array(
                                'post_title' => $item->id,
                                'post_date' => date('Y-m-d H:i:s', $item->created_at),
                                'post_status' => 'publish',
                                'post_type' => 'wpaicg_finetune',
                            ));
                            if (!is_wp_error($wpaicg_file_id)) {
                                add_post_meta($wpaicg_file_id, 'wpaicg_model', $item->model);
                                if (isset($item->updated_at)) {
                                    add_post_meta($wpaicg_file_id, 'wpaicg_updated_at', date('Y-m-d H:i:s', $item->updated_at));
                                }
                                add_post_meta($wpaicg_file_id, 'wpaicg_name', $item->fine_tuned_model);
                                add_post_meta($wpaicg_file_id, 'wpaicg_org', $item->organization_id);
                                add_post_meta($wpaicg_file_id, 'wpaicg_status', $item->status);
                                if (isset($item->training_files) && is_object($item->training_files) && isset($item->training_files->id)) {
                                    add_post_meta($wpaicg_file_id, 'wpaicg_fine_tune', $item->training_files->id);
                                }
                            } else {
                                $wpaicg_result['status'] = 'error';
                                $wpaicg_result['msg'] = $wpaicg_file_id->get_error_message();
                                break;
                            }
                        } else {
                            $wpaicg_file_id = $wpaicg_check->ID;
                            update_post_meta($wpaicg_check->ID, 'wpaicg_model', $item->model);
                            if (isset($item->updated_at)) {
                                update_post_meta($wpaicg_check->ID, 'wpaicg_updated_at', date('Y-m-d H:i:s', $item->updated_at));
                            }                                                      
                            update_post_meta($wpaicg_check->ID, 'wpaicg_name', $item->fine_tuned_model);
                            update_post_meta($wpaicg_check->ID, 'wpaicg_org', $item->organization_id);
                            update_post_meta($wpaicg_check->ID, 'wpaicg_status', $item->status);
                            if (isset($item->training_files->id)) {
                                update_post_meta($wpaicg_check->ID, 'wpaicg_fine_tune', $item->training_files->id);
                            }
                        }
                        if (!empty($item->fine_tuned_model)) {
                            $resultModel = $open_ai->retrieveModel($item->fine_tuned_model);
                            $resultModel = json_decode($resultModel);
                            if (isset($resultModel->error)) {
                                wp_delete_post($wpaicg_file_id);
                            } elseif ($item->status == 'succeeded') {
                                $finetone_models[] = $item->fine_tuned_model;
                            }
                        }
                    }
                    update_option('wpaicg_custom_models', $finetone_models);
                    if (count($wpaicgExist)) {
                        $commaDelimitedPlaceholders = implode(',', array_fill(0, count($wpaicgExist), '%s'));
                        $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->posts . " WHERE post_type='wpaicg_finetune' AND post_title NOT IN ($commaDelimitedPlaceholders)", $wpaicgExist));
                    } else {
                        $wpdb->query("DELETE FROM " . $wpdb->posts . " WHERE post_type='wpaicg_finetune'");
                    }
                } else {
                    $wpaicg_result['status'] = 'success';
                    $wpdb->query("DELETE FROM " . $wpdb->posts . " WHERE post_type='wpaicg_finetune'");
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_save_files($items)
        {
            global $wpdb;
            $wpaicgExist = array();
            foreach ($items as $item) {
                if ($item->purpose !== 'fine-tune-results' && $item->status != 'deleted') {
                    $wpaicg_check = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->posts . " WHERE post_type='wpaicg_file' AND post_title=%s", $item->id));
                    $wpaicgExist[] = $item->id;
                    if (!$wpaicg_check) {
                        $wpaicg_file_id = wp_insert_post(array(
                            'post_title' => $item->id,
                            'post_date' => date('Y-m-d H:i:s', $item->created_at),
                            'post_status' => 'publish',
                            'post_type' => 'wpaicg_file',
                        ));
                        if (!is_wp_error($wpaicg_file_id)) {
                            add_post_meta($wpaicg_file_id, 'wpaicg_filename', $item->filename);
                            add_post_meta($wpaicg_file_id, 'wpaicg_purpose', $item->purpose);
                            add_post_meta($wpaicg_file_id, 'wpaicg_file_size', $item->bytes);
                        } else {
                            $wpaicg_result['status'] = 'error';
                            $wpaicg_result['msg'] = $wpaicg_file_id->get_error_message();
                            break;
                        }
                    } else {
                        update_post_meta($wpaicg_check->ID, 'wpaicg_filename', $item->filename);
                        update_post_meta($wpaicg_check->ID, 'wpaicg_purpose', $item->purpose);
                        update_post_meta($wpaicg_check->ID, 'wpaicg_file_size', $item->bytes);
                    }

                }
            }
            if (count($wpaicgExist)) {
                $commaDelimitedPlaceholders = implode(',', array_fill(0, count($wpaicgExist), '%s'));
                $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->posts . " WHERE post_type='wpaicg_file' AND post_title NOT IN ($commaDelimitedPlaceholders)", $wpaicgExist));
            } else {
                $wpdb->query("DELETE FROM " . $wpdb->posts . " WHERE post_type='wpaicg_file'");
            }
        }

        public function wpaicg_files()
        {
            global $wpdb;
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('manage_options')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
            $open_ai = WPAICG_OpenAI::get_instance()->openai();
            // if provider not openai then assing azure to $open_ai
            if ($wpaicg_provider != 'OpenAI') {
                $open_ai = WPAICG_AzureAI::get_instance()->azureai();
            }
            if (!$open_ai) {
                $wpaicg_result['msg'] = esc_html__('Missing API Setting', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            $result = $open_ai->listFiles();
            $result = json_decode($result);
            if (isset($result->error)) {
                $wpaicg_result['msg'] = $result->error->message;
            } else {
                if (isset($result->data) && is_array($result->data) && count($result->data)) {
                    $wpaicg_result['status'] = 'success';
                    $this->wpaicg_save_files($result->data);
                } else {
                    $wpaicg_result['status'] = 'success';
                    $wpdb->query("DELETE FROM " . $wpdb->posts . " WHERE post_type='wpaicg_file'");
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function generate_table_row_files($post) {
            // $wpaicg_file->file_size
            $file_size = get_post_meta($post->ID, 'wpaicg_file_size', true);
            // $wpaicg_file->post_date
            $postdate = date('y-m-d H:i', strtotime($post->post_date));
            // $wpaicg_file->filename
            $filename = get_post_meta($post->ID, 'wpaicg_filename', true);
            // Truncate filename if it's longer than 10 characters
            $displayFilename = strlen($filename) > 10 ? substr($filename, 0, 10) . '...' : $filename;
            // $wpaicg_file->purpose
            $purpose = get_post_meta($post->ID, 'wpaicg_purpose', true);
            // $wpaicg_file->ID
            $file_id = $post->ID;
            // Check and format post title if it's more than 10 characters
            $post_title = strlen($post->post_title) > 10 ? substr($post->post_title, 0, 10) . '...' : $post->post_title;
            // Check and format filename if it's more than 10 characters
            $display_filename = strlen($filename) > 10 ? substr($filename, 0, 10) . '...' : $filename;

            // Build the buttons HTML
            $buttonsHtml = "<button data-id='" . esc_attr($file_id) . "' class='button button-small wpaicg_create_fine_tune'>" . esc_html__('Create Fine-tune', 'gpt3-ai-content-generator') . "</button> " .
            "<button style='margin-top: 0.5em;margin-bottom: 0.5em;' data-id='" . esc_attr($file_id) . "' class='button button-small wpaicg_retrieve_content'>" . esc_html__('Retrieve Content', 'gpt3-ai-content-generator') . "</button> " .
            "<button data-id='" . esc_attr($file_id) . "' class='button button-small button-link-delete wpaicg_delete_file'>" . esc_html__('Delete', 'gpt3-ai-content-generator') . "</button>";


            return "<tr id='post-row-{$post->ID}'>
                        <td class='column-id'>" . esc_html($post_title) . "</td>
                        <td class='column-size'>" . esc_html($file_size) . "</td>
                        <td class='column-created'>" . esc_html($postdate) . "</td>
                        <td class='column-filename'>" . esc_html($displayFilename) . "</td>
                        <td class='column-purpose'>" . esc_html($purpose) . "</td>
                        <td class='column-action' style='display: flex;flex-direction: column;align-items: flex-start;'>{$buttonsHtml}</td>
                    </tr>";
        }

        public function ajax_pagination_finetune() {
            global $wpdb;
            // Check for nonce security
            if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax_pagination_finetune_nonce' ) ) {
                wp_send_json_error(['msg' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')]);
            }
        
            $page_finetune = isset($_POST['wpage_finetune']) ? intval($_POST['wpage_finetune']) : 1;
            $posts_per_page_finetune = 3; // Adjust as needed
            $offset_finetune = ($page_finetune - 1) * $posts_per_page_finetune;
        
            // Calculate total number of posts from wpaicg_embeddings
            $total_posts_finetune = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->posts." f WHERE f.post_type='wpaicg_file' AND (f.post_status='publish' OR f.post_status = 'future')");
            $total_pages_finetune = ceil($total_posts_finetune / $posts_per_page_finetune);
        
            $posts_finetune = $wpdb->get_results($wpdb->prepare("SELECT f.*
            ,(SELECT fn.meta_value FROM ".$wpdb->postmeta." fn WHERE fn.post_id=f.ID AND fn.meta_key='wpaicg_filename') as filename 
            ,(SELECT fp.meta_value FROM ".$wpdb->postmeta." fp WHERE fp.post_id=f.ID AND fp.meta_key='wpaicg_purpose') as purpose 
            ,(SELECT fm.meta_value FROM ".$wpdb->postmeta." fm WHERE fm.post_id=f.ID AND fm.meta_key='wpaicg_purpose') as model 
            ,(SELECT fc.meta_value FROM ".$wpdb->postmeta." fc WHERE fc.post_id=f.ID AND fc.meta_key='wpaicg_custom_name') as custom_name 
            ,(SELECT fs.meta_value FROM ".$wpdb->postmeta." fs WHERE fs.post_id=f.ID AND fs.meta_key='wpaicg_file_size') as file_size 
            ,(SELECT ft.meta_value FROM ".$wpdb->postmeta." ft WHERE ft.post_id=f.ID AND ft.meta_key='wpaicg_fine_tune') as finetune 
            FROM ".$wpdb->posts." f WHERE f.post_type='wpaicg_file' AND (f.post_status='publish' OR f.post_status = 'future') ORDER BY f.post_date DESC LIMIT %d, %d", $offset_finetune, $posts_per_page_finetune));

            $output_finetune = '';
            foreach ( $posts_finetune as $post_finetune ) {
                $output_finetune .= $this->generate_table_row_files($post_finetune);
            }

            $pagination_html_finetune = $this->generate_smart_pagination_finetune($page_finetune, $total_pages_finetune);

            // Send back both the table content and pagination HTML
            wp_send_json_success(['content' => $output_finetune, 'pagination' => $pagination_html_finetune]);
        
            die();
        }

        public function generate_smart_pagination_finetune($current_page_finetune, $total_pages_finetune) {
            $html_finetune = '<div class="finetune-pagination">';
            $range_finetune = 2; // Adjust as needed. This will show two pages before and after the current page.
            $showEllipses_finetune = false;
        
            for ($i = 1; $i <= $total_pages_finetune; $i++) {
                // Always show the first page, the last page, and the current page with $range pages on each side.
                if ($i == 1 || $i == $total_pages_finetune || ($i >= $current_page_finetune - $range_finetune && $i <= $current_page_finetune + $range_finetune)) {
                    $html_finetune .= sprintf('<a href="#" data-page_finetune="%d">%d</a> ', $i, $i);
                    $showEllipses_finetune = true;
                } elseif ($showEllipses_finetune) {
                    $html_finetune .= '... ';
                    $showEllipses_finetune = false;
                }
            }
        
            $html_finetune .= '</div>';
            return $html_finetune;
        }
        
        public function generate_table_row_training($post) {
            $post_title = strlen($post->post_title) > 15 ? substr($post->post_title, 0, 15) . '...' : $post->post_title;
            $model = $post->model;
            $post_date = date('y-m-d H:i', strtotime($post->post_date));
            $ft_model = $post->ft_model;
            $org_id = $post->org_id;
            
            // combine model, ft_model, org_id, created, updated, title into details
            $details = "<div style='font-size: 90%;white-space: break-spaces;'>";
            $details .= "<strong>Base Model:</strong> {$model}<br>";
            $details .= "<strong>Fine-tuned Model:</strong> {$ft_model}<br>";
            $details .= "<strong>Created:</strong> {$post_date}<br>";
            $details .= "</div>";

            // Determine status color
            $statusColor = '#6C757D'; // Default to Grey for "cancelled" or undefined statuses
            switch ($post->ft_status) {
                case 'validating_files':
                    $statusColor = '#007BFF'; // Blue
                    break;
                case 'queued':
                    $statusColor = '#FFA500'; // Orange
                    break;
                case 'running':
                    $statusColor = '#28A745'; // Green
                    break;
                case 'succeeded':
                    $statusColor = '#5CB85C'; // Light Green
                    break;
                case 'failed':
                    $statusColor = '#DC3545'; // Red
                    break;
                case 'cancelled':
                    $statusColor = '#DC3545'; // Red
                    break;
            }

            // Now include the color in the status column
            $statusHTML = "<td class='column-status' style='color: {$statusColor};'>" . esc_html($post->ft_status) . "</td>";

            // Building the buttons HTML
            $buttonsHtml = "<a style='margin-bottom: 0.5em;' class='wpaicg_get_other button button-small' data-type='events' data-id='" . esc_attr($post->ID) . "' href='javascript:void(0)'>" . esc_html__('Events', 'gpt3-ai-content-generator') . "</a>";

            // Include Delete and Cancel buttons based on conditions
            if (!$post->deleted) {
                if ($post->ft_status == 'pending' || $post->ft_status == 'queued' || $post->ft_status == 'running') {
                    $buttonsHtml .= "<a class='wpaicg_cancel_finetune button button-small button-link-delete' data-id='" . esc_attr($post->ID) . "' href='javascript:void(0)'>" . esc_html__('Cancel', 'gpt3-ai-content-generator') . "</a><br>";
                }
                if (!empty($post->ft_model)) {
                    $buttonsHtml .= "<a class='wpaicg_delete_finetune button button-small button-link-delete' data-id='" . esc_attr($post->ID) . "' href='javascript:void(0)'>" . esc_html__('Delete', 'gpt3-ai-content-generator') . "</a><br>";
                }
            }

            
            return "<tr id='post-row-{$post->ID}'>
                        <td class='column-id'>" . esc_html($post_title) . "</td>
                        <td class='column-details'>" . $details . "</td>
                        {$statusHTML}
                        <td class='column-training' style='display: flex;flex-direction: column;align-items: flex-start;'>{$buttonsHtml}</td>
                    </tr>";
        }

        public function ajax_pagination_training() {
            global $wpdb;
            // Check for nonce security
            if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax_pagination_training_nonce' ) ) {
                wp_send_json_error(['msg' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')]);
            }
        
            $page_training = isset($_POST['wpage_training']) ? intval($_POST['wpage_training']) : 1;
            $posts_per_page_training = 3; // Adjust as needed
            $offset_training = ($page_training - 1) * $posts_per_page_training;
        
            // Calculate total number of posts from wpaicg_embeddings
            $total_posts_training = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->posts . " f WHERE f.post_type='wpaicg_finetune' AND (f.post_status='publish' OR f.post_status = 'future')");
            $total_pages_training = ceil($total_posts_training / $posts_per_page_training);
        
            $posts_trainings = $wpdb->get_results($wpdb->prepare("SELECT f.*
            ,(SELECT fn.meta_value FROM " . $wpdb->postmeta . " fn WHERE fn.post_id=f.ID AND fn.meta_key='wpaicg_model' LIMIT 1) as model
            ,(SELECT fp.meta_value FROM " . $wpdb->postmeta . " fp WHERE fp.post_id=f.ID AND fp.meta_key='wpaicg_updated_at' LIMIT 1) as updated_at
            ,(SELECT fm.meta_value FROM " . $wpdb->postmeta . " fm WHERE fm.post_id=f.ID AND fm.meta_key='wpaicg_name' LIMIT 1) as ft_model
            ,(SELECT fc.meta_value FROM " . $wpdb->postmeta . " fc WHERE fc.post_id=f.ID AND fc.meta_key='wpaicg_org' LIMIT 1) as org_id
            ,(SELECT fs.meta_value FROM " . $wpdb->postmeta . " fs WHERE fs.post_id=f.ID AND fs.meta_key='wpaicg_status' LIMIT 1) as ft_status
            ,(SELECT ft.meta_value FROM " . $wpdb->postmeta . " ft WHERE ft.post_id=f.ID AND ft.meta_key='wpaicg_fine_tune' LIMIT 1) as finetune
            ,(SELECT fd.meta_value FROM " . $wpdb->postmeta . " fd WHERE fd.post_id=f.ID AND fd.meta_key='wpaicg_deleted' LIMIT 1) as deleted
            FROM " . $wpdb->posts . " f WHERE f.post_type='wpaicg_finetune' AND (f.post_status='publish' OR f.post_status = 'future') ORDER BY f.post_date DESC LIMIT %d,%d", $offset_training, $posts_per_page_training));

            $output_training = '';
            foreach ( $posts_trainings as $post_training ) {
                $output_training .= $this->generate_table_row_training($post_training);
            }

            $pagination_html_training = $this->generate_smart_pagination_training($page_training, $total_pages_training);

            // Send back both the table content and pagination HTML
            wp_send_json_success(['content' => $output_training, 'pagination' => $pagination_html_training]);
        
            die();
        }

        public function generate_smart_pagination_training($current_page_training, $total_pages_training) {
            $html_training = '<div class="training-pagination">';
            $range_training = 2; // Adjust as needed. This will show two pages before and after the current page.
            $showEllipses_training = false;
        
            for ($i = 1; $i <= $total_pages_training; $i++) {
                // Always show the first page, the last page, and the current page with $range pages on each side.
                if ($i == 1 || $i == $total_pages_training || ($i >= $current_page_training - $range_training && $i <= $current_page_training + $range_training)) {
                    $html_training .= sprintf('<a href="#" data-page_training="%d">%d</a> ', $i, $i);
                    $showEllipses_training = true;
                } elseif ($showEllipses_training) {
                    $html_training .= '... ';
                    $showEllipses_training = false;
                }
            }
        
            $html_training .= '</div>';
            return $html_training;
        }
        
        public static function wpaicg_finetune()
        {
            include WPAICG_PLUGIN_DIR . 'admin/views/finetune/index.php';
        }
    }
    WPAICG_FineTune::get_instance();
}
