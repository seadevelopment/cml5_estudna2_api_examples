<?php
/**
 * Change these values to your own credentials and device serial number
 */
$USERNAME = "YOUR_USERNAME";
$PASSWORD = "YOUR_PASSWORD";
$SN = "YOUR_SN";

/**
 * Thingsboard API client
 */
class Thingsboard {
    private $url = "https://cml5.seapraha.cz";
    private $token = "";
    private $customer_id = "";
    private $device_id = "";
    private $keys = [];

    private function request($method, $endpoint, $data = null, $auth = false) {
        $url = $this->url . $endpoint;
        $ch = curl_init($url);

        $headers = ['Content-Type: application/json'];
        if ($auth && $this->token !== "") {
            $headers[] = "X-Authorization: Bearer {$this->token}";
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === "POST") {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === "GET" && $data) {
            $url .= '?' . http_build_query($data);
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status >= 200 && $status < 300) {
            return json_decode($response, true);
        } else {
            throw new Exception("HTTP $status: $response");
        }
    }

    public function authenticate($username, $password) {
        $data = ["username" => $username, "password" => $password];
        $resp = $this->request("POST", "/apiv2/auth/login", $data);
        $this->token = $resp["token"] ?? "";
        if ($this->token === "") {
            throw new Exception("Authentication failed: No token returned");
        }
    }

    public function get_customer_id($username) {
        $resp = $this->request("GET", "/apiv2/user?textSearch=" . urlencode($username), null, true);
        $this->customer_id = $resp["id"] ?? "";
        if ($this->customer_id === "") {
            throw new Exception("Failed to retrieve customer ID");
        }
    }

    public function get_device($sn) {
        $resp = $this->request("GET", "/apiv2/user/{$this->customer_id}/devices", null, true);
        foreach ($resp as $device) {
            if (isset($device["name"]) && $device["name"] === $sn) {
                $this->device_id = $device["id"];
                return;
            }
        }
        throw new Exception("Device with SN {$sn} not found");
    }

    public function get_keys() {
        $resp = $this->request("GET", "/apiv2/device/{$this->device_id}/keys", null, true);
        $this->keys = $resp["keys"] ?? [];
        return $this->keys;
    }

    public function get_telemetry($key) {
        $resp = $this->request("GET", "/apiv2/device/{$this->device_id}/latest?keys={$key}", null, true);
        if (isset($resp[$key][0]["value"])) {
            return $resp[$key][0]["value"];
        }
        return null;
    }
}

/**
 * Helper functions
 */
function eStudna2_get_device_keys($username, $password, $sn) {
    $tb = new Thingsboard();
    $tb->authenticate($username, $password);
    $tb->get_customer_id($username);
    $tb->get_device($sn);
    return $tb->get_keys();
}

function eStudna2_get_latest_telemetry_for_key($username, $password, $sn, $key) {
    $tb = new Thingsboard();
    $tb->authenticate($username, $password);
    $tb->get_customer_id($username);
    $tb->get_device($sn);
    $tb->get_keys();
    return $tb->get_telemetry($key);
}

function eStudna2_get_telemetry_for_all_keys($username, $password, $sn) {
    $tb = new Thingsboard();
    $tb->authenticate($username, $password);
    $tb->get_customer_id($username);
    $tb->get_device($sn);
    $keys = $tb->get_keys();
    foreach ($keys as $key) {
        $value = $tb->get_telemetry($key);
        echo "Telemetry data for {$key}: {$value}\n";
    }
}

/**
 * Run the example
 */
try {
    $keys = eStudna2_get_device_keys($USERNAME, $PASSWORD, $SN);
    echo "Available Device keys: " . implode(", ", $keys) . "\n";

    echo "Last telemetry for all keys:\n";
    eStudna2_get_telemetry_for_all_keys($USERNAME, $PASSWORD, $SN);

    $value = eStudna2_get_latest_telemetry_for_key($USERNAME, $PASSWORD, $SN, "ain1_v");
    echo "Last telemetry for key 'ain1_v': {$value}\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
