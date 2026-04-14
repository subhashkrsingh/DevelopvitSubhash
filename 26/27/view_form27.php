<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "clinic";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get single record if ID is provided
$single_record = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM health_registers WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $single_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch all entries for listing
$sql = "SELECT * FROM health_registers ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Form-27 Health Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; padding: 0; }
            .certificate { box-shadow: none; padding: 20px; }
        }
        body {
            background: linear-gradient(145deg, #0f172a 0%, #1e1b4b 100%);
            padding: 30px;
            font-family: 'Segoe UI', sans-serif;
        }
        .certificate {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h2 {
            font-weight: 800;
            color: #0f172a;
        }
        .btn-print, .btn-back, .btn-new {
            background: linear-gradient(105deg, #4f46e5, #9333ea);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 40px;
            margin-top: 20px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-back {
            background: #6c757d;
        }
        .btn-new {
            background: #10b981;
        }
        .info-row {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-label {
            font-weight: 700;
            color: #4f46e5;
            width: 250px;
            display: inline-block;
        }
        .table th {
            background: #4f46e5;
            color: white;
        }
        .action-btn {
            padding: 5px 12px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="certificate">
    <div class="header">
        <h2><i class="fas fa-book me-2"></i> FORM 27</h2>
        <h4>HEALTH REGISTER</h4>
        <p>[Prescribed under the Schedule specified under Rule 109]</p>
        <h5>NTPC DADRI</h5>
    </div>

    <?php if ($single_record): ?>
        <!-- Single Record View -->
        <h4 class="mb-4"><i class="fas fa-file-alt me-2"></i> Health Register Details</h4>
        <div class="info-row"><span class="info-label">Serial Number:</span> <?php echo htmlspecialchars($single_record['serial_number'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Department:</span> <?php echo htmlspecialchars($single_record['department'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Name of Worker:</span> <?php echo htmlspecialchars($single_record['name'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Sex:</span> <?php echo htmlspecialchars($single_record['sex'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Age:</span> <?php echo htmlspecialchars($single_record['age'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Date of Employment:</span> <?php echo htmlspecialchars($single_record['start_date'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Leave/Transfer:</span> <?php echo htmlspecialchars($single_record['leave_transfer'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Occupation:</span> <?php echo htmlspecialchars($single_record['occupation'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Raw Materials:</span> <?php echo htmlspecialchars($single_record['raw_materials'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Exam Date:</span> <?php echo htmlspecialchars($single_record['exam_date'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Result:</span> 
            <span class="badge bg-<?php 
                echo $single_record['result'] == 'Fit' ? 'success' : 
                    ($single_record['result'] == 'Unfit' ? 'danger' : 'warning'); 
            ?>"><?php echo htmlspecialchars($single_record['result'] ?? 'N/A'); ?></span>
        </div>
        <div class="info-row"><span class="info-label">Signs & Symptoms:</span> <?php echo nl2br(htmlspecialchars($single_record['signs_symptoms'] ?? 'N/A')); ?></div>
        <div class="info-row"><span class="info-label">Tests Result:</span> <?php echo nl2br(htmlspecialchars($single_record['tests_result'] ?? 'N/A')); ?></div>
        <div class="info-row"><span class="info-label">Suspension Details:</span> <?php echo nl2br(htmlspecialchars($single_record['suspension_details'] ?? 'N/A')); ?></div>
        <div class="info-row"><span class="info-label">Certificate Issued:</span> <?php echo htmlspecialchars($single_record['certificate_issued'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Recertified Date:</span> <?php echo htmlspecialchars($single_record['recertified_date'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Surgeon Signature:</span> <?php echo htmlspecialchars($single_record['surgeon_signature'] ?? 'N/A'); ?></div>
        <div class="info-row"><span class="info-label">Created Date:</span> <?php echo htmlspecialchars($single_record['created_at'] ?? 'N/A'); ?></div>
        
        <div class="text-center no-print mt-4">
            <button class="btn-print" onclick="window.print()"><i class="fas fa-print me-2"></i> PRINT</button>
            <a href="index.php" class="btn-new" style="text-decoration: none;"><i class="fas fa-plus me-2"></i> NEW ENTRY</a>
            <a href="view_form27.php" class="btn-back" style="text-decoration: none;"><i class="fas fa-list me-2"></i> VIEW ALL</a>
            <a href="../index.php" class="btn-back" style="background: #4f46e5; text-decoration: none;"><i class="fas fa-home me-2"></i> HOME</a>
        </div>
        
    <?php else: ?>
        <!-- List View -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-list me-2"></i> All Health Register Entries</h4>
            <a href="index.php" class="btn-new" style="padding: 8px 20px; text-decoration: none;"><i class="fas fa-plus me-2"></i> ADD NEW</a>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Serial No</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Age</th>
                        <th>Sex</th>
                        <th>Exam Date</th>
                        <th>Result</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $resultClass = '';
                            if ($row['result'] == 'Fit') $resultClass = 'badge bg-success';
                            elseif ($row['result'] == 'Unfit') $resultClass = 'badge bg-danger';
                            else $resultClass = 'badge bg-warning';
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['serial_number'] ?? ''); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['name'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['department'] ?? ''); ?></td>
                                <td><?php echo $row['age']; ?></td>
                                <td><?php echo htmlspecialchars($row['sex'] ?? ''); ?></td>
                                <td><?php echo $row['exam_date']; ?></td>
                                <td><span class="<?php echo $resultClass; ?>"><?php echo htmlspecialchars($row['result'] ?? 'N/A'); ?></span></td>
                                <td><?php echo $row['created_at']; ?></td>
                                <td>
                                    <a href="view_form27.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary action-btn">View</a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='10' class='text-center py-4'>No entries found. <a href='index.php'>Click here to add new entry</a></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-center no-print mt-4">
            <a href="index.php" class="btn-new" style="text-decoration: none;"><i class="fas fa-plus me-2"></i> ADD NEW ENTRY</a>
            <a href="../index.php" class="btn-back" style="background: #4f46e5; text-decoration: none;"><i class="fas fa-home me-2"></i> BACK TO HOME</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
<?php $conn->close(); ?>