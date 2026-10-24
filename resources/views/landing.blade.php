@php
    $brand = 'Infinity App';
    $cta = $contact['client_url'] ?: '#cotizar';
    $wa = preg_replace('/\D+/', '', (string) ($contact['whatsapp'] ?? ''));
    $tel = preg_replace('/[^\d+]/', '', (string) ($contact['phone'] ?? ''));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brand }} — Mensajería en moto para muestras veterinarias</title>
    <meta name="description" content="Mensajería en moto para clínicas veterinarias: recogemos tus muestras y las entregamos al laboratorio con foto de evidencia. Pagas por kilómetro, IVA incluido.">
    <meta name="theme-color" content="#2a2c30">
    <link rel="icon" type="image/png" href="{{ asset('landing/logo.png') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $brand }} — Mensajería en moto para muestras veterinarias">
    <meta property="og:description" content="Del consultorio al laboratorio en moto, con foto de evidencia en cada paso.">
    <meta property="og:image" content="{{ asset('landing/og.jpg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@300;400;500&family=Inter:wght@400;500;600&family=Space+Grotesk:wght@300;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Mismos tokens que las apps (tema carbón + cian) */
        :root {
            --bg: oklch(0.285 0.006 255);
            --bg-deep: oklch(0.235 0.006 255);
            --card: oklch(0.34 0.007 255);
            --ink: oklch(0.97 0.003 255);
            --muted: oklch(0.78 0.01 255);
            --line: oklch(1 0 0 / 14%);
            --primary: oklch(0.72 0.17 232);
            --primary-soft: oklch(0.86 0.09 232);
            --primary-deep: oklch(0.5 0.14 240);
            --primary-ink: oklch(0.145 0 0);
            --glow: color-mix(in oklch, var(--primary) 50%, transparent);
            --radius: 8px;
            --max: 1120px;
            --font-heading: 'Space Grotesk', system-ui, sans-serif;
            --font-body: 'Inter', system-ui, sans-serif;
            --font-mono: 'IBM Plex Mono', ui-monospace, monospace;
        }

        * { box-sizing: border-box; margin: 0; }
        html { scroll-behavior: smooth; }
        body {
            background: var(--bg);
            color: var(--ink);
            font-family: var(--font-body);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }
        img { display: block; max-width: 100%; }
        a { color: inherit; }
        :focus-visible { outline: 2px solid var(--primary); outline-offset: 3px; }

        .wrap { max-width: var(--max); margin: 0 auto; padding: 0 24px; }

        svg.i {
            width: 20px; height: 20px; fill: none; stroke: currentColor;
            stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round;
        }

        /* Botones */
        .btn {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 12px 22px; border: 1.5px solid var(--ink); border-radius: var(--radius);
            background: transparent; color: var(--ink);
            font: 600 15px/1 var(--font-body); text-decoration: none; cursor: pointer;
            transition: background .2s, color .2s, opacity .2s;
        }
        .btn:hover { background: var(--ink); color: var(--primary-ink); }
        .btn-primary {
            background: var(--primary); border-color: var(--primary); color: var(--primary-ink);
            box-shadow: 0 0 16px 2px var(--glow);
        }
        .btn-primary:hover { background: var(--primary); color: var(--primary-ink); opacity: .9; }
        .btn svg { width: 10px; height: 12px; fill: currentColor; stroke: none; }

        /* Encabezado */
        .top { position: absolute; inset: 0 0 auto 0; z-index: 5; padding: 24px 0; }
        .top .wrap { display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .brand { display: inline-flex; align-items: center; text-decoration: none; }
        .brand img { height: 48px; width: auto; }
        .hero-logo { height: clamp(56px, 8vw, 84px); width: auto; margin-bottom: 28px; }
        .nav { display: flex; align-items: center; gap: 28px; font-size: 15px; }
        .nav a.nav-link { text-decoration: none; }
        .nav a.nav-link:hover { color: var(--primary); }
        .icons { display: flex; gap: 6px; }
        .icons a { display: grid; place-items: center; width: 36px; height: 36px; }
        .icons a:hover { color: var(--primary); }

        /* Hero */
        .hero {
            position: relative; isolation: isolate; overflow: hidden;
            min-height: min(100svh, 880px);
            display: flex; flex-direction: column; justify-content: flex-end;
            background: var(--bg-deep);
        }
        .hero-img {
            position: absolute; inset: 0; z-index: -2;
            width: 100%; height: 100%; object-fit: cover; object-position: 70% 50%;
        }
        .hero::after {
            content: ""; position: absolute; inset: 0; z-index: -1;
            background:
                linear-gradient(90deg, color-mix(in oklch, var(--bg-deep) 88%, transparent) 0%, color-mix(in oklch, var(--bg-deep) 50%, transparent) 46%, transparent 76%),
                linear-gradient(180deg, color-mix(in oklch, var(--bg-deep) 60%, transparent) 0%, transparent 26%, transparent 58%, var(--bg) 100%);
        }
        .hero-copy { max-width: 600px; padding: 190px 0 56px; animation: rise .9s cubic-bezier(.2,.7,.2,1) both; }
        .hero h1 {
            font-family: var(--font-heading); font-size: clamp(34px, 5.4vw, 58px);
            font-weight: 700; line-height: 1.06; letter-spacing: -.025em;
        }
        .hero .lead { max-width: 500px; margin: 20px 0 32px; font-size: clamp(16px, 2vw, 19px); color: var(--ink); opacity: .92; }
        .hero-note { margin-top: 16px; font-size: 13px; color: var(--muted); }

        .strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; padding: 26px 0 44px; }
        .strip > div {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            max-width: 280px; font-size: 13px; color: var(--ink); opacity: .9;
        }
        .strip svg.i { width: 44px; height: 44px; stroke-width: 1.3; color: var(--primary); flex: none; }

        @keyframes rise { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }

        /* Secciones */
        .section { position: relative; padding: 96px 0; overflow: hidden; }
        .section.deep { background: var(--bg-deep); }
        .ghost {
            position: absolute; left: 0; top: 40px; max-width: 100%;
            font-family: var(--font-heading); font-size: clamp(90px, 18vw, 210px); font-weight: 700;
            line-height: 1; white-space: nowrap; color: oklch(1 0 0 / 4.5%);
            pointer-events: none; user-select: none;
        }

        .section-title { position: relative; font-size: 20px; font-weight: 500; }
        .section-title strong {
            display: block; font-family: var(--font-heading);
            font-size: clamp(28px, 3.6vw, 40px); font-weight: 700; line-height: 1.1; letter-spacing: -.015em;
        }

        .about { position: relative; display: grid; grid-template-columns: 1fr 1.3fr; gap: 48px; align-items: start; }
        .about h2 { font-size: 20px; font-weight: 500; }
        .about h2 strong {
            display: block; font-family: var(--font-heading);
            font-size: clamp(30px, 4vw, 44px); font-weight: 700; line-height: 1.08; letter-spacing: -.015em;
        }
        .about p { max-width: 560px; font-size: 15px; color: var(--ink); opacity: .9; }

        /* Círculo con tarjetas */
        .ring { position: relative; height: 480px; margin-top: 72px; }
        .ring-img {
            position: absolute; left: 50%; top: 50%; z-index: 1;
            width: 400px; height: 400px; transform: translate(-50%, -50%);
            border-radius: 50%; object-fit: cover; background: var(--bg-deep);
            box-shadow: 0 0 0 1px var(--line), 0 0 70px color-mix(in oklch, var(--primary) 20%, transparent);
        }
        .fcard {
            position: absolute; z-index: 2; width: 330px; padding: 22px 26px;
            background: color-mix(in oklch, var(--card) 96%, transparent);
            border: 1px solid var(--line); border-radius: var(--radius);
            font-size: 15px; font-weight: 600; line-height: 1.4;
        }
        .fcard::before {
            content: ""; position: absolute; border-radius: 50%;
            background: radial-gradient(circle at 32% 30%, var(--primary-soft), var(--primary) 55%, var(--primary-deep));
            box-shadow: 0 0 22px color-mix(in oklch, var(--primary) 45%, transparent);
        }
        .fcard.a { left: 0; top: 30px; }
        .fcard.a::before { width: 52px; height: 52px; top: -26px; left: -26px; }
        .fcard.b { right: 0; top: 60px; }
        .fcard.b::before { width: 56px; height: 56px; top: -28px; right: -28px; }
        .fcard.c { left: 60px; bottom: 30px; }
        .fcard.c::before { width: 80px; height: 80px; bottom: -34px; left: -50px; }
        .fcard.d { right: 0; bottom: 30px; }
        .fcard.d::before { width: 34px; height: 34px; top: -14px; right: -14px; }

        /* Cómo funciona */
        .steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 32px; margin-top: 40px; }
        .step { padding-top: 20px; border-top: 1px solid var(--line); }
        .step b {
            display: block; margin-bottom: 14px; font-family: var(--font-mono);
            font-size: 34px; font-weight: 300; line-height: 1; color: var(--primary);
        }
        .step h3 { margin-bottom: 6px; font-family: var(--font-heading); font-size: 18px; font-weight: 600; }
        .step p { font-size: 14px; color: var(--muted); }

        /* Cifras */
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; text-align: center; }
        .stat strong {
            display: block; font-family: var(--font-mono); font-size: clamp(24px, 3.2vw, 36px);
            font-weight: 300; color: var(--ink); white-space: nowrap;
        }
        .stat strong::after {
            content: ""; display: block; width: 54px; height: 2px; margin: 12px auto;
            background: var(--primary); box-shadow: 0 0 10px var(--glow);
        }
        .stat span { font-size: 14px; color: var(--muted); }

        /* Cotizador */
        .quote { position: relative; display: grid; grid-template-columns: 1.25fr 1fr; gap: 56px; align-items: start; }
        .quote .sub { margin-top: 14px; font-size: 14px; color: var(--muted); }
        .form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 28px; }
        .field label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; }
        .field input {
            width: 100%; height: 44px; padding: 0 14px;
            border: 1px solid var(--line); border-radius: var(--radius);
            background: var(--bg-deep); color: var(--ink); font: 400 14px var(--font-body);
        }
        .field input::placeholder { color: oklch(0.62 0.01 255); }
        .field input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px color-mix(in oklch, var(--primary) 25%, transparent); }
        .form-actions { grid-column: 1 / -1; display: flex; flex-wrap: wrap; align-items: center; gap: 22px; margin-top: 8px; }
        .form-actions p { max-width: 320px; font-size: 12.5px; color: var(--muted); }

        .contact { padding: 36px 32px; background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); }
        .contact h3 { display: flex; align-items: center; gap: 20px; margin-bottom: 8px; font-family: var(--font-heading); font-size: 26px; font-weight: 500; }
        .contact h3::before { content: ""; width: 50px; height: 2px; background: var(--primary); box-shadow: 0 0 10px var(--glow); }
        .contact dt { margin: 20px 0 6px; font-size: 14px; color: var(--muted); }
        .contact dd { font-size: 15px; font-weight: 600; }
        .contact dd a { text-decoration: none; }
        .contact dd a:hover { color: var(--primary); }

        /* Pie */
        .foot { padding: 28px 0 40px; background: var(--bg-deep); border-top: 1px solid var(--line); }
        .foot .wrap { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 18px; font-size: 13px; color: var(--muted); }
        .foot .brand img { height: 36px; }
        .foot nav { display: flex; gap: 24px; }
        .foot a { text-decoration: none; }
        .foot nav a:hover { color: var(--primary); }

        /* CTA fijo en móvil */
        .sticky {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 20; display: none;
            padding: 12px 16px; background: color-mix(in oklch, var(--bg-deep) 94%, transparent);
            backdrop-filter: blur(8px); border-top: 1px solid var(--line);
        }
        .sticky .btn { width: 100%; justify-content: center; }

        @media (max-width: 900px) {
            .nav a.nav-link, .icons { display: none; }
            .hero-copy { padding-top: 140px; }
            .strip { grid-template-columns: 1fr; gap: 20px; }
            .strip > div { max-width: none; }
            .section { padding: 72px 0; }
            .about { grid-template-columns: 1fr; gap: 24px; }

            .ring { height: auto; display: grid; gap: 14px; margin-top: 48px; }
            .ring-img { position: static; transform: none; width: min(78vw, 340px); height: min(78vw, 340px); margin: 0 auto 10px; }
            .fcard { position: static; width: auto; }
            .fcard::before { display: none; }

            .steps { grid-template-columns: 1fr 1fr; }
            .stats { grid-template-columns: 1fr 1fr; gap: 36px 16px; }
            .quote { grid-template-columns: 1fr; gap: 40px; }
        }

        @media (max-width: 760px) {
            .sticky { display: block; }
            body { padding-bottom: 76px; }
        }

        @media (max-width: 560px) {
            .form { grid-template-columns: 1fr; }
            .steps { grid-template-columns: 1fr; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .hero-copy { animation: none; }
        }
    </style>
</head>
<body>

{{-- Iconos reutilizables --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <symbol id="i-moto" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M9 17.5h5l-3-7h4l2.5 3"/><path d="M15 6h3l1 3"/></symbol>
    <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></symbol>
    <symbol id="i-camera" viewBox="0 0 24 24"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></symbol>
    <symbol id="i-chat" viewBox="0 0 24 24"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/></symbol>
    <symbol id="i-phone" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></symbol>
</svg>

<section class="hero">
    <img class="hero-img" src="{{ asset('landing/hero.jpg') }}" alt="Mensajero en moto recorriendo una avenida de la Ciudad de México al atardecer" width="2400" height="1350" fetchpriority="high">

    <header class="top">
        <div class="wrap">
            <a class="brand" href="/" aria-label="{{ $brand }}, inicio">
                <img src="{{ asset('landing/logo.png') }}" alt="{{ $brand }}">
            </a>

            <nav class="nav" aria-label="Principal">
                <a class="nav-link" href="#nosotros">Nosotros</a>
                <a class="nav-link" href="#cotizar">Cotizar</a>

                <span class="icons">
                    @if ($wa)
                        <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" aria-label="Escribir por WhatsApp"><svg class="i"><use href="#i-chat"/></svg></a>
                    @endif
                    @if ($tel)
                        <a href="tel:{{ $tel }}" aria-label="Llamar por teléfono"><svg class="i"><use href="#i-phone"/></svg></a>
                    @endif
                </span>

                <a class="btn" href="{{ $cta }}">Pedir recolección</a>
            </nav>
        </div>
    </header>

    <div class="wrap">
        <div class="hero-copy">
            <img class="hero-logo" src="{{ asset('landing/logo.png') }}" alt="" aria-hidden="true">
            <h1>Tus muestras, del consultorio al laboratorio en moto.</h1>
            <p class="lead">Mensajería para clínicas veterinarias: recogemos, entregamos y dejamos foto de evidencia en cada paso.</p>
            <a class="btn btn-primary" href="{{ $cta }}">Pedir una recolección <svg viewBox="0 0 10 12"><path d="M0 0l10 6-10 6z"/></svg></a>
            <p class="hero-note">Recibimos pedidos hasta las {{ $cutoffLabel }}.</p>
        </div>

        <div class="strip">
            <div><span>Recolección y entrega en moto, sin almacén ni transbordos</span><svg class="i"><use href="#i-moto"/></svg></div>
            <div><span>Entrega urgente en máximo {{ $urgentHours }} horas desde la recolección</span><svg class="i"><use href="#i-clock"/></svg></div>
            <div><span>Seguimiento del pedido y foto de evidencia al recolectar y entregar</span><svg class="i"><use href="#i-camera"/></svg></div>
        </div>
    </div>
</section>

<section class="section" id="nosotros">
    <div class="ghost" aria-hidden="true">NOSOTROS</div>

    <div class="wrap">
        <div class="about">
            <h2>Mensajería veterinaria<strong>{{ $brand }}</strong></h2>
            <p>
                Llevamos muestras de clínicas veterinarias a su laboratorio por la ruta más corta, con un solo
                mensajero por pedido. Sin almacén, sin transbordos y sin esperas: tú ves el estado del pedido desde
                el celular y recibes foto de evidencia cuando se recolecta y cuando se entrega.
            </p>
        </div>

        <div class="ring">
            <img class="ring-img" src="{{ asset('landing/nosotros.jpg') }}" alt="Mensajero recibiendo una caja térmica de muestras en una clínica veterinaria" width="800" height="800" loading="lazy">
            <div class="fcard a">Una moto por pedido: tu muestra no pasa por almacén ni cambia de manos.</div>
            <div class="fcard b">Ves el estado de tu pedido y recibes avisos cuando se recolecta y cuando se entrega.</div>
            <div class="fcard c">Foto de evidencia al recolectar y al entregar, guardada en el historial de tu pedido.</div>
            <div class="fcard d">Pagas por kilómetro recorrido, con el precio a la vista antes de confirmar.</div>
        </div>
    </div>
</section>

<section class="section deep">
    <div class="wrap">
        <div class="section-title">Así funciona<strong>De tu clínica al laboratorio en cuatro pasos</strong></div>

        <div class="steps">
            <div class="step"><b>1</b><h3>Marca tus puntos</h3><p>Elige dónde recogemos y dónde entregamos. Ves la ruta y el precio antes de confirmar.</p></div>
            <div class="step"><b>2</b><h3>Asignamos mensajero</h3><p>Un mensajero en turno toma tu pedido y ves su nombre y su foto.</p></div>
            <div class="step"><b>3</b><h3>Recolecta y viaja</h3><p>Sigue cada etapa del pedido desde tu celular, con avisos importantes.</p></div>
            <div class="step"><b>4</b><h3>Entrega confirmada</h3><p>Recibes la foto de evidencia. Si nadie recibe o el laboratorio está cerrado, la muestra regresa a tu clínica.</p></div>
        </div>
    </div>
</section>

<section class="section" style="padding-block:72px">
    <div class="wrap">
        <div class="stats">
            <div class="stat"><strong>${{ $perKm }}</strong><span>por kilómetro, IVA incluido</span></div>
            <div class="stat"><strong>{{ $cutoffLabel }}</strong><span>hora límite para solicitar recolecciones</span></div>
            <div class="stat"><strong>{{ $urgentHours }} h</strong><span>máximo en entregas urgentes, desde la recolección</span></div>
            <div class="stat"><strong>$0</strong><span>si cancelas antes de que se asigne mensajero</span></div>
        </div>
    </div>
</section>

<section class="section deep" id="cotizar">
    <div class="wrap">
        <div class="quote">
            <div>
                <div class="section-title">Cotiza tu envío<strong>Cuéntanos de dónde a dónde</strong></div>
                <p class="sub">Déjanos los datos y te respondemos con el costo.</p>

                <form class="form" id="quote-form" novalidate>
                    <div class="field">
                        <label for="from">Dónde recogemos</label>
                        <input id="from" name="from" autocomplete="street-address" placeholder="Colonia o dirección de tu clínica" required>
                    </div>
                    <div class="field">
                        <label for="to">Dónde entregamos</label>
                        <input id="to" name="to" placeholder="Laboratorio o dirección de destino" required>
                    </div>
                    <div class="field">
                        <label for="name">Tu nombre</label>
                        <input id="name" name="name" autocomplete="name" placeholder="¿Cómo te llamamos?" required>
                    </div>
                    <div class="field">
                        <label for="phone">Tu teléfono</label>
                        <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="55 1234 5678" required>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">{{ $wa ? 'Cotizar por WhatsApp' : 'Crear cuenta y cotizar' }}</button>
                        <p id="form-msg" role="status">El precio exacto lo ves en la app antes de confirmar: ${{ $perKm }} por km, IVA incluido.</p>
                    </div>
                </form>
            </div>

            <aside class="contact">
                <h3>Contacto</h3>
                <dl>
                    @if ($contact['phone'])
                        <dt>Teléfono</dt>
                        <dd><a href="tel:{{ $tel }}">{{ $contact['phone'] }}</a></dd>
                    @endif
                    @if ($wa)
                        <dt>WhatsApp</dt>
                        <dd><a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">Escríbenos</a></dd>
                    @endif
                    @if ($contact['email'])
                        <dt>Correo</dt>
                        <dd><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></dd>
                    @endif
                    <dt>Horario de pedidos</dt>
                    <dd>Hasta las {{ $cutoffLabel }}</dd>
                    @if ($contact['client_url'])
                        <dt>¿Ya tienes cuenta?</dt>
                        <dd><a href="{{ $contact['client_url'] }}">Entrar a mi cuenta de clínica</a></dd>
                    @endif
                </dl>
            </aside>
        </div>
    </div>
</section>

<footer class="foot">
    <div class="wrap">
        <a class="brand" href="/" aria-label="{{ $brand }}, inicio">
            <img src="{{ asset('landing/logo.png') }}" alt="{{ $brand }}" loading="lazy">
        </a>
        <nav aria-label="Accesos">
            @if ($contact['client_url'])<a href="{{ $contact['client_url'] }}">Acceso clínicas</a>@endif
            @if ($contact['courier_url'])<a href="{{ $contact['courier_url'] }}">Acceso mensajeros</a>@endif
        </nav>
        <span>© {{ date('Y') }} {{ $brand }}. Todos los derechos reservados.</span>
    </div>
</footer>

<div class="sticky">
    <a class="btn btn-primary" href="{{ $cta }}">Pedir una recolección</a>
</div>

<script>
    (function () {
        const CFG = @json(['wa' => $wa, 'client' => $contact['client_url']]);
        const form = document.getElementById('quote-form');
        const msg = document.getElementById('form-msg');

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const d = Object.fromEntries(new FormData(form));
            const missing = ['from', 'to', 'name', 'phone'].filter(function (k) { return !String(d[k] || '').trim(); });

            if (missing.length) {
                msg.textContent = 'Completa los cuatro campos para continuar.';
                form.elements[missing[0]].focus();
                return;
            }

            if (CFG.wa) {
                const text = 'Hola, quiero cotizar un envío de muestras.\n'
                    + 'Recolección: ' + d.from + '\n'
                    + 'Entrega: ' + d.to + '\n'
                    + 'Nombre: ' + d.name + '\n'
                    + 'Teléfono: ' + d.phone;

                window.open('https://wa.me/' + CFG.wa + '?text=' + encodeURIComponent(text), '_blank', 'noopener');
                msg.textContent = 'Abrimos WhatsApp con tu solicitud. Envía el mensaje y te respondemos.';
            } else if (CFG.client) {
                window.location.href = CFG.client;
            } else {
                msg.textContent = 'Por ahora no hay un canal de contacto configurado.';
            }
        });
    })();
</script>
</body>
</html>