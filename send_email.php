<?php
// Email functionality for order confirmations
// Simple email implementation - in production you'd use a proper email service

function send_order_confirmation_email($user_email, $order_data) {
    $subject = "Order Confirmation - Market Nest - Order #" . $order_data['order_number'];
    
    $message = "
    <html>
    <head>
        <title>Order Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #0f172a; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .footer { background: #e5e7eb; padding: 10px; text-align: center; font-size: 12px; }
            .highlight { background: #10b981; color: white; padding: 10px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Market Nest</h1>
                <h2>Order Confirmation</h2>
            </div>
            <div class='content'>
                <h3>Thank you for your order!</h3>
                <p><strong>Order Number:</strong> " . $order_data['order_number'] . "</p>
                <p><strong>Total Amount:</strong> " . number_format($order_data['total_amount'], 2) . " FCFA</p>
                <p><strong>Payment Method:</strong> " . ucwords(str_replace('_', ' ', $order_data['payment_method'])) . "</p>
                
                " . ($order_data['payment_method'] == 'mobile_money_cameroon' ? 
                "<div class='highlight'>
                    <h4>Mobile Money Payment Instructions</h4>
                    <p>Please send <strong>" . number_format($order_data['total_amount'], 2) . " FCFA</strong> to <strong>679871130</strong></p>
                    <p>Include your order number <strong>" . $order_data['order_number'] . "</strong> in the transaction message.</p>
                </div>" : "") . "
                
                <p>We'll process your order and send you tracking information once it ships.</p>
                <p>Thank you for choosing Market Nest!</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Market Nest. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@marketnest.com" . "\r\n";
    
    // For development, log the email instead of sending
    error_log("Email to " . $user_email . " - Subject: " . $subject);
    error_log("Email content: " . strip_tags($message));
    
    // In production, uncomment this line:
    // mail($user_email, $subject, $message, $headers);
    
    return ['success' => true, 'message' => 'Email confirmation sent'];
}

function send_payment_confirmation_email($user_email, $payment_data) {
    $subject = "Payment Confirmed - Market Nest - Order #" . $payment_data['order_number'];
    
    $message = "
    <html>
    <head>
        <title>Payment Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #10b981; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Payment Confirmed!</h1>
            </div>
            <div class='content'>
                <h3>Your payment has been received!</h3>
                <p><strong>Order Number:</strong> " . $payment_data['order_number'] . "</p>
                <p><strong>Amount Received:</strong> " . number_format($payment_data['amount'], 2) . " FCFA</p>
                <p>Your order is now being processed and will ship soon!</p>
            </div>
        </div>
    </body>
    </html>";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@marketnest.com" . "\r\n";
    
    // Log for development
    error_log("Payment email to " . $user_email . " - Subject: " . $subject);
    
    return ['success' => true, 'message' => 'Payment email sent'];
}
?>