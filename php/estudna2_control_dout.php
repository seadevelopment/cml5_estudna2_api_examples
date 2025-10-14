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

    public function post_rpc($method, $params) {
        $payload = [
            "method" => $method,
            "params" => $params
        ];
        $resp = $this->request("POST", "/apiv2/device/{$this->device_id}/rpc/twoway", $payload, true);
        return true;
    }

    public function set_dout($dout_number, $value = false) {
        /**
         * method: setdout1, setdout2
         * params: True/False
         */
        $method = "setdout" . $dout_number;
        return $this->post_rpc($method, $value);
    }
}

/**
 * Helper functions
 */
function eStudna2_get_latest_telemetry_for_dout($username, $password, $sn, $key) {
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

function eStudna2_set_dout($username, $password, $sn, $dout_number, $value = "off") {
    $tb = new Thingsboard();
    $tb->authenticate($username, $password);
    $tb->get_customer_id($username);
    $tb->get_device($sn);
    $boolValue = strtolower($value) === "on" || $value === true;
    $tb->set_dout($dout_number, $boolValue);
}

function eStudna2_toggle_dout($username, $password, $sn, $dout_number) {
    $tb = new Thingsboard();
    $tb->authenticate($username, $password);
    $tb->get_customer_id($username);
    $tb->get_device($sn);

    $dout_v_value = $tb->get_telemetry("dout{$dout_number}_v");
    $new_value = ($dout_v_value == "0") ? true : false;
    $tb->set_dout($dout_number, $new_value);
}

/**
 * Run demo (example usage)
 */
try {
    /**
     * dout1_v - raw value of the digital output 1 as an integer (0/1)
     * int
     * dout1_v = 1 -> ON
     * dout1_v = 0 -> OFF
     */
    echo "Last telemetry for 'dout1_v': " . 
        eStudna2_get_latest_telemetry_for_dout($USERNAME, $PASSWORD, $SN, "dout1_v") . "\n";
    /** dout1 - full info of the digital output 1 as a JSON string
    *Example:
    *{"mode":"manual","str":0,"alternating":false,"regulation_source":"ain1","manual_override":false}

    *mode - regulation mode (manual/scheduler/upper/lower)
    *str - value as a string (0/1) for OFF/ON
    *alternating - if true, the output alternates between ON and OFF states - used for dosing pumps
    *regulation_source - source of regulation (ain1 - for estudna2 and estunda2 Duo), (ain1, ain2, din1, din2, din1+din2 - for estudna2 Max)
    *manual_override - if true, the regulation and scheduler are overridden by manual mode */

    echo "Last telemetry for 'dout1': " . 
        eStudna2_get_latest_telemetry_for_dout($USERNAME, $PASSWORD, $SN, "dout1") . "\n";

    /**
    *Toggle dout1
    *    dout1 off -> on -> estudna2_set_dout(1, True)
    *    dout1 on -> off -> estudna2_set_dout(1, False)
     *   */    
    echo "Toggling dout1...\n";
    eStudna2_toggle_dout($USERNAME, $PASSWORD, $SN, 1);
    echo "dout1 toggled successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
