<?php
require_once 'Model.php';

class Result extends Model {
    protected $table = 'results';
    
    public function create(array $data) {
        $query = "INSERT INTO results (stagiaire_id, exam_id, score, total_score, graded_by) 
                  VALUES (:stagiaire_id, :exam_id, :score, :total_score, :graded_by)";
                  
        $stmt = $this->db->prepare($query);
        
        $params = [
            ':stagiaire_id' => $data['stagiaire_id'],
            ':exam_id' => $data['exam_id'],
            ':score' => isset($data['score']) ? $data['score'] : null,
            ':total_score' => isset($data['total_score']) ? $data['total_score'] : null,
            ':graded_by' => isset($data['graded_by']) ? $data['graded_by'] : null
        ];
        
        if ($this->db->execute($stmt, $params)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function update(array $data, $id) {
        $query = "UPDATE results SET 
                  score = :score,
                  total_score = :total_score,
                  graded_by = :graded_by
                  WHERE id = :id";
                  
        $stmt = $this->db->prepare($query);
        
        $params = [
            ':score' => $data['score'],
            ':total_score' => isset($data['total_score']) ? $data['total_score'] : null,
            ':graded_by' => isset($data['graded_by']) ? $data['graded_by'] : null,
            ':id' => $id
        ];
        
        return $this->db->execute($stmt, $params);
    }
    
    public function getResultsByExam($examId) {
        $query = "SELECT r.*, 
                  u.username as stagiaire_name,
                  f.username as formateur_name,
                  e.name as exam_name,
                  e.passing_score,
                  e.total_points
                  FROM results r
                  JOIN users u ON r.stagiaire_id = u.id
                  JOIN exams e ON r.exam_id = e.id
                  LEFT JOIN users f ON r.graded_by = f.id
                  WHERE r.exam_id = :exam_id
                  ORDER BY r.score DESC";
        $stmt = $this->db->prepare($query);
        $params = [':exam_id' => $examId];
        $this->db->execute($stmt, $params);
        return $this->db->resultSet($stmt);
    }
    
    public function getResultByStagiaireAndExam($stagiaireId, $examId) {
        $query = "SELECT r.*, 
                  u.username as stagiaire_name,
                  f.username as formateur_name,
                  e.name as exam_name,
                  e.passing_score,
                  e.total_points
                  FROM results r
                  JOIN users u ON r.stagiaire_id = u.id
                  JOIN exams e ON r.exam_id = e.id
                  LEFT JOIN users f ON r.graded_by = f.id
                  WHERE r.stagiaire_id = :stagiaire_id AND r.exam_id = :exam_id";
        $stmt = $this->db->prepare($query);
        $params = [
            ':stagiaire_id' => $stagiaireId,
            ':exam_id' => $examId
        ];
        $this->db->execute($stmt, $params);
        return $this->db->single($stmt);
    }
    
    public function getResultsByStagiaire($stagiaireId) {
        $query = "SELECT r.*, 
                  e.name as exam_name, 
                  e.passing_score,
                  e.total_points,
                  u.username as formateur_name
                  FROM results r
                  JOIN exams e ON r.exam_id = e.id
                  LEFT JOIN users u ON r.graded_by = u.id
                  WHERE r.stagiaire_id = :stagiaire_id
                  ORDER BY r.created_at DESC";
        $stmt = $this->db->prepare($query);
        $params = [':stagiaire_id' => $stagiaireId];
        $this->db->execute($stmt, $params);
        return $this->db->resultSet($stmt);
    }
    
    public function getAllStagiaireResults($stagiaireId) {
        $query = "SELECT r.*, 
                  e.name as exam_name, 
                  e.description as exam_description,
                  e.total_points as exam_total_points,
                  e.passing_score as exam_passing_score,
                  f.username as formateur_name
                  FROM results r
                  JOIN exams e ON r.exam_id = e.id
                  LEFT JOIN users f ON r.graded_by = f.id
                  WHERE r.stagiaire_id = :stagiaire_id
                  ORDER BY r.created_at DESC";
                  
        $stmt = $this->db->prepare($query);
        $params = [
            ':stagiaire_id' => $stagiaireId
        ];
        
        $this->db->execute($stmt, $params);
        
        return $this->db->resultSet($stmt);
    }
    
    public function resultExists($stagiaireId, $examId) {
        $query = "SELECT COUNT(*) as count FROM results 
                  WHERE stagiaire_id = :stagiaire_id AND exam_id = :exam_id";
                  
        $stmt = $this->db->prepare($query);
        $params = [
            ':stagiaire_id' => $stagiaireId,
            ':exam_id' => $examId
        ];
        
        $this->db->execute($stmt, $params);
        
        $result = $this->db->single($stmt);
        
        return $result && $result['count'] > 0;
    }
    
    /**
     * Update a result by stagiaire ID and exam ID
     *
     * @param array $data Result data (score, total_score, graded_by)
     * @param int $stagiaireId The stagiaire ID
     * @param int $examId The exam ID
     * @return bool Success or failure
     */
    public function updateResultByStagiaireAndExam(array $data, $stagiaireId, $examId) {
        $query = "UPDATE results SET ";
        $queryParts = [];
        $params = [
            ':stagiaire_id' => $stagiaireId,
            ':exam_id' => $examId
        ];
        
        // Build query parts based on provided data
        if (isset($data['score'])) {
            $queryParts[] = "score = :score";
            $params[':score'] = $data['score'];
        }
        
        if (isset($data['total_score'])) {
            $queryParts[] = "total_score = :total_score";
            $params[':total_score'] = $data['total_score'];
        }
        
        if (isset($data['graded_by'])) {
            $queryParts[] = "graded_by = :graded_by";
            $params[':graded_by'] = $data['graded_by'];
        }
        
        // If no data to update, return false
        if (empty($queryParts)) {
            return false;
        }
        
        // Complete the query
        $query .= implode(', ', $queryParts);
        $query .= " WHERE stagiaire_id = :stagiaire_id AND exam_id = :exam_id";
        
        $stmt = $this->db->prepare($query);
        
        return $this->db->execute($stmt, $params);
    }
}
