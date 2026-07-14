@php
    $firstDocument = $documents instanceof \Illuminate\Support\Collection ? $documents->first() : collect($documents ?? [])->first();
    $documentTitle = data_get($firstDocument, 'document_name')
        ?: data_get($firstDocument, 'name')
        ?: 'Document';
    $senderName = config('mail.from.name') ?: 'Contracts';
    $senderEmail = config('mail.from.address');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Review</title>
</head>
<body style="margin:0; padding:0; background:#242424; font-family:Arial, Helvetica, sans-serif; color:#f3f3f3;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#242424; margin:0; padding:0;">
        <tr>
            <td align="center" style="padding:28px 16px 40px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:680px; margin:0 auto;">
                    <!-- <tr>
                        <td style="padding:0 0 16px; color:#f2f2f2; font-size:16px; line-height:24px; font-weight:700;">
                            Here is your document: {{ $documentTitle }}
                        </td>
                    </tr> -->

                    <tr>
                        <td align="center" style="background:rgb(201, 218, 43); border-radius:16px; padding:30px 24px 38px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                                <tr>
                                    <td align="center" style="padding-bottom:24px;">
                                        <div style="width:52px; height:52px; border-radius:8px; background:#ffffff; color:#202124; font-size:30px; line-height:52px; font-weight:700;">
                                            &#10003;
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom:26px; color:#2d2430; font-size:17px; line-height:24px;">
                                        Your Document Is Ready
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <a href="{{ $signingLink }}" style="display:inline-block; background:#d1ff61; border-radius:7px; color:#161021; font-size:30px; line-height:30px; font-weight:700; text-decoration:none; padding:18px 36px;">
                                            View Document
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>   

                    <tr>
                        <td style="padding:32px 0 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #9d9d9d; border-radius:16px;">
                                <tr>
                                    <td style="padding:34px 50px 38px; color:#d9d9d9; font-size:15px; line-height:24px;">
                                        <div style="font-size:16px; line-height:22px; font-weight:700; color:#f3f3f3;">
                                            {{ $senderName }}
                                        </div>
                                        @if($senderEmail)
                                            <div style="padding-top:2px;">
                                                <a href="mailto:{{ $senderEmail }}" style="color:#b985ff; text-decoration:underline;">{{ $senderEmail }}</a>
                                            </div>
                                        @endif

                                        <div style="height:30px; line-height:30px;">&nbsp;</div>

                                        <div>
                                            {{ $senderName }} has sent you a document to review and sign electronically.
                                        </div>

                                        @if(!empty($description))
                                            <div style="height:12px; line-height:12px;">&nbsp;</div>
                                            <div>
                                                {{ $description }}
                                            </div>
                                        @endif

                                        <div style="height:16px; line-height:16px;">&nbsp;</div>

                                        <!-- <div>
                                            Select View Document to securely review and complete your documents.
                                        </div> -->
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

