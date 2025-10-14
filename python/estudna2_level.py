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
        

def eStudna2_get_device_keys() -> list:
    tb = Thingsboard()
    tb.authenticate(USERNAME, PASSWORD)
    tb.get_customer_id(USERNAME)
    tb.get_device(SN)
    tb.get_keys()
    return tb.keys

def eStudna2_get_latest_telemetry_for_key(key:str) -> str:
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

    

print(f"Available Device keys: {eStudna2_get_device_keys()}")
print(f"Last telemetry for all keys:")
eStudna2_get_telemetry_for_all_keys()

print(f"Last telemetry for key 'ain1_v': {eStudna2_get_latest_telemetry_for_key("ain1_v")}")
