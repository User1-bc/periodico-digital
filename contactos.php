<?php
require_once 'conexion.php';
$page_title = "Contactos";
$page_description = "Contacta con la redacción de Periódico Digital RD. Envíanos tus noticias, sugerencias o consultas publicitarias.";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Periódico Digital RD</title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <style>
        * { box-sizing: border-box; }
        html, body { overflow-x: hidden; }
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        
        header { 
            background: #1b263b; 
            color: white; 
            padding: 12px 16px; 
            display: grid; 
            grid-template-columns: auto 1fr auto; 
            align-items: center; 
            gap: 10px; 
            border-bottom: 3px solid #007bff; 
            position: relative;
        }
        .header-left { justify-self: start; }
        .header-title { text-align: center; justify-self: center; min-width: 0; }
        .header-title h1 { margin: 0; font-size: 20px; font-weight: bold; letter-spacing: 0.3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .header-title p { margin: 2px 0 0; color: #adb5bd; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .header-right { justify-self: end; display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; }
        .btn-icon { width: 44px; height: 44px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: all 0.2s ease; flex-shrink: 0; }
        .btn-icon:hover { transform: scale(1.1); box-shadow: 0 6px 16px rgba(0,0,0,0.3); }
        .btn-icon:active { transform: scale(0.95); }
        .btn-whatsapp-top { background: #25d366; color: white; }
        .btn-whatsapp-top:hover { background: #20ba5a; }
        .btn-notifications { background: #ffc107; color: #212529; }
        .btn-notifications:hover { background: #e0a800; }
        .btn-notifications.active { background: #28a745; color: white; }
        .btn-icon svg { width: 22px; height: 22px; stroke-width: 2.5; }
        
        .main-content { max-width: 800px; margin: 0 auto; padding: 30px 20px; }
        .page-header { text-align: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
        .page-header h1 { color: #1b263b; font-size: 32px; margin-bottom: 10px; }
        .page-header .subtitle { color: #666; font-size: 18px; }
        
        .content-section { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .content-section h2 { color: #1b263b; font-size: 24px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #007bff; }
        .content-section p { color: #444; line-height: 1.8; margin-bottom: 15px; font-size: 16px; }
        
        .contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
        .contact-card { background: #f8f9fa; padding: 25px; border-radius: 12px; border: 1px solid #e9ecef; text-align: center; transition: transform 0.2s, box-shadow 0.2s; }
        .contact-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .contact-icon { width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px; font-size: 24px; }
        .contact-icon.email { background: #e3f2fd; color: #1976d2; }
        .contact-icon.phone { background: #e8f5e9; color: #388e3c; }
        .contact-icon.location { background: #fff3e0; color: #f57c00; }
        .contact-icon.news { background: #fce4ec; color: #c2185b; }
        .contact-card h3 { color: #1b263b; margin-bottom: 10px; font-size: 18px; }
        .contact-card p { color: #666; margin: 5px 0; font-size: 15px; }
        .contact-card a { color: #007bff; text-decoration: none; font-weight: 600; }
        .contact-card a:hover { text-decoration: underline; }
        
        .form-section { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .form-section h2 { color: #1b263b; font-size: 24px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #007bff; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; font-family: inherit; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 3px rgba(0,123,255,0.15); }
        .form-group textarea { min-height: 150px; resize: vertical; }
        .btn-submit { background: #007bff; color: white; border: none; padding: 14px 30px; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: background 0.2s, transform 0.1s; width: 100%; }
        .btn-submit:hover { background: #0056b3; }
        .btn-submit:active { transform: scale(0.98); }
        
        footer { background: #1b263b; color: white; padding: 40px 20px 20px; margin-top: 50px; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto 30px; }
        .footer-col h4 { color: #ffc107; margin-bottom: 15px; font-size: 16px; }
        .footer-col ul { list-style: none; padding: 0; }
        .footer-col li { margin-bottom: 10px; }
        .footer-col a { color: #adb5bd; text-decoration: none; transition: color 0.2s; font-size: 14px; }
        .footer-col a:hover { color: #ffc107; }
        .footer-bottom { text-align: center; padding-top: 20px; border-top: 1px solid #34495e; color: #8898aa; font-size: 13px; }
        
        @media (max-width: 768px) {
            header { grid-template-columns: auto 1fr auto; padding: 10px 12px; gap: 8px; }
            .header-left img { height: 40px; }
            .header-title h1 { font-size: 17px; }
            .header-title p { font-size: 10px; }
            .header-right { justify-self: end; gap: 6px; }
            .btn-icon { width: 38px; height: 38px; }
            .btn-icon svg { width: 20px; height: 20px; }
            .page-header h1 { font-size: 26px; }
            .page-header .subtitle { font-size: 16px; }
            .content-section { padding: 20px; }
            .content-section h2 { font-size: 20px; }
        }
        @media (max-width: 480px) {
            header { padding: 8px 10px; gap: 6px; }
            .header-left img { height: 36px; }
            .header-title h1 { font-size: 15px; }
            .header-title p { font-size: 9px; }
            .btn-icon { width: 38px; height: 38px; }
            .btn-icon svg { width: 20px; height: 20px; }
            .page-header h1 { font-size: 22px; }
            .content-section { padding: 15px; }
        }
    </style>
</head>
<body>
<header>
    <div class="header-left">
        <a href="index.php" style="text-decoration: none;">
            <img src="uploads/125688.png" alt="Periódico Digital RD" style="height: 50px; width: auto; max-width: 100%;">
        </a>
    </div>
    <div class="header-title">
        <h1>Periódico Digital RD</h1>
        <p>La fuente más confiable de República Dominicana</p>
    </div>
    <div class="header-right">
        <button id="btn-suscripcion" class="btn-icon btn-notifications" onclick="toggleSuscripcion()" aria-label="Suscribirse a notificaciones">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </button>
        <a href="https://wa.me/18295482901?text=Hola,%20deseo%20enviar%20una%20informacion%20o%20consultar%20sobre%20espacios%20publicitarios." target="_blank" class="btn-icon btn-whatsapp-top" aria-label="Contactar por teléfono">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
        </a>
    </div>
</header>

<div class="main-content">
    <div class="page-header">
        <h1>Contactos</h1>
        <p class="subtitle">Estamos para escucharte. Escríbenos por el canal que prefieras.</p>
    </div>
    
    <div class="content-section">
        <h2>Canales de Contacto</h2>
        <div class="contact-grid">
            <div class="contact-card">
                <div class="contact-icon email">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <h3>Redacción General</h3>
                <p>Noticias, reportajes, correcciones</p>
                <p><a href="mailto:redaccion@periodicodigitalrd.online">redaccion@periodicodigitalrd.online</a></p>
            </div>
            <div class="contact-card">
                <div class="contact-icon phone">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <h3>WhatsApp Directo</h3>
                <p>Respuesta rápida 9am-6pm</p>
                <p><a href="https://wa.me/18295482901" target="_blank">+1 829 548 2901</a></p>
            </div>
            <div class="contact-card">
                <div class="contact-icon location">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <h3>Oficina Principal</h3>
                <p>Santo Domingo, República Dominicana</p>
                <p>Zona Universitaria</p>
            </div>
            <div class="contact-card">
                <div class="contact-icon news">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <h3>Enviar Noticia / Denuncia</h3>
                <p>¿Tienes una primicia? Cuéntanos</p>
                <p><a href="mailto:noticias@periodicodigitalrd.online">noticias@periodicodigitalrd.online</a></p>
            </div>
        </div>
    </div>
    
    <div class="form-section">
        <h2>Envíanos un Mensaje</h2>
        <form action="enviar_contacto.php" method="POST">
            <div class="form-group">
                <label for="nombre">Nombre completo *</label>
                <input type="text" id="nombre" name="nombre" required placeholder="Tu nombre">
            </div>
            <div class="form-group">
                <label for="email">Correo electrónico *</label>
                <input type="email" id="email" name="email" required placeholder="tu@email.com">
            </div>
            <div class="form-group">
                <label for="telefono">Teléfono / WhatsApp</label>
                <input type="tel" id="telefono" name="telefono" placeholder="+1 809 xxx xxxx">
            </div>
            <div class="form-group">
                <label for="asunto">Asunto *</label>
                <select id="asunto" name="asunto" required>
                    <option value="">Selecciona un tema</option>
                    <option value="noticia">Enviar noticia / denuncia</option>
                    <option value="correccion">Solicitar corrección</option>
                    <option value="publicidad">Consulta publicitaria</option>
                    <option value="tecnico">Problema técnico web</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="form-group">
                <label for="mensaje">Mensaje *</label>
                <textarea id="mensaje" name="mensaje" required placeholder="Escribe tu mensaje aquí..."></textarea>
            </div>
            <button type="submit" class="btn-submit">Enviar Mensaje</button>
        </form>
    </div>
</div>

<footer>
    <div class="footer-grid">
        <div class="footer-col">
            <h4>Periódico Digital RD</h4>
            <p style="color: #adb5bd; font-size: 14px; line-height: 1.6;">Tu fuente confiable de noticias en República Dominicana. Periodismo independiente, veraz y accesible.</p>
        </div>
        <div class="footer-col">
            <h4>Secciones</h4>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="index.php#politica">Política</a></li>
                <li><a href="index.php#economia">Economía</a></li>
                <li><a href="index.php#deportes">Deportes</a></li>
                <li><a href="index.php#sociedad">Sociedad</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Enlaces Legales</h4>
            <ul>
                <li><a href="quienes_somos.php">Quiénes Somos</a></li>
                <li><a href="contactos.php">Contactos</a></li>
                <li><a href="publicidad.php">Publicidad</a></li>
                <li><a href="politica_privacidad.php">Política de Privacidad</a></li>
                <li><a href="derechos_reservados.php">Derechos Reservados</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contacto</h4>
            <ul>
                <li>📍 Santo Domingo, RD</li>
                <li>✉️ redaccion@periodicodigitalrd.online</li>
                <li>📱 <a href="https://wa.me/18295482901" style="color: #25d366;">WhatsApp: +1 829 548 2901</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; 2024 Periódico Digital RD. Todos los derechos reservados.
    </div>
</footer>

<script>
    const publicVapidKey = 'BDNidqse1xgK0WW5rCgFJx7jxeDeEB6fFH_FQZ2JPMplderSAXF8Tl3eqOZM0OW-Oe6GVJqbKb2XIqLGtwV3iQ4';

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    async function toggleSuscripcion() {
        if (!("Notification" in window)) {
            alert("Tu navegador no soporta notificaciones de escritorio.");
            return;
        }
        if (Notification.permission === "granted") {
            alert("Ya estás suscrito a las notificaciones.");
        } else if (Notification.permission !== "denied") {
            let permission = await Notification.requestPermission();
            if (permission === "granted") {
                updateSuscripcionButtonState(true);
                await suscribirUsuarioPush();
            }
        } else {
            alert("Las notificaciones están bloqueadas en la configuración de tu navegador.");
        }
    }

    async function suscribirUsuarioPush() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicVapidKey)
            });
            await fetch('guardar_suscripcion.php', {
                method: 'POST',
                body: JSON.stringify(subscription),
                headers: { 'content-type': 'application/json' }
            });
            console.log("Usuario suscrito correctamente.");
        } catch (error) {
            console.error("Error al suscribir:", error);
        }
    }

    function updateSuscripcionButtonState(active) {
        const btn = document.getElementById("btn-suscripcion");
        if (btn) {
            if (active) {
                btn.classList.add("active");
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
                btn.setAttribute('aria-label', 'Suscrito');
            } else {
                btn.classList.remove("active");
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
                btn.setAttribute('aria-label', 'Suscribirse');
            }
        }
    }

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('SW registrado:', reg))
                .catch(err => console.error('Error SW:', err));
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        if (!("Notification" in window) || !('serviceWorker' in navigator)) {
            const btn = document.getElementById("btn-suscripcion");
            if(btn) btn.style.display = "none";
            return;
        }
        if (Notification.permission === "granted") {
            updateSuscripcionButtonState(true);
        }
    });
</script>
</body>
</html>