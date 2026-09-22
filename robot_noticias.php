<?php
/**
 * Robot de Noticias EXCEPCIONAL - IA + Breaking News + Imágenes + Deduplicación Semántica
 * Ejecutar via cron: php robot_noticias.php
 * Endpoint AJAX: robot_ejecutar.php
 */

require_once 'conexion.php';

// ============ CONFIGURACIÓN ============
$LIMITE_NOTICIAS = 50;
$USAR_IA = true; // Cambiar a false si no hay API key
$OPENAI_API_KEY = getenv('OPENAI_API_KEY') ?: ''; // Configurar en Render Environment Variables
$CLAUDE_API_KEY = getenv('CLAUDE_API_KEY') ?: ''; // Alternativa: Anthropic

// Fuentes RSS con prioridad (score de credibilidad 1-10)
$FUENTES_RSS = [
    // TIER 1: Máxima credibilidad + Breaking News (score 10)
    ['nombre' => 'Google News Última Hora', 'url' => 'https://news.google.com/rss/search?q=%C3%BAltima+hora+Rep%C3%BAblica+Dominicana&hl=es-419&gl=DO&ceid=DO:es-419', 'score' => 10, 'tier' => 'breaking'],
    ['nombre' => 'Google News Breaking RD', 'url' => 'https://news.google.com/rss/search?q=breaking+Rep%C3%BAblica+Dominicana&hl=es-419&gl=DO&ceid=DO:es-419', 'score' => 10, 'tier' => 'breaking'],
    
    // TIER 2: Periódicos nacionales principales (score 9)
    ['nombre' => 'Listín Diario', 'url' => 'https://www.listindiario.com/rss.xml', 'score' => 9, 'tier' => 'nacional'],
    ['nombre' => 'Diario Libre', 'url' => 'https://www.diariolibre.com/rss.xml', 'score' => 9, 'tier' => 'nacional'],
    ['nombre' => 'El Caribe', 'url' => 'https://elcaribe.com.do/feed/', 'score' => 9, 'tier' => 'nacional'],
    ['nombre' => 'Acento', 'url' => 'https://acento.com.do/feed/', 'score' => 8, 'tier' => 'nacional'],
    
    // TIER 3: Google News por categorías (score 8)
    ['nombre' => 'Google News Política RD', 'url' => 'https://news.google.com/rss/search?q=pol%C3%ADtica+Rep%C3%BAblica+Dominicana&hl=es-419&gl=DO&ceid=DO:es-419', 'score' => 8, 'tier' => 'categoria'],
    ['nombre' => 'Google News Economía RD', 'url' => 'https://news.google.com/rss/search?q=econom%C3%ADa+Rep%C3%BAblica+Dominicana&hl=es-419&gl=DO&ceid=DO:es-419', 'score' => 8, 'tier' => 'categoria'],
    ['nombre' => 'Google News Sociedad RD', 'url' => 'https://news.google.com/rss/search?q=sociedad+Rep%C3%BAblica+Dominicana&hl=es-419&gl=DO&ceid=DO:es-419', 'score' => 8, 'tier' => 'categoria'],
    
    // TIER 4: Otros medios (score 7)
    ['nombre' => 'El Día', 'url' => 'https://eldia.com.do/feed/', 'score' => 7, 'tier' => 'nacional'],
    ['nombre' => 'Hoy Digital', 'url' => 'https://hoy.com.do/feed/', 'score' => 7, 'tier' => 'nacional'],
    ['nombre' => 'El Nacional', 'url' => 'https://elnacional.com.do/feed/', 'score' => 7, 'tier' => 'nacional'],
    ['nombre' => 'Google News Deportes RD', 'url' => 'https://news.google.com/rss/search?q=deportes+Rep%C3%BAblica+Dominicana&hl=es-419&gl=DO&ceid=DO:es-419', 'score' => 7, 'tier' => 'categoria'],
    ['nombre' => 'Google News General RD', 'url' => 'https://news.google.com/rss/search?q=Rep%C3%BAblica+Dominicana&hl=es-419&gl=DO&ceid=DO:es-419', 'score' => 6, 'tier' => 'categoria'],
];

