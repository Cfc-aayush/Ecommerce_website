<?php
session_start();

$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = md5($_POST['password']); // Hashing the entered password with MD5
    $usertype = $_POST['usertype'];

    // Query to fetch user data
    $sql = 'SELECT * FROM users WHERE email = :email AND role = :role';
    $stid = oci_parse($conn, $sql);

    oci_bind_by_name($stid, ':email', $email);
    oci_bind_by_name($stid, ':role', $usertype);

    oci_execute($stid);

    $user = oci_fetch_assoc($stid);

// Verifying the hashed password with the stored hash and checking status
if ($user && $password === $user['PASSWORD']) { 
  if ($user['STATUS'] == 1) { // Check if status is activated
      // Set session variables
      $_SESSION['user_id'] = $user['USER_ID'];
      $_SESSION['email'] = $user['EMAIL'];
      $_SESSION['role'] = $user['ROLE'];
      $_SESSION['first_name'] = $user['FIRST_NAME'];

      // Redirect based on user type
      switch ($usertype) {
          case 'customer':
              header('Location: home.php');
              break;
          case 'trader':
              header('Location: trader_home.php');
              break;
          case 'admin':
              header('Location: Admin_request.php');
              break;
      }
      exit;
  } else {
      $error = "User not activated. Please contact the administrator.";
  }
} else {
  $error = "Invalid email or password!";
}


    oci_free_statement($stid);
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>
  <div class="login-container">
    <form class="login-form" method="post">
      <h2>Login</h2>
      <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
      <?php endif; ?>
      <div class="input-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="input-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <span class="toggle-password" onclick="togglePasswordVisibility()">Show</span>
      </div>
      <div class="input-group">
        <label for="usertype">User type:</label>
        <select id="usertype" name="usertype" required>
          <option value="customer">Customer</option>
          <option value="trader">Trader</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <button type="submit">Log in</button>
      <p>Don't have an account? Create one as <a href="Signup_customer.php">customer</a> or <a href="Signup_trader.php">trader</a></p>
    </form>
  </div>
  
  <script>
    function togglePasswordVisibility() {
      var passwordInput = document.getElementById("password");
      var toggleButton = document.querySelector(".toggle-password");
      
      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleButton.textContent = "Hide";
      } else {
        passwordInput.type = "password";
        toggleButton.textContent = "Show";
      }
    }
  </script>
</body>
</html>
