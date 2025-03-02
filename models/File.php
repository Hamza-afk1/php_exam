<?php
require_once 'Model.php';

class File extends Model {
    protected $table = 'files';
    
    public function create(array $data) {
        $query = "INSERT INTO files (exam_id, file_name, file_path) 
                  VALUES (:exam_id, :file_name, :file_path)";
                  
        $stmt = $this->db->prepare($query);
        
        $params = [
            ':exam_id' => $data['exam_id'],
            ':file_name' => $data['file_name'],
            ':file_path' => $data['file_path']
        ];
        
        if ($this->db->execute($stmt, $params)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function update(array $data, $id) {
        $query = "UPDATE files SET 
                  file_name = :file_name, 
                  file_path = :file_path
                  WHERE id = :id";
                  
        $stmt = $this->db->prepare($query);
        
        $params = [
            ':file_name' => $data['file_name'],
            ':file_path' => $data['file_path'],
            ':id' => $id
        ];
        
        return $this->db->execute($stmt, $params);
    }
    
    public function getFilesByExam($examId) {
        $query = "SELECT * FROM files WHERE exam_id = :exam_id";
        $stmt = $this->db->prepare($query);
        $params = [':exam_id' => $examId];
        $this->db->execute($stmt, $params);
        return $this->db->resultSet($stmt);
    }
}
?>