// Categorías expandidas
$CATEGORIAS = [
    'política' => ['política', 'gobierno', 'presidente', 'congreso', 'diputado', 'senador', 'ministerio', 'decreto', 'ley', 'elecciones', 'partido', 'prd', 'prm', 'pld', 'fuerza del pueblo', 'abina', 'cámara', 'senado', 'municipal', 'alcalde'],
    'economía' => ['economía', 'banco', 'reservas', 'dólar', 'inflación', 'pib', 'turismo', 'zona franca', 'exportación', 'importación', 'impuesto', 'presupuesto', 'deuda', 'fmi', 'banco central', 'tasas', 'crédito', 'inversión', 'bolsa', 'mercado'],
    'deportes' => ['deporte', 'fútbol', 'béisbol', 'baloncesto', 'olimpiadas', 'selección', 'liga', 'torneo', 'campeonato', 'lidom', 'águilas', 'leones', 'estrellas', 'toros', 'gigantes', 'escogido', 'cibao', 'futbol', 'balonmano', 'voleibol'],
    'sociedad' => ['salud', 'educación', 'universidad', 'hospital', 'vacuna', 'covid', 'dengue', 'crimen', 'seguridad', 'policía', 'justicia', 'corte', 'fiscalía', 'tránsito', 'accidente', 'incendio', 'bomberos', 'clima', 'huracán', 'tormenta'],
    'internacional' => ['haití', 'estados unidos', 'eeuu', 'china', 'rusia', 'ucrania', 'israel', 'gaza', 'venezuela', 'colombia', 'méxico', 'españa', 'ue', 'onu', 'oem', 'fmi', 'bm', 'ocde', 'cumbres'],
    'cultura' => ['cultura', 'arte', 'música', 'cine', 'festival', 'teatro', 'exposición', 'libro', 'escritor', 'premio', 'carnaval', 'patrimonio', 'folklore'],
    'tecnología' => ['tecnología', 'digital', 'inteligencia artificial', 'ia', 'startup', 'innovación', 'ciberseguridad', 'datos', '5g', 'fibra', 'robot', 'automatización'],
];

// Palabras clave para detectar BREAKING NEWS
$BREAKING_KEYWORDS = [
    'última hora', 'último minuto', 'breaking', 'urgente', 'alerta', 'ahora mismo', 
    'en vivo', 'en directo', 'acaba de', 'acaba de ocurrir', 'reportan', 'confirman',
    'oficial', 'comunicado urgente', 'declaración de emergencia', 'estado de emergencia'
];

function esBreakingNews($titulo, $descripcion) {
    global $BREAKING_KEYWORDS;
    $texto = strtolower($titulo . ' ' . $descripcion);
    foreach ($BREAKING_KEYWORDS as $kw) {
        if (stripos($texto, $kw) !== false) return true;
    }
    return false;
}

function obtenerCategoria($titulo, $descripcion) {
    global $CATEGORIAS;
    $texto = strtolower($titulo . ' ' . $descripcion);
    $scores = [];
    foreach ($CATEGORIAS as $cat => $keywords) {
        $score = 0;
        foreach ($keywords as $kw) {
            if (stripos($texto, $kw) !== false) $score++;
        }
        if ($score > 0) $scores[$cat] = $score;
    }
    if (empty($scores)) return 'General';
    arsort($scores);
    return ucfirst(array_key_first($scores));
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
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m)) {
        return $m[1];
    }
    return null;
}

