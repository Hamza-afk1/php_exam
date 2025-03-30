<?php
require_once 'Model.php';

class Exam extends Model {
    protected $table = 'exams';
    
    public function create(array $data) {
        $query = "INSERT INTO exams (formateur_id, name, description, time_limit) 
                  VALUES (:formateur_id, :name, :description, :time_limit)";
                  
        $stmt = $this->db->prepare($query);
        
        $params = [
            ':formateur_id' => $data['formateur_id'],
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':time_limit' => $data['time_limit']
        ];
        
        if ($this->db->execute($stmt, $params)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function update(array $data, $id) {
        $query = "UPDATE exams SET 
                  name = :name, 
                  description = :description, 
                  time_limit = :time_limit
                  WHERE id = :id";
                  
        $stmt = $this->db->prepare($query);
        
        $params = [
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':time_limit' => $data['time_limit'],
            ':id' => $id
        ];
        
        return $this->db->execute($stmt, $params);
    }
    
    // Alias for getExamsByFormateur to maintain compatibility with existing code
    public function getExamsByFormateurId($formateurId) {
        return $this->getExamsByFormateur($formateurId);
    }
    
    public function getExamsByFormateur($formateurId) {
        $query = "SELECT * FROM exams WHERE formateur_id = :formateur_id";
        $stmt = $this->db->prepare($query);
        $params = [':formateur_id' => $formateurId];
        $this->db->execute($stmt, $params);
        return $this->db->resultSet($stmt);
    }
    
    public function getAllExams() {
        $query = "SELECT e.*, u.username as formateur_name 
                  FROM exams e 
                  JOIN users u ON e.formateur_id = u.id 
                  ORDER BY e.created_at DESC";
        $stmt = $this->db->prepare($query);
        $this->db->execute($stmt);
        return $this->db->resultSet($stmt);
    }
    
    public function getExamsWithFormateur() {
        $query = "SELECT e.*, u.username as formateur_name 
                  FROM exams e 
                  JOIN users u ON e.formateur_id = u.id 
                  ORDER BY e.created_at DESC";
        $stmt = $this->db->prepare($query);
        $this->db->execute($stmt);
        return $this->db->resultSet($stmt);
    }
    
    /**
     * Get available exams for a stagiaire that they haven't taken yet or can retake
     *
     * @param int $stagiaireId The ID of the stagiaire
     * @return array List of available exams
     */
    public function getAvailableExams($stagiaireId) {
        $query = "SELECT e.*, u.username as formateur_name 
                  FROM exams e 
                  JOIN users u ON e.formateur_id = u.id 
                  LEFT JOIN results r ON e.id = r.exam_id AND r.stagiaire_id = :stagiaire_id
                  WHERE r.id IS NULL OR r.score < 70  
                  ORDER BY e.created_at DESC";
        $stmt = $this->db->prepare($query);
        $params = [':stagiaire_id' => $stagiaireId];
        
        $this->db->execute($stmt, $params);
        return $this->db->resultSet($stmt);
    }
    
    public function getById($id) {
        $query = "SELECT e.*, u.username as formateur_name 
                  FROM exams e 
                  JOIN users u ON e.formateur_id = u.id 
                  WHERE e.id = :id";
        $stmt = $this->db->prepare($query);
        $params = [':id' => $id];
        $this->db->execute($stmt, $params);
        return $this->db->single($stmt);
    }
    
    public function delete($id) {
        $query = "DELETE FROM exams WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $params = [':id' => $id];
        return $this->db->execute($stmt, $params);
    }
    
    /**
     * Get exams that need grading (have open-ended questions with ungraded answers)
     *
     * @param int $formateurId The ID of the formateur
     * @return array List of exams needing grading
     */
    public function getExamsNeedingGrading($formateurId) {
        $query = "SELECT DISTINCT 
                    e.id, 
                    e.name, 
                    (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id AND q.question_type = 'open') as open_question_count,
                    (SELECT COUNT(DISTINCT a.stagiaire_id) 
                     FROM answers a 
                     JOIN questions q ON a.question_id = q.id 
                     WHERE q.exam_id = e.id AND a.is_correct IS NULL) as stagiaires_waiting
                  FROM exams e
                  JOIN questions q ON e.id = q.exam_id
                  JOIN answers a ON q.id = a.question_id
                  WHERE e.formateur_id = :formateur_id 
                    AND q.question_type = 'open'
                    AND a.is_correct IS NULL
                  ORDER BY stagiaires_waiting DESC";
                  
        $stmt = $this->db->prepare($query);
        $params = [
            ':formateur_id' => $formateurId
        ];
        
        $this->db->execute($stmt, $params);
        
        return $this->db->resultSet($stmt);
    }
    
    /**
     * Count the total number of exams created by a formateur
     * 
     * @param int $formateurId The formateur ID
     * @return int The count of exams
     */
    public function countExamsByFormateur($formateurId) {
        try {
            $query = "SELECT COUNT(*) as total_exams
                      FROM exams
                      WHERE formateur_id = :formateur_id";
                      
            $stmt = $this->db->prepare($query);
            $this->db->execute($stmt, [':formateur_id' => $formateurId]);
            $result = $this->db->single($stmt);
            
            return $result['total_exams'] ?? 0;
        } catch (Exception $e) {
            error_log("Error counting exams by formateur: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Count the total number of questions across all exams created by a formateur
     * 
     * @param int $formateurId The formateur ID
     * @return int The count of questions
     */
    public function countQuestionsByFormateur($formateurId) {
        try {
            $query = "SELECT COUNT(q.id) as total_questions
                      FROM questions q
                      JOIN exams e ON q.exam_id = e.id
                      WHERE e.formateur_id = :formateur_id";
                      
            $stmt = $this->db->prepare($query);
            $this->db->execute($stmt, [':formateur_id' => $formateurId]);
            $result = $this->db->single($stmt);
            
            return $result['total_questions'] ?? 0;
        } catch (Exception $e) {
            error_log("Error counting questions by formateur: " . $e->getMessage());
            return 0;
        }
    }
}
