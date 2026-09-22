<?php
require_once 'conexion.php';
$page_title = "Derechos Reservados";
$page_description = "Aviso legal, derechos de autor, propiedad intelectual y términos de uso de Periódico Digital RD.";
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
        
        .legal-notice { background: #fff3e0; border-left: 4px solid #f57c00; padding: 20px; border-radius: 0 8px 8px 0; margin: 20px 0; }
        .legal-notice h4 { color: #e65100; margin-bottom: 10px; margin-top: 0; }
        .legal-notice p { color: #bf360c; margin: 0; }
        
        .clause-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .clause-card { background: #f8f9fa; padding: 25px; border-radius: 12px; border: 1px solid #e9ecef; }
        .clause-card h3 { color: #1b263b; margin-bottom: 15px; font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .clause-card p { color: #444; font-size: 15px; margin: 0; }
        
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
        <h1>Derechos Reservados</h1>
        <p class="subtitle">Aviso legal, propiedad intelectual y términos de uso</p>
        <p class="last-updated">Última actualización: Septiembre 2024</p>
    </div>
    
    <div class="legal-notice">
        <h4>⚖️ Aviso Legal</h4>
        <p>El acceso y uso de este sitio web implica la aceptación plena y sin reservas de los presentes términos. Si no estás de acuerdo, por favor no utilices este sitio.</p>
    </div>
    
    <div class="content-section">
        <h2>1. Titularidad y Identificación</h2>
        <p>En cumplimiento de la Ley 53-07 sobre Crímenes y Delitos de Alta Tecnología y la Ley 65-00 sobre Derecho de Autor de la República Dominicana, se informa que:</p>
        <ul>
            <li><strong>Titular:</strong> Periódico Digital RD</li>
            <li><strong>Domicilio:</strong> Santo Domingo, República Dominicana</li>
            <li><strong>Email legal:</strong> <a href="mailto:legal@periodicodigitalrd.online" style="color: #007bff;">legal@periodicodigitalrd.online</a></li>
            <li><strong>Dominio:</strong> periodicodigitalrd.online</li>
        </ul>
    </div>
    
    <div class="content-section">
        <h2>2. Propiedad Intelectual e Industrial</h2>
        <h3>Contenido Protegido</h3>
        <p>Todos los contenidos de este sitio web están protegidos por la legislación dominicana e internacional sobre propiedad intelectual:</p>
        <ul>
            <li><strong>Textos y artículos:</strong> Originales de nuestra redacción o licenciados. Prohibida su reproducción total o parcial sin autorización escrita.</li>
            <li><strong>Fotografías y videos:</strong> Propios, de agencias (AP, EFE, Reuters) o bancos de imágenes licenciados. Uso editorial únicamente.</li>
            <li><strong>Diseño web, código y estructura:</strong> Propiedad exclusiva de Periódico Digital RD.</li>
            <li><strong>Logotipos, marcas e identidades visuales:</strong> Registrados ante ONAPI (Oficina Nacional de la Propiedad Industrial).</li>
            <li><strong>Infografías y elementos interactivos:</strong> Creación propia, uso exclusivo.</li>
        </ul>
        
        <h3>Licencia de Uso Personal</h3>
        <p>Se autoriza únicamente:</p>
        <ul>
            <li>Visualización en pantalla para uso personal y no comercial.</li>
            <li>Compartir enlaces (URLs) mediante redes sociales, mensajería o email.</li>
            <li>Impresión ocasional de artículos para lectura personal.</li>
        </ul>
        <p><strong>Queda EXPRESAMENTE PROHIBIDO sin autorización previa y por escrito:</strong></p>
        <ul>
            <li>Reproducción, distribución o comunicación pública de contenidos.</li>
            <li>Extracción sistemática de datos (scraping, crawling automatizado).</li>
            <li>Creación de obras derivadas, traducciones o adaptaciones.</li>
            <li>Uso comercial, publicitario o promocional de contenidos.</li>
            <li>Eliminación o alteración de metadatos de autoría y derechos.</li>
            <li>Uso de framing, mirroring o deep-linking engañoso.</li>
        </ul>
    </div>
    
    <div class="content-section">
        <h2>3. Derechos de Autor sobre Contenidos de Terceros</h2>
        <ul>
            <li><strong>Agencias de noticias:</strong> Contenidos de AP, EFE, Reuters, Diario Libre, Listín Diario, etc., se usan bajo licencia editorial. Sus derechos pertenecen a sus respectivos titulares.</li>
            <li><strong>Imágenes de archivo:</strong> Bancos licenciados (Getty, Shutterstock, Unsplash, Pexels) según sus términos.</li>
            <li><strong>Contenido usuario:</strong> Comentarios, fotos o videos enviados por lectores: el autor conserva sus derechos, pero nos concede licencia no exclusiva, mundial, perpetua y gratuita para publicar, moderar, distribuir y crear obras derivadas en nuestros canales.</li>
        </ul>
        <div class="legal-notice">
            <h4>📋 Notificación de Infracción (DMCA / Ley 65-00 Art. 85)</h4>
            <p>Si consideras que tu obra ha sido usada sin autorización, envíanos una notificación a <strong>legal@periodicodigitalrd.online</strong> con: (1) Identificación de la obra, (2) URL exacta en nuestro sitio, (3) Tus datos de contacto, (4) Declaración de buena fe, (5) Firma electrónica. Retiraremos el contenido en ≤48h tras verificación.</p>
        </div>
    </div>
    
    <div class="content-section">
        <h2>4. Marcas Registradas</h2>
        <p>Los siguientes signos distintivos son marcas registradas o en proceso de registro ante ONAPI:</p>
        <ul>
            <li><strong>Periódico Digital RD®</strong> (denominativa y mixta)</li>
            <li><strong>Logo del periódico</strong> (figurativa)</li>
            <li><strong>Eslogan: "La fuente más confiable de República Dominicana"®</strong></li>
        </ul>
        <p>Su uso no autorizado en publicidad, dominio web, redes sociales o mercancía constituye infracción marcaria sancionable bajo Ley 20-00.</p>
    </div>
    
    <div class="content-section">
        <h2>5. Términos de Uso del Sitio Web</h2>
        
        <h3>Acceso y Disponibilidad</h3>
        <ul>
            <li>Acceso gratuito, sin registro obligatorio para leer noticias.</li>
            <li>Nos reservamos el derecho a suspender, modificar o restringir acceso temporal o permanentemente sin previo aviso.</li>
            <li>No garantizamos disponibilidad 100% (mantenimiento, fuerza mayor, ataques).</li>
        </ul>
        
        <h3>Conducta del Usuario</h3>
        <p>Queda prohibido usar el sitio para:</p>
        <ul>
            <li>Actividades ilegales, difamación, acoso, discurso de odio.</li>
            <li>Distribuir malware, virus, código malicioso.</li>
            <li>Suplantar identidad, falsificar datos, spam.</li>
            <li>Interferir con la seguridad, bots agresivos, ingeniería inversa.</li>
            <li>Violar derechos de propiedad intelectual o privacidad.</li>
        </ul>
        <p>Nos reservamos el derecho a bloquear IPs, eliminar comentarios y denunciar a autoridades competentes.</        </p>
        
        <h3>Comentarios e Interacciones</h3>
        <ul>
            <li>Los comentarios reflejan opinión de sus autores, no de Periódico Digital RD.</li>
            <li>Moderamos: eliminamos spam, insultos, datos personales, contenido ilegal.</li>
            <li>Al comentar, concedes licencia para publicar, editar por extensión y moderar tu texto.</li>
        </ul>
    </div>
    
    <div class="content-section">
        <h2>5. Exclusión de Responsabilidad</h2>
        <div class="clause-grid">
            <div class="clause-card">
                <h3>📰 Contenido Informativo</h3>
                <p>Las noticias se publican con diligencia periodística, pero pueden contener errores, omisiones o quedar desactualizadas. No sustituyen asesoría profesional (legal, médica, financiera).</p>
            </div>
            <div class="clause-card">
                <h3>🔗 Enlaces Externos</h3>
                <p>Enlaces a terceros no implican endorsoso ni control. No nos responsabilizamos de su contenido, privacidad o disponibilidad.</p>
            </div>
            <div class="clause-card">
                <h3>💻 Disponibilidad Técnica</h3>
                <p>No garantizamos ausencia de virus, errores, interrupciones o compatibilidad con todos los dispositivos/navegadores.</p>
            </div>
            <div class="clause-card">
                <h3>📊 Publicidad</h3>
                <p>Los anuncios son responsabilidad de sus anunciantes. No avalamos productos/servicios publicitados.</p>
            </div>
        </div>
    </div>
    
    <div class="content-section">
        <h2>6. Ley Aplicable y Jurisdicción</h2>
        <p>Estos términos se rigen por las leyes de la <strong>República Dominicana</strong>. Cualquier controversia se someterá a los tribunales ordinarios de <strong>Santo Domingo de Guzmán</strong>, renunciando el usuario a cualquier otro fuero.</p>
        <p>Leyes aplicables principales:</p>
        <ul>
            <li>Ley 65-00 sobre Derecho de Autor</li>
            <li>Ley 20-00 sobre Propiedad Industrial</li>
            <li>Ley 53-07 Crímenes y Delitos de Alta Tecnología</li>
            <li>Ley 172-13 Protección de Datos Personales</li>
            <li>Ley 42-08 de Defensa del Consumidor</li>
            <li>Código de Comercio y Código Civil Dominicano</li>
        </ul>
    </div>
    
    <div class="content-section">
        <h2>7. Uso de Contenidos con Fines Educativos / Periodísticos</h2>
        <p>Bajo el principio de <strong>cita legítima (Ley 65-00 Art. 33)</strong> y <strong>derecho de información</strong>, se permite:</p>
        <ul>
            <li>Citar fragmentos breves (≤100 palabras) con atribución clara: "Fuente: Periódico Digital RD" + enlace.</li>
            <li>Resúmenes en reseñas de medios, boletines de prensa o análisis académicos.</li>
            <li>Uso en motores de búsqueda y agregadores de noticias (Google News, etc.) bajo estándares de la industria.</li>
        </ul>
        <p>Para cualquier uso fuera de estos límites, solicita autorización a <a href="mailto:legal@periodicodigitalrd.online" style="color: #007bff;">legal@periodicodigitalrd.online</a>.</p>
    </div>
    
    <div class="content-section">
        <h2>8. Modificaciones</h2>
        <p>Podemos actualizar estos términos en cualquier momento. La versión vigente será la publicada en esta página con su fecha de actualización. El uso continuado del sitio tras cambios implica aceptación.</p>
    </div>
    
    <div class="content-section">
        <h2>9. Contacto Legal</h2>
        <p>Para consultas sobre derechos de autor, marcas, licencias, denuncias o ejercer derechos:</p>
        <ul>
            <li><strong>Email:</strong> <a href="mailto:legal@periodicodigitalrd.online" style="color: #007bff;">legal@periodicodigitalrd.online</a></li>
            <li><strong>Asunto sugerido:</strong> "Consulta Legal / Derechos de Autor - [Tu Nombre/Empresa]"</li>
            <li><strong>Tiempo respuesta:</strong> ≤ 5 días hábiles</li>
        </ul>
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
                <li>✉️ legal@periodicodigitalrd.online</li>
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