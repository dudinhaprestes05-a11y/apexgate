<?php

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';

    public function findByEmail($email) {
        return $this->findBy('email', $email);
    }

    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);

        if (!$user || $user['status'] !== 'active') {
            return false;
        }

        if (password_verify($password, $user['password'])) {
            $this->updateLastLogin($user['id']);
            return $user;
        }

        return false;
    }

    public function createUser($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return $this->create($data);
    }

    public function updatePassword($userId, $newPassword) {
        return $this->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
    }

    public function updateLastLogin($userId) {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }

    public function getUserWithSeller($userId) {
        $sql = "
            SELECT u.*, s.name as seller_name, s.balance, s.status as seller_status
            FROM users u
            LEFT JOIN sellers s ON u.seller_id = s.id
            WHERE u.id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetch();
    }
}
