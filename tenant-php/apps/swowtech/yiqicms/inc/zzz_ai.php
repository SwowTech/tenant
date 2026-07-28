<?php
if(file_exists('../config/zzz_ai_config.php')){
    require_once '../config/zzz_ai_config.php';
}

class zzz_ai {
    private $config; // AI配置
    private $client; // HTTP客户端
    private $cache;  // 缓存实例
    
    /**
     * 构造函数
     */
    public function __construct() {
        global $ai_conf;
        $this->config = $ai_conf ?: array();
        $this->client = $this->getHttpClient();         
    }
    
    /**
     * 获取HTTP客户端
     */
    private function getHttpClient() {
        // 检查是否有curl扩展
        if(function_exists('curl_init')) {
            return 'curl';
        } elseif(function_exists('file_get_contents')) {
            return 'file_get_contents';
        }
        return null;
    }
    
    /**
     * 判断AI功能是否开启
     */
    public function isEnabled() {
        return isset($this->config['enable_ai']) && $this->config['enable_ai'] == 1;
    }
    
    /**
     * 获取指定AI服务的配置
     */
    private function getAIServiceConfig($service = null) {
        if(!$service) $service =  $this->config['default_ai'];
        if(!isset($this->config[$service]) || !($this->config[$service]['api_key'])){
            return false;
        }
        $service_config = $this->config[$service];
        return $service_config;
    }

    /**
     * 生成缓存键
     */
    private function generateCacheKey($service, $prompt, $params) {
        $data = array(
            'service' => $service,
            'prompt' => $prompt,
            'params' => $params
        );
        return 'ai_' . md5(json_encode($data));
    }

    /**
     * 通用文本生成接口
     */
    public function generateText($prompt, $service = null, $params = array()) {
        // 检查AI功能是否开启
        if(!$this->isEnabled()) {
            return array('error'=>1,'message' => 'AI功能未开启');
        }
        
        // 获取服务配置
        $service_config = $this->getAIServiceConfig($service);

        if(!$service_config) {
            return array('error'=>1,'message' => 'AI服务未配置或未开启');
        }
        
        $service = $service ?: $this->config['default_ai'];        
        // 合并参数
        $params = array_merge(array(
            'max_tokens' => $this->config['max_tokens'] ?: 2000,
            'temperature' => $this->config['temperature'] ?: 0.7,
            'top_p' => $this->config['top_p'] ?: 0.9,
            'model' => $service_config['model']
        ), $params);
        
        $result = $this->Chat($prompt, $params, $service_config);                    
        return $result;
    }
    
    /**
     * HTML模板生成接口
     */
    public function generateHtml($prompt, $save_path = null, $service = null, $params = array()) {
        // 检查AI功能是否开启
        if(!$this->isEnabled()) {
            return array('error'=>1,'message' => 'AI功能未开启');
        }
        
        // 获取服务配置
        $service_config = $this->getAIServiceConfig($service);

        if(!$service_config) {
            return array('error'=>1,'message' => 'AI服务未配置或未开启');
        }
        
        $service = $service ?: $this->config['default_ai'];        
        // 合并参数 - HTML生成需要更多的tokens
        $params = array_merge(array(
            'max_tokens' => $this->config['max_tokens'] ?: 5000, // 增加最大token数以支持较长的HTML
            'temperature' => $this->config['temperature'] ?: 0.7,
            'top_p' => $this->config['top_p'] ?: 0.9,
            'model' => $service_config['model']
        ), $params);
        
        // 确保prompt中包含HTML生成的要求
        if(strpos(strtolower($prompt), 'html') === false) {
            $prompt = "请生成符合以下要求的HTML模板代码：\n" . $prompt;
        }
        if(strpos(strtolower($prompt), '只输出html代码') === false) {
            $prompt .= "\n请只输出HTML代码，不要包含任何解释或说明。";
        }
        
        // 调用AI生成HTML
        $result = $this->Chat($prompt, $params, $service_config);
        
        // 如果指定了保存路径，并且AI生成成功，则保存文件
        if ($save_path && !isset($result['error'])) {
            // 清理生成内容（去除可能的代码块标记）
            $html_content = $result['text'];
            $html_content = preg_replace('/^```html\n/', '', $html_content);
            $html_content = preg_replace('/```$/', '', $html_content);
            $html_content = trim($html_content);
            
            // 确保目录存在
            $dir = dirname($save_path);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    return array('error' => 1, 'message' => '无法创建保存目录');
                }
            }
            
