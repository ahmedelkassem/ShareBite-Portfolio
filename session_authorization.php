<?php
// Use the account record from the database when checking a signed-in session.
function sharebiteCurrentAccount($sessionId, ?array $account): ?array
{
    $id = filter_var($sessionId, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    if ($id === false || $account === null ||
        (int)($account['user_id'] ?? 0) !== $id) {
        return null;
    }
    $role = $account['role'] ?? '';
    if (!in_array($role, ['Admin', 'Donor', 'Volunteer', 'Charity'], true)) {
        return null;
    }
    return [
        'user_id' => $id,
        'role' => $role,
        'full_name' => (string)($account['full_name'] ?? ''),
        'email' => (string)($account['email'] ?? '')
    ];
}
