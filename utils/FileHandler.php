<?php
class FileHandler {
    private $allowedExtensions;
    private $maxFileSize;
    private $uploadDir;
    private $errors = [];
    
    public function __construct($allowedExtensions = [], $maxFileSize = null, $uploadDir = null) {
        $this->allowedExtensions = !empty($allowedExtensions) ? 
                                   $allowedExtensions : 
                                   ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif'];
        $this->maxFileSize = $maxFileSize ?: MAX_FILE_SIZE;
        $this->uploadDir = $uploadDir ?: UPLOAD_DIR;
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    public function upload($fileInputName, $customDir = '') {
        if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
            $this->errors[] = 'No file was uploaded.';
            return false;
        }
        
        $file = $_FILES[$fileInputName];
        
        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadError($file['error']);
            return false;
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $this->errors[] = 'File is too large. Maximum file size allowed is ' . 
                              round($this->maxFileSize / (1024 * 1024), 2) . ' MB.';
            return false;
        }
        
        // Check file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $this->allowedExtensions)) {
            $this->errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $this->allowedExtensions);
            return false;
        }
        
        // Create target directory if it doesn't exist
        $targetDir = $this->uploadDir;
        if (!empty($customDir)) {
            $targetDir .= $customDir . '/';
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }
        
        // Generate unique filename
        $uniqueName = md5(uniqid() . time()) . '.' . $fileExtension;
        $targetFile = $targetDir . $uniqueName;
        
        // Try to upload the file
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return [
                'original_name' => $file['name'],
                'unique_name' => $uniqueName,
                'file_path' => $targetFile,
                'file_type' => $fileExtension,
                'file_size' => $file['size']
            ];
        } else {
            $this->errors[] = 'Failed to upload file.';
            return false;
        }
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    private function getUploadError($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File is too large.';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'Unknown upload error.';
        }
    }
    
    public function deleteFile($filePath) {
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}
?>