            // 保存到文件
            if (!file_put_contents($save_path, $html_content)) {
                return array('error' => 1, 'message' => '保存文件失败');
            }
            
            // 将结果文本改为保存路径
            $result['text'] = $save_path;
        }
        
        return $result;
    }
    
    private function Chat($prompt, $params, $config) {        
        try {
            $url = $config['url'];

            $messages = array();
            if(is_string($prompt)) {
                $messages[] = array('role' => 'user', 'content' => $prompt);
            } elseif(is_array($prompt)) {
                $messages = $prompt;
            }
            
            $body = json_encode(array(
                'model' => $params['model'],
                'messages' => $messages,
                'temperature' => (float)$params['temperature'],
                'max_tokens' => (int)$params['max_tokens'],
                'stream'=>false,
                'response_format' => array('type' => 'json_object'),
            ));
            
            $headers = array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['api_key']
            );
            $this->log('info', 'AI请求', array('url' => $url, 'body' => $body, 'headers' => $headers));
            $response = $this->httpRequest($url, $body, $headers, 'POST');
            if(!$response) {
                return array('error'=>1,'message' => 'AI请求失败');
            }
            $data = json_decode($response, true);
           
            if(isset($data['error'])) {
                return array('error' => 'AI请求错误', 'code' => $data['error']['code'], 'message' => $data['error']['message']);
            }
            
            if(isset($data['choices'][0]['message']['content'])) {
                $result=$data['choices'][0]['message']['content'];
                $log_name = $this->log('data', $result);
                return array('text' => $result,'log_name'=>$log_name);
            }
            
            return array('error'=>1,'message' => '通用AI返回格式错误', 'details' => $data);
        } catch(Exception $e) {
            $this->log('error', '通用AI调用异常', array('error' => $e->getMessage()));
            return array('error'=>1,'message' => '通用AI调用异常', 'details' => $e->getMessage());
        }
    }
    
    /**
     * HTTP请求函数
     */
    private function httpRequest($url, $data = array(), $headers = array(), $method = 'POST') {
        if($this->client == 'curl') {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_TIMEOUT, 180); // 设置180秒超时
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); // 设置10秒连接超时
            
            if(!empty($headers)) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            }
            
            if($method == 'POST' && !empty($data)) {
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
            }
            
            $response = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);
            
            if($error) {
                $log_name = $this->log('error', 'HTTP请求失败', array('url' => $url, 'error' => $error));
                return array('error' => $error, 'log_name' => $log_name);
            }
            
            return $response;
        } elseif($this->client == 'file_get_contents') {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => $method,
                    'header' => implode("\r\n", $headers),
                    'content' => is_array($data) ? json_encode($data) : $data,
                    'timeout' => 20 // 设置20秒超时
                ),
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false
                )
            ));
            
            $response = file_get_contents($url, false, $context);
            if($response === false) {
                $log_name = $this->log('error', 'HTTP请求失败', array('url' => $url, 'error' => error_get_last()['message']));
                return array('error' => error_get_last()['message'], 'log_name' => $log_name);
            }
            
            return $response;
        }
        
        $log_name = $this->log('error', '不支持的HTTP客户端');
        return array('error' => '不支持的HTTP客户端', 'log_name' => $log_name);
    }
        
    /**
     * 记录日志
     */
    private function log($level, $message, $data = array(), $log_name = '') {    
        // 生成日志文件名，如果没有提供则使用默认名称
        $log_filename = !empty($log_name) ? $log_name : 'ai_' . date('YmdHis').rand(1000,9999);
        $log_file = '../runtime/'.$level.'/ai/' . $log_filename . '.log';
        $log_dir = dirname($log_file);
        if(!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        if($level=='data'){
            $log_content=$message;
        }else{
            // 构建详细的日志内容
            $log_content = date('Y-m-d H:i:s') . ' [' . $level . '] ' . $message;
            if(!empty($data)) {
                $log_content .= PHP_EOL . 'Data: ' . print_r($data, true);
            }
            $log_content .= PHP_EOL . '------------------------' . PHP_EOL;        
        }       
        file_put_contents($log_file, $log_content, FILE_APPEND);        
        
        // 返回日志文件名（不含路径和扩展名），方便读取
        return $log_filename;
    }
    
}

