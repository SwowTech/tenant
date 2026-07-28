<?php

declare(strict_types=1);

const DEMO_APP = 'mineadmin/demo';
const DEMO_ISSUER = 'app-member:mineadmin/demo';

function gateway_secret(): string
{
    return getenv('APP_GATEWAY_SECRET') ?: 'dev-app-gateway-secret';
}

function require_gateway(): void
{
    $secret = $_SERVER['HTTP_X_APP_GATEWAY_SECRET'] ?? '';
    if (! hash_equals(gateway_secret(), (string) $secret)) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'invalid gateway secret']);
        exit;
    }
}

function tenant_id(): int
{
    $id = (int) ($_SERVER['HTTP_X_TENANT_ID'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'missing tenant']);
        exit;
    }

    return $id;
}

function storage_dir(): string
{
    $dir = dirname(__DIR__) . '/storage';
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function members_file(int $tenantId): string
{
    return storage_dir() . '/members.' . $tenantId . '.json';
}

/**
 * @return array<string, array{password_hash: string, created_at: string}>
 */
function load_members(int $tenantId): array
{
    $file = members_file($tenantId);
    if (! is_file($file)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($file), true);

    return is_array($data) ? $data : [];
}

/**
 * @param array<string, array{password_hash: string, created_at: string}> $members
 */
function save_members(int $tenantId, array $members): void
{
    file_put_contents(members_file($tenantId), json_encode($members, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

function sign_token(int $tenantId, string $username): string
{
    $payload = [
        'iss' => DEMO_ISSUER,
        'app' => DEMO_APP,
        'tenant_id' => $tenantId,
        'username' => $username,
        'exp' => time() + 86400,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $b64 = rtrim(strtr(base64_encode((string) $json), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $b64, gateway_secret());

    return $b64 . '.' . $sig;
}

function verify_token(string $token): ?array
{
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) {
        return null;
    }
    [$b64, $sig] = $parts;
    $expected = hash_hmac('sha256', $b64, gateway_secret());
    if (! hash_equals($expected, $sig)) {
        return null;
    }
    $json = base64_decode(strtr($b64, '-_', '+/'), true);
    if (! is_string($json)) {
        return null;
    }
    $payload = json_decode($json, true);
    if (! is_array($payload)) {
        return null;
    }
    if (($payload['iss'] ?? '') !== DEMO_ISSUER || (int) ($payload['exp'] ?? 0) < time()) {
        return null;
    }

    return $payload;
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($uri === '/health') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    return;
}

if (str_starts_with($uri, '/api/')) {
    require_gateway();
    $tenantId = tenant_id();
    header('Content-Type: application/json; charset=utf-8');

    if ($uri === '/api/auth/register' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $data = json_body();
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        if ($username === '' || $password === '') {
            http_response_code(422);
            echo json_encode(['message' => 'username/password required']);
            return;
        }
        $members = load_members($tenantId);
        if (isset($members[$username])) {
            http_response_code(409);
            echo json_encode(['message' => 'username exists']);
            return;
        }
        $members[$username] = [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date('c'),
        ];
        save_members($tenantId, $members);
        echo json_encode(['token' => sign_token($tenantId, $username)]);
        return;
    }

    if ($uri === '/api/auth/login' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $data = json_body();
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $members = load_members($tenantId);
        $row = $members[$username] ?? null;
        if ($row === null || ! password_verify($password, (string) ($row['password_hash'] ?? ''))) {
            http_response_code(401);
            echo json_encode(['message' => 'invalid credentials']);
            return;
        }
        echo json_encode(['token' => sign_token($tenantId, $username)]);
        return;
    }

    if ($uri === '/api/auth/me' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        $auth = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (! str_starts_with($auth, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['message' => 'missing token']);
            return;
        }
        $payload = verify_token(substr($auth, 7));
        if ($payload === null || (int) ($payload['tenant_id'] ?? 0) !== $tenantId) {
            http_response_code(401);
            echo json_encode(['message' => 'invalid token']);
            return;
        }
        echo json_encode([
            'username' => $payload['username'] ?? '',
            'tenant_id' => $tenantId,
            'app' => DEMO_APP,
        ]);
        return;
    }

    http_response_code(404);
    echo json_encode(['message' => 'not found']);
    return;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'not found';