// Descargar imagen y subir a Cloudinary (persistente)
function descargarImagen($url, $titulo) {
    if (!$url) return null;
    try {
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0']]);
        $imgData = @file_get_contents($url, false, $ctx);
        if (!$imgData) return $url; // Fallback a URL original
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $imgData);
        finfo_close($finfo);
        
        $ext = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg'
        };
        
        $safeTitle = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', mb_substr($titulo, 0, 50)));
        $filename = 'robot_' . $safeTitle . '_' . time() . '.' . $ext;
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        if (!file_put_contents($filepath, $imgData)) {
            return $url;
        }
        
        // Subir a Cloudinary
        $cloudinaryUrl = getenv('CLOUDINARY_URL') ?: '';
        if (empty($cloudinaryUrl)) {
            error_log("CLOUDINARY_URL no configurado - imagen no persistirá");
            return $url;
        }
        
        if (!preg_match('/^cloudinary:\/\/([^:]+):([^@]+)@(.+)$/', $cloudinaryUrl, $m)) {
            error_log("CLOUDINARY_URL formato inválido");
            return $url;
        }
        $apiKey = $m[1]; $apiSecret = $m[2]; $cloudName = $m[3];
        
        $timestamp = time();
        $publicId = 'periodico/robot_' . $safeTitle . '_' . $timestamp;
        
        // Cloudinary signature: all params except file, api_key, signature - sorted alphabetically, RAW values
        // Note: resource_type is NOT included in signature per Cloudinary's validation
        $paramsToSign = [
            'folder' => 'periodico-digital',
            'public_id' => $publicId,
            'timestamp' => $timestamp
        ];
        ksort($paramsToSign);
        $signatureParts = [];
        foreach ($paramsToSign as $k => $v) {
            $signatureParts[] = "$k=$v";
        }
        $signatureString = implode('&', $signatureParts) . $apiSecret;
        $signature = sha1($signatureString);
        
        $postFields = [
            'file' => new CURLFile($filepath),
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'public_id' => $publicId,
            'signature' => $signature,
            'folder' => 'periodico-digital',
            'resource_type' => 'image'
        ];
        
        $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/upload");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        @unlink($filepath);
        
        if ($httpCode !== 200) {
            error_log("Cloudinary upload failed: $response");
            return $url;
        }
        
        $result = json_decode($response, true);
        return $result['secure_url'] ?? $url;
        
    } catch (Exception $e) {
        error_log("Error descargando/subiendo imagen: " . $e->getMessage());
    }
    return $url;
}

// Generar artículo con IA (OpenAI o Claude)
function generarConIA($titulo, $descripcion, $fuente, $esBreaking) {
    global $USAR_IA, $OPENAI_API_KEY, $CLAUDE_API_KEY;
    
    if (!$USAR_IA || (empty($OPENAI_API_KEY) && empty($CLAUDE_API_KEY))) {
        return generarArticuloPlantilla($titulo, $descripcion, $fuente, $esBreaking);
    }
    
    $prompt = "Eres un periodista profesional de Periodico Digital RD. Escribe un artículo periodístico completo, riguroso y bien estructurado en español dominicano.\n\n";
    $prompt .= "TÍTULO: $titulo\n";
    $prompt .= "FUENTE ORIGINAL: $fuente\n";
    $prompt .= "RESUMEN FUENTE: $descripcion\n";
    $prompt .= ($esBreaking ? "TIPO: 🔴 ÚLTIMA HORA / BREAKING NEWS\n" : "");
    $prompt .= "\nREQUISITOS:\n";
    $prompt .= "- MÍNIMO 5 párrafos separados por línea en blanco\n";
    $prompt .= "- Estilo periodístico: lead, contexto, declaraciones, antecedentes, próximos pasos\n";
    $prompt .= "- Tono objetivo, veraz, sin opiniones personales\n";
    $prompt .= "- Vocabulario dominicano natural\n";
    $prompt .= "- Incluir datos concretos, cifras, nombres si están en la fuente\n";
    $prompt .= "- NO inventes información no presente en la fuente\n";
    $prompt .= "- Longitud: 300-600 palabras\n";
    $prompt .= "- Formato: solo el artículo, sin encabezados ni meta-comentarios\n";
    
    try {
        if (!empty($OPENAI_API_KEY)) {
            return llamarOpenAI($prompt, $OPENAI_API_KEY);
        } elseif (!empty($CLAUDE_API_KEY)) {
            return llamarClaude($prompt, $CLAUDE_API_KEY);
        }
    } catch (Exception $e) {
        error_log("Error IA: " . $e->getMessage());
    }
    return generarArticuloPlantilla($titulo, $descripcion, $fuente, $esBreaking);
}

