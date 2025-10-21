<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT username as id, full_name, role FROM users WHERE deleted_at IS NULL");
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (username, full_name, password, role) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$data['username'], $data['fullName'], $hashedPassword, $data['role']]);
    }

    public function updateInfo($data) {
        $stmt = $this->pdo->prepare("UPDATE users SET full_name = ?, role = ? WHERE username = ?");
        return $stmt->execute([$data['fullName'], $data['role'], $data['username']]);
    }

    public function changePassword($username, $oldPassword, $newPassword) {
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($oldPassword, $user['password'])) {
            return false;
        }

        $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        return $stmt->execute([$newHashedPassword, $username]);
    }

    public function delete($username) {
        $stmt = $this->pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE username = ?");
        return $stmt->execute([$username]);
    }
}