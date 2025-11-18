<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update - {{ $order->order_number }}</title>
</head>
<body style="font-family: 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #111827; max-width: 600px; margin: 0 auto; padding: 0; background-color: #f9fafb;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: bold;">
                                Order Status Updated
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #111827; margin-top: 0; font-size: 24px; font-weight: 700;">
                                Hi {{ $order->seller->name }},
                            </h2>
                            
                            <p style="color: #4b5563; font-size: 15px; margin-bottom: 20px; line-height: 1.7;">
                                The status of an order in your store has been updated. Here are the details:
                            </p>
                            
                            <!-- Order Details Card -->
                            <div style="background-color: #ffffff; border: 1px solid #e5e7eb; @if($order->status === 'completed') border-left: 4px solid #10b981; @elseif($order->status === 'cancelled') border-left: 4px solid #ef4444; @else border-left: 4px solid #3b82f6; @endif border-radius: 8px; padding: 24px; margin: 24px 0;">
                                <h3 style="color: #111827; margin-top: 0; margin-bottom: 20px; font-size: 18px; font-weight: 600; border-bottom: 1px solid #e5e7eb; padding-bottom: 12px;">
                                    Order Details
                                </h3>
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding: 8px 0; color: #4b5563; font-size: 14px;"><strong style="color: #111827;">Order Number:</strong></td>
                                        <td style="padding: 8px 0; color: #111827; font-size: 14px; text-align: right; font-weight: 600;">{{ $order->order_number }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #4b5563; font-size: 14px;"><strong style="color: #111827;">Customer:</strong></td>
                                        <td style="padding: 8px 0; color: #4b5563; font-size: 14px; text-align: right;">{{ $order->buyer->name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #4b5563; font-size: 14px;"><strong style="color: #111827;">Email:</strong></td>
                                        <td style="padding: 8px 0; color: #4b5563; font-size: 14px; text-align: right;">{{ $order->buyer->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #4b5563; font-size: 14px;"><strong style="color: #111827;">Previous Status:</strong></td>
                                        <td style="padding: 8px 0; color: #4b5563; font-size: 14px; text-align: right;">{{ ucfirst($previousStatus) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #4b5563; font-size: 14px;"><strong style="color: #111827;">Current Status:</strong></td>
                                        <td style="padding: 8px 0; text-align: right;">
                                            <span style="background-color: @if($order->status === 'completed') #d1fae5; color: #065f46; @elseif($order->status === 'cancelled') #fee2e2; color: #991b1b; @else #dbeafe; color: #1e40af; @endif padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #4b5563; font-size: 14px;"><strong style="color: #111827;">Total Amount:</strong></td>
                                        <td style="padding: 8px 0; color: #111827; font-size: 16px; text-align: right; font-weight: 700;">Rs. {{ $orderTotal }}</td>
                                    </tr>
                                </table>
                            </div>
                            
                            @if($order->status === 'completed')
                                <div style="background-color: #d1fae5; border-left: 4px solid #10b981; border-radius: 6px; padding: 16px; margin: 24px 0;">
                                    <p style="color: #065f46; margin: 0; font-size: 14px; line-height: 1.6;">
                                        ✅ This order has been marked as completed. The customer has been notified.
                                    </p>
                                </div>
                            @elseif($order->status === 'cancelled')
                                <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; border-radius: 6px; padding: 16px; margin: 24px 0;">
                                    <p style="color: #991b1b; margin: 0; font-size: 14px; line-height: 1.6;">
                                        ⚠️ This order has been cancelled. The customer has been notified.
                                    </p>
                                </div>
                            @elseif($order->status === 'shipped')
                                <div style="background-color: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 6px; padding: 16px; margin: 24px 0;">
                                    <p style="color: #1e40af; margin: 0; font-size: 14px; line-height: 1.6;">
                                        📦 This order has been shipped. The customer has been notified.
                                    </p>
                                </div>
                            @endif
                            
                            <p style="color: #4b5563; font-size: 14px; margin-top: 20px; line-height: 1.7;">
                                You can view and manage all your orders in your seller dashboard.
                            </p>
                            
                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.frontend_url', url('/seller/orders')) }}" style="display: inline-block; background: #f97316; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                            View Seller Dashboard
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #111827; padding: 24px; text-align: center;">
                            <p style="color: #ffffff; margin: 0 0 8px 0; font-size: 16px; font-weight: 600;">
                                {{ $appName }}
                            </p>
                            <p style="color: #9ca3af; margin: 0; font-size: 12px;">
                                © {{ date('Y') }} {{ $appName }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
