<?php
/**
 * Webhook Handler
 * Sends and manages webhook requests to external services
 */

class WebhookHandler {
    
    /**
     * Send job data to Zapier for AI analysis
     */
    public static function sendToZapierForAnalysis($jobId, $company, $roleTitle, $jobDescription) {
        $webhookUrl = WEBHOOKS['zapier_ai_analysis'] ?? null;
        
        if (!$webhookUrl || $webhookUrl === 'YOUR_ZAPIER_WEBHOOK_URL_HERE') {
            error_log("Zapier webhook not configured. Skipping AI analysis for job #{$jobId}");
            return false;
        }
        
        $payload = [
            'job_id' => $jobId,
            'company' => $company,
            'role_title' => $roleTitle,
            'job_description' => $jobDescription,
            'callback_url' => self::getCallbackUrl()
        ];
        
        try {
            $result = self::sendWebhook($webhookUrl, $payload);
            error_log("Sent job #{$jobId} to Zapier for AI analysis");
            return $result;
        } catch (Exception $e) {
            error_log("Failed to send job #{$jobId} to Zapier: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send webhook POST request
     */
    private static function sendWebhook($url, $data) {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: JobLead/1.0'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: $error");
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("HTTP error: $httpCode");
        }
        
        return [
            'success' => true,
            'http_code' => $httpCode,
            'response' => $response
        ];
    }
    
    /**
     * Get the callback URL for receiving webhook responses
     */
    private static function getCallbackUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . BASE_URL . '/?page=webhook_receive';
    }
}
