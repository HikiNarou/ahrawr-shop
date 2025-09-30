<?php
class AccountService
{
    /** @var mysqli */
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->conn->set_charset('utf8mb4');
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $this->conn->query(
            'CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_id VARCHAR(191) NOT NULL UNIQUE,
                session_token VARCHAR(191) NOT NULL,
                user_agent VARCHAR(255) NOT NULL,
                ip_address VARCHAR(64) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $this->conn->query(
            'CREATE TABLE IF NOT EXISTS user_activity_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                category VARCHAR(32) NOT NULL,
                message VARCHAR(255) NOT NULL,
                ip_address VARCHAR(64) DEFAULT \'\',
                user_agent VARCHAR(255) DEFAULT \'\',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_activity_user (user_id),
                INDEX idx_user_activity_category (category),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function getUserProfile(int $userId): ?array
    {
        $stmt = $this->conn->prepare('SELECT id, name, email, phone, address FROM users WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    public function updateProfile(int $userId, string $name, string $phone, string $address, string $ip, string $agent): array
    {
        $name = trim($name);
        $phone = trim($phone);
        $address = trim($address);

        if ($name === '') {
            return ['success' => false, 'message' => 'Nama tidak boleh kosong.'];
        }

        if (mb_strlen($name) > 120) {
            return ['success' => false, 'message' => 'Nama maksimal 120 karakter.'];
        }

        if ($phone !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
            return ['success' => false, 'message' => 'Nomor telepon harus 8-20 digit dan hanya boleh berisi angka, spasi, + atau -.'];
        }

        if (mb_strlen($address) > 255) {
            return ['success' => false, 'message' => 'Alamat maksimal 255 karakter.'];
        }

        $stmt = $this->conn->prepare('UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Gagal menyiapkan perintah: ' . $this->conn->error];
        }

        $stmt->bind_param('sssi', $name, $phone, $address, $userId);
        $success = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();

        if (!$success) {
            return ['success' => false, 'message' => 'Gagal memperbarui profil: ' . $error];
        }

        $this->logActivity($userId, 'profile', 'Memperbarui informasi profil', $ip, $agent);

        return [
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
            ],
        ];
    }

    public function changePassword(int $userId, string $current, string $new, string $confirm, string $ip, string $agent): array
    {
        $current = trim($current);
        $new = trim($new);
        $confirm = trim($confirm);

        if ($new === '' || $confirm === '') {
            return ['success' => false, 'message' => 'Password baru dan konfirmasi wajib diisi.'];
        }

        if ($new !== $confirm) {
            return ['success' => false, 'message' => 'Konfirmasi password tidak cocok.'];
        }

        if (!$this->isPasswordStrong($new)) {
            return ['success' => false, 'message' => 'Password minimal 8 karakter dengan kombinasi huruf besar, huruf kecil, dan angka.'];
        }

        $stmt = $this->conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Gagal menyiapkan perintah: ' . $this->conn->error];
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($current, $row['password'])) {
            return ['success' => false, 'message' => 'Password saat ini tidak sesuai.'];
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Gagal menyiapkan perintah: ' . $this->conn->error];
        }

        $stmt->bind_param('si', $hash, $userId);
        $success = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();

        if (!$success) {
            return ['success' => false, 'message' => 'Gagal memperbarui password: ' . $error];
        }

        $this->logActivity($userId, 'security', 'Mengganti password akun', $ip, $agent);

        return ['success' => true, 'message' => 'Password berhasil diperbarui.'];
    }

    public function registerSession(int $userId, string $sessionId, string $sessionToken, string $ip, string $agent): void
    {
        $query = 'INSERT INTO user_sessions (user_id, session_id, session_token, user_agent, ip_address, created_at, last_seen_at)
                  VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                  ON DUPLICATE KEY UPDATE session_token = VALUES(session_token), user_agent = VALUES(user_agent), ip_address = VALUES(ip_address), last_seen_at = NOW()';
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('issss', $userId, $sessionId, $sessionToken, $agent, $ip);
        $stmt->execute();
        $stmt->close();
    }

    public function touchSession(string $sessionId, string $ip, string $agent): void
    {
        $stmt = $this->conn->prepare('UPDATE user_sessions SET last_seen_at = NOW(), ip_address = ?, user_agent = ? WHERE session_id = ?');
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('sss', $ip, $agent, $sessionId);
        $stmt->execute();
        $stmt->close();
    }

    public function getActiveSessions(int $userId): array
    {
        $stmt = $this->conn->prepare('SELECT session_id, session_token, ip_address, user_agent, created_at, last_seen_at FROM user_sessions WHERE user_id = ? ORDER BY last_seen_at DESC');
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $sessions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $sessions;
    }

    public function revokeSession(int $userId, string $sessionId, string $ip, string $agent): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM user_sessions WHERE user_id = ? AND session_id = ?');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('is', $userId, $sessionId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            $this->logActivity($userId, 'security', 'Menghapus sesi masuk pada perangkat lain', $ip, $agent);
        }

        return $affected > 0;
    }

    public function revokeOtherSessions(int $userId, string $currentSessionId, string $ip, string $agent): int
    {
        $stmt = $this->conn->prepare('DELETE FROM user_sessions WHERE user_id = ? AND session_id <> ?');
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('is', $userId, $currentSessionId);
        $stmt->execute();
        $count = $stmt->affected_rows;
        $stmt->close();

        if ($count > 0) {
            $this->logActivity($userId, 'security', 'Mengeluarkan semua sesi lainnya', $ip, $agent);
        }

        return max(0, $count);
    }

    public function getRecentActivities(int $userId, int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));
        $stmt = $this->conn->prepare('SELECT category, message, ip_address, user_agent, created_at FROM user_activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $activities = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $activities;
    }

    public function logActivity(int $userId, string $category, string $message, string $ip, string $agent): void
    {
        $stmt = $this->conn->prepare('INSERT INTO user_activity_log (user_id, category, message, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)');
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('issss', $userId, $category, $message, $ip, $agent);
        $stmt->execute();
        $stmt->close();
    }

    private function isPasswordStrong(string $password): bool
    {
        if (strlen($password) < 8) {
            return false;
        }

        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasDigit = preg_match('/[0-9]/', $password);

        return $hasUpper && $hasLower && $hasDigit;
    }
}
?>

