CREATE DATABASE IF NOT EXISTS lrc1970 CHARACTER
SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE lrc1970;

-- 1. USUÁRIOS
CREATE TABLE
    users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(50) NOT NULL,
        sobrenome varchar(100) not null,
        telefone VARCHAR(20),
        email VARCHAR(100) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        tipo ENUM ('cliente', 'administrador') NOT NULL DEFAULT 'cliente'
    );

-- 2. CATEGORIAS
CREATE TABLE
    categorias (
        id_categoria INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL UNIQUE
    );

-- 3. CEPS (Isolamento de dependências transitivas - 3FN)
CREATE TABLE
    ceps (
        cep VARCHAR(9) PRIMARY KEY,
        logradouro VARCHAR(150) NOT NULL,
        bairro VARCHAR(100) NOT NULL,
        cidade VARCHAR(100) NOT NULL,
        estado CHAR(2) NOT NULL
    );

-- 4. ENDEREÇOS DE USUÁRIOS
CREATE TABLE
    enderecos (
        id_endereco INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        cep VARCHAR(9) NOT NULL,
        numero VARCHAR(20) NOT NULL,
        complemento VARCHAR(100),
        FOREIGN KEY (id_usuario) REFERENCES users (id) ON DELETE CASCADE,
        FOREIGN KEY (cep) REFERENCES ceps (cep)
    );

-- 5. VEÍCULOS
CREATE TABLE
    veiculos (
        id_veiculo INT AUTO_INCREMENT PRIMARY KEY,
        marca VARCHAR(50) NOT NULL,
        modelo VARCHAR(100) NOT NULL,
        ano_inicio YEAR,
        ano_fim YEAR
    );

-- 6. PRODUTOS
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

-- 7. COMPATIBILIDADE
CREATE TABLE
    compatibilidades (
        id_produto INT NOT NULL,
        id_veiculo INT NOT NULL,
        PRIMARY KEY (id_produto, id_veiculo),
        FOREIGN KEY (id_produto) REFERENCES produtos (id_produto) ON DELETE CASCADE,
        FOREIGN KEY (id_veiculo) REFERENCES veiculos (id_veiculo) ON DELETE CASCADE
    );

-- 8. CARRINHOS
CREATE TABLE
    carrinhos (
        id_carrinho INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status ENUM ('ativo', 'finalizado', 'abandonado') NOT NULL DEFAULT 'ativo',
        FOREIGN KEY (id_usuario) REFERENCES users (id)
    );

-- 9. ITENS DO CARRINHO
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

-- 10. PEDIDOS
CREATE TABLE
    pedidos (
        id_pedido INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        id_endereco INT NOT NULL,
        id_carrinho INT UNIQUE,
        data_pedido DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status ENUM (
            'aguardando_pagamento',
            'pago',
            'em_preparacao',
            'enviado',
            'entregue',
            'cancelado'
        ) NOT NULL DEFAULT 'aguardando_pagamento',
        FOREIGN KEY (id_usuario) REFERENCES users (id),
        FOREIGN KEY (id_endereco) REFERENCES enderecos (id_endereco),
        FOREIGN KEY (id_carrinho) REFERENCES carrinhos (id_carrinho)
    );

-- 11. ITENS DO PEDIDO
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

-- 12. MOVIMENTAÇÕES DE ESTOQUE (HISTÓRICO)
CREATE TABLE
    movimentacoes_estoque (
        id_movimentacao INT AUTO_INCREMENT PRIMARY KEY,
        id_produto INT NOT NULL,
        id_usuario INT,
        tipo ENUM ('entrada', 'saida', 'ajuste') NOT NULL,
        quantidade INT NOT NULL,
        data_movimentacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        observacao VARCHAR(255),
        FOREIGN KEY (id_produto) REFERENCES produtos (id_produto) ON DELETE CASCADE,
        FOREIGN KEY (id_usuario) REFERENCES users (id) ON DELETE SET NULL
    );