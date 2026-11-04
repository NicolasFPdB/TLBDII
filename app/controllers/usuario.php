<?php
// Middleware de Sessão e JSON
    require_once __DIR__ . '/../middleware/session.php';
// Conexão com a Database
    include_once('conection.php');

$userId = $_SESSION['user_id'] ?? 1; // ID fallback para testes
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // Retorna dados do perfil do usuário logado
        case 'get_profile':
            $stmt = $pdo->prepare("SELECT id, nome, email, telefone, tipo FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // Mock de teste se banco estiver vazio
                $user = [
                    'id' => $userId,
                    'nome' => $_SESSION['user_name'] ?? 'Administrador',
                    'email' => 'admin@lrc1970.com',
                    'telefone' => '(11) 99159-9709',
                    'tipo' => 'administrador'
                ];
            }

            echo json_encode(['status' => 'ok', 'user' => $user], JSON_UNESCAPED_UNICODE);
            break;

        // Atualizar Informações Pessoais
        case 'update_info':
            $nome = trim($_POST['nome'] ?? '');
            $sobrenome = trim($_POST['sobrenome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefone = trim($_POST['telefone'] ?? '');
            $nomeCompleto = trim($nome . ' ' . $sobrenome);

            $stmt = $pdo->prepare("UPDATE users SET nome = :nome, email = :email, telefone = :telefone WHERE id = :id");
            $stmt->execute([
                ':nome' => $nomeCompleto,
                ':email' => $email,
                ':telefone' => $telefone,
                ':id' => $userId
            ]);

            echo json_encode(['status' => 'ok', 'message' => 'Dados atualizados com sucesso!'], JSON_UNESCAPED_UNICODE);
            break;

        // Listar produtos (Lê da base de dados ou faz fallback para data.json)
        case 'list_products':
            $stmt = $pdo->query("SELECT id_produto as id, codigo, nome, preco, estoque FROM produtos LIMIT 50");
            $products = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            if (empty($products)) {
                $jsonPath = __DIR__ . '/../../public/assets/data/data.json';
                if (file_exists($jsonPath)) {
                    $jsonData = file_get_contents($jsonPath);
                    $products = array_slice(json_decode($jsonData, true), 0, 15);
                }
            }

            echo json_encode(['status' => 'ok', 'products' => $products], JSON_UNESCAPED_UNICODE);
            break;

        // Sincronizar arquivo JSON com o banco de dados
        case 'sync_json':
            $jsonPath = __DIR__ . '/../../public/assets/data/data.json';
            if (file_exists($jsonPath)) {
                $jsonData = file_get_contents($jsonPath);
                $items = json_decode($jsonData, true);

                $stmt = $pdo->prepare("INSERT INTO produtos (codigo, nome, preco, estoque) VALUES (:codigo, :nome, 45.90, 10) ON DUPLICATE KEY UPDATE nome = VALUES(nome)");
                foreach ($items as $item) {
                    $stmt->execute([
                        ':codigo' => $item['Código'] ?? '000',
                        ':nome' => $item['Descrição do Produto'] ?? 'Sem Descrição'
                    ]);
                }
                echo json_encode(['status' => 'ok', 'message' => 'Banco de dados sincronizado com sucesso a partir do data.json!'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Arquivo data.json não encontrado.'], JSON_UNESCAPED_UNICODE);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Ação inválida.'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>