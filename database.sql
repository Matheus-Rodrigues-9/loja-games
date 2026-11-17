CREATE DATABASE IF NOT EXISTS loja_games
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE loja_games;
CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS games (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0,
    estoque INT UNSIGNED NOT NULL DEFAULT 0,
    categoria VARCHAR(100) NOT NULL,
    categoria_id INT UNSIGNED NULL,
    imagem VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_games_titulo (titulo),
    INDEX idx_games_categoria (categoria),
    INDEX idx_games_categoria_id (categoria_id),
    CONSTRAINT fk_games_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('admin','operador','cliente') NOT NULL DEFAULT 'operador',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE usuarios MODIFY nivel ENUM('admin','operador','cliente') NOT NULL DEFAULT 'operador';

CREATE TABLE IF NOT EXISTS clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL UNIQUE,
    telefone VARCHAR(30) NULL,
    endereco VARCHAR(255) NULL,
    cidade VARCHAR(120) NULL,
    cep VARCHAR(20) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clientes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS pedidos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    nome_destinatario VARCHAR(150) NOT NULL,
    endereco VARCHAR(255) NOT NULL,
    cidade VARCHAR(120) NOT NULL,
    cep VARCHAR(20) NOT NULL,
    forma_pagamento ENUM('pix','cartao','boleto') NOT NULL DEFAULT 'pix',
    status ENUM('recebido','em_preparacao','enviado','concluido','cancelado') NOT NULL DEFAULT 'recebido',
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    frete DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pedidos_usuario_data (usuario_id, created_at),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itens_pedido (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id BIGINT UNSIGNED NOT NULL,
    game_id INT UNSIGNED NULL,
    titulo_jogo VARCHAR(150) NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    quantidade INT UNSIGNED NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    INDEX idx_itens_pedido_pedido (pedido_id),
    CONSTRAINT fk_itens_pedido_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    CONSTRAINT fk_itens_pedido_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuarios (nome, email, senha, nivel, created_at, updated_at)
SELECT 'Administrador', 'admin@lojagames.local', '$2y$12$HXHcgUsvrfdOc5hvDSyub.GD/DrRgyQjKZsweOv4/A0RRP9eArjWK', 'admin', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE email = 'admin@lojagames.local');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'The Legend of Zelda: Breath of the Wild', 'Um jogo de aventura em mundo aberto com exploração, enigmas e liberdade para descobrir novos caminhos.', 299.99, 10, 'Aventura', 'zelda.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'The Legend of Zelda: Breath of the Wild');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'God of War', 'Uma jornada de ação intensa com combates, exploração e uma narrativa épica.', 249.99, 15, 'Ação', 'god-of-war.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'God of War');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Minecraft', 'Construa, explore e sobreviva em um mundo aberto feito de blocos e possibilidades quase infinitas.', 129.99, 20, 'Sandbox', 'minecraft.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Minecraft');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Cyber Odyssey', 'Explore uma metrópole futurista, evolua seu personagem e tome decisões que mudam o rumo da história.', 179.90, 12, 'RPG', 'cyber-odyssey.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Cyber Odyssey');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Neon Velocity', 'Corridas em alta velocidade por pistas neon, campeonatos urbanos e carros personalizáveis.', 149.90, 8, 'Corrida', 'neon-velocity.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Neon Velocity');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Kingdom of Ashes', 'RPG de fantasia com reinos em guerra, magia antiga e missões que alteram alianças.', 219.90, 7, 'RPG', 'kingdom-of-ashes.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Kingdom of Ashes');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Pixel Raiders', 'Ação retrô com fases rápidas, chefes desafiadores e modo cooperativo local.', 69.90, 18, 'Ação', 'pixel-raiders.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Pixel Raiders');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Stellar Colony', 'Gerencie uma colônia espacial, pesquise tecnologias e sobreviva em planetas hostis.', 119.90, 9, 'Estratégia', 'stellar-colony.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Stellar Colony');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Nightfall Protocol', 'Suspense e terror em uma instalação abandonada, com recursos limitados e investigação.', 159.90, 5, 'Terror', 'nightfall-protocol.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Nightfall Protocol');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Turbo Legends', 'Compita em campeonatos internacionais com pistas dinâmicas e diferentes classes de veículos.', 189.90, 14, 'Corrida', 'turbo-legends.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Turbo Legends');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Arcane Arena', 'Combates mágicos em arenas competitivas, com heróis, habilidades e partidas rápidas.', 99.90, 16, 'Ação', 'arcane-arena.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Arcane Arena');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Farmverse', 'Construa sua fazenda, plante, crie animais e expanda sua comunidade em ritmo tranquilo.', 79.90, 25, 'Simulação', 'farmverse.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Farmverse');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Skybound Adventures', 'Aventura de plataforma em ilhas flutuantes com desafios, colecionáveis e segredos.', 89.90, 11, 'Plataforma', 'skybound-adventures.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Skybound Adventures');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Tactical Frontline', 'Planeje esquadrões, controle territórios e vença campanhas táticas por turnos.', 139.90, 6, 'Estratégia', 'tactical-frontline.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Tactical Frontline');

INSERT INTO games (titulo, descricao, preco, estoque, categoria, imagem, created_at, updated_at)
SELECT 'Goal Masters 26', 'Monte seu time, dispute campeonatos e gerencie estratégias em partidas de futebol.', 199.90, 22, 'Esportes', 'goal-masters-26.svg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM games WHERE titulo = 'Goal Masters 26');


-- Normaliza as categorias existentes e vincula os jogos à tabela categorias.
INSERT IGNORE INTO categorias (nome) SELECT DISTINCT categoria FROM games WHERE categoria IS NOT NULL AND categoria <> '';
UPDATE games g INNER JOIN categorias c ON c.nome = g.categoria SET g.categoria_id = c.id WHERE g.categoria_id IS NULL;
INSERT IGNORE INTO clientes (usuario_id) SELECT id FROM usuarios WHERE nivel = 'cliente';
