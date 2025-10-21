<?php
class MedicalRecord {
    private $pdo;

    public function __construct($db) {
        $this->pdo = $db;
    }

    public function getDataForModal($appointmentId) {
        $data = [];
        $stmt_record = $this->pdo->prepare("SELECT * FROM medical_records WHERE appointment_id = ?");
        $stmt_record->execute([$appointmentId]);
        $data['record'] = $stmt_record->fetch();

        $stmt_items = $this->pdo->prepare("SELECT * FROM invoice_items WHERE appointment_id = ?");
        $stmt_items->execute([$appointmentId]);
        $data['lineItems'] = $stmt_items->fetchAll();

        // These would be used to populate the "Add Item" dropdown in the modal
        $data['services'] = $this->pdo->query("SELECT * FROM services ORDER BY name ASC")->fetchAll();
        $data['inventory'] = $this->pdo->query("SELECT * FROM inventory_items WHERE stock > 0 ORDER BY name ASC")->fetchAll();
        
        return $data;
    }
    
    public function saveRecordAndInvoice($data) {
        $this->pdo->beginTransaction();
        try {
            $appointmentId = $data['appointmentId'];
            $record = $data['record'];
            $lineItems = $data['lineItems'];

            // 1. Insert or Update the medical record (UPSERT)
            $sql_record = "INSERT INTO medical_records (appointment_id, subjective, objective, assessment, plan) 
                           VALUES (?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE 
                           subjective = VALUES(subjective), objective = VALUES(objective), 
                           assessment = VALUES(assessment), plan = VALUES(plan)";
            $stmt_record = $this->pdo->prepare($sql_record);
            $stmt_record->execute([
                $appointmentId,
                $record['subjective'],
                $record['objective'],
                $record['assessment'],
                $record['plan']
            ]);

            // 2. Delete old invoice items for this appointment to prevent duplicates
            $stmt_delete_items = $this->pdo->prepare("DELETE FROM invoice_items WHERE appointment_id = ?");
            $stmt_delete_items->execute([$appointmentId]);

            // 3. Insert the new invoice items
            if (!empty($lineItems)) {
                $sql_items = "INSERT INTO invoice_items (appointment_id, item_type, item_id, quantity, price_at_time) 
                              VALUES (?, ?, ?, ?, ?)";
                $stmt_items = $this->pdo->prepare($sql_items);
                foreach ($lineItems as $item) {
                    $stmt_items->execute([
                        $appointmentId,
                        $item['item_type'],
                        $item['item_id'],
                        $item['quantity'],
                        $item['price_at_time']
                    ]);
                }
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            // For debugging: error_log('Medical Record Save Failed: ' . $e->getMessage());
            return false;
        }
    }
}