function llamarOpenAI($prompt, $apiKey) {
    $data = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => 'Eres un periodista profesional dominicano de Periodico Digital RD.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.3,
        'max_tokens' => 800
    ];
    
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer $apiKey\r\n",
            'content' => json_encode($data),
            'timeout' => 30
        ]
    ]);
    
    $response = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $ctx);
    if (!$response) throw new Exception('Sin respuesta OpenAI');
    
    $result = json_decode($response, true);
    if (isset($result['error'])) throw new Exception($result['error']['message']);
    
    return trim($result['choices'][0]['message']['content'] ?? '');
}

function llamarClaude($prompt, $apiKey) {
    $data = [
        'model' => 'claude-3-haiku-20240307',
        'max_tokens' => 800,
        'temperature' => 0.3,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];
    
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nx-api-key: $apiKey\r\nanthropic-version: 2023-06-01\r\n",
            'content' => json_encode($data),
            'timeout' => 30
        ]
    ]);
    
    $response = @file_get_contents('https://api.anthropic.com/v1/messages', false, $ctx);
    if (!$response) throw new Exception('Sin respuesta Claude');
    
    $result = json_decode($response, true);
    if (isset($result['error'])) throw new Exception($result['error']['message']);
    
    return trim($result['content'][0]['text'] ?? '');
}

// Plantilla mejorada como fallback
function generarArticuloPlantilla($titulo, $descripcion, $fuente, $esBreaking) {
    $parrafos = [];
    $prefijo = $esBreaking ? "🔴 ÚLTIMA HORA | " : "";
    
    // P1: Lead mejorado con 5W
    $lead = $descripcion ?: $titulo;
    if ($esBreaking) $lead = "$prefijo $lead";
    $parrafos[] = $lead;
    
    // P2: Contexto específico según categoría
    $contexto = obtenerContextoCategoria($titulo, $descripcion, $fuente);
    $parrafos[] = $contexto;
    
    // P3: Declaraciones/Reacciones
    $parrafos[] = "Fuentes oficiales consultadas por Periodico Digital RD indicaron que se encuentra en proceso la recopilación de información detallada para ofrecer una respuesta oficial en las próximas horas. Expertos en la materia señalan que este tipo de situaciones requieren un análisis cuidadoso antes de emitir pronunciamientos definitivos.";
    
    // P4: Impacto y consecuencias
    $parrafos[] = "Analistas consideran que las implicaciones de este hecho podrían tener repercusiones en el corto y mediano plazo, dependiendo de cómo evolucionen los acontecimientos en las próximas jornadas. La ciudadanía permanece atenta a los canales oficiales para recibir información verificada y oportuna.";
    
    // P5: Seguimiento y compromiso editorial
    $parrafos[] = "Este medio continuará dando seguimiento a la presente noticia y actualizará la información tan pronto como surjan nuevos datos confirmados. Periodico Digital RD reafirma su compromiso con el periodismo responsable, la verificación de fuentes y la información de interés público para la República Dominicana.";
    
    return implode("\n\n", $parrafos);
}

function obtenerContextoCategoria($titulo, $descripcion, $fuente) {
    $cat = strtolower(obtenerCategoria($titulo, $descripcion));
    $contextos = [
        'política' => "En el ámbito político dominicano, este acontecimiento se suma a la agenda legislativa y gubernamental que ha marcado las últimas semanas. Observadores políticos señalan que las decisiones que se tomen en torno a este tema podrían influir en el equilibrio de fuerzas con miras a los próximos procesos electorales.",
        'economía' => "Desde la perspectiva económica, analistas financieros destacan que este desarrollo ocurre en un momento clave para la estabilidad macroeconómica del país. El Banco Central y el Ministerio de Hacienda han reiterado su compromiso con las metas fiscales y monetarias establecidas.",
        'deportes' => "En el plano deportivo, esta noticia genera expectativa entre la afición dominicana, que sigue de cerca el desempeño de sus equipos y atletas representantes. Las ligas profesionales y federaciones deportivas han manifestado su disposición para colaborar en lo que sea necesario.",
        'sociedad' => "A nivel social, organizaciones comunitarias y sociedad civil han expresado su interés en que las autoridades garanticen transparencia y celeridad en los procesos correspondientes. La población espera respuestas claras y soluciones concretas a las problemáticas planteadas.",
        'internacional' => "En el contexto internacional, este acontecimiento tiene implicaciones para las relaciones bilaterales y multilaterales de la República Dominicana. Cancillería y organismos internacionales mantienen canales de comunicación activos para coordinar posiciones.",
        'cultura' => "Desde la perspectiva cultural, este evento enriquece la agenda artística y patrimonial del país. Gestores culturales destacan la importancia de preservar y promocionar la identidad dominicana a través de estas manifestaciones.",
        'tecnología' => "En el sector tecnológico, este avance posiciona a la República Dominicana en la ruta de la transformación digital. Emprendedores y centros de innovación ven oportunidades para desarrollar soluciones locales con impacto regional."
    ];
    return $contextos[$cat] ?? "Según informaciones obtenidas por {$fuente}, este acontecimiento se enmarca dentro de una serie de desarrollos recientes que han captado la atención de la opinión pública dominicana. Fuentes oficiales han señalado que se están tomando las medidas correspondientes para dar seguimiento a la situación.";
}

