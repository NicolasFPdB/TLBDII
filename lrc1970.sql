CREATE DATABASE IF NOT EXISTS lrc1970 CHARACTER
SET
    utf8mb4 COLLATE utf8mb4_unicode_ci;

USE lrc1970;

-- 1. USUÁRIOS
CREATE TABLE
    users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        telefone VARCHAR(20),
        email VARCHAR(100) NOT NULL UNIQUE,
        cep VARCHAR(9),
        senha VARCHAR(255) NOT NULL,
        tipo ENUM ('cliente', 'administrador') NOT NULL DEFAULT 'cliente'
    );

-- 2. CATEGORIAS
CREATE TABLE
    categorias (
        id_categoria INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL UNIQUE
    );

-- 3. VEÍCULOS
CREATE TABLE
    veiculos (
        id_veiculo INT AUTO_INCREMENT PRIMARY KEY,
        marca VARCHAR(50) NOT NULL,
        modelo VARCHAR(100) NOT NULL,
        ano_inicio YEAR,
        ano_fim YEAR
    );

-- 5. PRODUTOS
CREATE TABLE
    produtos (
        id_produto INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(30) NOT NULL UNIQUE,
        nome VARCHAR(150) NOT NULL,
        descricao TEXT,
        preco DECIMAL(10, 2) NOT NULL,
        estoque INT NOT NULL DEFAULT 0,
        id_categoria INT,
        FOREIGN KEY (id_categoria) REFERENCES categorias (id_categoria)
    );

-- 6. COMPATIBILIDADE
CREATE TABLE
    compatibilidades (
        id_produto INT NOT NULL,
        id_veiculo INT NOT NULL,
        PRIMARY KEY (id_produto, id_veiculo),
        FOREIGN KEY (id_produto) REFERENCES produtos (id_produto) ON DELETE CASCADE,
        FOREIGN KEY (id_veiculo) REFERENCES veiculos (id_veiculo) ON DELETE CASCADE
    );

-- 7. CARRINHOS
CREATE TABLE
    carrinhos (
        id_carrinho INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status ENUM ('ativo', 'finalizado', 'abandonado') NOT NULL DEFAULT 'ativo',
        FOREIGN KEY (id_usuario) REFERENCES users (id)
    );

-- 8. ITENS DO CARRINHO
CREATE TABLE
    itens_carrinho (
        id_item INT AUTO_INCREMENT PRIMARY KEY,
        id_carrinho INT NOT NULL,
        id_produto INT NOT NULL,
        quantidade INT NOT NULL,
        FOREIGN KEY (id_carrinho) REFERENCES carrinhos (id_carrinho) ON DELETE CASCADE,
        FOREIGN KEY (id_produto) REFERENCES produtos (id_produto),
        UNIQUE (id_carrinho, id_produto)
    );

-- 9. PEDIDOS
CREATE TABLE
    pedidos (
        id_pedido INT AUTO_INCREMENT PRIMARY KEY,
        id_carrinho INT NOT NULL UNIQUE,
        data_pedido DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status ENUM (
            'aguardando_pagamento',
            'pago',
            'em_preparacao',
            'enviado',
            'entregue',
            'cancelado'
        ) NOT NULL DEFAULT 'aguardando_pagamento',
        valor_total DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (id_carrinho) REFERENCES carrinhos (id_carrinho)
    );

-- 10. ITENS DO PEDIDO
CREATE TABLE
    itens_pedido (
        id_item_pedido INT AUTO_INCREMENT PRIMARY KEY,
        id_pedido INT NOT NULL,
        id_produto INT NOT NULL,
        quantidade INT NOT NULL,
        preco_unitario DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (id_pedido) REFERENCES pedidos (id_pedido) ON DELETE CASCADE,
        FOREIGN KEY (id_produto) REFERENCES produtos (id_produto)
    );

-- 11. ENDEREÇOS
CREATE TABLE
    enderecos (
        id_endereco INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        cep VARCHAR(9) NOT NULL,
        logradouro VARCHAR(150),
        numero VARCHAR(20),
        complemento VARCHAR(100),
        bairro VARCHAR(100),
        cidade VARCHAR(100),
        estado CHAR(2),
        FOREIGN KEY (id_usuario) REFERENCES users (id) ON DELETE CASCADE
    );