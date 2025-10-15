import requests


"""
Change these values to your own credentials and device serial number
"""
USERNAME = "YOUR_USERNAME"
PASSWORD = "YOUR_PASSWORD"
SN = "YOUR_SN"

"""
All API endpoints: https://cml5.seapraha.cz/apiv2/docs#/
Thingsboard API
"""

class Thingsboard:
    def __init__(self):
        self.url = "https://cml5.seapraha.cz"
        self.customer_id = ""
        self.device_id = ""
        self.keys = []



    def authenticate(self, username: str, password: str):
        url = f"{self.url}/apiv2/auth/login"
        payload = {"username": username, "password": password}
        headers = {"Content-Type": "application/json"}

        
        resp = requests.post(url, json=payload, headers=headers)
        if resp.status_code == 200:
            data = resp.json()
            self.token = data.get("token", "")
                #print(f"Authentication successful")
                #self.customer_id = data.get("customerId", "")
                #return resp.status_code
        else:
            raise ValueError(f"Authentication failed - Status code: {resp.status_code}")
        


    def get_customer_id(self, username: str):
        url = f"{self.url}/apiv2/user?textSearch={username}"
        headers = {"X-Authorization": f"Bearer {self.token}"}

        
        resp = requests.get(url, headers=headers)
        if resp.status_code == 200:
            data = resp.json()
            self.customer_id = data.get("id", "")
            #print(f"Customer ID retrieved successfully")
        else:
            raise ValueError(f"Failed to retrieve Customer ID - Status code: {resp.status_code}")
        


    def get_device(self, sn:str):
        url = f"{self.url}/apiv2/user/{self.customer_id}/devices"
        headers = {"X-Authorization": f"Bearer {self.token}"}

        
        resp = requests.get(url, headers=headers)
        if resp.status_code == 200:
            data = resp.json()
            for device in data:
                if device.get("name", "") == sn:
                    self.device_id = device.get("id", "")
                    break
            else:
                raise ValueError(f"No devices found with the given serial number {SN}")
        else:
            raise ValueError(f"Failed to retrieve device - Status code: {resp.status_code}")
        


    def get_keys(self) -> list:
        url = f"{self.url}/apiv2/device/{self.device_id}/keys"
        headers = {"X-Authorization": f"Bearer {self.token}"}

        resp = requests.get(url, headers=headers)
        if resp.status_code == 200:
            data = resp.json()
            self.keys = data.get("keys", [])
            return self.keys
                #print(f"Device keys retrieved successfully: {self.keys}")
        else:
            raise ValueError(f"Failed to retrieve device keys - Status code: {resp.status_code}")
        


    def get_telemetry(self, key:str):
        url = f"{self.url}/apiv2/device/{self.device_id}/latest?keys={key}"
        headers = {"X-Authorization": f"Bearer {self.token}"}

        
        resp = requests.get(url, headers=headers)
        if resp.status_code == 200:
            data = resp.json()
            key_telemetry = data.get(key, None)
            key_telemetry = key_telemetry[0]['value'] if key_telemetry else None
            return key_telemetry

        else:
            raise ValueError(f"Failed to retrieve telemetry data - Status code: {resp.status_code}")
        

    def post_rpc(self, method: str, params: dict) -> bool:
        url = f"{self.url}/apiv2/device/{self.device_id}/rpc/twoway"
        headers = {
            "X-Authorization": f"Bearer {self.token}",
            "Content-Type": "application/json"
        }
        payload = {
            "method": method,
            "params": params
        }

        
        resp = requests.post(url, json=payload, headers=headers)
        if resp.status_code == 200:
            return True
        else:
            raise ValueError(f"Failed to send RPC command - Status code: {resp.status_code}")
        

    def set_dout(self, dout_number: int, value: bool = False) -> str:
        """
        method: setdout1, setdout2
        params: True/False
        True = ON
        False = OFF
        """
        self.post_rpc(f"setdout{dout_number}",value )
        

def eStudna2_get_latest_telemetry_for_dout(key:str) -> str:
    tb = Thingsboard()
    tb.authenticate(USERNAME, PASSWORD)
    tb.get_customer_id(USERNAME)
    tb.get_device(SN)
    tb.get_keys()
    return tb.get_telemetry(key)

def eStudna2_get_telemetry_for_all_keys():
    tb = Thingsboard()
    tb.authenticate(USERNAME, PASSWORD)
    tb.get_customer_id(USERNAME)
    tb.get_device(SN)
    tb.get_keys()
    for key in tb.keys:
        print(f"Telemetry data for {key}: {tb.get_telemetry(key)}")

def eStudna2_set_dout(dout_number: int, value: str = "off") -> bool:
    tb = Thingsboard()
    tb.authenticate(USERNAME, PASSWORD)
    tb.get_customer_id(USERNAME)
    tb.get_device(SN)
    tb.set_dout(dout_number, value)


def eStudna2_toggle_dout(dout_number: int) -> bool:
    tb = Thingsboard()
    tb.authenticate(USERNAME, PASSWORD)
    tb.get_customer_id(USERNAME)
    tb.get_device(SN)
    dout_v_value = tb.get_telemetry(f"dout{dout_number}_v")
    new_value= True if dout_v_value == "0" else False
    tb.set_dout(dout_number, new_value)
    print(f"Toggled dout{dout_number} to" ,"ON" if new_value else "OFF")


#get dout1 raw value
"""
dout1_v - raw value of the digital output 1 as aan integer (0/1)
int
dout1_v = 1 -> ON
dout1_v = 0 -> OFF
"""
print(f"Last telemetry for 'dout1_v': {eStudna2_get_latest_telemetry_for_dout('dout1_v')}")

#get dout1 full info
"""
dout1 - full info of the digital output 1 as a JSON string
Example:
{"mode":"manual","str":0,"alternating":false,"regulation_source":"ain1","manual_override":false}

mode - regulation mode (manual/scheduler/upper/lower)
str - value as a string (0/1) for OFF/ON
alternating - if true, the output alternates between ON and OFF states - used for dosing pumps
regulation_source - source of regulation (ain1 - for estudna2 and estunda2 Duo), (ain1, ain2, din1, din2, din1+din2 - for estudna2 Max)
manual_override - if true, the regulation and scheduler are overridden by manual mode
"""
print(f"Last telemetry for 'dout1': {eStudna2_get_latest_telemetry_for_dout('dout1')}")

"""
Toggle dout1
dout1 off -> on -> estudna2_set_dout(1, True)
dout1 on -> off -> estudna2_set_dout(1, False)
"""
eStudna2_toggle_dout(1)
