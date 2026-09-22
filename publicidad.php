<?php
require_once 'conexion.php';
$page_title = "Publicidad";
$page_description = "Espacios publicitarios en Periódico Digital RD. Llega a miles de lectores dominicanos con tus anuncios.";
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
        
        .main-content { max-width: 900px; margin: 0 auto; padding: 30px 20px; }
        .page-header { text-align: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
        .page-header h1 { color: #1b263b; font-size: 32px; margin-bottom: 10px; }
        .page-header .subtitle { color: #666; font-size: 18px; }
        
        .content-section { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .content-section h2 { color: #1b263b; font-size: 24px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #007bff; }
        .content-section p { color: #444; line-height: 1.8; margin-bottom: 15px; font-size: 16px; }
        .content-section ul { padding-left: 20px; }
        .content-section li { margin-bottom: 10px; line-height: 1.7; color: #444; }
        
        .ad-spaces-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .ad-space-card { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 12px; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
        .ad-space-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .ad-space-preview { height: 160px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; position: relative; }
        .ad-space-preview.banner { background: linear-gradient(135deg, #1b263b 0%, #007bff 100%); }
        .ad-space-preview.sidebar { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .ad-space-preview.carousel { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
        .ad-space-preview.native { background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); }
        .ad-space-info { padding: 20px; }
        .ad-space-info h3 { color: #1b263b; margin-bottom: 10px; font-size: 18px; }
        .ad-space-info .specs { color: #666; font-size: 14px; margin-bottom: 15px; line-height: 1.7; }
        .ad-space-info .price { color: #28a745; font-weight: bold; font-size: 16px; margin-bottom: 15px; }
        .btn-ad { background: #007bff; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 600; width: 100%; transition: background 0.2s; }
        .btn-ad:hover { background: #0056b3; }
        
        .benefits-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .benefit-card { background: white; padding: 25px; border-radius: 12px; border: 1px solid #e9ecef; text-align: center; }
        .benefit-icon { width: 60px; height: 60px; border-radius: 50%; background: #e3f2fd; color: #1976d2; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px; font-size: 24px; }
        .benefit-card h3 { color: #1b263b; margin-bottom: 10px; font-size: 18px; }
        .benefit-card p { color: #666; margin: 0; font-size: 15px; }
        
        .cta-section { background: linear-gradient(135deg, #1b263b 0%, #007bff 100%); color: white; padding: 40px; border-radius: 12px; text-align: center; margin-top: 30px; }
        .cta-section h2 { font-size: 28px; margin-bottom: 15px; }
        .cta-section p { font-size: 18px; opacity: 0.9; margin-bottom: 25px; }
        .btn-cta { background: #ffc107; color: #1b263b; border: none; padding: 14px 40px; border-radius: 8px; cursor: pointer; font-size: 18px; font-weight: 700; transition: transform 0.2s, background 0.2s; }
        .btn-cta:hover { background: #e0a800; transform: scale(1.02); }
        
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
        <h1>Publicidad</h1>
        <p class="subtitle">Conecta tu marca con miles de lectores dominicanos cada día</p>
    </div>
    
    <div class="content-section">
        <h2>¿Por qué anunciar en Periódico Digital RD?</h2>
        <p>Somos el medio digital de mayor crecimiento en República Dominicana, con una audiencia fiel que confía en nuestro periodismo. Tu marca junto a contenido de calidad genera confianza y recordación.</p>
        
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <h3>Audiencia Calificada</h3>
                <p>Lectores activos interesados en noticias, economía y actualidad nacional.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div class="benefit-icon" style="background:#e8f5e9;color:#388e3c;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                </div>
                <h3>Multi-Device</h3>
                <p>Tu anuncio se ve perfecto en móvil, tablet y desktop. 80% tráfico móvil.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon" style="background:#fff3e0;color:#f57c00;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><circle cx="12" cy="12" r="10"/><line x1="12" y1="6" x2="12" y2="12"/><line x1="12" y1="12" x2="12.01" y2="18"/></svg>
                </div>
                <h3>Métricas en Tiempo Real</h3>
                <p>Dashboard con impresiones, clicks, CTR y datos demográficos actualizados.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon" style="background:#fce4ec;color:#c2185b;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3>Formatos Flexibles</h3>
                <p>Imagen, video, carrusel, nativo, video en autoplay. Adaptamos a tu necesidad.</p>
            </div>
        </div>
    </div>
    
    <div class="content-section">
        <h2>Espacios Publicitarios Disponibles</h2>
        
        <div class="ad-spaces-grid">
            <div class="ad-space-card">
                <div class="ad-space-preview banner">
                    <span>📺 Banner Superior (Carousel)</span>
                </div>
                <div class="ad-space-info">
                    <h3>Carousel Superior - Full Width</h3>
                    <div class="specs">
                        <strong>Formato:</strong> 16:9 (Video/Imagen)<br>
                        <strong>Posición:</strong> Debajo del header, sticky<br>
                        <strong>Visibilidad:</strong> 100% usuarios (desktop + móvil)<br>
                        <strong>Rotación:</strong> Múltiples anuncios auto-play
                    </div>
                    <div class="price">Desde RD$ 15,000/semana</div>
                    <button class="btn-ad" onclick="window.location.href='https://wa.me/18295482901?text=Interesado%20en%20Banner%20Superior'">Solicitar</button>
                </div>
            </div>
            
            <div class="ad-space-card">
                <div class="ad-space-preview sidebar">
                    <span>📱 Columna Derecha</span>
                </div>
                <div class="ad-space-info">
                    <h3>Sidebar Derecho - Sidebar</h3>
                    <div class="specs">
                        <strong>Formato:</strong> 300x250 / 300x600<br>
                        <strong>Posición:</strong> Junto a noticias (desktop)<br>
                        <strong>Visibilidad:</strong> Alta permanencia<br>
                        <strong>Formatos:</strong> Imagen, GIF, Video
                    </div>
                    <div class="price">Desde RD$ 8,000/semana</div>
                    <button class="btn-ad" onclick="window.location.href='https://wa.me/18295482901?text=Interesado%20en%20Sidebar'">Solicitar</button>
                </div>
            </div>
            
            <div class="ad-space-card">
                <div class="ad-space-preview carousel">
                    <span>🎞️ Carrusel In-Article</span>
                </div>
                <div class="ad-space-info">
                    <h3>Carrusel entre Noticias</h3>
                    <div class="specs">
                        <strong>Formato:</strong> Múltiples imágenes 1:1<br>
                        <strong>Posición:</strong> Entre artículos<br>
                        <strong>Ideal para:</strong> Catálogos, inmobiliarias, autos<br>
                        <strong>Interacción:</strong> Swipe táctil nativo
                    </div>
                    <div class="price">Desde RD$ 10,000/semana</div>
                    <button class="btn-ad" onclick="window.location.href='https://wa.me/18295482901?text=Interesado%20en%20Carrusel'">Solicitar</button>
                </div>
            </div>
            
            <div class="ad-space-card">
                <div class="ad-space-preview native">
                    <span>📝 Contenido Patrocinado</span>
                </div>
                <div class="ad-space-info">
                    <h3>Artículo Nativo / Branded Content</h3>
                    <div class="specs">
                        <strong>Formato:</strong> Artículo periodístico + multimedia<br>
                        <strong>Posición:</strong> En feed de noticias (etiquetado)<br>
                        <strong>Incluye:</strong> Redacción profesional + SEO<br>
                        <strong>Duración:</strong> Permanente en web
                    </div>
                    <div class="price">Desde RD$ 25,000/artículo</div>
                    <button class="btn-ad" onclick="window.location.href='https://wa.me/18295482901?text=Interesado%20en%20Branded%20Content'">Solicitar</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content-section">
        <h2>Datos de Audiencia</h2>
        <ul>
            <li><strong>Visitas mensuales:</strong> 150,000+ usuarios únicos</li>
            <li><strong>Páginas vistas:</strong> 500,000+ / mes</li>
            <li><strong>Tiempo promedio:</strong> 3:45 minutos por sesión</li>
            <li><strong>Tráfico móvil:</strong> 82% (optimizado mobile-first)</li>
            <li><strong>Demografía:</strong> 65% entre 25-54 años, 52% mujeres</li>
            <li><strong>Geografía:</strong> 90% República Dominicana, 10% diáspora</li>
        </ul>
    </div>
    
    <div class="cta-section">
        <h2>¿Listo para crecer tu marca?</h2>
        <p>Nuestro equipo comercial te asesora para elegir el mejor formato y maximizar tu ROI.</p>
        <a href="https://wa.me/18295482901?text=Hola,%20quiero%20informaci%C3%B3n%20sobre%20espacios%20publicitarios%20en%20Peri%C3%B3dico%20Digital%20RD" target="_blank" class="btn-cta">
            💬 Hablar por WhatsApp Ahora
        </a>
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
                <li>✉️ publicidad@periodicodigitalrd.online</li>
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