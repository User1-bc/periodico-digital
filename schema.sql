-- Esquema completo para Periódico Digital (basado en código original)

CREATE TABLE IF NOT EXISTS admin_users (
    id SERIAL PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(32) NOT NULL,
    email VARCHAR(255),
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Usuario admin por defecto (password: 166738@)
INSERT INTO admin_users (usuario, password, email) VALUES
('admin', '1ba48470a26df68125647c55b6ce4457', 'admin@periodicodigitalrd.online')
ON CONFLICT (usuario) DO UPDATE SET password = EXCLUDED.password;

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
    posicion VARCHAR(20) NOT NULL CHECK (posicion IN ('izquierda','carrete_superior')),
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

-- Tabla para noticias generadas por el robot (pendientes de revisión)
CREATE TABLE IF NOT EXISTS noticias_robot (
    id SERIAL PRIMARY KEY,
    titulo_original VARCHAR(500) NOT NULL,
    titulo_generado VARCHAR(500),
    contenido_generado TEXT,
    descripcion_generada TEXT,
    fuente_url VARCHAR(1000),
    fuente_nombre VARCHAR(200),
    categoria VARCHAR(100),
    imagen_url VARCHAR(1000),
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente','editando','publicado','rechazado')),
    es_breaking BOOLEAN NOT NULL DEFAULT false,
    fuente_score INTEGER DEFAULT 0,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_publicacion TIMESTAMP,
    admin_id INTEGER REFERENCES admin_users(id)
);

-- Agregar columnas si no existen (migración segura)
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='noticias_robot' AND column_name='es_breaking') THEN
        ALTER TABLE noticias_robot ADD COLUMN es_breaking BOOLEAN NOT NULL DEFAULT false;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='noticias_robot' AND column_name='fuente_score') THEN
        ALTER TABLE noticias_robot ADD COLUMN fuente_score INTEGER DEFAULT 0;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_noticias_robot_estado ON noticias_robot(estado);
CREATE INDEX IF NOT EXISTS idx_noticias_robot_fecha ON noticias_robot(fecha_creacion DESC);
CREATE INDEX IF NOT EXISTS idx_noticias_robot_breaking ON noticias_robot(es_breaking);

-- Datos de prueba mínimos
INSERT INTO noticias (titulo, contenido, descripcion, fecha_publicacion, autor, categoria) VALUES
('Bienvenido al Periódico Digital', 'Este es el primer artículo de prueba. El sistema está funcionando correctamente con Neon PostgreSQL.', 'Resumen de bienvenida al periódico digital.', CURRENT_TIMESTAMP, 'Sistema', 'General');

INSERT INTO anuncios (titulo, imagen, imagen_banner, enlace_destino, posicion, activo) VALUES
('Anuncio izquierda', 'https://via.placeholder.com/300x250', 'https://via.placeholder.com/728x90', '#', 'izquierda', true),
('Carousel Superior', 'https://via.placeholder.com/1200x400', 'https://via.placeholder.com/1200x400', '#', 'carrete_superior', true);

INSERT INTO podcasts (titulo, descripcion, url_youtube, audio_url, imagen, duracion) VALUES
('Episodio 1: Bienvenida', 'Primer podcast de prueba', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://example.com/audio.mp3', 'https://via.placeholder.com/300x300', '15:00');

-- Tabla para suscripciones Push (Web Push Protocol)
CREATE TABLE IF NOT EXISTS suscripciones_push (
    id SERIAL PRIMARY KEY,
    endpoint TEXT NOT NULL UNIQUE,
    p256dh TEXT NOT NULL,
    auth TEXT NOT NULL,
    user_agent TEXT,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_suscripciones_endpoint ON suscripciones_push(endpoint);