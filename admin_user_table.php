<?php 
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    echo '<script>alert("You are not authorized to access this page."); window.location.href = "login.php";</script>';
    exit(); // Stop further execution
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php
        include("admin-navbar.php");
    ?>
 <div class="line mb-3"></div>

<div class="text-box mb-3">
    <div class="text">User</div>
</div>

<div class="container">
<?php
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');



// SQL query to fetch data
$sql = "SELECT * 
FROM USERS WHERE ROLE='customer' "; // Replace 'schema_name' with the actual schema name if needed
$stid = oci_parse($conn, $sql);
oci_execute($stid);



echo "<style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
      </style>";

echo "<table>
        <tr>
            <th>First Name</th>
            <th>Phone Number</th>
            <th>Email</th>
            <th>Password</th>
            <th>Last Name</th>
            <th>Role</th>
            <th>Date of Birth</th>
        </tr>";

// Output data of each row
while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    echo "<tr>
            <td>" . htmlspecialchars($row['FIRST_NAME']) . "</td>
            <td>" . htmlspecialchars($row['PHN_NO']) . "</td>
            <td>" . htmlspecialchars($row['EMAIL']) . "</td>
            <td>" . htmlspecialchars($row['PASSWORD']) . "</td>
            <td>" . htmlspecialchars($row['LAST_NAME']) . "</td>
            <td>" . htmlspecialchars($row['ROLE']) . "</td>
            <td>" . htmlspecialchars($row['DOB']) . "</td>
          </tr>";
}
echo "</table>";

// Free statement resources and close the connection
oci_free_statement($stid);
oci_close($conn);
?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>



</body>
</html>
