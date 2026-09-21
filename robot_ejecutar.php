<?php
/**
 * Ejecuta el robot de noticias via AJAX desde el panel admin
 * Devuelve JSON con resultado
 */

require_once 'conexion.php';

header('Content-Type: application/json');

try {
    // Configuración
    $LIMITE_NOTICIAS = 50;
    $FUENTES_RSS = [
        'Google News RD' => 'https://news.google.com/rss/search?q=Rep%C3%BAblica+Dominicana&hl=es-419&gl=DO&ceid=DO:es-419',
        'Google News Última Hora' => 'https://news.google.com/rss/search?q=%C3%BAltima+hora+Rep%C3%BAblica+Dominicana&hl=es-419&gl=DO&ceid=DO:es-419',
        'Google News Política RD' => 'https://news.google.com/rss/search?q=pol%C3%ADtica+Rep%C3%BAblica+Dominicana&hl=es-419&gl=DO&ceid=DO:es-419',
        'Google News Economía RD' => 'https://news.google.com/rss/search?q=econom%C3%ADa+Rep%C3%BAblica+Dominicana&hl=es-419&gl=DO&ceid=DO:es-419',
        'Google News Deportes RD' => 'https://news.google.com/rss/search?q=deportes+Rep%C3%BAblica+Dominicana&hl=es-419&gl=DO&ceid=DO:es-419',
        'Listín Diario' => 'https://www.listindiario.com/rss.xml',
        'Diario Libre' => 'https://www.diariolibre.com/rss.xml',
        'El Día' => 'https://eldia.com.do/feed/',
        'Hoy Digital' => 'https://hoy.com.do/feed/',
        'El Caribe' => 'https://elcaribe.com.do/feed/',
        'Acento' => 'https://acento.com.do/feed/',
        'El Nacional' => 'https://elnacional.com.do/feed/',
    ];
    
    $CATEGORIAS = [
        'política' => ['política', 'gobierno', 'presidente', 'congreso', 'diputado', 'senador', 'ministerio', 'decreto', 'ley', 'elecciones', 'partido', 'prd', 'prm', 'pld', 'fuerza del pueblo'],
        'economía' => ['economía', 'banco', 'reservas', 'dólar', 'inflación', 'pib', 'turismo', 'zona franca', 'exportación', 'importación', 'impuesto', 'presupuesto', 'deuda', 'fmi'],
        'deportes' => ['deporte', 'fútbol', 'béisbol', 'baloncesto', 'olimpiadas', 'selección', 'liga', 'torneo', 'campeonato', 'lidom', 'águilas', 'leones', 'estrellas', 'toros', 'gigantes'],
        'sociedad' => ['salud', 'educación', 'universidad', 'hospital', 'vacuna', 'covid', 'dengue', 'crimen', 'seguridad', 'policía', 'justicia', 'corte', 'fiscalía'],
        'internacional' => ['haití', 'estados unidos', 'eeuu', 'china', 'rusia', 'ucrania', 'israel', 'gaza', 'venezuela', 'colombia', 'méxico', 'españa', 'ue', 'onu'],
    ];
    
    function obtenerCategoria($texto, $CATEGORIAS) {
        $texto = strtolower($texto);
        foreach ($CATEGORIAS as $cat => $keywords) {
            foreach ($keywords as $kw) {
                if (stripos($texto, $kw) !== false) return ucfirst($cat);
            }
        }
        return 'General';
    }
    
    function limpiarTexto($texto) {
        $texto = strip_tags($texto);
        $texto = html_entity_decode($texto, ENT_QUOTES, 'UTF-8');
        $texto = preg_replace('/\s+/', ' ', $texto);
        return trim($texto);
    }
    
    function extraerImagen($item) {
        if (isset($item->enclosure['url'])) return (string)$item->enclosure['url'];
        if (isset($item->{'media:content'}['url'])) return (string)$item->{'media:content'}['url'];
        if (isset($item->{'media:thumbnail'}['url'])) return (string)$item->{'media:thumbnail'}['url'];
        $content = (string)($item->description ?? $item->content ?? '');
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m)) return $m[1];
        return null;
    }
    
    function generarArticuloCompleto($titulo, $descripcion, $fuente) {
        $parrafos = [];
        $parrafos[] = $descripcion ?: $titulo;
        $parrafos[] = "Según informaciones obtenidas por {$fuente}, este acontecimiento se enmarca dentro de una serie de desarrollos recientes que han captado la atención de la opinión pública dominicana. Fuentes oficiales han señalado que se están tomando las medidas correspondientes para dar seguimiento a la situación y garantizar la transparencia en el proceso.";
        $parrafos[] = "Voceros autorizados han manifestado su posición al respecto, enfatizando la importancia de mantener informada a la ciudadanía sobre los pormenores de este caso. Expertos en la materia consultados por este medio coinciden en que las implicaciones podrían ser significativas para el desarrollo nacional en los próximos meses.";
        $parrafos[] = "Las autoridades competentes han anunciado que continuarán las investigaciones y procesos correspondientes, comprometiéndose a ofrecer actualizaciones periódicas a través de los canales oficiales. Periodico Digital RD mantendrá informados a sus lectores sobre cualquier novedad relevante que surja en torno a esta noticia de interés nacional.";
        $parrafos[] = "Este medio reafirma su compromiso con el periodismo responsable y veraz, priorizando siempre la verificación de las fuentes y el contraste de la información antes de su publicación. Agradecemos a nuestra audiencia la confianza depositada en nuestra labor informativa diaria.";
        return implode("\n\n", $parrafos);
    }
    
    function generarDescripcion($contenido) {
        $oraciones = preg_split('/[.!?]+/', $contenido, -1, PREG_SPLIT_NO_EMPTY);
        $desc = trim($oraciones[0] ?? '');
        if (strlen($desc) > 200) $desc = substr($desc, 0, 197) . '...';
        return $desc . '.';
    }
    
    function reescribirTitulo($tituloOriginal) {
        $titulo = trim($tituloOriginal);
        $titulo = preg_replace('/^(Última hora|Último minuto|Breaking|Alerta|Noticia:|Informe:)\s*[:\-]\s*/i', '', $titulo);
        $titulo = mb_strtoupper(mb_substr($titulo, 0, 1)) . mb_substr($titulo, 1);
        if (mb_strlen($titulo) > 120) $titulo = mb_substr($titulo, 0, 117) . '...';
        return $titulo;
    }
    
    // Ejecución
    $noticiasProcesadas = 0;
    $noticiasNuevas = 0;
    $noticiasDuplicadas = 0;
    $errores = 0;
    
    foreach ($FUENTES_RSS as $nombreFuente => $urlRss) {
        if ($noticiasProcesadas >= $LIMITE_NOTICIAS) break;
        
        $ctx = stream_context_create([
            'http' => ['timeout' => 15, 'user_agent' => 'Mozilla/5.0 (PeriodicoDigitalRD Robot/1.0)']
        ]);
        
        $xmlContent = @file_get_contents($urlRss, false, $ctx);
        if (!$xmlContent) { $errores++; continue; }
        
        $xml = simplexml_load_string($xmlContent);
        if (!$xml) { $errores++; continue; }
        
        $items = isset($xml->channel->item) ? $xml->channel->item : [];
        $countFuente = 0;
        
        foreach ($items as $item) {
            if ($noticiasProcesadas >= $LIMITE_NOTICIAS) break;
            if ($countFuente >= 10) break;
            
            $tituloOriginal = (string)($item->title ?? '');
            $enlace = (string)($item->link ?? '');
            $descripcionOriginal = limpiarTexto((string)($item->description ?? ''));
            $imagenUrl = extraerImagen($item);
            
            if (!$tituloOriginal || !$enlace) continue;
            
            // Verificar duplicados
            $stmt = $pdo->prepare("SELECT id FROM noticias_robot WHERE fuente_url = ?");
            $stmt->execute([$enlace]);
            if ($stmt->fetch()) { $noticiasDuplicadas++; continue; }
            
            $stmt = $pdo->prepare("SELECT id FROM noticias_robot WHERE titulo_original = ? AND fecha_creacion > NOW() - INTERVAL '7 days'");
            $stmt->execute([$tituloOriginal]);
            if ($stmt->fetch()) { $noticiasDuplicadas++; continue; }
            
            $tituloGenerado = reescribirTitulo($tituloOriginal);
            $contenidoGenerado = generarArticuloCompleto($tituloGenerado, $descripcionOriginal, $nombreFuente);
            $descripcionGenerada = generarDescripcion($contenidoGenerado);
            $categoria = obtenerCategoria($tituloGenerado . ' ' . $descripcionGenerada, $CATEGORIAS);
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO noticias_robot 
                    (titulo_original, titulo_generado, contenido_generado, descripcion_generada, fuente_url, fuente_nombre, categoria, imagen_url, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
                ");
                $stmt->execute([
                    $tituloOriginal, $tituloGenerado, $contenidoGenerado, $descripcionGenerada,
                    $enlace, $nombreFuente, $categoria, $imagenUrl
                ]);
                
                $noticiasNuevas++;
                $noticiasProcesadas++;
                $countFuente++;
                
            } catch (Exception $e) {
                $errores++;
            }
        }
    }
    
    // Log
    $logMsg = date('Y-m-d H:i:s') . " | AJAX | Nuevas: {$noticiasNuevas} | Duplicadas: {$noticiasDuplicadas} | Errores: {$errores}\n";
    file_put_contents('robot_log.txt', $logMsg, FILE_APPEND);
    
    echo json_encode([
        'ok' => true,
        'nuevas' => $noticiasNuevas,
        'duplicadas' => $noticiasDuplicadas,
        'errores' => $errores,
        'procesadas' => $noticiasProcesadas
    ]);
    
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}