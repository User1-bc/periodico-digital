<?php
// Forzar la zona horaria local de Rep├║blica Dominicana para evitar desfases de fecha
date_default_timezone_set('America/Santo_Domingo');

require_once 'conexion.php';

// Auto-migración: crear tablas si no existen
try {
    $tableCheck = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='noticias'")->fetchColumn();
    $mediaCheck = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='anuncios_multimedia'")->fetchColumn();
    $adminCheck = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='admin_users'")->fetchColumn();
    if (!$tableCheck || !$mediaCheck || !$adminCheck) {
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($sql);
        error_log("Migración ejecutada: tablas creadas/actualizadas");
    }
} catch (PDOException $e) {
    error_log("Error en auto-migración: " . $e->getMessage());
    // Forzar recreación si falla
    try {
        $pdo->exec("DROP TABLE IF EXISTS noticias_multimedia, anuncios_multimedia, podcasts, anuncios, noticias CASCADE;");
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($sql);
        error_log("Migración forzada completada");
    } catch (PDOException $e2) {
        error_log("Error en migración forzada: " . $e2->getMessage());
    }
}

// 1. Determinar la fecha a consultar (si el usuario seleccion├│ una fecha, usamos esa; si no, usamos la fecha de hoy local)
$fecha_hoy = date('Y-m-d');
$fecha_seleccionada = isset($_GET['fecha']) && !empty($_GET['fecha']) ? $_GET['fecha'] : $fecha_hoy;

// 2. Obtener noticias filtradas por la fecha seleccionada
$stmt_noticias = $pdo->prepare("SELECT * FROM noticias WHERE DATE(fecha_publicacion) = ? ORDER BY fecha_publicacion DESC");
$stmt_noticias->execute([$fecha_seleccionada]);
$noticias = $stmt_noticias->fetchAll(PDO::FETCH_ASSOC);

// Obtener anuncios activos para la columna izquierda
$stmt_izq = $pdo->query("SELECT * FROM anuncios WHERE posicion = 'izquierda' AND activo = true");
$anuncios_izq = $stmt_izq->fetchAll(PDO::FETCH_ASSOC);

// Obtener anuncios activos para la columna derecha
$stmt_der = $pdo->query("SELECT * FROM anuncios WHERE posicion = 'derecha' AND activo = true");
$anuncios_der = $stmt_der->fetchAll(PDO::FETCH_ASSOC);

