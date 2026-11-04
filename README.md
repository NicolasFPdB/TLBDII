# LRC1970 — TLBDII

## Estrutura
- `app/`: controladores PHP, configuração e middleware.
- `database/`: script único de criação/ajuste da estrutura do banco.
- `public/`: páginas, estilos, JavaScript e catálogo JSON.

## Preparação local
1. Faça uma cópia de segurança do banco atual. **Atenção:** o script `lrc1970.sql` deve ser executado uma única vez em cada banco. Se as colunas `reset_token_hash` e `reset_token_expires_at` já existirem, remova o bloco `ALTER TABLE users` antes de executar novamente.
2. Execute `database/lrc1970.sql` no MySQL. Esse é o único arquivo SQL do projeto: cria as tabelas e adiciona as colunas necessárias à redefinição de senha.
4. Configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS` no ambiente do PHP. `.env.example` é apenas um modelo; PHP não carrega esse arquivo automaticamente.
5. Configure as variáveis `SMTP_*` e instale PHPMailer via Composer para que a redefinição de senha envie e-mails.
6. Configure o servidor web para servir a pasta `public/` e mantenha `app/` e `database/` fora da raiz pública. Se executar com outra raiz, revise os caminhos relativos usados pelo frontend.

## Alterações principais nesta revisão
- Cadastro e login agora respondem com um contrato JSON único (`status` e `message`), sem encerrar a execução antes das validações.
- Perfil atualiza a tabela `users`, com validação de e-mail e telefone.
- Redefinição de senha usa hash SHA-256 do código, expiração de 30 minutos, limite de tentativas e invalidação após uso.
- Middleware valida também a presença do identificador do usuário e configura cookie de sessão conforme HTTPS.
- Sincronização do catálogo ignora códigos duplicados/invalidos, usa transação e não sobrescreve produtos existentes.
- Filtros de catálogo combinam busca e categoria; o carrinho não cria itens de demonstração nem inventa preço.
- Checkout permanece desabilitado: o projeto ainda não possui fluxo servidor validado para preço, estoque, frete, pagamento e criação de pedido.

## Observações importantes
- O catálogo `public/assets/data/data.json` não possui IDs internos nem preços. O carrinho usa o primeiro código de cada produto apenas como identificador local. O backend continua sendo necessário para consultar preço/estoque reais.
- A tabela `produtos.codigo` é única. Como há códigos repetidos ou múltiplos códigos para uma mesma peça no JSON, esta versão insere apenas códigos ainda não cadastrados; não tenta inferir que registros com códigos diferentes representam a mesma peça.
- O CEP é guardado na tabela compartilhada `ceps`; ao salvar endereço, os dados já existentes do CEP não são sobrescritos por dados enviados por outro usuário.
- Ainda é necessário implementar proteção CSRF para todas as operações que alteram dados, além de testes de integração no MySQL e revisão do fluxo de pedidos antes de publicar o sistema.
- Não foram executados testes contra seu servidor ou banco de dados real. Faça os testes descritos abaixo no seu ambiente.

## Checklist de validação
1. Cadastrar usuário com dados válidos, e-mail repetido e senha curta.
2. Entrar com senha correta/incorreta e verificar que falhas não redirecionam para a área autenticada.
3. Solicitar redefinição de senha com SMTP configurado; testar código correto, incorreto e expirado.
4. Alterar dados do perfil e tentar usar e-mail já cadastrado por outra conta.
5. Salvar endereço e verificar que um CEP existente não teve seus dados mestres sobrescritos.
6. Entrar como administrador, sincronizar o JSON duas vezes e conferir que a segunda execução não duplica códigos.
7. Testar edição de produto, código duplicado, busca e filtros por categoria.
8. Verificar carrinho vazio e itens salvos; confirmar que preço e checkout não são apresentados como valores reais.
