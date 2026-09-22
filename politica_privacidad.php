<?php
require_once 'conexion.php';
$page_title = "Política de Privacidad";
$page_description = "Política de privacidad y protección de datos de Periódico Digital RD. Cómo recopilamos, usamos y protegemos tu información.";
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
        .last-updated { color: #888; font-size: 14px; margin-top: 10px; }
        
        .content-section { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .content-section h2 { color: #1b263b; font-size: 24px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #007bff; }
        .content-section h3 { color: #333; font-size: 18px; margin: 25px 0 10px; }
        .content-section p { color: #444; line-height: 1.8; margin-bottom: 15px; font-size: 16px; }
        .content-section ul { padding-left: 20px; }
        .content-section li { margin-bottom: 10px; line-height: 1.7; color: #444; }
        .content-section strong { color: #1b263b; }
        
        .highlight-box { background: #e3f2fd; border-left: 4px solid #1976d2; padding: 20px; border-radius: 0 8px 8px 0; margin: 20px 0; }
        .highlight-box h4 { color: #1976d2; margin-bottom: 10px; margin-top: 0; }
        .highlight-box p { margin: 0; color: #1565c0; }
        
        .contact-section { background: #f8f9fa; padding: 25px; border-radius: 8px; margin-top: 30px; }
        .contact-section h3 { color: #1b263b; margin-bottom: 15px; }
        
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
        <h1>Política de Privacidad</h1>
        <p class="subtitle">Cómo protegemos y gestionamos tu información personal</p>
        <p class="last-updated">Última actualización: Septiembre 2024</p>
    </div>
    
    <div class="highlight-box">
        <h4>🔒 Compromiso de Privacidad</h4>
        <p>En Periódico Digital RD, tu privacidad es nuestra prioridad. Esta política explica qué datos recopilamos, cómo los usamos y qué derechos tienes sobre tu información. No vendemos tus datos a terceros.</p>
    </div>
    
    <div class="content-section">
        <h2>1. Información que Recopilamos</h2>
        <h3>Datos de Navegación (Automáticos)</h3>
        <ul>
            <li>Dirección IP anonimizada</li>
            <li>Tipo de navegador y dispositivo</li>
            <li>Páginas visitadas y tiempo de permanencia</li>
            <li>País/ciudad aproximada (geolocalización IP)</li>
            <li>Página de referencia (de dónde vienes)</li>
        </ul>
        <h3>Datos que Tú Proporcionas (Voluntarios)</h3>
        <ul>
            <li>Nombre y email (formulario de contacto)</li>
            <li>Teléfono/WhatsApp (opcional en formularios)</li>
            <li>Contenido de tus mensajes</li>
            <li>Suscripción a notificaciones push (token del navegador)</li>
        </ul>
        <h3>Datos de Publicidad</h3>
        <ul>
            <li>Identificadores publicitarios anónimos (Google Ads, Meta)</li>
            <li>Intereses inferidos por navegación</li>
            <li>Frecuencia y tipo de anuncios vistos</li>
        </ul>
    </div>
    
    <div class="content-section">
        <h2>2. Cómo Usamos tu Información</h2>
        <ul>
            <li><strong>Prestar el servicio:</strong> Mostrar noticias, guardar preferencias, enviar notificaciones solicitadas.</li>
            <li><strong>Mejorar el sitio:</strong> Analítica agregada (Google Analytics) para optimizar contenido y UX.</li>
            <li><strong>Comunicaciones:</strong> Responder tus consultas, enviar newsletters si te suscribes.</li>
            <li><strong>Publicidad relevante:</strong> Mostrar anuncios basados en intereses generales (no datos personales identificables).</li>
            <li><strong>Seguridad:</strong> Detectar fraude, bots, ataques y uso abusivo.</li>
            <li><strong>Cumplimiento legal:</strong> Responder requerimientos judiciales válidos.</li>
        </ul>
    </div>
    
    <div class="content-section">
        <h2>3. Compartir Información con Terceros</h2>
        <p><strong>NO vendemos tus datos personales.</strong> Solo compartimos en estos casos:</p>
        <ul>
            <li><strong>Proveedores de servicio:</strong> Hosting (Render), CDN (Cloudinary), Analytics (Google), Push (Firebase/FCM). Todos bajo contratos de confidencialidad (DPA).</li>
            <li><strong>Publicidad programática:</strong> Google AdSense, Meta Audience Network - usan IDs anónimos, no tu identidad.</li>
            <li><strong>Obligación legal:</strong> Solo si lo exige una orden judicial o autoridad competente.</li>
            <li><strong>Protección de derechos:</strong> Para defender nuestros términos, propiedad o seguridad.</li>
        </ul>
    </div>
    
    <div class="content-section">
        <h2>4. Cookies y Tecnologías Similares</h2>
        <h3>Cookies Esenciales (Siempre activas)</h3>
        <ul>
            <li>Sesión de usuario (login admin)</li>
            <li>Preferencias de cookies (consentimiento)</li>
            <li>Seguridad CSRF / XSRF</li>
        </ul>
        <h3>Cookies de Analítica (Opcionales)</h3>
        <ul>
            <li>Google Analytics (_ga, _gid) - anonimizadas</li>
            <li>Puedes desactivarlas en configuración del navegador</li>
        </ul>
        <h3>Cookies de Publicidad (Opcionales)</h3>
        <ul>
            <li>Google AdSense, DoubleClick</li>
            <li>Meta Pixel (Facebook/Instagram)</li>
            <li>Controlables desde "Configuración de anuncios" de Google/Meta</li>
        </ul>
    </div>
    
    <div class="content-section">
        <h2>5. Notificaciones Push</h2>
        <ul>
            <li>Solo se envían si das consentimiento explícito (botón "Suscribirse").</li>
            <li>Usamos Firebase Cloud Messaging (Google) y protocolo Web Push (VAPID).</li>
            <li>El token del dispositivo se guarda encriptado en nuestra BD.</li>
            <li>Puedes revocar en cualquier momento desde la configuración del navegador o clickando "Suscrito" en el header.</li>
            <li>No usamos el token para identificar tu persona, solo para enviar la notificación.</li>
        </ul>
    </div>
    
    <div class="content-section">
        <h2>6. Tus Derechos (Ley 172-13 RD y GDPR)</h2>
        <p>Como titular de datos, tienes derecho a:</p>
        <ul>
            <li><strong>Acceso:</strong> Saber qué datos tuyos tenemos.</li>
            <li><strong>Rectificación:</strong> Corregir datos inexactos.</li>
            <li><strong>Supresión ("Olvido"):</strong> Pedir que borremos tus datos (salvo obligación legal).</li>
            <li><strong>Oposición:</strong> Negarte a procesamiento para marketing directo.</li>
            <li><strong>Portabilidad:</strong> Recibir tus datos en formato estándar.</li>
            <li><strong>Limitación:</strong> Restringir uso temporalmente.</li>
        </ul>
        <p>Para ejercer tus derechos, escríbenos a <strong>privacidad@periodicodigitalrd.online</strong> o usa el formulario de <a href="contactos.php">Contactos</a>. Responderemos en ≤30 días.</p>
    </div>
    
    <div class="content-section">
        <h2>7. Retención de Datos</h2>
        <ul>
            <li><strong>Datos de contacto:</strong> 2 años tras última interacción.</li>
            <li><strong>Logs de servidor/IP:</strong> 30 días (rotación automática).</li>
            <li><strong>Analytics agregados:</strong> 26 meses (configuración GA4).</li>
            <li><strong>Tokens push:</strong> Hasta que revocas consentimiento o token expira (FCM).</li>
            <li><strong>Backups:</strong> 90 días (cifrados, acceso restringido).</li>
        </ul>
    </div>
    
    <div class="content-section">
        <h2>8. Seguridad</h2>
        <ul>
            <li>HTTPS obligatorio (TLS 1.2+) en todo el sitio.</li>
            <li>Base de datos en Neon (PostgreSQL) con cifrado en reposo y tránsito.</li>
            <li>Acceso a admin solo por sesión segura + contraseña hasheada (MD5 legacy, migración a bcrypt planificada).</li>
            <li>Headers de seguridad: CSP, HSTS, X-Frame-Options, Referrer-Policy.</li>
            <li>Monitoreo de accesos anómalos y rate-limiting en formularios.</li>
        </ul>
    </div>
    
    <div class="content-section">
        <h2>9. Menores de Edad</h2>
        <p>Nuestro contenido es apto para todo público. No recopilamos intencionadamente datos de menores de 13 años. Si detectamos que un menor nos ha proporcionado datos sin consentimiento parental, los eliminaremos. Padres/tutores pueden contactarnos para ejercer derechos en nombre del menor.</p>
    </div>
    
    <div class="content-section">
        <h2>10. Transferencias Internacionales</h2>
        <p>Nuestros servidores están en EE. UU. (Render, Oregon) y servicios de Google/Meta/Cloudinary pueden procesar datos fuera de RD. Estas transferencias se basan en cláusulas contractuales estándar aprobadas y decisiones de adecuación. Al usar el sitio, aceptas esta transferencia.</p>
    </div>
    
    <div class="content-section">
        <h2>11. Cambios a esta Política</h2>
        <p>Actualizaremos esta página cuando cambien nuestras prácticas. La fecha de "Última actualización" reflejará la versión vigente. Cambios materiales se notificarán via banner en el sitio o notificación push (si estás suscrito).</p>
    </div>
    
    <div class="contact-section">
        <h3>📧 Contacto del Delegado de Protección de Datos</h3>
        <p><strong>Email:</strong> <a href="mailto:privacidad@periodicodigitalrd.online" style="color: #007bff;">privacidad@periodicodigitalrd.online</a></p>
        <p><strong>Asunto sugerido:</strong> "Ejercicio de Derechos ARCO / GDPR - [Tu Nombre]"</p>
        <p><strong>Tiempo de respuesta:</strong> ≤ 30 días hábiles (Ley 172-13 Art. 38)</p>
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
                <li>✉️ privacidad@periodicodigitalrd.online</li>
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