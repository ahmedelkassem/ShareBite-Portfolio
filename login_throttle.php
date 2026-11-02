<?php
// Per-account and per-client-IP login limits on a single PHP server.
function sharebiteLoginThrottle(string $email, string $ip, string $action = 'check'): bool
{
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR .
        'sharebite-login-' . hash('sha256', __DIR__) . '.json';
    $handle = @fopen($path, 'c+');
    if ($handle === false) {
        error_log('ShareBite login rate-limit storage is unavailable.');
        return false;
    }
    try {
        if (!flock($handle, LOCK_EX)) {
            return false;
        }
        @chmod($path, 0600);
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false) {
            return false;
        }
        $records = $raw === '' ? [] : json_decode($raw, true);
        if (!is_array($records)) {
            error_log('ShareBite login rate-limit storage is invalid.');
            return false;
        }
        $now = time();
        foreach ($records as $key => $attempts) {
            if (!is_array($attempts)) {
                unset($records[$key]);
                continue;
            }
            $attempts = array_values(array_filter(
                $attempts,
                static function ($at) use ($now): bool {
                    return is_int($at) && $at > ($now - 900) && $at <= $now;
                }
            ));
            if ($attempts) {
                $records[$key] = $attempts;
            } else {
                unset($records[$key]);
            }
        }
        $account = 'account:' . hash('sha256', strtolower(trim($email)));
        $ipKey = 'ip:' . hash('sha256', $ip);
        $blocked = count($records[$account] ?? []) >= 5 ||
            count($records[$ipKey] ?? []) >= 30;
        if ($action === 'check') {
            return !$blocked;
        }
        if ($action === 'failure') {
            if (!$blocked) {
                $records[$account][] = $now;
                $records[$ipKey][] = $now;
            }
        } elseif ($action === 'success') {
            unset($records[$account]);
        } else {
            return false;
        }
        $json = json_encode($records, JSON_THROW_ON_ERROR);
        rewind($handle);
        if (!ftruncate($handle, 0)) {
            return false;
        }
        $written = 0;
        while ($written < strlen($json)) {
            $count = fwrite($handle, substr($json, $written));
            if ($count === false || $count === 0) {
                return false;
            }
            $written += $count;
        }
        return fflush($handle);
    } catch (Throwable $e) {
        error_log('ShareBite login limiter failure: ' . $e->getMessage());
        return false;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