// Obtener los podcasts m├ís recientes
$stmt_podcasts = $pdo->query("SELECT * FROM podcasts ORDER BY id DESC LIMIT 3");
$podcasts = $stmt_podcasts->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peri├│dico Digital - RD</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        
        header { 
            background: #1b263b; 
            color: white; 
            padding: 20px 30px; 
            display: grid; 
            grid-template-columns: 1fr auto 1fr; 
            align-items: center; 
            gap: 15px; 
            border-bottom: 4px solid #007bff; 
        }
        
        .header-left { justify-self: start; }
        
        .header-title { text-align: center; justify-self: center; }
        .header-title h1 { margin: 0; font-size: 26px; font-weight: bold; letter-spacing: 0.5px; }
        .header-title p { margin: 5px 0 0; color: #adb5bd; font-size: 13px; }
        
        .header-right { justify-self: end; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }

        .btn-whatsapp-top { 
            background: linear-gradient(135deg, #25d366, #1ebe57); 
            color: white; 
            padding: 12px 22px; 
            border-radius: 30px; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 15px; 
            display: inline-flex; 
            align-items: center; 
            gap: 10px; 
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            white-space: nowrap;
        }
        .btn-whatsapp-top:hover { 
            background: linear-gradient(135deg, #20ba5a, #189e47); 
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(37, 211, 102, 0.4);
        }

        .btn-notifications {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
            padding: 12px 18px;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .btn-notifications:hover {
            background: linear-gradient(135deg, #e0a800, #c69500);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 193, 7, 0.4);
        }
        .btn-notifications.active {
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .date-bar {
            background: #ffffff;
            max-width: 1200px;
            margin: 20px auto 0 auto;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .date-bar form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .date-bar input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            outline: none;
        }
        .btn-filter {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            text-decoration: none;
            transition: background 0.2s;
            display: inline-block;
        }
        .btn-filter:hover { background: #0056b3; }
        .btn-today {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        .btn-today:hover { background: #5a6268; }
        .current-view-text {
            font-weight: bold;
            color: #333;
            font-size: 15px;
        }

        .main-container { display: flex; flex-wrap: wrap; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 10px; }
        
        aside.col-sidebar { flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 15px; }
        main.col-center { flex: 2; min-width: 300px; }
        
        .card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; }
        .card img, .card video { max-width: 100%; height: auto; border-radius: 4px; display: block; margin: 0 auto; }
        
        .widget-title { font-size: 16px; font-weight: bold; color: #333; margin-bottom: 10px; border-bottom: 2px solid #007bff; padding-bottom: 5px; text-align: left; }
        .crypto-item, .fx-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .crypto-item:last-child, .fx-item:last-child { border-bottom: none; }
        .price { font-weight: bold; color: #28a745; }

        .noticia-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .noticia-card h2 { margin-top: 0; color: #222; font-size: 22px; }
        .fecha { font-size: 12px; color: #888; margin-bottom: 10px; }
        
        .carrete-multimedia { display: flex; overflow-x: auto; gap: 10px; margin: 15px 0; padding-bottom: 5px; scroll-snap-type: x mandatory; }
        .carrete-multimedia img, .carrete-multimedia video { flex: 0 0 auto; max-height: 380px; width: 100%; object-fit: contain; border-radius: 6px; background: #000; scroll-snap-align: center; }
        
        .carrete-anuncio { display: flex; overflow-x: auto; gap: 8px; margin: 5px 0; padding-bottom: 5px; scroll-snap-type: x mandatory; }
        .carrete-anuncio img, .carrete-anuncio video { flex: 0 0 auto; height: 180px; width: 100%; object-fit: cover; border-radius: 4px; background: #000; scroll-snap-align: center; }

        .descripcion { color: #444; line-height: 1.6; white-space: pre-line; text-align: left; }

        @media (max-width: 768px) {
            .main-container { flex-direction: column; }
            header { grid-template-columns: 1fr; text-align: center; justify-items: center; }
            .header-left, .header-title, .header-right { justify-self: center; text-align: center; }
            .header-right { justify-content: center; }
            .date-bar { flex-direction: column; align-items: stretch; text-align: center; }
            .date-bar form { justify-content: center; }
        }
    </style>
</head>
<body>

    <header>
        <div class="header-left"></div>

        <div class="header-title">
            <h1>­ƒô░ Peri├│dico Digital RD</h1>
            <p>La fuente de informaci├│n m├ís confiable de Rep├║blica Dominicana</p>
        </div>

        <div class="header-right">
            <button id="btn-notif" class="btn-notifications" onclick="toggleNotifications()">
                ­ƒöö Activar Alertas
            </button>

            <a href="https://wa.me/18090000000?text=Hola,%20deseo%20enviar%20una%20informaci├│n%20o%20consultar%20sobre%20espacios%20publicitarios." target="_blank" class="btn-whatsapp-top">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                Cont├íctanos / WhatsApp
            </a>
        </div>
    </header>

    <div class="date-bar">
        <div class="current-view-text">
            ­ƒôà Mostrando noticias del: <span style="color: #007bff;"><?php echo date('d/m/Y', strtotime($fecha_seleccionada)); ?></span>
        </div>
        <form method="GET" action="index.php">
            <input type="date" name="fecha" value="<?php echo htmlspecialchars($fecha_seleccionada); ?>">
            <button type="submit" class="btn-filter">Ver Fecha</button>
            <?php if ($fecha_seleccionada !== $fecha_hoy): ?>
                <a href="index.php?fecha=<?php echo $fecha_hoy; ?>" class="btn-today">Ver Hoy</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="main-container">
        
        <aside class="col-sidebar">
            <div class="card">
                <div class="widget-title">An├║nciate Aqu├¡</div>
                <?php if (empty($anuncios_izq)): ?>
                    <p style="font-size: 13px; color: #777;">Espacio publicitario disponible</p>
                <?php else: ?>
                    <?php foreach ($anuncios_izq as $ad): ?>
                        <div style="margin-bottom: 20px; border-bottom: 1px dashed #ddd; padding-bottom: 15px;">
                            <?php 
                                $stmt_ad_media = $pdo->prepare("SELECT * FROM anuncios_multimedia WHERE anuncio_id = ?");
                                $stmt_ad_media->execute([$ad['id']]);
                                $ad_archivos = $stmt_ad_media->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <?php if (!empty($ad_archivos)): ?>
                                <a href="<?php echo htmlspecialchars($ad['enlace_destino']); ?>" target="_blank" style="text-decoration: none;">
                                    <div class="carrete-anuncio">
                                        <?php foreach ($ad_archivos as $item): ?>
                                            <?php if ($item['tipo'] == 'video'): ?>
                                                <video src="<?php echo htmlspecialchars($item['archivo']); ?>" autoplay muted loop></video>
                                            <?php else: ?>
                                                <img src="<?php echo htmlspecialchars($item['archivo']); ?>" alt="Art├¡culo">
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </a>
                                <span style="font-size: 11px; color: #666; display: block; margin-top: 4px;">Ô×í´©Å Desliza para ver m├ís</span>
                            <?php elseif (!empty($ad['imagen_banner'])): ?>
                                <a href="<?php echo htmlspecialchars($ad['enlace_destino']); ?>" target="_blank">
                                    <?php 
                                        $extension = strtolower(pathinfo($ad['imagen_banner'], PATHINFO_EXTENSION));
                                        $es_video = in_array($extension, ['mp4', 'webm', 'ogg', 'mov']);
                                    ?>
                                    <?php if ($es_video): ?>
                                        <video src="<?php echo htmlspecialchars($ad['imagen_banner']); ?>" autoplay muted loop style="max-width:100%; height:auto; border-radius:4px;"></video>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($ad['imagen_banner']); ?>" alt="Anuncio" style="max-width:100%; height:auto; border-radius:4px;">
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <main class="col-center">
            <?php if (empty($noticias)): ?>
                <div class="noticia-card" style="text-align: center; padding: 40px 20px;">
                    <h3 style="color: #555;">No hay noticias publicadas para el d├¡a <?php echo date('d/m/Y', strtotime($fecha_seleccionada)); ?>.</h3>
                    <p style="color: #777; font-size: 14px;">Intenta seleccionando otra fecha en el calendario superior o regresa a las noticias de hoy.</p>
                    <a href="index.php?fecha=<?php echo $fecha_hoy; ?>" class="btn-filter" style="display: inline-block; text-decoration: none; margin-top: 10px;">Ver noticias de Hoy</a>
                </div>
            <?php else: ?>
                <?php foreach ($noticias as $noticia): ?>
                    <article class="noticia-card">
                        <h2><?php echo htmlspecialchars($noticia['titulo']); ?></h2>
                        <div class="fecha">Publicado el: <?php echo $noticia['fecha_publicacion']; ?></div>
                        
                        <?php 
                            $stmt_media = $pdo->prepare("SELECT * FROM noticias_multimedia WHERE noticia_id = ?");
                            $stmt_media->execute([$noticia['id']]);
                            $archivos_multimedia = $stmt_media->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if (!empty($archivos_multimedia)): ?>
                            <div class="carrete-multimedia">
                                <?php foreach ($archivos_multimedia as $item): ?>
                                    <?php if ($item['tipo'] == 'video'): ?>
                                        <video controls preload="metadata">
                                            <source src="<?php echo htmlspecialchars($item['archivo']); ?>">
                                            Tu navegador no soporta la reproducci├│n de videos.
                                        </video>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($item['archivo']); ?>" alt="Multimedia de la noticia">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="descripcion"><?php echo nl2br(htmlspecialchars($noticia['descripcion'])); ?></div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <aside class="col-sidebar">
            <div class="card">
                <div class="widget-title">­ƒôè Indicadores Econ├│micos</div>
                <div class="crypto-item">
                    <span>Bitcoin (BTC):</span>
                    <span class="price" id="btc-price">Cargando...</span>
                </div>
                <div class="crypto-item">
                    <span>Ethereum (ETH):</span>
                    <span class="price" id="eth-price">Cargando...</span>
                </div>
                <div class="fx-item">
                    <span>Oro (XAU/USD):</span>
                    <span class="price" id="gold-price">Cargando...</span>
                </div>
                <div class="fx-item">
                    <span>USD / DOP:</span>
                    <span class="price" id="usd-dop">Cargando...</span>
                </div>
                <div class="fx-item">
                    <span>EUR / DOP:</span>
                    <span class="price" id="eur-dop">Cargando...</span>
                </div>
            </div>

            <?php if (!empty($podcasts)): ?>
                <div class="card" style="text-align: left;">
                    <div class="widget-title">­ƒÄÖ´©Å ├Ültimos Envivos</div>
                    
                    <?php foreach ($podcasts as $pod): ?>
                        <div style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                            <h4 style="margin: 0 0 8px 0; color: #333; font-size: 15px;"><?php echo htmlspecialchars($pod['titulo']); ?></h4>
                            
                            <div style="position: relative; width: 100%; padding-bottom: 56.25%; height: 0; margin-bottom: 10px;">
                                <iframe src="<?php echo htmlspecialchars($pod['url_youtube']); ?>" 
                                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 6px; border: none;" 
                                        allowfullscreen>
                                </iframe>
                            </div>
                            
                            <p style="font-size: 12px; color: #555; line-height: 1.4; margin: 0;"><?php echo nl2br(htmlspecialchars($pod['descripcion'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="widget-title">An├║nciate Aqu├¡</div>
                <?php if (empty($anuncios_der)): ?>
                    <p style="font-size: 13px; color: #777;">Espacio publicitario disponible</p>
                <?php else: ?>
                    <?php foreach ($anuncios_der as $ad): ?>
                        <div style="margin-bottom: 20px; border-bottom: 1px dashed #ddd; padding-bottom: 15px;">
                            <?php 
                                $stmt_ad_media = $pdo->prepare("SELECT * FROM anuncios_multimedia WHERE anuncio_id = ?");
                                $stmt_ad_media->execute([$ad['id']]);
                                $ad_archivos = $stmt_ad_media->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <?php if (!empty($ad_archivos)): ?>
                                <a href="<?php echo htmlspecialchars($ad['enlace_destino']); ?>" target="_blank" style="text-decoration: none;">
                                    <div class="carrete-anuncio">
                                        <?php foreach ($ad_archivos as $item): ?>
                                            <?php if ($item['tipo'] == 'video'): ?>
                                                <video src="<?php echo htmlspecialchars($item['archivo']); ?>" autoplay muted loop></video>
                                            <?php else: ?>
                                                <img src="<?php echo htmlspecialchars($item['archivo']); ?>" alt="Art├¡culo">
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </a>
                                <span style="font-size: 11px; color: #666; display: block; margin-top: 4px;">Ô×í´©Å Desliza para ver m├ís</span>
                            <?php elseif (!empty($ad['imagen_banner'])): ?>
                                <a href="<?php echo htmlspecialchars($ad['enlace_destino']); ?>" target="_blank">
                                    <?php 
                                        $extension = strtolower(pathinfo($ad['imagen_banner'], PATHINFO_EXTENSION));
                                        $es_video = in_array($extension, ['mp4', 'webm', 'ogg', 'mov']);
                                    ?>
                                    <?php if ($es_video): ?>
                                        <video src="<?php echo htmlspecialchars($ad['imagen_banner']); ?>" autoplay muted loop style="max-width:100%; height:auto; border-radius:4px;"></video>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($ad['imagen_banner']); ?>" alt="Anuncio" style="max-width:100%; height:auto; border-radius:4px;">
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

    </div>

    <script>
        // Registrar Service Worker al cargar la p├ígina
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker registrado con ├®xito:', reg))
                    .catch(err => console.error('Error al registrar el Service Worker:', err));
            });
        }

        async function fetchMarketData() {
            try {
                let responseCrypto = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,pax-gold&vs_currencies=usd');
                let dataCrypto = await responseCrypto.json();
                
                if(dataCrypto.bitcoin) {
                    document.getElementById('btc-price').innerText = "$" + dataCrypto.bitcoin.usd.toLocaleString() + " USD";
                }
                if(dataCrypto.ethereum) {
                    document.getElementById('eth-price').innerText = "$" + dataCrypto.ethereum.usd.toLocaleString() + " USD";
                }
                if(dataCrypto['pax-gold']) {
                    document.getElementById('gold-price').innerText = "$" + dataCrypto['pax-gold'].usd.toLocaleString() + " USD";
                } else {
                    document.getElementById('gold-price').innerText = "No disponible";
                }

                let responseFx = await fetch('https://open.er-api.com/v6/latest/USD');
                let dataFx = await responseFx.json();
                
                if(dataFx && dataFx.rates) {
                    let rateUSD_DOP = dataFx.rates.DOP; 
                    let rateEUR_USD = dataFx.rates.EUR; 
                    
                    if(rateUSD_DOP) {
                        document.getElementById('usd-dop').innerText = "~ " + rateUSD_DOP.toFixed(2) + " RD$";
                        
                        if(rateEUR_USD) {
                            let rateEUR_DOP = rateUSD_DOP / rateEUR_USD;
                            document.getElementById('eur-dop').innerText = "~ " + rateEUR_DOP.toFixed(2) + " RD$";
                        }
                    }
                }
            } catch (error) {
                console.log("Error al actualizar indicadores:", error);
            }
        }
        
        fetchMarketData();
        setInterval(fetchMarketData, 60000);

        document.addEventListener("DOMContentLoaded", () => {
            if (!("Notification" in window) || !('serviceWorker' in navigator)) {
                const btn = document.getElementById("btn-notif");
                if(btn) btn.style.display = "none";
                return;
            }

            if (Notification.permission === "granted") {
                updateNotificationButtonState(true);
            }
        });

        // Clave VAPID p├║blica integrada para el env├¡o de notificaciones
        const publicVapidKey = 'TU_CLAVE_PUBLICA_VAPID_AQUI';

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

        async function toggleNotifications() {
            if (!("Notification" in window)) {
                alert("Tu navegador no soporta notificaciones de escritorio.");
                return;
            }

            if (Notification.permission === "granted") {
                alert("Las notificaciones ya se encuentran activadas en este navegador.");
            } else if (Notification.permission !== "denied") {
                let permission = await Notification.requestPermission();
                if (permission === "granted") {
                    updateNotificationButtonState(true);
                    await suscribirUsuarioPush();
                }
            } else {
                alert("Las notificaciones est├ín bloqueadas en la configuraci├│n de tu navegador.");
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
                    headers: {
                        'content-type': 'application/json'
                    }
                });

                console.log("Usuario suscrito a las push notifications correctamente.");
            } catch (error) {
                console.error("Error al suscribir al usuario:", error);
            }
        }

        function updateNotificationButtonState(active) {
            const btn = document.getElementById("btn-notif");
            if (btn) {
                if (active) {
                    btn.innerHTML = "­ƒöò Alertas Activas";
                    btn.classList.add("active");
                } else {
                    btn.innerHTML = "­ƒöö Activar Alertas";
                    btn.classList.remove("active");
                }
            }
        }
    </script>
</body>
</html>






