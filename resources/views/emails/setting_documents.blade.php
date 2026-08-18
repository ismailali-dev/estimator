@php
    $senderName = config('mail.from.name') ?: 'EZ Estimater';
    $senderEmail = config('mail.from.address') ?: 'contact@ezestimater.com';
    $appUrl = rtrim(config('app.url') ?: 'https://api.ezestimater.com', '/');
    $websiteUrl = 'https://www.ezestimater.com';
    $logoUrl = $appUrl . '/assets/img/logo.png';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light">
    <title>Your Document Is Ready</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0; mso-table-rspace: 0; }
        table { border-collapse: separate; }
        img { -ms-interpolation-mode: bicubic; }
        @media only screen and (max-width: 620px) {
            .email-shell { width: 100% !important; border-radius: 0 !important; }
            .header-pad { padding: 22px !important; }
            .brand-logo { width: 155px !important; }
            .tagline { display: none !important; }
            .hero-pad { padding: 30px 22px 28px !important; }
            .headline { font-size: 29px !important; line-height: 36px !important; }
            .content-pad { padding-left: 20px !important; padding-right: 20px !important; }
            .trust-item { display: block !important; width: auto !important; border-right: 0 !important; border-bottom: 1px solid #d7e0ec; }
            .trust-last { border-bottom: 0 !important; }
            .contact-copy { font-size: 12px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f4f6ed; color:#071638; font-family:Arial, Helvetica, sans-serif;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent; mso-hide:all;">Your document is ready to securely review and sign.</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; background-color:#f4f6ed;">
        <tr><td align="center" style="padding:18px 12px 28px;">
            <table role="presentation" width="760" cellspacing="0" cellpadding="0" border="0" class="email-shell" style="width:760px; max-width:100%; background-color:#ffffff; border:1px solid #dce4ef; border-radius:18px; box-shadow:0 10px 32px rgba(20,45,80,.10); overflow:hidden;">
                <tr><td class="header-pad" style="padding:25px 48px; border-bottom:1px solid #dce4ef; background:#ffffff;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>
                        <td valign="middle" class="brand-logo" style="color:#071638; font-size:31px; line-height:36px; font-weight:800; letter-spacing:-1px;"><span style="color:#071638;">EZ</span> <span style="color:#bfd51b;">Estimater</span></td>
                        <td align="right" valign="middle" class="tagline" style="color:#52627d; font-size:14px; line-height:20px; white-space:nowrap;">Construction Estimating Made <strong style="color:#172746;">Simple</strong></td>
                    </tr></table>
                </td></tr>
                <tr><td align="center" class="hero-pad" style="padding:38px 42px 34px; background-color:#ffffff; background-image:linear-gradient(145deg,#f8faef 0%,#ffffff 43%,#f7faeb 100%);">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                        <tr><td align="center" style="padding-bottom:21px;">
                            <table role="presentation" width="154" cellspacing="0" cellpadding="0" border="0" style="width:154px; background:#ffffff; border:1px solid #e2e9f3; border-radius:15px; box-shadow:0 13px 25px rgba(21,92,180,.15);">
                                <tr><td style="padding:16px 17px 7px;"><span style="display:inline-block; padding:3px 6px; border-radius:3px; background:#e74350; color:#ffffff; font-size:9px; line-height:12px; font-weight:bold;">PDF</span></td><td align="right" valign="top"><span style="display:inline-block; width:40px; height:40px; margin:-17px -15px 0 0; border-radius:50%; background:#0caf70; color:#ffffff; font-size:25px; line-height:40px; font-weight:bold; text-align:center; box-shadow:0 5px 11px rgba(12,175,112,.28);">&#10003;</span></td></tr>
                                <tr><td colspan="2" style="padding:0 18px 16px;"><div style="height:7px; width:102px; margin:5px 0; border-radius:5px; background:#e2e7ef;"></div><div style="height:7px; width:106px; margin:7px 0; border-radius:5px; background:#e2e7ef;"></div><div style="height:7px; width:70px; margin:7px 0; border-radius:5px; background:#e2e7ef;"></div><div style="padding-top:7px; border-bottom:2px solid #17467f; color:#0c2859; font-family:Georgia,serif; font-size:18px; font-style:italic; line-height:21px; text-align:right; transform:rotate(-3deg);">Signed</div></td></tr>
                            </table>
                        </td></tr>
                        <tr><td align="center" class="headline" style="padding:0 0 14px; color:#061536; font-size:37px; line-height:44px; font-weight:800; letter-spacing:-1px;">Your <span style="color:#bfd51b; border-bottom:3px solid #bfd51b;">Document</span> Is Ready</td></tr>
                        <tr><td align="center" style="padding:9px 0 25px; color:#172746; font-size:16px; line-height:25px;"><span style="font-size:17px;">Hi there,</span><br>EZ Estimater has sent you a document to review and sign electronically.<br>It only takes a minute to view and complete.@if(!empty($description))<br><span style="color:#52627d;">{{ $description }}</span>@endif</td></tr>
                        <tr><td align="center"><table role="presentation" width="390" cellspacing="0" cellpadding="0" border="0" style="width:390px; max-width:100%;"><tr><td align="center" style="border-radius:13px; background:#bfd51b; background-image:linear-gradient(135deg,#cde326,#a9c512); box-shadow:0 10px 20px rgba(155,181,16,.28);"><a href="{{ $signingLink }}" style="display:block; padding:17px 20px; color:#101406; font-size:20px; line-height:24px; font-weight:bold; text-align:center; text-decoration:none;"><span style="font-size:22px;">&#128196;</span>&nbsp;&nbsp; View Document &nbsp;&nbsp;<span style="font-size:25px; font-weight:normal;">&#8594;</span></a></td></tr></table></td></tr>
                        <tr><td align="center" style="padding:15px 0 0; color:#52627d; font-size:14px; line-height:20px;">&#128737;&nbsp; Secure &bull; Fast &bull; Paperless</td></tr>
                    </table>
                </td></tr>
                <tr><td class="content-pad" style="padding:0 52px 22px;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f2f6fc; border-radius:13px;"><tr>
                    <td width="33.33%" align="center" class="trust-item" style="padding:15px 7px; color:#172746; font-size:13px; border-right:1px solid #d7e0ec;"><span style="color:#9caf0d; font-size:18px;">&#128737;</span>&nbsp; Secure &amp; Encrypted</td>
                    <td width="33.33%" align="center" class="trust-item" style="padding:15px 7px; color:#172746; font-size:13px; border-right:1px solid #d7e0ec;"><span style="color:#9caf0d; font-size:18px;">&#9201;</span>&nbsp; Sign in Minutes</td>
                    <td width="33.33%" align="center" class="trust-item trust-last" style="padding:15px 7px; color:#172746; font-size:13px;"><span style="color:#9caf0d; font-size:18px;">&#127807;</span>&nbsp; Eco-Friendly</td>
                </tr></table></td></tr>
                <tr><td class="content-pad" style="padding:0 52px 25px;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f7df; border-radius:14px;"><tr>
                    <td width="76" align="center" style="padding:18px 8px 18px 20px;"><div style="width:52px; height:52px; border-radius:50%; background:#ffffff; border:1px solid #dfe8ac; color:#bfd51b; font-size:20px; line-height:52px; font-weight:800; box-shadow:0 4px 12px rgba(17,105,220,.12);">EZ</div></td>
                    <td class="contact-copy" style="padding:18px 8px; color:#172746; font-size:13px; line-height:20px;"><strong style="display:block; color:#071638; font-size:16px;">{{ $senderName }}</strong><a href="mailto:{{ $senderEmail }}" style="color:#819400; font-size:14px; text-decoration:underline;">{{ $senderEmail }}</a><br>Need help? Reply to this email or contact our team anytime.</td>
                    <td width="38" align="center" style="padding-right:18px; color:#30415e; font-size:27px;">&#8250;</td>
                </tr></table></td></tr>
                <tr><td align="center" class="content-pad" style="padding:0 45px 30px; color:#53637d; font-size:13px; line-height:21px;"><div style="height:1px; background:#d9e1eb; margin-bottom:20px;"></div>Powering smarter, faster, and more accurate construction estimates.<br><a href="{{ $websiteUrl }}" style="display:inline-block; padding-top:10px; color:#819400; text-decoration:underline;">www.ezestimater.com</a><span style="display:inline-block; padding-left:22px; color:#6c7a91; font-size:15px;">&#9679;&nbsp;&nbsp; &#9679;&nbsp;&nbsp; &#9679;</span></td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
