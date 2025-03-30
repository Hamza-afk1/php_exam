<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration 
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Test</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4>Test Form</h4>
                    </div>
                    <div class="card-body">
                        <form id="testForm" method="post">
                            <div class="form-group">
                                <label for="testInput">Test Input</label>
                                <input type="text" class="form-control" id="testInput" name="testInput" placeholder="Enter text here">
                            </div>
                            
                            <div class="form-group">
                                <label for="testTextarea">Test Textarea</label>
                                <textarea class="form-control" id="testTextarea" name="testTextarea" rows="3" placeholder="Enter text here"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Modal Test</h4>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#testModal">
                            Open Test Modal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Test Modal -->
    <div class="modal fade" id="testModal" tabindex="-1" role="dialog" aria-labelledby="testModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testModalLabel">Test Modal</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="modalForm">
                        <div class="form-group">
                            <label for="modalInput">Modal Input</label>
                            <input type="text" class="form-control" id="modalInput" name="modalInput" placeholder="Enter text here">
                        </div>
                        
                        <div class="form-group">
                            <label for="modalTextarea">Modal Textarea</label>
                            <textarea class="form-control" id="modalTextarea" name="modalTextarea" rows="3" placeholder="Enter text here"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="modalSubmit">Submit</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.9.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Check if inputs are enabled when modal opens
        $('#testModal').on('shown.bs.modal', function () {
            console.log('Modal shown');
            console.log('Modal input disabled:', document.getElementById('modalInput').disabled);
            console.log('Modal textarea disabled:', document.getElementById('modalTextarea').disabled);
            
            // Force enable inputs
            document.getElementById('modalInput').disabled = false;
            document.getElementById('modalTextarea').disabled = false;
        });
        
        // Modal submit button
        document.getElementById('modalSubmit').addEventListener('click', function() {
            const inputValue = document.getElementById('modalInput').value;
            const textareaValue = document.getElementById('modalTextarea').value;
            
            alert('Input value: ' + inputValue + '\nTextarea value: ' + textareaValue);
        });
    </script>
</body>
</html> 