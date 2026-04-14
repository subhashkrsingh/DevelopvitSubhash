<?php
// Database connection
$servername = "localhost";
$username   = "root";  // change if needed
$password   = "";      // change if needed
$dbname     = "clinic";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all entries
$sql = "SELECT * FROM form26 ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>View Form-26 Entries</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<div class="container">
  <h3 class="mb-4">Certificate of Fitness</h3>
  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Serial No</th>
        <th>Patient Name</th>
        <th>Father Name</th>
        <th>Address</th>
        <th>Employment</th>
        <th>Designation</th>
        <th>Age</th>
        <th>Fitness Status</th>
        <th>Further Exam</th>
        <th>Previous Certificate</th>
        <th>Created At</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
              echo "<tr>
                  <td>{$row['id']}</td>
                  <td>{$row['serial_number']}</td>
                  <td>{$row['patient_name']}</td>
                  <td>{$row['father_name']}</td>
                  <td>{$row['address']}</td>
                  <td>{$row['employment_details']}</td>
                  <td>{$row['designation']}</td>
                  <td>{$row['age']}</td>
                  <td>{$row['fitness_status']}</td>
                  <td>{$row['further_exam_period']}</td>
                  <td>{$row['previous_certificate_no']}</td>
                  <td>{$row['created_at']}</td>
              </tr>";
          }
      } else {
          echo "<tr><td colspan='12' class='text-center'>No entries found.</td></tr>";
      }
      $conn->close();
      ?>
    </tbody>
  </table>
</div>

</body>
</html>
