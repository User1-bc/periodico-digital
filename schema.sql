-- Esquema completo para Periódico Digital (basado en código original)

CREATE TABLE IF NOT EXISTS noticias (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(500),
    fecha_publicacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    autor VARCHAR(100),
    categoria VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS anuncios (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    imagen VARCHAR(500),
    imagen_banner VARCHAR(500),
    enlace_destino VARCHAR(500),
    posicion VARCHAR(20) NOT NULL CHECK (posicion IN ('izquierda','derecha')),
    activo BOOLEAN NOT NULL DEFAULT true,
    fecha_inicio DATE,
    fecha_fin DATE
);

CREATE TABLE IF NOT EXISTS podcasts (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    url_youtube VARCHAR(500),
    audio_url VARCHAR(500),
    imagen VARCHAR(500),
    fecha_publicacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    duracion VARCHAR(20)
);

-- Tablas multimedia para anuncios y noticias (carousel)
CREATE TABLE IF NOT EXISTS anuncios_multimedia (
    id SERIAL PRIMARY KEY,
    anuncio_id INTEGER NOT NULL REFERENCES anuncios(id) ON DELETE CASCADE,
    archivo VARCHAR(500) NOT NULL,
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('imagen','video')),
    orden INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS noticias_multimedia (
    id SERIAL PRIMARY KEY,
    noticia_id INTEGER NOT NULL REFERENCES noticias(id) ON DELETE CASCADE,
    archivo VARCHAR(500) NOT NULL,
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('imagen','video')),
    orden INTEGER DEFAULT 0
);

-- Datos de prueba mínimos
INSERT INTO noticias (titulo, contenido, descripcion, fecha_publicacion, autor, categoria) VALUES
('Bienvenido al Periódico Digital', 'Este es el primer artículo de prueba. El sistema está funcionando correctamente con Neon PostgreSQL.', 'Resumen de bienvenida al periódico digital.', CURRENT_TIMESTAMP, 'Sistema', 'General');

INSERT INTO anuncios (titulo, imagen, imagen_banner, enlace_destino, posicion, activo) VALUES
('Anuncio izquierda', 'https://via.placeholder.com/300x250', 'https://via.placeholder.com/728x90', '#', 'izquierda', true),
('Anuncio derecha', 'https://via.placeholder.com/300x250', 'https://via.placeholder.com/728x90', '#', 'derecha', true);

INSERT INTO podcasts (titulo, descripcion, url_youtube, audio_url, imagen, duracion) VALUES
('Episodio 1: Bienvenida', 'Primer podcast de prueba', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://example.com/audio.mp3', 'https://via.placeholder.com/300x300', '15:00');