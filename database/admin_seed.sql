USE lrc1970;

-- Gere o hash com PHP: php -r "echo password_hash('SuaSenhaAqui', PASSWORD_DEFAULT), PHP_EOL;"
INSERT INTO users(nome,sobrenome,telefone,email,senha,tipo) VALUES('Administrador','LRC1970','(11) 99159-9709','admin@lrc1970.com','COLE_AQUI_O_HASH_DA_SENHA','administrador');
