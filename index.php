<?php
// --- DATABASE SETUP ---
$db_file = 'vib_database.json';
if (!file_exists($db_file)) file_put_contents($db_file, json_encode([]));
$data = json_decode(file_get_contents($db_file), true);

// --- 1. API SECTION (For Termux) ---
// Termux se aane wali request ko handle karta hai
if (isset($_GET['action']) && $_GET['action'] == 'validate') {
    $key = $_GET['key'] ?? '';
    $hwid = $_GET['hwid'] ?? '';
    $today = date('Y-m-d');

    if (!isset($data[$key])) {
        die("INVALID");
    }

    if ($today > $data[$key]['expiry']) {
        die("EXPIRED");
    }

    if ($data[$key]['multi_device'] == "1") {
        if (empty($data[$key]['hwid'])) {
            $data[$key]['hwid'] = $hwid; // Link HWID on first login
            file_put_contents($db_file, json_encode($data));
            die("VALID");
        } else {
            die(($data[$key]['hwid'] == $hwid) ? "VALID" : "DEVICE_LOCKED");
        }
    } else {
        die("VALID");
    }
}

// --- 2. ADMIN PANEL SECTION (For You) ---
if (isset($_POST['generate'])) {
    $new_key = "VIB-" . strtoupper(bin2hex(random_bytes(4)));
    $validity = $_POST['days']; 
    $multi = $_POST['multi'];
    $expiry = date('Y-m-d', strtotime("+$validity days"));

    $data[$new_key] = [
        "expiry" => $expiry,
        "hwid" => "",
        "multi_device" => $multi
    ];
    file_put_contents($db_file, json_encode($data));
    $msg = "Generated: $new_key | Exp: $expiry";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIB ADMIN PANEL</title>
    <style>
        body { background: #0a0a0a; color: #ff3333; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-align: center; }
        .container { border: 2px solid #ff0000; padding: 25px; display: inline-block; margin-top: 30px; border-radius: 15px; background: #111; box-shadow: 0 0 20px #ff0000; width: 90%; max-width: 400px; }
        input, select, button { width: 100%; padding: 12px; margin: 10px 0; border-radius: 5px; border: 1px solid #ff3333; background: #000; color: #fff; box-sizing: border-box; }
        button { background: #ff0000; font-weight: bold; cursor: pointer; transition: 0.3s; }
        button:hover { background: #aa0000; box-shadow: 0 0 10px #ff0000; }
        .table-container { margin-top: 30px; overflow-x: auto; }
        table { width: 100%; color: #fff; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #333; padding: 10px; text-align: left; }
        th { background: #222; color: #ff0000; }
    </style>
</head>
<body>
    <div class="container">
        <h2 style="text-shadow: 2px 2px #000;">VIB CHEATS CONTROL</h2>
        <?php if(isset($msg)) echo "<p style='color: #00ff00; font-weight:bold;'>$msg</p>"; ?>
        <form method="POST">
            <input type="number" name="days" placeholder="Validity (1-30 Days)" min="1" max="30" required>
            <select name="multi">
                <option value="1">1 Device Lock (HWID)</option>
                <option value="0">Multi-Device Support</option>
            </select>
            <button type="submit" name="generate">GENERATE KEY 🚀</button>
        </form>
    </div>

    <div class="table-container">
        <h3>ACTIVE LICENSES</h3>
        <table>
            <tr><th>KEY</th><th>EXPIRY</th><th>HWID</th></tr>
            <?php foreach(array_reverse($data) as $k => $v): ?>
            <tr>
                <td style="color:yellow;"><?php echo $k; ?></td>
                <td><?php echo $v['expiry']; ?></td>
                <td style="font-size: 10px;"><?php echo $v['hwid'] ?: '<span style="color:gray;">Waiting Login...</span>'; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>