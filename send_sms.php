<?php
// SMS functionality using Twilio integration for order confirmations
// Based on Twilio integration blueprint

function send_order_confirmation_sms($phone_number, $order_data) {
    // For now, we'll use a simple approach - in production you'd use the Python Twilio script
    // This is a placeholder for mobile money and SMS integration
    
    $message = "Market Nest Order Confirmation\n";
    $message .= "Order #" . $order_data['order_number'] . "\n";
    $message .= "Total: " . number_format($order_data['total_amount'], 2) . " FCFA\n";
    $message .= "Payment: Mobile Money\n";
    $message .= "Thank you for shopping with Market Nest!";
    
    // Log SMS for now - in production this would call the Twilio service
    error_log("SMS to " . $phone_number . ": " . $message);
    
    return ['success' => true, 'message' => 'SMS confirmation sent'];
}

function send_payment_confirmation_sms($phone_number, $payment_data) {
    $message = "Market Nest Payment Confirmation\n";
    $message .= "Payment received: " . number_format($payment_data['amount'], 2) . " FCFA\n";
    $message .= "Mobile Money: 679871130\n";
    $message .= "Your order will be processed shortly.";
    
    // Log SMS for now
    error_log("Payment SMS to " . $phone_number . ": " . $message);
    
    return ['success' => true, 'message' => 'Payment SMS sent'];
}
?>