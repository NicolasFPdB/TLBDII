<?php
declare(strict_types=1);

/**
 * Incrementa o contador de forma atômica para reduzir condições de corrida
 * quando várias requisições chegam ao mesmo tempo.
 */
function RateLimitDb(PDO $pdo, string $ip, string $action, int $maxAttempts = 5, int $seconds = 300): bool
{
    try {
        $upsert = $pdo->prepare(
            'INSERT INTO rate_limits (ip, acao, attempts, last_attempt)
             VALUES (:ip, :acao, 1, NOW())
             ON DUPLICATE KEY UPDATE
                attempts = IF(TIMESTAMPDIFF(SECOND, last_attempt, NOW()) > :window_seconds, 1, attempts + 1),
                last_attempt = NOW()'
        );
        $upsert->execute([':ip' => $ip, ':acao' => $action, ':window_seconds' => $seconds]);

        $check = $pdo->prepare('SELECT attempts FROM rate_limits WHERE ip=:ip AND acao=:acao');
        $check->execute([':ip' => $ip, ':acao' => $action]);
        $record = $check->fetch(PDO::FETCH_ASSOC);
        return $record !== false && (int) $record['attempts'] <= $maxAttempts;
    } catch (Throwable $e) {
        error_log('Rate limit indisponível: ' . $e->getMessage());
        // Fail closed: se não for possível verificar o limite, não libera a operação.
        return false;
    }
}

function ResetRateLimit(PDO $pdo, string $ip, string $action): void
{
    $stmt = $pdo->prepare('DELETE FROM rate_limits WHERE ip=:ip AND acao=:acao');
    $stmt->execute([':ip' => $ip, ':acao' => $action]);
}
