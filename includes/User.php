<?php
require_once 'Database.php';

class User
{
    private $db;

    public function __construct()
    {
        global $db;
        if (isset($db)) {
            $this->db = $db;
        } else {
            $this->db = Database::getInstance()->getConnection();
        }
    }

    public function register($username, $email, $password)
    {
        try {
            $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);

            if ($stmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Email или имя пользователя уже заняты'
                ];
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare(
                "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)"
            );
            $stmt->execute([$username, $email, $passwordHash]);

            return [
                'success' => true,
                'message' => 'Регистрация успешна',
                'user_id' => $this->db->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при регистрации: ' . $e->getMessage()
            ];
        }
    }

    public function login($email, $password, $remember = false)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT user_id, username, display_name, password_hash 
                 FROM users 
                 WHERE email = ? AND is_active = TRUE"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $this->updateLastLogin($user['user_id']);

                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['display_name'] = $user['display_name'] ?? $user['username'];

                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

                    $stmt = $this->db->prepare(
                        "INSERT INTO remember_tokens (user_id, token, expires_at) 
                         VALUES (?, ?, ?)"
                    );
                    $stmt->execute([$user['user_id'], $token, $expires]);
                    setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
                }

                return [
                    'success' => true,
                    'message' => 'Вход выполнен успешно',
                    'user' => [
                        'id' => $user['user_id'],
                        'username' => $user['username']
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'Неверный email или пароль'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при входе: ' . $e->getMessage()
            ];
        }
    }

    public function checkRememberToken($token)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT u.user_id, u.username, u.display_name 
                 FROM users u 
                 JOIN remember_tokens rt ON u.user_id = rt.user_id 
                 WHERE rt.token = ? AND rt.expires_at > NOW()"
            );
            $stmt->execute([$token]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function logout()
    {
        try {
            if (isset($_COOKIE['remember_token'])) {
                $stmt = $this->db->prepare("DELETE FROM remember_tokens WHERE token = ?");
                $stmt->execute([$_COOKIE['remember_token']]);

                setcookie('remember_token', '', time() - 3600, '/');
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION = array();
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            session_destroy();

            return [
                'success' => true,
                'message' => 'Выход выполнен успешно'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выходе: ' . $e->getMessage()
            ];
        }
    }

    private function updateLastLogin($userId)
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
    }
}
