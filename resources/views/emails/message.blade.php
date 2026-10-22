{{-- Simple branded email: $heading, $lines (array of paragraphs), optional $button ['label', 'url'] and $details [label => value] --}}
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $heading }}</title></head>
<body style="margin:0;background:#F6F7F9;font-family:Arial,Helvetica,sans-serif;color:#11161B">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #E4E7EA;border-radius:12px">
                <tr><td style="padding:24px 28px;border-bottom:1px solid #E4E7EA;font-size:22px;font-weight:bold">Skoo<span style="color:#FF5B35">line</span></td></tr>
                <tr><td style="padding:28px">
                    <h1 style="margin:0 0 16px;font-size:20px">{{ $heading }}</h1>
                    @foreach ($lines as $line)
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#535C64">{{ $line }}</p>
                    @endforeach
                    @if (! empty($details))
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 18px;font-size:14px">
                            @foreach ($details as $label => $value)
                                <tr><td style="padding:4px 16px 4px 0;color:#5F676E">{{ $label }}</td><td style="padding:4px 0;font-weight:bold">{{ $value }}</td></tr>
                            @endforeach
                        </table>
                    @endif
                    @if (! empty($button))
                        <a href="{{ $button['url'] }}" style="display:inline-block;background:#FF5B35;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:8px">{{ $button['label'] }}</a>
                    @endif
                </td></tr>
                <tr><td style="padding:16px 28px;border-top:1px solid #E4E7EA;font-size:12px;color:#5F676E">Skooline by AivraSol · School management system</td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
