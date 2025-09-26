<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            width: 100%;
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #4f4b51;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
        }

        .header {
            padding: 10px 0;
            text-align: center;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .content {
            padding: 20px;
        }

        .logo {
            text-align: center;
        }

        .code-box {
            background: #037ac5;
            padding: 20px;
            border-radius: 8px;
            font-size: 28px;
            letter-spacing: 6px;
            text-align: center;
            margin: 30px 0;
            font-family: monospace;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .expiry-notice {
            color: #e74c3c;
            font-weight: bold;
            background: #fce4e3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
        }

        h1 {
            color: #4f4b51;
            font-size: 28px;
            margin-bottom: 20px;
            text-align: center;
        }

        p {
            color: #4f4b51;
            font-size: 16px;
            text-align: center;
            margin-bottom: 20px;
        }

        a {
            color: #037ac5;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #1750b2;
        }

        hr {
            border: 0;
            height: 1px;
            background: rgba(120, 119, 121, 0.27);
            margin: 20px 0;
        }

        .footer-wrapper {
            background: #4f4b51;
            padding: 20px 0;
            text-align: center;
            border-radius: 8px;
        }

        .footer {
            max-width: 600px;
            margin: 0 auto;
            color: #ffffff;
        }

        .social-links img {
            margin: 0 10px;
            transition: opacity 0.3s ease;
            width: 34px;
            height: 34px;
        }

        .social-links img:hover {
            opacity: 0.8;
        }

        .legal-links {
            margin-top: 10px;
        }

        .legal-links a {
            color: #ffffff;
            font-size: 13px;
            margin: 0 10px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .legal-links a:hover {
            color: #037ac5;
        }

        @media screen and (max-width: 600px) {
            .container {
                padding: 20px;
            }

            .content {
                padding: 10px;
            }

            h1 {
                font-size: 24px;
            }

            .code-box {
                font-size: 24px;
                padding: 15px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="#" target="_blank">
                <img src="{{ asset('logo/logo.png') }}" alt="Restaurent Logo" class="logo" width="150">
            </a>
        </div>

        <hr>

        <div class="content">
            <h1>Verify Your Email</h1>
            <p>Your confirmation code is below. Enter it in the open browser window.It will help you complete Verify Your Email.</p>
            <div class="code-box">{{ $otp }}</div>
            <p class="expiry-notice">⚠️ This link expires in 10 Minutes</p>
            <p>If you didn't request this email, there's nothing to worry about — you can safely ignore it.</p>
        </div>

        <hr>

        <div class="footer-wrapper">
            <div class="footer">
                <div class="social-links">
                    <a href="#" target="_blank">Facebook</a> |
                    <a href="#" target="_blank">Instagram</a> |
                    <a href="#" target="_blank" title="Follow us on YouTube">YouTube</a>
                </div>

                <div class="legal-links">
                    <a href="https://restaurent.biolab-ye.net/privacy">Privacy Policy</a> |
                    <a href="https://restaurent.biolab-ye.net/terms">Terms of Service</a> |
                    <a href="https://restaurent.biolab-ye.net/contact">Contact Us</a>
                </div>

                <p>Restaurent © {{date("Y")}} All Rights Reserved</p>
            </div>
        </div>

    </div>
</body>
</html>