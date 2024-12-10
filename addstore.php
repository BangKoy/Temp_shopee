<?php
    include 'koneksi.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Form</title>
</head>
<body>
    <form method="post">
        <label for="code">Code:</label>
        <input type="text" id="code" name="code"><br><br>

        <label for="shopid">Shop ID:</label>
        <input type="text" id="shopid" name="shopid"><br><br>

        <button type="button" onclick="window.location.href='index.php'">Cancel</button>
        <button type="submit" name="save">Save</button>
        <button type="submit" name="auth">Auth</button>
    </form>

    <?php
    if (isset($_POST['auth'])) {
        $url = addStoreFunction();
        echo "<script>window.open('$url', '_blank');</script>";
        exit(); // Make sure to exit after the script
    }

    function addStoreFunction() {
        $ml = time(); // Current Unix timestamp
        
        $sb = "https://partner.shopeemobile.com";
        $path = "/api/v2/shop/auth_partner";
        $sk = "794261665252484c5a7543524352556a6161617a72464c6f63614e4970626768";
        
        $partnerid = 2010008;
        $redirect = "https://pemilik.kiosq.id";
        
        $basestring = $partnerid . $path . $ml;
        
        // Use hash_hmac for HMAC-SHA256 hashing
        $sign = hash_hmac('sha256', $basestring, $sk);
        
        // Construct query parameters
        $param = "?partner_id=" . $partnerid . "&timestamp=" . $ml . "&sign=" . $sign . "&redirect=" . urlencode($redirect);
        
        $url = $sb . $path . $param;

        return $url; // Return the constructed URL
    }

    if (isset($_POST['save'])) {
        $url = getValues();
    }

    // New function to get values from code and shopid
    function getValues() {
        $ml = time(); // Current Unix timestamp
        $sb = "https://partner.shopeemobile.com";
        $path = "/api/v2/auth/token/get";
        $sk = "794261665252484c5a7543524352556a6161617a72464c6f63614e4970626768";
        $partnerid = 2010008;

        $code = isset($_POST['code']) ? $_POST['code'] : '';
        $shopid = isset($_POST['shopid']) ? (int) $_POST['shopid'] : 0;

        // Construct the base string for signing
        $basestring = $partnerid . $path . $ml;

        // Use hash_hmac for HMAC-SHA256 hashing
        $sign = hash_hmac('sha256', $basestring, $sk);

        $timestamp = htmlspecialchars("&timestamp=");
        // Construct the API URL with parameters
        $param = "?partner_id=" . $partnerid . $timestamp . $ml . "&sign=" . $sign;

        $apiUrl = $sb . $path . $param;

        // echo htmlspecialchars($apiUrl);

        $json = array(
            "code" => $_POST['code'],
            "shop_id" => $shopid,
            "partner_id" => 2010008
        );

        // echo json_encode($json, JSON_PRETTY_PRINT);

        // Call the API and return the response
        return callApiGetToken($sb,$path,$partnerid,$timestamp,$ml,$sign,$json);
    }

    function callApiGetToken($sb,$path,$partnerid,$timestamp,$ml,$sign,$json) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://partner.shopeemobile.com/api/v2/auth/token/get?partner_id='. $partnerid .'&timestamp='. $ml .'&sign='.$sign,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($json),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        // echo $response;

        curl_close($curl);
        // echo $response;

            $data = json_decode($response, true); // Set second parameter to true for associative array
            // echo $data;
            // Check if decoding was successful
            if (json_last_error() === JSON_ERROR_NONE) {
                if (!isset($data['refresh_token'], $data['access_token'], $data['expire_in'])) { 
                    echo "Expected keys are missing in the response.<br>";
                }
                $refresh_token = $data['refresh_token'];
                $access_token = $data['access_token'];
                $expire_in = $data['expire_in'];

                echo "Refresh Token: " . htmlspecialchars($refresh_token) . "<br>";
                echo "Access Token: " . htmlspecialchars($access_token) . "<br>";
                echo "Expire In: " . htmlspecialchars($expire_in) . "<br>";
                saveMerchant($access_token,$refresh_token,$expire_in);
            } else {
                // Handle JSON decoding error
                echo "Failed to decode JSON: " . json_last_error_msg();
            }
    }

    function saveMerchant($access_token,$refresh_token,$expire_in) {
        global $conn;

        $ml = time(); // Current Unix timestamp
        $sb = "https://partner.shopeemobile.com";
        $path = "/api/v2/shop/get_shop_info";
        $sk = "794261665252484c5a7543524352556a6161617a72464c6f63614e4970626768";
        $partnerid = 2010008;
        $shopid = isset($_POST['shopid']) ? (int) $_POST['shopid'] : 0;

        // Construct the base string for signing
        $basestring = $partnerid . $path . $ml . $access_token . $shopid;

        // Use hash_hmac for HMAC-SHA256 hashing
        $sign = hash_hmac('sha256', $basestring, $sk);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://partner.shopeemobile.com/api/v2/shop/get_shop_info?partner_id='. $partnerid .'&timestamp='. $ml .'&access_token='. $access_token .'&shop_id='.$shopid .'&sign='.$sign,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        // echo $response;

        $data = json_decode($response, true);
        $shopName = $data['shop_name'];
        $expireTime = $data['expire_time'];

        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO settings (
            shop_name, 
            token, 
            refresh_token, 
            expired_token, 
            expired_auth,
            shop_id
        ) VALUES (
            ?, 
            ?, 
            ?, 
            FROM_UNIXTIME(?), 
            FROM_UNIXTIME(?), 
            ?
        ) ");

        if (!$stmt) {
            echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
            return false;
        }
        // Calculate expiration time
        $expires_at = time() + $expire_in;
        $expires_auth = time() + $expireTime;

        // Bind parameters
        $bind_result = $stmt->bind_param(
            "sssiii", 
            $shopName, 
            $access_token, 
            $refresh_token, 
            $expires_at, 
            $expires_auth, 
            $shopid
        );

        // Check if binding failed
        if (!$bind_result) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
            return false;
        }

        // Execute the statement
        try {
            $result = $stmt->execute();

            if ($result) {
                echo "Merchant information saved successfully!";
                header("Location: index.php");
                exit();
            } else {
                echo "Error saving merchant information: " . $stmt->error;
                return false;
            }
        } catch (Exception $e) {
            echo "Exception: " . $e->getMessage();
            return false;
        } finally {
            $stmt->close();
        }
    }
    ?>
</body>
</html>