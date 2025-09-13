<?php
// Email service using Replit Mail integration
// Reference: replitmail integration

class EmailService {
    private $api_url = 'https://connectors.replit.com/api/v2/mailer/send';
    
    public function __construct() {
        // Check for authentication token
        if (!$this->getAuthToken()) {
            throw new Exception('No authentication token found. Please ensure you\'re running in Replit environment.');
        }
    }
    
    private function getAuthToken() {
        $repl_identity = getenv('REPL_IDENTITY');
        $web_repl_renewal = getenv('WEB_REPL_RENEWAL');
        
        if ($repl_identity) {
            return 'repl ' . $repl_identity;
        } elseif ($web_repl_renewal) {
            return 'depl ' . $web_repl_renewal;
        }
        
        return null;
    }
    
    public function sendEmail($to, $subject, $text, $html = null, $attachments = null) {
        $auth_token = $this->getAuthToken();
        
        if (!$auth_token) {
            throw new Exception('No authentication token available for email sending.');
        }
        
        $data = [
            'to' => $to,
            'subject' => $subject,
            'text' => $text
        ];
        
        if ($html) {
            $data['html'] = $html;
        }
        
        if ($attachments) {
            $data['attachments'] = $attachments;
        }
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'X_REPLIT_TOKEN: ' . $auth_token
                ],
                'content' => json_encode($data)
            ]
        ]);
        
        $response = file_get_contents($this->api_url, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to send email - network error');
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse email service response');
        }
        
        return $result;
    }
    
    public function sendVendorApprovalEmail($vendor_email, $vendor_name, $business_name, $username, $temp_password) {
        $subject = "🎉 Welcome to " . SITE_NAME . " - Your Vendor Application Approved!";
        
        $text_body = "Congratulations {$vendor_name}!\n\n";
        $text_body .= "We're excited to inform you that your vendor application for '{$business_name}' has been approved!\n\n";
        $text_body .= "You can now start selling your products on " . SITE_NAME . ".\n\n";
        $text_body .= "Your Login Credentials:\n";
        $text_body .= "Username: {$username}\n";
        $text_body .= "Temporary Password: {$temp_password}\n\n";
        $text_body .= "IMPORTANT: Please log in and change your password immediately for security reasons.\n\n";
        $text_body .= "Getting Started:\n";
        $text_body .= "1. Log in to your vendor dashboard\n";
        $text_body .= "2. Change your password\n";
        $text_body .= "3. Complete your vendor profile\n";
        $text_body .= "4. Start adding your products\n\n";
        $text_body .= "If you have any questions, please don't hesitate to contact our support team.\n\n";
        $text_body .= "Welcome aboard!\n";
        $text_body .= "The " . SITE_NAME . " Team";
        
        $html_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0f172a; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .credentials { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .steps { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #666; margin-top: 30px; }
                .button { background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Welcome to " . SITE_NAME . "!</h1>
                    <p>Your vendor application has been approved</p>
                </div>
                <div class='content'>
                    <h2>Congratulations, {$vendor_name}!</h2>
                    <p>We're excited to inform you that your vendor application for <strong>'{$business_name}'</strong> has been approved!</p>
                    <p>You can now start selling your products on " . SITE_NAME . " and reach thousands of customers.</p>
                    
                    <div class='credentials'>
                        <h3>Your Login Credentials:</h3>
                        <p><strong>Username:</strong> {$username}</p>
                        <p><strong>Temporary Password:</strong> {$temp_password}</p>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ IMPORTANT:</strong> Please log in and change your password immediately for security reasons.
                    </div>
                    
                    <div class='steps'>
                        <h3>Getting Started:</h3>
                        <ol>
                            <li>Log in to your vendor dashboard</li>
                            <li>Change your password</li>
                            <li>Complete your vendor profile</li>
                            <li>Start adding your products</li>
                        </ol>
                    </div>
                    
                    <p>If you have any questions, please don't hesitate to contact our support team.</p>
                    
                    <div class='footer'>
                        <p>Welcome aboard!<br>The " . SITE_NAME . " Team</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        
        try {
            return $this->sendEmail($vendor_email, $subject, $text_body, $html_body);
        } catch (Exception $e) {
            error_log("Failed to send vendor approval email: " . $e->getMessage());
            throw $e;
        }
    }
}
?>