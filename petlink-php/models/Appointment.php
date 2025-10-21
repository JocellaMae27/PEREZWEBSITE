<?php
class Appointment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllPaginated($page = 1, $limit = 10, $searchTerm = '') {
         $offset = ($page - 1) * $limit;
        
        $baseWhere = "WHERE a.deleted_at IS NULL AND p.deleted_at IS NULL AND c.deleted_at IS NULL";
        $searchWhere = '';
        $params = [];
        if (!empty($searchTerm)) {
            $searchWhere = " AND (p.pet_name LIKE ? OR c.full_name LIKE ? OR mr.assessment LIKE ?)";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        }
        
        $countQuery = "SELECT COUNT(a.id) FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN clients c ON p.client_id = c.id LEFT JOIN medical_records mr ON a.id = mr.appointment_id $baseWhere $searchWhere";
        $countStmt = $this->pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();
        
        $sql = "
            SELECT a.id, a.patient_id as patientId, a.appointment_date as date, 
                   a.status, a.completion_date, a.payment_status as paymentStatus, a.amount, a.is_emergency as isEmergency,
                   p.pet_name as `patient.petName`, c.full_name as `patient.ownerName`,
                   mr.assessment as service
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN clients c ON p.client_id = c.id
            LEFT JOIN medical_records mr ON a.id = mr.appointment_id
            $baseWhere $searchWhere
            ORDER BY a.appointment_date DESC
            LIMIT ? OFFSET ?
        ";

        $finalParams = array_merge($params, [$limit, $offset]);
        $stmt = $this->pdo->prepare($sql);
        
        // PDO requires integer type for LIMIT and OFFSET, so we bind them explicitly
        foreach ($finalParams as $key => &$val) {
            if (is_int($val)) {
                $stmt->bindParam($key + 1, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindParam($key + 1, $val, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $appointments = [];
        foreach ($results as $row) {
            $row['patient'] = ['petName' => $row['patient.petName'], 'ownerName' => $row['patient.ownerName']];
            $row['isEmergency'] = (bool)$row['isEmergency'];
            if (is_null($row['service']) || $row['service'] === '') {
                $row['service'] = 'General Consultation';
            }
            unset($row['patient.petName'], $row['patient.ownerName']);
            $appointments[] = $row;
        }
        
        return [
            'appointments' => $appointments,
            'totalPages' => ceil($totalRecords / $limit),
            'currentPage' => $page
        ];
    }

    public function getAllWithPatientInfo() {
        $stmt = $this->pdo->query("
            SELECT 
                a.id, a.patient_id as patientId, a.appointment_date as date, 
                a.status, a.payment_status as paymentStatus, a.amount, a.is_emergency as isEmergency,
                p.pet_name as `patient.petName`,
                c.full_name as `patient.ownerName`,
                mr.assessment as service
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN clients c ON p.client_id = c.id
            LEFT JOIN medical_records mr ON a.id = mr.appointment_id
            ORDER BY a.appointment_date DESC
        ");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $appointments = [];
        foreach ($results as $row) {
            $row['patient'] = ['petName' => $row['patient.petName'], 'ownerName' => $row['patient.ownerName']];
            $row['isEmergency'] = (bool)$row['isEmergency'];
            
            if (is_null($row['service']) || $row['service'] === '') {
                $row['service'] = 'General Consultation';
            }

            unset($row['patient.petName'], $row['patient.ownerName']);
            $appointments[] = $row;
        }
        return $appointments;
    }

    public function create($data) {
        $isEmergency = isset($data['isEmergency']) && $data['isEmergency'] ? 1 : 0;
        $stmt = $this->pdo->prepare("INSERT INTO appointments (patient_id, appointment_date, status, is_emergency) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$data['patientId'], str_replace('T', ' ', $data['date']), $data['status'], $isEmergency]);
    }

    public function update($data) {
        $fields = [];
        $params = [];
        
        if (isset($data['patientId'])) { $fields[] = 'patient_id = ?'; $params[] = $data['patientId']; }
        if (isset($data['date'])) { $fields[] = 'appointment_date = ?'; $params[] = str_replace('T', ' ', $data['date']); }
        if (isset($data['status'])) { 
            $fields[] = 'status = ?'; 
            $params[] = $data['status'];
            if ($data['status'] === 'Completed' && !isset($data['completion_date'])) {
                $fields[] = 'completion_date = CURDATE()';
            }
        }
        if (isset($data['isEmergency'])) { $fields[] = 'is_emergency = ?'; $params[] = $data['isEmergency'] ? 1 : 0; }
        if (isset($data['paymentStatus'])) { $fields[] = 'payment_status = ?'; $params[] = $data['paymentStatus']; }
        if (isset($data['amount'])) { $fields[] = 'amount = ?'; $params[] = $data['amount']; }

        if (empty($fields)) { return false; }

        $sql = "UPDATE appointments SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $data['id'];
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("UPDATE appointments SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }
}