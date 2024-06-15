<?php
session_start();

// Oracle Database connection
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Check if the total amount is set in the session
if (!isset($_SESSION['totalamount'])) {
    header('Location: shopping_cart.php');
    exit;
}

// Fetch slots from the database
$query = "SELECT SLOT_ID, SLOT_DAY, TO_CHAR(SLOT_TIME, 'HH24:MI AM') AS SLOT_TIME, TO_CHAR(SLOT_END_TIME, 'HH24:MI AM') AS SLOT_END_TIME FROM collection_slot ORDER BY SLOT_DAY, SLOT_TIME";
$stid = oci_parse($conn, $query);
oci_execute($stid);

// Fetch the first slot's ID and store it in the session
if ($row = oci_fetch_assoc($stid)) {
    $_SESSION['slot_id'] = $row['SLOT_ID'];
} else {
    error_log("No slots found");
    echo "No slots found";
}
$slots = [];
while ($row = oci_fetch_assoc($stid)) {
    $slots[$row['SLOT_DAY']][] = $row;
}
oci_free_statement($stid);
oci_close($conn);

$paypalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
$paypalID = 'sb-yqcjf29065468@business.example.com'; // Business Email
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete your purchase</title>
    <link rel="stylesheet" href="checkout.css">
    <style>
        .selected {
            background-color: yellow;
        }
        .timeslot-bubble {
            cursor: pointer;
            margin: 5px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: inline-block;
        }
        .timeslot-bubble.selected {
            background-color: yellow;
        }
    </style>
    <script>
    function selectTimeSlot(slotId, element) {
      var previouslySelected = document.querySelector('.timeslot-bubble.selected');
      if (previouslySelected) {
        previouslySelected.classList.remove('selected');
      }
      element.classList.add('selected');
      document.getElementById('selected_slot_id').value = slotId;

      // Get selected slot details
      const selectedText = element.textContent;
      const selectedTimeParts = selectedText.split(' - ')[0].split(':'); // Extract selected time (hours:minutes)

      // Disable all slots except for the next day slot
      const timeSlots = document.querySelectorAll('.timeslot-bubble');
      timeSlots.forEach(slot => {
        slot.disabled = true; // Disable all slots initially

        // Check if slot belongs to the next day
        const slotText = slot.textContent;
        const slotTimeParts = slotText.split(' - ')[0].split(':'); // Extract slot time (hours:minutes)

        if (slotText.includes(`${(new Date()).getDay() + 1}`) && // Check for next day (day index starts at 0)
            slotTimeParts[0] === selectedTimeParts[0] && // Match hours
            slotTimeParts[1] === selectedTimeParts[1]) { // Match minutes (optional, adjust if needed)
          slot.disabled = false; // Enable the matching next day slot
        }
      });
    }

    function submitPayPalForm() {
      if (!document.getElementById('selected_slot_id').value) {
        alert('Please select a time slot.');
        return;
      }
      document.getElementById('paypal_form').submit();
    }
  </script>
</head>
<body>
    <div class="heading">
        <h1>Complete your purchase</h1>
    </div>
    <div class="background">
        <div class="left-part">
            <h3>Select a time slot</h3>
            <br/>
            <?php
            foreach ($slots as $day => $daySlots) {
                echo "<h5>$day</h5><div class='time-slot'>";
                foreach ($daySlots as $slot) {
                    echo "<div class='timeslot-bubble' onclick='selectTimeSlot({$slot['SLOT_ID']}, this)'>{$slot['SLOT_TIME']} - {$slot['SLOT_END_TIME']}</div>";
                }
                echo '</div><br/>';
            }
            ?>
        </div>
        <div class="right-part">
            <img src="image/LogoDark.png" alt="Logo">
            <div>
                <p><strong>Total Payable Amount: </strong>$<?php echo number_format($_SESSION['totalamount'], 2); ?></p>
            </div>
            <div class="checkout-button" onclick="submitPayPalForm()">
                <p>Pay with PayPal</p>
            </div>
        </div>
    </div>

    <form id="paypal_form" action="<?php echo $paypalURL;?>" method="post" style="display: none;">
        <input type="hidden" name="business" value="<?php echo $paypalID;?>">
        <input type="hidden" name="cmd" value="_xclick">
        <input type="hidden" name="item_name" value="Purchase">
        <input type="hidden" name="item_number" value="1">
        <input type="hidden" name="amount" value="<?php echo number_format($_SESSION['totalamount'], 2);?>">
        <input type="hidden" name="currency_code" value="USD">
        <input type="hidden" name="quantity" value="1">
        <input type="hidden" id="selected_slot_id" name="custom" value="">
        <input type='hidden' name='cancel_return' value='http://localhost/clekbuy/cancel.php'>
        <input type='hidden' name='return' value='http://localhost/clekbuy/success.php'>
    </form>
</body>
</html>
