<?php
require_once 'conexion.php';
$page_title = "Quiénes Somos";
$page_description = "Conoce la misión, visión y valores de Periódico Digital RD, tu fuente confiable de noticias en República Dominicana.";
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
        .content-section ul { padding-left: 20px; }
        .content-section li { margin-bottom: 10px; line-height: 1.7; color: #444; }
        
        .values-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .value-card { background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #007bff; transition: transform 0.2s; }
        .value-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .value-card h3 { color: #1b263b; margin-bottom: 10px; font-size: 18px; }
        .value-card p { color: #666; margin: 0; font-size: 15px; }
        
        .team-section { text-align: center; }
        .team-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px; }
        .team-member { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .team-member h4 { color: #1b263b; margin-bottom: 5px; }
        .team-member .role { color: #007bff; font-size: 14px; font-weight: 600; margin-bottom: 10px; }
        .team-member p { color: #666; font-size: 14px; margin: 0; }
        
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
        <h1>Quiénes Somos</h1>
        <p class="subtitle">Conoce la historia y el equipo detrás de Periódico Digital RD</p>
    </div>
    
    <div class="content-section">
        <h2>Nuestra Historia</h2>
        <p>Periódico Digital RD nació en 2024 con una misión clara: ofrecer a los dominicanos una fuente de información confiable, rápida y accesible desde cualquier dispositivo. En un mundo donde la información viaja a la velocidad de la luz, nos comprometemos a ser tu brújula diaria para navegar la actualidad nacional e internacional.</p>
        <p>Lo que comenzó como un proyecto de periodismo digital independiente se ha convertido en uno de los medios de referencia para miles de lectores que buscan noticias verificadas, análisis profundos y una perspectiva equilibrada de los acontecimientos que marcan nuestra realidad.</p>
    </div>
    
    <div class="content-section">
        <h2>Misión, Visión y Valores</h2>
        <div class="values-grid">
            <div class="value-card">
                <h3>🎯 Misión</h3>
                <p>Informar con veracidad, ética y responsabilidad, brindando a la ciudadanía dominicana las herramientas para comprender su realidad y tomar decisiones informadas.</            </div>
            <div class="value-card">
                <h3>🔭 Visión</h3>
                <p>Ser el medio digital de referencia en República Dominicana, reconocido por su calidad periodística, innovación tecnológica y compromiso social.</p>
            </div>
            <div class="value-card">
                <h3>⚖️ Valores</h3>
                <p><strong>Veracidad:</strong> Verificamos cada dato antes de publicar.<br>
                <strong>Independencia:</strong> Sin ataduras políticas ni económicas.<br>
                <strong>Transparencia:</strong> Correcciones visibles y rendición de cuentas.<br>
                <strong>Servicio público:</strong> El interés ciudadano por encima de todo.</p>
            </div>
        </div>
    </div>
    
    <div class="content-section">
        <h2>Nuestro Compromiso</h2>
        <ul>
            <li><strong>Periodismo de calidad:</strong> Investigamos, contrastamos y contextualizamos cada historia.</li>
            <li><strong>Innovación constante:</strong> Usamos IA y tecnología para llegar a más lectores con mejor contenido.</li>
            <li><strong>Accesibilidad total:</strong> Gratis, sin muros de pago, optimizado para móviles y datos limitados.</li>
            <li><strong>Comunidad:</strong> Escuchamos a nuestros lectores y abrimos espacios para su participación.</li>
        </ul>
    </div>
    
    <div class="content-section team-section">
        <h2>Equipo Editorial</h2>
        <p>Un grupo de periodistas, desarrolladores y comunicadores apasionados por informar bien.</        <div class="team-grid">
            <div class="team-member">
                <h4>Dirección Editorial</h4>
                <div class="role">Editor en Jefe</div>
                <p>Responsable de la línea editorial y calidad informativa.</p>
            </div>
            <div class="team-member">
                <h4>Redacción</h4>
                <div class="role">Periodistas</div>
                <p>Cobertura nacional, internacional, economía, deportes y sociedad.</p>
            </div>
            <div class="team-member">
                <h4>Tecnología</h4>
                <div class="role">Desarrollo & IA</div>
                <p>Plataforma, automatización, robot de noticias y experiencia de usuario.</p>
            </div>
            <div class="team-member">
                <h4>Comercial</h4>
                <div class="role">Publicidad & Alianzas</div>
                <p>Espacios publicitarios, contenidos patrocinados y partnerships.</p>
            </div>
        </div>
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