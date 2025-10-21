<?php
class Client {
    private $pdo;

    public function __construct($db) {
        $this->pdo = $db;
    }

    public function getAll() {
        $stmt = $this->pdo->prepare("SELECT * FROM clients WHERE deleted_at IS NULL ORDER BY full_name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO clients (full_name, phone, email, address) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $data['fullName'],
            $data['phone'],
            $data['email'],
            $data['address']
        ]);
    }

    public function update($data) {
        $stmt = $this->pdo->prepare("UPDATE clients SET full_name = ?, phone = ?, email = ?, address = ? WHERE id = ?");
        return $stmt->execute([
            $data['fullName'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['id']
        ]);
    }

    public function delete($id) {
        $this->pdo->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            
            // Soft delete the client
            $stmt_client = $this->pdo->prepare("UPDATE clients SET deleted_at = ? WHERE id = ?");
            $stmt_client->execute([$now, $id]);

            // Find all pets owned by this client
            $stmt_pets = $this->pdo->prepare("SELECT id FROM patients WHERE client_id = ?");
            $stmt_pets->execute([$id]);
            $pet_ids = $stmt_pets->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($pet_ids)) {
                // Soft delete the associated pets
                $petPlaceholders = implode(',', array_fill(0, count($pet_ids), '?'));
                $stmt_del_pets = $this->pdo->prepare("UPDATE patients SET deleted_at = ? WHERE id IN ($petPlaceholders)");
                $stmt_del_pets->execute(array_merge([$now], $pet_ids));
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}