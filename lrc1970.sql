create database lrc1970;
use lrc1970;

create table users(
	id int auto_increment primary key,
	nome varchar(100) not null,
    telefone varchar(15),
    email varchar(100) unique not null,
    cep varchar(9) unique not null,
    senha varchar(100) not null,
    token varchar(20) null,
    token_expiration datetime null
);