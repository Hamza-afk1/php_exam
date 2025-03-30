<?php
require_once 'Model.php';

class Result extends Model {
    protected $table = 'results';
    
    public function create(array $data) {
        try {
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
            
            // Log the operation
            error_log('Result::create - Executing query with params: ' . json_encode($params));
            
            if ($this->db->execute($stmt, $params)) {
                $resultId = $this->db->lastInsertId();
                error_log('Result::create - Successfully created result with ID: ' . $resultId);
                return $resultId;
            } else {
                error_log('Result::create - Failed to execute statement');
                return false;
            }
        } catch (Exception $e) {
            error_log('Result::create - Exception: ' . $e->getMessage());
            return false;
        }
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
                  e.passing_score
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
                  e.passing_score
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
    
    /**
     * Get recent results for all stagiaires
     *
     * @param int $limit Maximum number of results to return
     * @return array List of recent results
     */
    public function getRecentResults($limit = 5) {
        $query = "SELECT r.*, 
                  e.name as exam_name,
                  u.username as stagiaire_name 
                  FROM results r
                  JOIN exams e ON r.exam_id = e.id
                  JOIN users u ON r.stagiaire_id = u.id
                  ORDER BY r.created_at DESC
                  LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $params = [
            ':limit' => $limit
        ];
        $this->db->execute($stmt, $params);
        return $this->db->resultSet($stmt);
    }
    
    /**
     * Get recent exam results for a specific stagiaire
     * 
     * @param int $stagiaireId The ID of the stagiaire
     * @param int $limit Maximum number of results to return (default: 5)
     * @return array List of recent results
     */
    public function getRecentResultsForStagiaire($stagiaireId, $limit = 5) {
        $query = "SELECT r.*, 
                  e.name as exam_name, 
                  e.passing_score,
                  u.username as formateur_name
                  FROM results r
                  JOIN exams e ON r.exam_id = e.id
                  LEFT JOIN users u ON r.graded_by = u.id
                  WHERE r.stagiaire_id = :stagiaire_id
                  ORDER BY r.created_at DESC
                  LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $params = [
            ':stagiaire_id' => $stagiaireId,
            ':limit' => $limit
        ];
        $this->db->execute($stmt, $params);
        return $this->db->resultSet($stmt);
    }
    
    public function getAllStagiaireResults($stagiaireId) {
        $query = "SELECT r.*, 
                  e.name as exam_name, 
                  e.description as exam_description,
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
    
    /**
     * Count the number of unique students who took exams by a specific formateur
     * 
     * @param int $formateurId The formateur ID
     * @return int The count of unique students
     */
    public function countStudentsByFormateur($formateurId) {
        try {
            $query = "SELECT COUNT(DISTINCT r.stagiaire_id) as total_students
                      FROM results r
                      JOIN exams e ON r.exam_id = e.id
                      WHERE e.formateur_id = :formateur_id";
                      
            $stmt = $this->db->prepare($query);
            $this->db->execute($stmt, [':formateur_id' => $formateurId]);
            $result = $this->db->single($stmt);
            
            return $result['total_students'] ?? 0;
        } catch (Exception $e) {
            error_log("Error counting students by formateur: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get recent results for exams created by a specific formateur
     * 
     * @param int $formateurId The formateur ID
     * @param int $limit Maximum number of results to return
     * @return array The recent results
     */
    public function getRecentResultsByFormateur($formateurId, $limit = 5) {
        try {
            $query = "SELECT r.*, e.name as exam_name, u.username as student_name, e.total_points
                      FROM results r
                      JOIN exams e ON r.exam_id = e.id
                      JOIN users u ON r.stagiaire_id = u.id
                      WHERE e.formateur_id = :formateur_id
                      ORDER BY r.submission_date DESC
                      LIMIT :limit";
                      
            $stmt = $this->db->prepare($query);
            $this->db->execute($stmt, [
                ':formateur_id' => $formateurId,
                ':limit' => $limit
            ]);
            
            return $this->db->resultSet($stmt);
        } catch (Exception $e) {
            error_log("Error getting recent results by formateur: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get performance statistics for exams created by a specific formateur
     * 
     * @param int $formateurId The formateur ID
     * @return array Performance statistics
     */
    public function getExamPerformanceByFormateur($formateurId) {
        try {
            // Get average scores for each exam
            $query = "SELECT e.id, e.name, 
                      AVG((r.score / e.total_points) * 100) as avg_score,
                      COUNT(r.id) as attempt_count
                      FROM results r
                      JOIN exams e ON r.exam_id = e.id
                      WHERE e.formateur_id = :formateur_id
                      GROUP BY e.id
                      HAVING attempt_count >= 1";
                      
            $stmt = $this->db->prepare($query);
            $this->db->execute($stmt, [':formateur_id' => $formateurId]);
            $examScores = $this->db->resultSet($stmt);
            
            if (empty($examScores)) {
                return [];
            }
            
            // Find highest and lowest scoring exams
            $highest = null;
            $lowest = null;
            $highestScore = 0;
            $lowestScore = 100;
            
            foreach ($examScores as $exam) {
                if ($exam['avg_score'] > $highestScore) {
                    $highestScore = $exam['avg_score'];
                    $highest = $exam;
                }
                
                if ($exam['avg_score'] < $lowestScore) {
                    $lowestScore = $exam['avg_score'];
                    $lowest = $exam;
                }
            }
            
            return [
                'highest' => $highest,
                'lowest' => $lowest,
                'all_exams' => $examScores
            ];
        } catch (Exception $e) {
            error_log("Error getting exam performance by formateur: " . $e->getMessage());
            return [];
        }
    }
}
