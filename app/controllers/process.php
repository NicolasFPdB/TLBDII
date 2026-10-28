<?php
function RateLimitDb(PDO $pdo, string $ip, string $action, int $maxAttempts = 5, int $seconds = 300): bool
{
    try {
        $now = date('Y-m-d H:i:s');

        $sql = "SELECT id, attempts, last_attempt FROM rate_limits WHERE ip = :ip AND action = :action";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ip' => $ip, ':action' => $action]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            $timePassed = time() - strtotime($record['last_attempt']);

            if ($timePassed > $seconds) {
                $updateSql = "UPDATE rate_limits SET attempts = 1, last_attempt = :now WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([':now' => $now, ':id' => $record['id']]);
                return true;
            }

            if ($record['attempts'] < $maxAttempts) {
                $updateSql = "UPDATE rate_limits SET attempts = attempts + 1, last_attempt = :now WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([':now' => $now, ':id' => $record['id']]);
                return true;
            }

            return false;
        } else {
            $insertSql = "INSERT INTO rate_limits (ip, action, attempts, last_attempt) VALUES (:ip, :action, 1, :now)";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([':ip' => $ip, ':action' => $action, ':now' => $now]);
            return true;
        }
    } catch (Throwable $e) {
        return true;
    }
}
?>