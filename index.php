<?php include 'koneksi.php';?>

<html>
<head>
    <title>Page Title</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head>
<body>
    <table class="table table-striped table-bordered table-hover">
        <thead>
            <tr>
                <th>Name</th>
                <th>Token</th>
                <th>Refresh Token</th>
                <th>Auth Expired</th>
                <th>Token Expired</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $query = "SELECT * FROM settings";
                $result = mysqli_query($conn, $query);
                if (!$result) {
                    die("Query failed: " . mysqli_error($koneksi));
                }

                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_array($result)){
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['shop_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['token'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['refresh_token'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['expired_auth'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['expired_token'] ?? ''); ?></td>
            </tr>
            <?php 
                    }
                } else {
                    echo "<tr><td colspan='7'>No data available</td></tr>";
                }
            ?>
        </tbody>    
    </table>

    <form method="post" action="">
        <button type="submit" onclick="window.location.href='addstore.php'" class="btn btn-primary">Add Store</button>
    </form>

    <?php
    if (isset($_POST['add_store'])) {
        addStoreFunction();
    }

    function addStoreFunction() {
        $ml = time(); // Current Unix timestamp
        
        $sb = "https://partner.shopeemobile.com";
        $path = "/api/v2/shop/auth_partner";
        $sk = "794261665252484c5a7543524352556a6161617a72464c6f63614e4970626768";
        
        $sign = "";
        $partnerid = 2010008;
        $redirect = "https://pemilik.kiosq.id";
        
        $basestring = $partnerid . $path . $ml;
        
        // Use hash_hmac for HMAC-SHA256 hashing
        $sign = hash_hmac('sha256', $basestring, $sk);
        
        // Construct query parameters
        $param = "?partner_id=" . $partnerid . "&timestamp=" . $ml . "&sign=" . $sign . "&redirect=" . urlencode($redirect);
        
        $url = $sb . $path . $param;

        // Redirect to the constructed URL
        header("Location: $url");
        exit(); // Make sure to exit after the redirect
    }
    ?>
    
    <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/crypto-js.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    
</body>
</html>