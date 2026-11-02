<?php
/**
 * Rate-limit password reset EMAIL REQUESTS, not code verification attempts.
 *
 * Single-server storage in the PHP temporary directory, outside the web root.
 * Allows up to 3 requests per email and 20 per client IP in 60 minutes.
 * Uses an exclusive file lock so parallel requests cannot bypass counters.
 * Fails closed if storage is unavailable. A clustered server requires shared
 * storage (such as Redis) rather than this local-file implementation.
 */
function sharebitePasswordResetThrottle(
    string $email,
    string $clientIp,
    string $action = 'record',
    ?string $storagePath = null
): bool {
    $storagePath = $storagePath ?? (
        sys_get_temp_dir() . DIRECTORY_SEPARATOR .
        'sharebite-reset-' . hash('sha256', __DIR__) . '.json'
    );
    $handle = @fopen($storagePath, 'c+');
    if ($handle === false) {
        error_log('ShareBite reset limiter storage cannot be opened.');
        return false;
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            return false;
        }

        @chmod($storagePath, 0600);
        rewind($handle);
        $json = stream_get_contents($handle);
        if ($json === false) {
            return false;
        }
        $records = $json === '' ? [] : json_decode($json, true);
        if (!is_array($records)) {
            error_log('ShareBite reset limiter storage is invalid.');
            return false;
        }

        $now = time();
        foreach ($records as $key => $timestamps) {
            if (!is_array($timestamps)) {
                unset($records[$key]);
                continue;
            }
            $recent = array_values(array_filter(
                $timestamps,
                static function ($at) use ($now): bool {
                    return is_int($at) && $at > $now - 3600 && $at <= $now;
                }
            ));
            if ($recent) {
                $records[$key] = $recent;
            } else {
                unset($records[$key]);
            }
        }

        $emailKey = 'email:' . hash('sha256', strtolower(trim($email)));
        $ipKey = 'ip:' . hash('sha256', $clientIp);
        $allowed = count($records[$emailKey] ?? []) < 3
            && count($records[$ipKey] ?? []) < 20;

        if ($action === 'check') {
            return $allowed;
        }
        if ($action !== 'record' || !$allowed) {
            return false;
        }

        $records[$emailKey][] = $now;
        $records[$ipKey][] = $now;
        $updated = json_encode($records, JSON_THROW_ON_ERROR);

        rewind($handle);
        if (!ftruncate($handle, 0)) {
            return false;
        }
        $bytesWritten = 0;
        while ($bytesWritten < strlen($updated)) {
            $count = fwrite($handle, substr($updated, $bytesWritten));
            if ($count === false || $count === 0) {
                return false;
            }
            $bytesWritten += $count;
        }
        return fflush($handle);
    } catch (Throwable $error) {
        error_log('ShareBite password reset limiter failed: ' . $error->getMessage());
        return false;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
