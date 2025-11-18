<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Namaste - Welcome to {{ $appName }}</title>
</head>
<body style="font-family: 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #111827; max-width: 600px; margin: 0 auto; padding: 0; background-color: #f9fafb;">
    <!-- Main Container -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 36px; font-weight: bold;">
                                🙏 नमस्ते
                            </h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 18px; opacity: 0.95;">
                                Namaste!
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #111827; margin-top: 0; font-size: 28px; font-weight: 700;">
                                Welcome, {{ $user->name }}! 🎉
                            </h2>
                            
                            <p style="color: #4b5563; font-size: 15px; margin-bottom: 20px; line-height: 1.7;">
                                We're absolutely delighted to have you join the <strong style="color: #f97316;">{{ $appName }}</strong> family! Your journey into the world of elegant fashion and style begins right here.
                            </p>
                            
                            <!-- Fashion Focus Box -->
                            <div style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border-left: 4px solid #ff6f00; padding: 20px; border-radius: 8px; margin: 25px 0;">
                                <h3 style="color: #ea580c; margin-top: 0; font-size: 20px; font-weight: 600;">
                                    ✨ Your Fashion Journey Awaits
                                </h3>
                                <p style="color: #78350f; margin-bottom: 15px; font-size: 14px; line-height: 1.6;">
                                    Discover the latest trends, exclusive collections, and timeless pieces that celebrate your unique style. From traditional elegance to modern sophistication, we've got something special just for you!
                                </p>
                            </div>
                            
                            <!-- Features List -->
                            <div style="margin: 30px 0;">
                                <h3 style="color: #111827; font-size: 20px; margin-bottom: 15px; font-weight: 600;">
                                    What's Next? 🛍️
                                </h3>
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e0e0e0;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="padding-right: 15px; vertical-align: top; width: 40px;">
                                                        <div style="width: 35px; height: 35px; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; font-weight: bold;">1</div>
                                                    </td>
                                                    <td>
                                                        <strong style="color: #111827; display: block; margin-bottom: 5px; font-size: 14px;">Explore Our Collections</strong>
                                                        <p style="color: #4b5563; margin: 0; font-size: 13px; line-height: 1.6;">Browse through curated fashion pieces that blend traditional Nepali aesthetics with contemporary style.</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e0e0e0;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="padding-right: 15px; vertical-align: top; width: 40px;">
                                                        <div style="width: 35px; height: 35px; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; font-weight: bold;">2</div>
                                                    </td>
                                                    <td>
                                                        <strong style="color: #2c3e50; display: block; margin-bottom: 5px;">Track Your Orders</strong>
                                                        <p style="color: #666; margin: 0; font-size: 14px;">Stay updated on your fashion finds from order placement to delivery right to your doorstep.</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="padding-right: 15px; vertical-align: top; width: 40px;">
                                                        <div style="width: 35px; height: 35px; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; font-weight: bold;">3</div>
                                                    </td>
                                                    <td>
                                                        <strong style="color: #2c3e50; display: block; margin-bottom: 5px;">Enjoy Exclusive Benefits</strong>
                                                        <p style="color: #666; margin: 0; font-size: 14px;">Get access to special offers, early access to new collections, and personalized style recommendations.</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Special Welcome Offer -->
                            <div style="background: linear-gradient(135deg, #fff9c4 0%, #fff59d 100%); border: 2px dashed #f57f17; border-radius: 12px; padding: 25px; margin: 30px 0; text-align: center;">
                                <div style="background-color: #f57f17; color: #ffffff; display: inline-block; padding: 8px 20px; border-radius: 20px; font-size: 12px; font-weight: bold; letter-spacing: 1px; margin-bottom: 15px;">
                                    🎁 SPECIAL WELCOME OFFER
                                </div>
                                <h3 style="color: #e65100; margin: 0 0 10px 0; font-size: 24px; font-weight: 700;">
                                    10% OFF Your First Order! 🎉
                                </h3>
                                <p style="color: #5d4037; margin: 0 0 20px 0; font-size: 15px; line-height: 1.6;">
                                    We're so happy to welcome you! Use this special discount code on your first order to get started.
                                </p>
                                
                                <!-- Discount Code Box -->
                                <div style="background-color: #ffffff; border: 2px solid #f57f17; border-radius: 8px; padding: 15px; margin: 20px 0; display: inline-block; min-width: 250px;">
                                    <p style="color: #666; margin: 0 0 8px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Your Discount Code:</p>
                                    <div style="background-color: #fff3e0; border: 2px dashed #ff6f00; border-radius: 6px; padding: 12px 20px; margin: 10px 0;">
                                        <span style="font-family: 'Courier New', monospace; font-size: 28px; font-weight: bold; color: #f97316; letter-spacing: 3px;">WELCOME10</span>
                                    </div>
                                    <p style="color: #666; margin: 10px 0 0 0; font-size: 12px;">
                                        Copy this code at checkout ✨
                                    </p>
                                </div>
                                
                                <!-- CTA Button -->
                                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 25px;">
                                    <tr>
                                        <td align="center">
                                            <a href="{{ config('app.frontend_url', url('/')) }}" style="display: inline-block; background: #f97316; color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                                Shop Now & Use WELCOME10 🛒
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Reminder About Discount -->
                            <div style="background-color: #e3f2fd; border-left: 4px solid #1976d2; padding: 15px 20px; border-radius: 6px; margin: 30px 0;">
                                <p style="color: #1565c0; margin: 0; font-size: 14px; line-height: 1.6;">
                                    💡 <strong>Remember:</strong> Don't forget to use your code <strong style="color: #f97316;">WELCOME10</strong> at checkout to save 10% on your first order!
                                </p>
                            </div>
                            
                            <!-- Nepali Quote Section -->
                            <div style="background-color: #f9f9f9; border-radius: 8px; padding: 20px; margin: 30px 0; text-align: center; border: 1px solid #e0e0e0;">
                                <p style="color: #666; font-style: italic; font-size: 15px; margin: 0; line-height: 1.7;">
                                    "Fashion is not just about what you wear, it's about expressing your unique story with style and grace." 
                                    <br><span style="color: #f97316; font-weight: 600;">- {{ $appName }} Family</span>
                                </p>
                            </div>
                            
                            <!-- Closing -->
                            <p style="color: #4b5563; font-size: 15px; margin-top: 30px; line-height: 1.7;">
                                We're here to help you look and feel your absolute best. If you have any questions or need assistance, our friendly support team is always ready to help!
                            </p>
                            
                            <p style="color: #4b5563; font-size: 15px; margin-top: 20px; line-height: 1.7;">
                                Happy shopping, and welcome to the family! 🙏✨
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #111827; padding: 30px; text-align: center;">
                            <p style="color: #ffffff; margin: 0 0 10px 0; font-size: 16px; font-weight: 600;">
                                {{ $appName }}
                            </p>
                            <p style="color: #9ca3af; margin: 0 0 15px 0; font-size: 13px; line-height: 1.6;">
                                Your trusted fashion destination in Nepal<br>
                                Style | Quality | Tradition | Innovation
                            </p>
                            <p style="color: #6b7280; margin: 0; font-size: 12px;">
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
