<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Karla&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.2/css/all.css"
        integrity="sha384-vSIIfh2YWi9wW0r9iZe7RJPrKwp6bG+s9QZMoITbCckVJqGCCRhc+ccxNcdpHuYu" crossorigin="anonymous">
        <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <title>Home Page</title>
</head>
<style>
        .card-img-overlay {
            pointer-events: none;
        }
        .card-link.like {
            pointer-events: auto;
        }
        body{
            background-color: white;
        }
    </style>

</style>
<body>
<?php 
include('navbar.php');

if (isset($_SESSION['role']) && $_SESSION['role'] === 'trader') {
    include('homepage-trader-nav.php');
}



include('slider.php');
include('productcategories.php');
include('random_product.php');
?>
 <script>
function toggleFavorite(element) {
    const heartIcon = element.querySelector("i");
    const productId = element.getAttribute("data-product-id");
    const isAdding = heartIcon.classList.contains("far"); // If it has "far", it means we're adding to wishlist

    // Toggle heart icon
    if (isAdding) {
        heartIcon.classList.remove("far");
        heartIcon.classList.add("fas");
    } else {
        heartIcon.classList.remove("fas");
        heartIcon.classList.add("far");
    }

    // Send AJAX request to add or remove from wishlist
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "add_to_wishlist.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            if (xhr.responseText === "User not logged in") {
                // Revert heart icon change
                if (isAdding) {
                    heartIcon.classList.remove("fas");
                    heartIcon.classList.add("far");
                } else {
                    heartIcon.classList.remove("far");
                    heartIcon.classList.add("fas");
                }
                alert("Please log in to add items to your wishlist.");
            } else {
                alert(xhr.responseText); // Log response from the server
            }
        }
    };

    xhr.send(`product_id=${productId}&action=${isAdding ? 'add' : 'remove'}`);
}


    function addToCart(productId) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "add_to_cart.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                alert(xhr.responseText); // Show response from the server
            }
        }; 

        xhr.send("product_id=" + productId);
    }
    </script>
    <footer>
        <?php
            include('footer.php');
        ?>
    </footer>
</body>
</html>