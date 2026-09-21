<?php
require_once 'conexion.php';

try {
    // Actualizar el check constraint
    $pdo->exec("ALTER TABLE anuncios DROP CONSTRAINT IF EXISTS anuncios_posicion_check;");
    $pdo->exec("ALTER TABLE anuncios ADD CONSTRAINT anuncios_posicion_check CHECK (posicion IN ('izquierda','carrete_superior'));");
    echo "✅ Check constraint actualizado\n";
    
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT id FROM anuncios WHERE posicion = 'carrete_superior'");
    $stmt->execute();
    $exists = $stmt->fetchColumn();
    
    if (!$exists) {
        $stmt = $pdo->prepare("INSERT INTO anuncios (titulo, imagen, imagen_banner, enlace_destino, posicion, activo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'Carousel Superior',
            'https://via.placeholder.com/1200x400',
            'https://via.placeholder.com/1200x400',
            '#',
            'carrete_superior',
            true
        ]);
        echo "✅ Anuncio 'Carousel Superior' creado\n";
    } else {
        echo "ℹ️ Ya existe anuncio en carrete_superior\n";
    }
    
    // Verificar multimedia
    $stmt = $pdo->prepare("SELECT id FROM anuncios WHERE posicion = 'carrete_superior'");
    $stmt->execute();
    $ad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ad) {
        $stmt = $pdo->prepare("SELECT * FROM anuncios_multimedia WHERE anuncio_id = ?");
        $stmt->execute([$ad['id']]);
        $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($media)) {
            $stmt = $pdo->prepare("INSERT INTO anuncios_multimedia (anuncio_id, archivo, tipo, orden) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ad['id'], 'https://via.placeholder.com/1200x400/FF6B6B/FFFFFF?text=Carousel+1', 'imagen', 1]);
            $stmt->execute([$ad['id'], 'https://via.placeholder.com/1200x400/4ECDC4/FFFFFF?text=Carousel+2', 'imagen', 2]);
            $stmt->execute([$ad['id'], 'https://via.placeholder.com/1200x400/45B7D1/FFFFFF?text=Carousel+3', 'imagen', 3);
            echo "✅ Multimedia de prueba agregada (3 slides)\n";
        } else {
            echo "ℹ️ Ya tiene multimedia: " . count($media) . " items\n";
        }
    }
    
    echo "✅ Listo\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>