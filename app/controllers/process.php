<?php
declare(strict_types=1);

function RateLimitDb(PDO $pdo, string $ip, string $action, int $maxAttempts = 5, int $seconds = 300): bool
{
    try {
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare('SELECT id, attempts, last_attempt FROM rate_limits WHERE ip = :ip AND acao = :acao');
        $stmt->execute([':ip' => $ip, ':acao' => $action]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            if (time() - strtotime($record['last_attempt']) > $seconds) {
                $update = $pdo->prepare('UPDATE rate_limits SET attempts = 1, last_attempt = :now WHERE id = :id');
                $update->execute([':now' => $now, ':id' => $record['id']]);
                return true;
            }
            if ((int) $record['attempts'] >= $maxAttempts) {
                return false;
            }
            $update = $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1, last_attempt = :now WHERE id = :id');
            $update->execute([':now' => $now, ':id' => $record['id']]);
            return true;
        }

        $insert = $pdo->prepare('INSERT INTO rate_limits (ip, acao, attempts, last_attempt) VALUES (:ip, :acao, 1, :now)');
        $insert->execute([':ip' => $ip, ':acao' => $action, ':now' => $now]);
        return true;
    } catch (Throwable $e) {
        error_log('Rate limit indisponível: ' . $e->getMessage());
        return false;
    }
}
function ResetRateLimit(
    PDO $pdo,
    string $ip,
    string $action
): void {

    $stmt = $pdo->prepare(
        'DELETE FROM rate_limits
         WHERE ip = :ip AND acao = :acao'
    );

    $stmt->execute([
        ':ip' => $ip,
        ':acao' => $action
    ]);
}