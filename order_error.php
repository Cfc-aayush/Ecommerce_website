<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="alert alert-danger text-center">
                    <h4 class="alert-heading">Order Processing Error</h4>
                    <p>We're sorry, but there was an error processing your order. Please try again later.</p>
                    <hr>
                    <p>If the problem persists, please contact our support team.</p>
                    <div class="mt-4">
                        <a href="shopping_cart.php" class="btn btn-primary">Back to Shopping Cart</a>
                        <a href="home.php" class="btn btn-secondary">Home</a>
                    </div>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-warning mt-4">
                            <strong>Error Details:</strong> <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
