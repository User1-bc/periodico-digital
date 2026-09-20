-- Esquema para Periódico Digital

CREATE TABLE IF NOT EXISTS noticias (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    imagen VARCHAR(500),
    fecha_publicacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    autor VARCHAR(100),
    categoria VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS anuncios (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    imagen VARCHAR(500),
    enlace VARCHAR(500),
    posicion VARCHAR(20) NOT NULL CHECK (posicion IN ('izquierda','derecha')),
    activo BOOLEAN NOT NULL DEFAULT true,
    fecha_inicio DATE,
    fecha_fin DATE
);

CREATE TABLE IF NOT EXISTS podcasts (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    audio_url VARCHAR(500) NOT NULL,
    imagen VARCHAR(500),
    fecha_publicacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    duracion VARCHAR(20)
);

-- Datos de prueba mínimos
INSERT INTO noticias (titulo, contenido, fecha_publicacion, autor, categoria) VALUES
('Bienvenido al Periódico Digital', 'Este es el primer artículo de prueba. El sistema está funcionando correctamente con Neon PostgreSQL.', CURRENT_TIMESTAMP, 'Sistema', 'General');

INSERT INTO anuncios (titulo, imagen, enlace, posicion, activo) VALUES
('Anuncio izquierda', 'https://via.placeholder.com/300x250', '#', 'izquierda', true),
('Anuncio derecha', 'https://via.placeholder.com/300x250', '#', 'derecha', true);

INSERT INTO podcasts (titulo, descripcion, audio_url, imagen, duracion) VALUES
('Episodio 1: Bienvenida', 'Primer podcast de prueba', 'https://example.com/audio.mp3', 'https://via.placeholder.com/300x300', '15:00');