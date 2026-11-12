<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#0f172a;padding:18px 24px;color:#ffffff;font-size:18px;font-weight:bold;">Infinity</td></tr>
<tr><td style="padding:24px;">
    <h1 style="margin:0 0 8px;font-size:20px;">{{ $heading }}</h1>
    <p style="margin:0 0 20px;font-size:14px;color:#374151;line-height:1.5;">{{ $intro }}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-top:1px solid #e5e7eb;">
        <tr><td style="padding:8px 0;color:#6b7280;width:38%;">Pedido</td><td style="padding:8px 0;font-weight:bold;">#{{ $order->id }}{{ $order->is_urgent ? ' · URGENTE' : '' }}</td></tr>
        <tr><td style="padding:8px 0;color:#6b7280;">Clínica</td><td style="padding:8px 0;">{{ $order->clinic->business_name }}</td></tr>
        @if($order->scheduled_for)
        <tr><td style="padding:8px 0;color:#6b7280;">Programado para</td><td style="padding:8px 0;">{{ $order->scheduled_for->format('d/m/Y') }}</td></tr>
        @endif
        <tr><td style="padding:8px 0;color:#6b7280;">Recolección</td><td style="padding:8px 0;">{{ $order->pickup_address }}<br><span style="color:#6b7280;">{{ $order->pickup_contact_name }} {{ $order->pickup_contact_phone }}</span></td></tr>
        @foreach($order->stops->where('type', 'pickup') as $s)
        <tr><td style="padding:8px 0;color:#6b7280;">Recolección extra {{ $s->sequence }}</td><td style="padding:8px 0;">{{ $s->address }}<br><span style="color:#6b7280;">{{ $s->contact_name }} {{ $s->contact_phone }}</span></td></tr>
        @endforeach
        <tr><td style="padding:8px 0;color:#6b7280;">Entrega</td><td style="padding:8px 0;">{{ $order->delivery_address }}<br><span style="color:#6b7280;">{{ $order->delivery_contact_name }} {{ $order->delivery_contact_phone }}</span></td></tr>
        @foreach($order->stops->where('type', 'delivery') as $s)
        <tr><td style="padding:8px 0;color:#6b7280;">Entrega extra {{ $s->sequence }}</td><td style="padding:8px 0;">{{ $s->address }}<br><span style="color:#6b7280;">{{ $s->contact_name }} {{ $s->contact_phone }}</span></td></tr>
        @endforeach
        @if($order->courier)
        <tr><td style="padding:8px 0;color:#6b7280;">Mensajero</td><td style="padding:8px 0;">{{ $order->courier->name }}</td></tr>
        @endif
        <tr><td style="padding:8px 0;color:#6b7280;">Total</td><td style="padding:8px 0;">${{ number_format((float) $order->price, 2) }} MXN</td></tr>
        @if($order->requires_invoice)
        <tr><td style="padding:8px 0;color:#6b7280;">Factura</td><td style="padding:8px 0;">Solicitada</td></tr>
        @endif
        @if($order->sample_type)
        <tr><td style="padding:8px 0;color:#6b7280;">Tipo de muestra</td><td style="padding:8px 0;">{{ $order->sample_type }}</td></tr>
        @endif
        @if($order->notes)
        <tr><td style="padding:8px 0;color:#6b7280;">Notas</td><td style="padding:8px 0;">{{ $order->notes }}</td></tr>
        @endif
    </table>
</td></tr>
<tr><td style="padding:16px 24px;background:#f9fafb;font-size:12px;color:#6b7280;">Este es un mensaje automático de Infinity. Si tienes dudas, responde a este correo o contacta al administrador.</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
