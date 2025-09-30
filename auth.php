<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function current_session_token(): string
{
    if (empty($_SESSION['session_token']) || !is_string($_SESSION['session_token'])) {
        $_SESSION['session_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['session_token'];
}

function require_login(): void
{
    if (!is_logged_in()) {
        $target = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: login.php?redirect=' . urlencode($target));
        exit;
    }
}

function login_user(array $user): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? '',
        'address' => $user['address'] ?? '',
    ];

    $_SESSION['session_token'] = bin2hex(random_bytes(32));
}

function refresh_session_user(array $profile): void
{
    if (!is_logged_in()) {
        return;
    }

    $_SESSION['user'] = array_merge($_SESSION['user'], $profile);
}

function logout_user(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
    session_start();
    session_regenerate_id(true);
}

function client_ip_address(): string
{
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $value = $_SERVER[$key];
            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $parts = explode(',', $value);
                $value = trim($parts[0]);
            }
            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }
    }

    return '0.0.0.0';
}

function client_user_agent(): string
{
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return trim($agent) !== '' ? substr($agent, 0, 250) : 'unknown';
}

function csrf_token(string $key): string
{
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }

    if (empty($_SESSION['csrf_tokens'][$key]) || !is_string($_SESSION['csrf_tokens'][$key])) {
        $_SESSION['csrf_tokens'][$key] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_tokens'][$key];
}

function verify_csrf_token(string $key, ?string $token): bool
{
    if (!isset($_SESSION['csrf_tokens'][$key]) || !$token) {
        return false;
    }

    $isValid = hash_equals($_SESSION['csrf_tokens'][$key], $token);
    if ($isValid) {
        unset($_SESSION['csrf_tokens'][$key]);
    }

    return $isValid;
}
?>
