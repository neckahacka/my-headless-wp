<?php
namespace WPAICG;

if ( ! defined( 'ABSPATH' ) ) exit;

if(!class_exists('\\WPAICG\\WPAICG_Assistants')) {
    class WPAICG_Assistants
    {
        private static $instance = null;

        public static function get_instance()
        {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function handle_assistant_api($assistant_id, $wpaicg_message, $thread_id = null, $additional_instructions = null, $stream = false, $image_final_data = null)
        {
            $wpaicg_result = [
                'status' => 'error',
                'msg'    => esc_html__('Something went wrong', 'gpt3-ai-content-generator'),
            ];
        
            // Initialize the OpenAI instance
            $open_ai = WPAICG_OpenAI::get_instance()->openai();
        
            if (!$open_ai) {
                $wpaicg_result['msg'] = esc_html__('Unable to initialize the AI instance. Please make sure the API key is valid.', 'gpt3-ai-content-generator');
                return $wpaicg_result;
            }

            if ($stream) {
                // Handle streaming response
                $response = $this->handle_assistant_api_streaming($open_ai, $assistant_id, $wpaicg_message, $thread_id, $additional_instructions);
                return $response;
            }
        
            if (!$thread_id) {

                $messages[] = [
                    'role' => 'user',
                    'content' => $this->processImageContent($wpaicg_message, $image_final_data)
                ];
                
                $thread_response = $open_ai->createThread($messages);
                $thread_data = json_decode($thread_response, true);

                // Check for error in the response
                if (isset($thread_data['error'])) {
                    $wpaicg_result['msg'] = $thread_data['error']['message'] ?? 'An unknown error occurred.';
                    return $wpaicg_result;
                }
        
                if (isset($thread_data['id'])) {
                    $thread_id = $thread_data['id'];
                } else {
                    // Handle error
                    $wpaicg_result['msg'] = 'Failed to create thread.';
                    return $wpaicg_result;
                }
            } else {
                // Add the user's message to the existing thread
                $message_data = [
                    'role' => 'user',
                    'content' => $this->processImageContent($wpaicg_message, $image_final_data)
                ];
                $message_response = $open_ai->addMessageToThread($thread_id, $message_data);
                $message_data = json_decode($message_response, true);

                // Check for error in the response
                if (isset($message_data['error'])) {
                    $wpaicg_result['msg'] = $message_data['error']['message'] ?? 'An unknown error occurred.';
                    return $wpaicg_result;
                }
        
                if (!isset($message_data['id'])) {
                    // Handle error
                    $wpaicg_result['msg'] = 'Failed to add message to thread.';
                    return $wpaicg_result;
                }
            }
        
            // Create a run with the assistant ID
            $run_data = [
                'assistant_id' => $assistant_id
            ];
            // Include additional_instructions if provided
            if (!empty($additional_instructions)) {
                $run_data['additional_instructions'] = $additional_instructions;
            }
            $run_response = $open_ai->createRun($thread_id, $run_data);
            $run_data = json_decode($run_response, true);

            // Check for error in the response
            if (isset($run_data['error'])) {
                $wpaicg_result['msg'] = $run_data['error']['message'] ?? 'An unknown error occurred.';
                return $wpaicg_result;
            }
        
            if (isset($run_data['id'])) {
                $run_id = $run_data['id'];
            } else {
                // Default error message
                $error_message = 'Run failed.';

                // Check if 'last_error' exists in the response
                if (isset($run_status_data['last_error'])) {
                    $last_error = $run_status_data['last_error'];
                    $error_message = $last_error['message'] ?? 'An unknown error occurred during the run.';
                }

                $wpaicg_result['msg'] = $error_message;
                return $wpaicg_result;
            }
        
            // Poll the run status until it's completed or timeout occurs
            $max_checks = 20; // Increased for longer runs
            $check_interval = 1; // seconds
            $status = '';
            for ($i = 0; $i < $max_checks; $i++) {
                sleep($check_interval);
                $run_status_response = $open_ai->getRun($thread_id, $run_id);
                $run_status_data = json_decode($run_status_response, true);
        
                if (isset($run_status_data['status'])) {
                    $status = $run_status_data['status'];
                    if ($status == 'completed') {
                        // Get the usage data
                        $usage = isset($run_status_data['usage']) ? $run_status_data['usage'] : null;
                        // Get the model name
                        $model_name = isset($run_status_data['model']) ? $run_status_data['model'] : 'unknown';
                        break;
                    } elseif ($status == 'failed') {
                        // Default error message
                        $error_message = 'Run failed.';

                        // Check if 'last_error' exists in the response
                        if (isset($run_status_data['last_error'])) {
                            $last_error = $run_status_data['last_error'];
                            $error_message = $last_error['message'] ?? 'An unknown error occurred during the run.';
                        }

                        $wpaicg_result['msg'] = $error_message;
                        return $wpaicg_result;
                    }
                    // Continue polling if status is 'queued' or 'in_progress'
                } else {
                    // Handle error
                    $wpaicg_result['msg'] = 'Failed to get run status.';
                    return $wpaicg_result;
                }
            }
        
            if ($status != 'completed') {
                $wpaicg_result['msg'] = 'Run did not complete in time.';
                return $wpaicg_result;
            }
        
            // Retrieve run steps to find the assistant's message_id
            $run_steps_response = $open_ai->listRunSteps($thread_id, $run_id);
            $run_steps_data = json_decode($run_steps_response, true);
        
            if (isset($run_steps_data['data'])) {
                $run_steps = $run_steps_data['data'];
                // Find the latest assistant message creation step
                $latest_assistant_message_id = null;
                foreach ($run_steps as $step) {
                    if ($step['type'] === 'message_creation' && isset($step['step_details']['message_creation']['message_id'])) {
                        $message_id = $step['step_details']['message_creation']['message_id'];
                        // Retrieve the message to confirm the role
                        $message_response = $open_ai->getMessage($thread_id, $message_id);
                        $message_data = json_decode($message_response, true);
                        if (isset($message_data['role']) && $message_data['role'] === 'assistant') {
                            $latest_assistant_message_id = $message_id;
                            // Assuming steps are ordered, the last one is the latest
                        }
                    }
                }
        
                if ($latest_assistant_message_id) {
                    // Retrieve the assistant message using getMessage
                    $assistant_message_response = $open_ai->getMessage($thread_id, $latest_assistant_message_id);
                    $assistant_message_data = json_decode($assistant_message_response, true);
        
                    if (isset($assistant_message_data['content'])) {
                        $assistant_content = $assistant_message_data['content'];
        
                        $assistant_text = '';
        
                        if (is_array($assistant_content)) {
                            // Loop through the content array
                            foreach ($assistant_content as $content_item) {
                                if ($content_item['type'] == 'text' && isset($content_item['text']['value'])) {
                                    $assistant_text .= $content_item['text']['value'];
                                }
                                // Handle other content types if needed
                            }
                        } else {
                            // If content is a string, assign directly
                            $assistant_text = $assistant_content;
                        }
                        // Get the total_tokens from usage
                        $total_tokens = isset($usage['total_tokens']) ? $usage['total_tokens'] : 0;
                        // Return the assistant's response
                        $wpaicg_result['status'] = 'success';
                        $wpaicg_result['data'] = $assistant_text;
                        $wpaicg_result['thread_id'] = $thread_id;
                        $wpaicg_result['total_tokens'] = $total_tokens;
                        $wpaicg_result['model'] = $model_name;
                        return $wpaicg_result;
                    } else {
                        $wpaicg_result['msg'] = 'No content found in assistant message.';
                        return $wpaicg_result;
                    }
                } else {
                    $wpaicg_result['msg'] = 'No assistant message found in run steps.';
                    return $wpaicg_result;
                }
            } else {
                // Handle error
                $wpaicg_result['msg'] = 'Failed to get run steps.';
                return $wpaicg_result;
            }
        }
        
        private function processImageContent($message, $image_final_data = null)
        {
            $content = [
                ['type' => 'text', 'text' => $message]
            ];
        
            // fetch image processing method and vision quality from the options table
            $img_processing_method = get_option('wpaicg_img_processing_method', '');
            $img_vision_quality = get_option('wpaicg_img_vision_quality', 'auto'); // defaults to 'auto'
        
            if ($image_final_data) {
                if ($img_processing_method === 'base64') {
                    // handle image as file
                    $content[] = [
                        'type' => 'image_file',
                        'image_file' => [
                            'file_id' => $image_final_data,
                            'detail' => $img_vision_quality
                        ]
                    ];
                } else {
                    // handle image as url
                    $content[] = [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $image_final_data,
                            'detail' => $img_vision_quality
                        ]
                    ];
                }
            }
        
            return $content;
        }                      

        private function handle_assistant_api_streaming($open_ai, $assistant_id, $wpaicg_message, $thread_id = null, $additional_instructions = null)
        {
            $wpaicg_result = [
                'status' => 'error',
                'msg'    => esc_html__('Something went wrong', 'gpt3-ai-content-generator'),
            ];
            // Disable time limit and buffering
            set_time_limit(0);
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            ini_set('zlib.output_compression', 0);
            ob_implicit_flush(true);
        
            // Set headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header("X-Accel-Buffering: no"); // For Nginx to disable buffering
        
            // Function to send SSE events
            function send_sse($event, $data) {
                if (is_array($data) || is_object($data)) {
                    $data = json_encode($data);
                }
                echo "event: {$event}\n";
                echo "data: {$data}\n\n";
                flush();
            }
        
            // Step 1: Handle Thread Creation or Message Addition
            if (empty($thread_id)) {
                // Create a new thread with the user's message
                $messages = [
                    [
                        'role' => 'user',
                        'content' => $wpaicg_message
                    ]
                ];
                $thread_response = $open_ai->createThread($messages);
                $thread_data = json_decode($thread_response, true);
        
                if (isset($thread_data['id'])) {
                    $thread_id = $thread_data['id'];
                    // Send thread_id to the client
                    send_sse('thread_id', $thread_id);
                } else {
                    if (isset($thread_data['error']['message'])) {
                        $error_msg = $thread_data['error']['message'];
                    } else {
                        $error_msg = 'Failed to create thread.';
                    }
                    send_sse('assistant_error', ['status' => 'error', 'msg' => $error_msg]);
                    send_sse('done', '');
                    exit;
                }
            } else {
                // Add the user's message to the existing thread
                $message_data = [
                    'role' => 'user',
                    'content' => $wpaicg_message
                ];
                $add_message_response = $open_ai->addMessageToThread($thread_id, $message_data);
                $add_message_data = json_decode($add_message_response, true);
        
                if (!isset($add_message_data['id'])) {
                    if (isset($add_message_data['error']['message'])) {
                        $error_msg = $add_message_data['error']['message'];
                    } else {
                        $error_msg = 'Failed to add message to thread.';
                    }
                    send_sse('assistant_error', ['status' => 'error', 'msg' => $error_msg]);
                    send_sse('done', '');
                    exit;
                }
            }
        
            // Step 2: Initiate the Run with Streaming
            $run_data = [
                'assistant_id' => $assistant_id,
                'stream' => true, // Ensure streaming is enabled
            ];
            // Include additional_instructions if provided
            if (!empty($additional_instructions)) {
                $run_data['additional_instructions'] = $additional_instructions;
            }
        
            // Define a stream function to handle incoming data
            $stream_function = function ($data) {
                // Output the data directly
                echo $data;
                flush();
            };

            // Call createRun with streaming
            $response = $open_ai->createRun($thread_id, $run_data, $stream_function);

            $response_data = json_decode($response, true);
            $wpaicg_result['status'] = 'success';
            $wpaicg_result['data'] = $response_data['response_text'];
            $wpaicg_result['total_tokens'] = $response_data['total_tokens'];
            $wpaicg_result['model'] = $response_data['model'];

            return $wpaicg_result;
        }
                  
    }
    WPAICG_Assistants::get_instance();
}