function generarDescripcion($contenido) {
    $oraciones = preg_split('/[.!?]+/', $contenido, -1, PREG_SPLIT_NO_EMPTY);
    $desc = trim($oraciones[0] ?? '');
    if (strlen($desc) > 200) $desc = substr($desc, 0, 197) . '...';
    return $desc . '.';
}

function reescribirTitulo($tituloOriginal) {
    $titulo = trim($tituloOriginal);
    $titulo = preg_replace('/^(Última hora|Último minuto|Breaking|Alerta|Noticia:|Informe:|URGENTE:)\s*[:\-]\s*/i', '', $titulo);
    $titulo = mb_strtoupper(mb_substr($titulo, 0, 1)) . mb_substr($titulo, 1);
    if (mb_strlen($titulo) > 120) $titulo = mb_substr($titulo, 0, 117) . '...';
    return $titulo;
}

// Deduplicación semántica simple (primeras 8 palabras del título)
function tituloFirma($titulo) {
    $palabras = preg_split('/\s+/', strtolower($titulo), -1, PREG_SPLIT_NO_EMPTY);
    return implode(' ', array_slice($palabras, 0, 8));
}

// ============ EJECUCIÓN PRINCIPAL ============
echo "=== Robot de Noticias EXCEPCIONAL Iniciado ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "Límite: {$LIMITE_NOTICIAS} noticias | IA: " . ($USAR_IA && ($OPENAI_API_KEY || $CLAUDE_API_KEY) ? 'ACTIVA' : 'PLANTILLA') . "\n\n";

$noticiasProcesadas = 0;
$noticiasNuevas = 0;
$noticiasDuplicadas = 0;
$breakingCount = 0;
$errores = 0;
$firmasExistentes = [];

// Cargar firmas existentes (últimos 7 días) para deduplicación semántica
$stmt = $pdo->query("SELECT titulo_original FROM noticias_robot WHERE fecha_creacion > NOW() - INTERVAL '7 days'");
while ($row = $stmt->fetch()) {
    $firmasExistentes[] = tituloFirma($row['titulo_original']);
}

// Ordenar fuentes por score descendente (priorizar breaking + alta credibilidad)
usort($FUENTES_RSS, fn($a, $b) => $b['score'] <=> $a['score']);

