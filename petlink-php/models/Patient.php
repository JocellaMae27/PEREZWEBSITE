<?php
class Patient {
    private $pdo;
    private $table = 'patients';

    public function __construct($db) {
        $this->pdo = $db;
    }

    public function getAll() {
        $query = "SELECT 
                    p.id, p.client_id as clientId, p.pet_name as petName, p.species, p.breed, 
                    p.sex, p.dob, p.first_visit_date as firstVisitDate, 
                    c.full_name AS ownerName
                  FROM " . $this->table . " p
                  LEFT JOIN clients c ON p.client_id = c.id
                  WHERE p.deleted_at IS NULL AND c.deleted_at IS NULL
                  ORDER BY p.pet_name ASC";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create($data) {
        // This query correctly inserts the client_id, not owner_name.
        $query = "INSERT INTO " . $this->table . " 
                    (client_id, pet_name, species, breed, sex, dob, first_visit_date)
                  VALUES 
                    (:clientId, :petName, :species, :breed, :sex, :dob, :firstVisitDate)";

        $stmt = $this->pdo->prepare($query);

        // Sanitize and bind data
        $stmt->bindParam(':clientId', $data['clientId']);
        $stmt->bindParam(':petName', $data['petName']);
        $stmt->bindParam(':species', $data['species']);
        $stmt->bindParam(':breed', $data['breed']);
        $stmt->bindParam(':sex', $data['sex']);
        $stmt->bindParam(':dob', $data['dob']);
        $stmt->bindParam(':firstVisitDate', $data['firstVisitDate']);

        return $stmt->execute();
    }

    public function update($data) {
        // This query correctly updates using client_id.
        $query = "UPDATE " . $this->table . "
                  SET 
                    client_id = :clientId, 
                    pet_name = :petName, 
                    species = :species, 
                    breed = :breed, 
                    sex = :sex, 
                    dob = :dob, 
                    first_visit_date = :firstVisitDate
                  WHERE 
                    id = :id";
        
        $stmt = $this->pdo->prepare($query);

        // Sanitize and bind data
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':clientId', $data['clientId']);
        $stmt->bindParam(':petName', $data['petName']);
        $stmt->bindParam(':species', $data['species']);
        $stmt->bindParam(':breed', $data['breed']);
        $stmt->bindParam(':sex', $data['sex']);
        $stmt->bindParam(':dob', $data['dob']);
        $stmt->bindParam(':firstVisitDate', $data['firstVisitDate']);

        return $stmt->execute();
    }

    public function delete($id) {
        $this->pdo->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');

            // Soft delete the patient
            $stmt_patient = $this->pdo->prepare("UPDATE patients SET deleted_at = ? WHERE id = ?");
            $stmt_patient->execute([$now, $id]);
            
            // Soft delete associated appointments
            $stmt_del_appts = $this->pdo->prepare("UPDATE appointments SET deleted_at = ? WHERE patient_id = ?");
            $stmt_del_appts->execute([$now, $id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}