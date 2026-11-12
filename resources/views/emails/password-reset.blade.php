<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Recupera tu contraseña</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#0f172a;padding:18px 24px;color:#ffffff;font-size:18px;font-weight:bold;">Infinity</td></tr>
<tr><td style="padding:24px;">
    <h1 style="margin:0 0 12px;font-size:20px;">Hola, {{ $name }}</h1>
    <p style="margin:0 0 20px;font-size:14px;color:#374151;line-height:1.5;">Recibimos una solicitud para restablecer tu contraseña. Presiona el botón para elegir una nueva. El enlace es válido por {{ $minutes }} minutos.</p>
    <p style="margin:0 0 24px;"><a href="{{ $url }}" style="display:inline-block;background:#0ea5e9;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;padding:12px 22px;border-radius:6px;">Restablecer contraseña</a></p>
    <p style="margin:0;font-size:12px;color:#6b7280;line-height:1.5;">Si el botón no funciona, copia este enlace en tu navegador:<br><span style="word-break:break-all;">{{ $url }}</span></p>
    <p style="margin:16px 0 0;font-size:12px;color:#6b7280;">Si no solicitaste este cambio, ignora este correo: tu contraseña seguirá igual.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