foreach ($FUENTES_RSS as $fuente) {
    if ($noticiasProcesadas >= $LIMITE_NOTICIAS) break;
    
    $nombreFuente = $fuente['nombre'];
    $urlRss = $fuente['url'];
    $scoreFuente = $fuente['score'];
    $tier = $fuente['tier'];
    
    echo "Procesando [{$tier}/score:{$scoreFuente}]: {$nombreFuente}...\n";
    
    $ctx = stream_context_create([
        'http' => ['timeout' => 15, 'user_agent' => 'Mozilla/5.0 (PeriodicoDigitalRD Robot/2.0)']
    ]);
    
    $xmlContent = @file_get_contents($urlRss, false, $ctx);
    if (!$xmlContent) { echo "  ✗ Error RSS\n"; $errores++; continue; }
    
    $xml = simplexml_load_string($xmlContent);
    if (!$xml) { echo "  ✗ Error XML\n"; $errores++; continue; }
    
    $items = isset($xml->channel->item) ? $xml->channel->item : [];
    $countFuente = 0;
    $maxPorFuente = $tier === 'breaking' ? 15 : 8; // Más breaking news
    
    foreach ($items as $item) {
        if ($noticiasProcesadas >= $LIMITE_NOTICIAS) break;
        if ($countFuente >= $maxPorFuente) break;
        
        $tituloOriginal = (string)($item->title ?? '');
        $enlace = (string)($item->link ?? '');
        $descripcionOriginal = limpiarTexto((string)($item->description ?? ''));
        $imagenUrlOriginal = extraerImagen($item);
        
        if (!$tituloOriginal || !$enlace) continue;
        
        // Detectar breaking
        $esBreaking = esBreakingNews($tituloOriginal, $descripcionOriginal) || $tier === 'breaking';
        if ($esBreaking) $breakingCount++;
        
        // Deduplicación por URL exacta
        $stmt = $pdo->prepare("SELECT id FROM noticias_robot WHERE fuente_url = ?");
        $stmt->execute([$enlace]);
        if ($stmt->fetch()) { $noticiasDuplicadas++; continue; }
        
        // Deduplicación semántica (título similar)
        $firma = tituloFirma($tituloOriginal);
        $duplicadoSemantico = false;
        foreach ($firmasExistentes as $existente) {
            similar_text($firma, $existente, $pct);
            if ($pct > 85) { $duplicadoSemantico = true; break; }
        }
        if ($duplicadoSemantico) { $noticiasDuplicadas++; continue; }
        $firmasExistentes[] = $firma;
        
        // Generar contenido
        $tituloGenerado = reescribirTitulo($tituloOriginal);
        if ($esBreaking && !preg_match('/^(🔴|\[URGENTE\])/i', $tituloGenerado)) {
            $tituloGenerado = "🔴 $tituloGenerado";
        }
        
        $contenidoGenerado = generarConIA($tituloGenerado, $descripcionOriginal, $nombreFuente, $esBreaking);
        $descripcionGenerada = generarDescripcion($contenidoGenerado);
        $categoria = obtenerCategoria($tituloGenerado, $descripcionGenerada);
        
        // Descargar imagen
        $imagenLocal = descargarImagen($imagenUrlOriginal, $tituloGenerado);
        
        // Insertar en BD
        try {
            $stmt = $pdo->prepare("
                INSERT INTO noticias_robot 
                (titulo_original, titulo_generado, contenido_generado, descripcion_generada, fuente_url, fuente_nombre, categoria, imagen_url, estado, es_breaking, fuente_score)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?, ?)
            ");
            $stmt->execute([
                $tituloOriginal, $tituloGenerado, $contenidoGenerado, $descripcionGenerada,
                $enlace, $nombreFuente, $categoria, $imagenLocal, $esBreaking ? 1 : 0, (int)$scoreFuente
            ]);
            
            $noticiasNuevas++;
            $noticiasProcesadas++;
            $countFuente++;
            $badge = $esBreaking ? ' 🔴' : '';
            echo "  ✓ {$tituloGenerado} [{$categoria}] (score:{$scoreFuente}){$badge}\n";
            
        } catch (Exception $e) {
            echo "  ✗ Error BD: " . $e->getMessage() . "\n";
            $errores++;
        }
    }
    
    echo "  {$countFuente} noticias de {$nombreFuente}\n";
}

echo "\n=== RESUMEN ===\n";
echo "✅ Nuevas: {$noticiasNuevas}\n";
echo "🔴 Breaking: {$breakingCount}\n";
echo "🔄 Duplicadas: {$noticiasDuplicadas}\n";
echo "❌ Errores: {$errores}\n";
echo "📊 Total: {$noticiasProcesadas}\n";
echo "Fecha fin: " . date('Y-m-d H:i:s') . "\n";

$logMsg = date('Y-m-d H:i:s') . " | Nuevas: {$noticiasNuevas} | Breaking: {$breakingCount} | Duplicadas: {$noticiasDuplicadas} | Errores: {$errores}\n";
file_put_contents('robot_log.txt', $logMsg, FILE_APPEND);