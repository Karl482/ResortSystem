<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title'] ?? 'Promotion') ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f7fa;
            padding: 20px;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }
        .email-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        .promotion-image {
            width: 100%;
            height: auto;
            display: block;
            max-height: 400px;
            object-fit: cover;
        }
        .email-body {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #333333;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .promotion-title {
            font-size: 24px;
            color: #667eea;
            margin-bottom: 20px;
            font-weight: 700;
            line-height: 1.4;
        }
        .promotion-message {
            font-size: 16px;
            color: #555555;
            margin-bottom: 30px;
            line-height: 1.8;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .discount-code-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .discount-label {
            color: #ffffff;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .discount-code {
            background-color: #ffffff;
            color: #f5576c;
            font-size: 24px;
            font-weight: 700;
            padding: 12px 24px;
            border-radius: 6px;
            display: inline-block;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
        .expiration-notice {
            color: #ffffff;
            font-size: 13px;
            margin-top: 10px;
            font-weight: 500;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            transition: transform 0.2s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .cta-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer-text {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .social-links a:hover {
            text-decoration: underline;
        }
        .unsubscribe {
            font-size: 12px;
            color: #999999;
            margin-top: 15px;
            line-height: 1.5;
        }
        .unsubscribe a {
            color: #667eea;
            text-decoration: none;
        }
        .unsubscribe a:hover {
            text-decoration: underline;
        }
        .spacer {
            height: 20px;
        }
        
        /* Mobile Responsive */
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            .email-header {
                padding: 25px 15px;
            }
            .email-header h1 {
                font-size: 22px;
            }
            .email-body {
                padding: 30px 20px;
            }
            .greeting {
                font-size: 16px;
            }
            .promotion-title {
                font-size: 20px;
            }
            .promotion-message {
                font-size: 15px;
            }
            .discount-code-section {
                padding: 15px;
            }
            .discount-code {
                font-size: 18px;
                padding: 10px 15px;
            }
            .cta-button {
                padding: 14px 30px;
                font-size: 16px;
                display: block;
                width: 100%;
            }
            .email-footer {
                padding: 25px 20px;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-container {
                background-color: #ffffff;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1><?= !empty($data['title']) ? htmlspecialchars($data['title']) : '🌴 Special Promotion Just For You!' ?></h1>
        </div>

        <!-- Promotion Image (flexible - only shows if provided) -->
        <?php if (!empty($data['image_url'])): ?>
        <img src="<?= htmlspecialchars($data['image_url']) ?>" alt="<?= htmlspecialchars($data['title'] ?? 'Promotion') ?>" class="promotion-image">
        <?php endif; ?>

        <!-- Body -->
        <div class="email-body">
            <!-- Greeting (flexible - adapts to available name) -->
            <?php if (!empty($data['customer_name'])): ?>
            <div class="greeting">
                Hello <?= htmlspecialchars($data['customer_name']) ?>! 👋
            </div>
            <?php endif; ?>

            <!-- Title (flexible - only shows if different from header) -->
            <?php if (!empty($data['title']) && empty($data['image_url'])): ?>
            <div class="promotion-title">
                <?= htmlspecialchars($data['title']) ?>
            </div>
            <?php endif; ?>

            <!-- Message (flexible - handles line breaks) -->
            <?php if (!empty($data['message'])): ?>
            <div class="promotion-message">
                <?= nl2br(htmlspecialchars($data['message'])) ?>
            </div>
            <?php endif; ?>

            <!-- Discount Code Section (flexible - only shows if code provided) -->
            <?php if (!empty($data['discount_code'])): ?>
            <div class="discount-code-section">
                <div class="discount-label">Use Promo Code</div>
                <div class="discount-code"><?= htmlspecialchars($data['discount_code']) ?></div>
                <?php if (!empty($data['expiration_date'])): ?>
                <div class="expiration-notice">
                    ⏰ Valid until <?= date('F j, Y', strtotime($data['expiration_date'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif (!empty($data['expiration_date'])): ?>
            <!-- Show expiration without code box if no code provided -->
            <div style="text-align: center; margin-bottom: 30px; padding: 15px; background-color: #fff3cd; border-radius: 8px; color: #856404;">
                <strong>⏰ Limited Time Offer</strong><br>
                Valid until <?= date('F j, Y', strtotime($data['expiration_date'])) ?>
            </div>
            <?php endif; ?>

            <!-- CTA Button (flexible - customizable text) -->
            <div class="cta-container">
                <a href="<?= !empty($data['cta_link']) ? htmlspecialchars($data['cta_link']) : (defined('BASE_URL') ? BASE_URL . '/public/index.php?controller=resort&action=list' : '#') ?>" class="cta-button">
                    <?= htmlspecialchars($data['cta_button_text'] ?? 'Book Now') ?>
                </a>
            </div>
        </div>

        <!-- Footer (flexible - can be customized) -->
        <div class="email-footer">
            <div class="footer-text">
                <strong><?= !empty($data['company_name']) ? htmlspecialchars($data['company_name']) : 'Resort Booking System' ?></strong><br>
                <?= !empty($data['tagline']) ? htmlspecialchars($data['tagline']) : 'Your perfect getaway awaits!' ?>
            </div>
            
            <?php if (!empty($data['show_social_links']) || !isset($data['show_social_links'])): ?>
            <div class="social-links">
                <a href="<?= $data['facebook_url'] ?? '#' ?>">Facebook</a> • 
                <a href="<?= $data['instagram_url'] ?? '#' ?>">Instagram</a> • 
                <a href="<?= $data['twitter_url'] ?? '#' ?>">Twitter</a>
            </div>
            <?php endif; ?>

            <div class="unsubscribe">
                You received this email because you're a valued customer.<br>
                <a href="<?= $data['unsubscribe_url'] ?? '#' ?>">Unsubscribe</a> from promotional emails
            </div>
        </div>
    </div>
</body>
</html>
