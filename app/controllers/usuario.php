<?php
declare(strict_types=1);
require_once __DIR__ . '/../middleware/session.php';
require_once __DIR__ . '/../config/conection.php';
header('Content-Type: application/json; charset=utf-8');

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$isAdmin = ($_SESSION['user_type'] ?? '') === 'administrador';

function respond(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function requireAdmin(bool $isAdmin): void
{
    if (!$isAdmin)
        respond(['status' => 'error', 'message' => 'Acesso restrito a administradores.'], 403);
}

try {
    switch ($action) {
        case 'get_profile':
            $stmt = $pdo->prepare('SELECT id, nome, sobrenome, email, telefone, tipo FROM users WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
            if (!$user)
                respond(['status' => 'error', 'message' => 'Usuário não encontrado.'], 404);
            $user['nome_completo'] = trim($user['nome'] . ' ' . $user['sobrenome']);
            respond(['status' => 'ok', 'user' => $user]);

        case 'update_info':
            $nome = trim((string) ($_POST['nome'] ?? ''));
            $sobrenome = trim((string) ($_POST['sobrenome'] ?? ''));
            $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            $telefone = trim((string) ($_POST['telefone'] ?? ''));
            if ($nome === '' || $sobrenome === '' || !$email)
                respond(['status' => 'error', 'message' => 'Nome, sobrenome e e-mail válido são obrigatórios.'], 422);
            $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id');
            $check->execute([':email' => $email, ':id' => $userId]);
            if ($check->fetch())
                respond(['status' => 'error', 'message' => 'Este e-mail já está em uso.'], 409);
            $stmt = $pdo->prepare('UPDATE users SET nome=:nome, sobrenome=:sobrenome, email=:email, telefone=:telefone WHERE id=:id');
            $stmt->execute([':nome' => $nome, ':sobrenome' => $sobrenome, ':email' => $email, ':telefone' => $telefone, ':id' => $userId]);
            respond(['status' => 'ok', 'message' => 'Dados atualizados com sucesso!']);

        case 'get_addresses':
            $stmt = $pdo->prepare('SELECT e.id_endereco, e.cep, e.numero, e.complemento, c.logradouro, c.bairro, c.cidade, c.estado FROM enderecos e JOIN ceps c ON c.cep=e.cep WHERE e.id_usuario=:id ORDER BY e.id_endereco DESC');
            $stmt->execute([':id' => $userId]);
            respond(['status' => 'ok', 'addresses' => $stmt->fetchAll()]);

        case 'save_address':
            $cep = preg_replace('/\D/', '', (string) ($_POST['cep'] ?? ''));
            $logradouro = trim((string) ($_POST['logradouro'] ?? ''));
            $numero = trim((string) ($_POST['numero'] ?? ''));
            $complemento = trim((string) ($_POST['complemento'] ?? ''));
            $bairro = trim((string) ($_POST['bairro'] ?? ''));
            $cidade = trim((string) ($_POST['cidade'] ?? ''));
            $estado = strtoupper(trim((string) ($_POST['estado'] ?? '')));
            if (!preg_match('/^\d{8}$/', $cep) || !$logradouro || !$numero || !$bairro || !$cidade || !preg_match('/^[A-Z]{2}$/', $estado)) {
                respond(['status' => 'error', 'message' => 'Preencha todos os campos do endereço corretamente.'], 422);
            }
            $pdo->beginTransaction();
            $cepStmt = $pdo->prepare('INSERT INTO ceps (cep, logradouro, bairro, cidade, estado) VALUES (:cep,:logradouro,:bairro,:cidade,:estado) ON DUPLICATE KEY UPDATE logradouro=VALUES(logradouro), bairro=VALUES(bairro), cidade=VALUES(cidade), estado=VALUES(estado)');
            $cepStmt->execute([':cep' => $cep, ':logradouro' => $logradouro, ':bairro' => $bairro, ':cidade' => $cidade, ':estado' => $estado]);
            $existing = $pdo->prepare('SELECT id_endereco FROM enderecos WHERE id_usuario=:id AND cep=:cep AND numero=:numero LIMIT 1');
            $existing->execute([':id' => $userId, ':cep' => $cep, ':numero' => $numero]);
            if ($existing->fetch()) {
                $upd = $pdo->prepare('UPDATE enderecos SET complemento=:complemento WHERE id_usuario=:id AND cep=:cep AND numero=:numero');
                $upd->execute([':complemento' => $complemento, ':id' => $userId, ':cep' => $cep, ':numero' => $numero]);
            } else {
                $ins = $pdo->prepare('INSERT INTO enderecos (id_usuario, cep, numero, complemento) VALUES (:id,:cep,:numero,:complemento)');
                $ins->execute([':id' => $userId, ':cep' => $cep, ':numero' => $numero, ':complemento' => $complemento]);
            }
            $pdo->commit();
            respond(['status' => 'ok', 'message' => 'Endereço salvo com sucesso!']);

        case 'list_products':
            requireAdmin($isAdmin);
            $stmt = $pdo->query('SELECT id_produto AS id, codigo, nome, descricao, preco, estoque FROM produtos ORDER BY id_produto DESC LIMIT 100');
            respond(['status' => 'ok', 'products' => $stmt->fetchAll()]);

        case 'sync_json':
            requireAdmin($isAdmin);
            $jsonPath = __DIR__ . '/../../public/assets/data/data.json';
            $items = json_decode((string) file_get_contents($jsonPath), true);
            if (!is_array($items))
                respond(['status' => 'error', 'message' => 'Arquivo de produtos inválido.'], 422);
            $stmt = $pdo->prepare('INSERT INTO produtos (codigo, nome, descricao, preco, estoque) VALUES (:codigo,:nome,:descricao,:preco,:estoque) ON DUPLICATE KEY UPDATE nome=VALUES(nome), descricao=VALUES(descricao)');
            $count = 0;
            foreach ($items as $item) {
                $rawCodes = $item['Código'] ?? [];
                $codes = is_array($rawCodes) ? $rawCodes : [$rawCodes];
                $name = trim((string) ($item['Descrição do Produto'] ?? 'Produto sem descrição'));
                foreach ($codes as $rawCode) {
                    $code = trim((string) $rawCode);
                    if ($code === '' || strlen($code) > 30)
                        continue;
                    $stmt->execute([':codigo' => $code, ':nome' => substr($name, 0, 150), ':descricao' => $name, ':preco' => 0, ':estoque' => 0]);
                    $count++;
                }
            }
            respond(['status' => 'ok', 'message' => "Sincronização concluída: {$count} códigos processados. Preço e estoque permanecem zerados até serem informados no cadastro do produto."]);

        case 'save_product':
            requireAdmin($isAdmin);
            $id = (int) ($_POST['id'] ?? 0);
            $codigo = trim((string) ($_POST['codigo'] ?? ''));
            $nome = trim((string) ($_POST['descricao'] ?? ''));
            $preco = filter_var($_POST['preco'] ?? null, FILTER_VALIDATE_FLOAT);
            $estoque = filter_var($_POST['estoque'] ?? null, FILTER_VALIDATE_INT);
            if ($codigo === '' || strlen($codigo) > 30 || $nome === '' || $preco === false || $preco < 0 || $estoque === false || $estoque < 0)
                respond(['status' => 'error', 'message' => 'Informe código, descrição, preço e estoque válidos.'], 422);
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE produtos SET codigo=:codigo,nome=:nome,preco=:preco,estoque=:estoque WHERE id_produto=:id');
                $stmt->execute([':codigo' => $codigo, ':nome' => $nome, ':preco' => $preco, ':estoque' => $estoque, ':id' => $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO produtos (codigo,nome,preco,estoque) VALUES (:codigo,:nome,:preco,:estoque)');
                $stmt->execute([':codigo' => $codigo, ':nome' => $nome, ':preco' => $preco, ':estoque' => $estoque]);
            }
            respond(['status' => 'ok', 'message' => 'Produto salvo com sucesso!']);

        default:
            respond(['status' => 'error', 'message' => 'Ação inválida.'], 400);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    error_log('Controller usuario: ' . $e->getMessage());
    respond(['status' => 'error', 'message' => 'Não foi possível concluir a operação. Verifique os dados e tente novamente.'], 500);
}
