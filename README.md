# Příklad na zjištění aktuální hodnoty hladiny vaší eStudny2 z CML 

Pro Python:
- stáhněte si estudna2_level.py
- upravte na začátku souboru  vaše přihlašovací údaje a SN vaší eStudny.
- spusťte

```
c:\> python estudna2_level.py
Available Device keys: ['system', 'dout1', 'wifi', 'ain1_v', 'ain1', 'version', 'dout1_v', 'log']
Last telemetry for all keys:
Telemetry data for system: {"v5v":4.1,"uptime_sec":5437}
Telemetry data for dout1: {"mode":"manual","str":1,"alternating":false,"regulation_source":"ain1","manual_override":false}
Telemetry data for wifi: {"state":"connected","signal_percent":33,"ssid":"seapraha"}
Telemetry data for ain1_v: 1.584375
Telemetry data for ain1: {"zone":"critical","units":"m","str":"1.58"}
Telemetry data for version: 2
Telemetry data for dout1_v: 1
Telemetry data for log: {"action":"dout1 false->true","type":"event"}
Last telemetry for key 'ain1_v': 1.584375
```
Pro PHP:
- stáhněte si estudna2_level.php
- upravte na začátku souboru  vaše přihlašovací údaje a SN vaší eStudny.
- spusťte

```
Available Device keys: system, dout1, wifi, ain1_v, ain1, version, dout1_v, log

Last telemetry for all keys:

Telemetry data for system: {"v5v":4.1,"uptime_sec":513}

Telemetry data for dout1: {"mode":"manual","str":1,"alternating":false,"regulation_source":"ain1","manual_override":false}

Telemetry data for wifi: {"state":"connected","signal_percent":33,"ssid":"seapraha"}

Telemetry data for ain1_v: 1.5875

Telemetry data for ain1: {"zone":"critical","units":"m","str":"1.59"}

Telemetry data for version: 2

Telemetry data for dout1_v: 1

Telemetry data for log: {"action":"dout1 false->true","type":"event"}

Last telemetry for key 'ain1_v': 1.5875
```
# Příklad na poslání RPC vaší eStudny2 přes CML

Pro Python:
- stáhněte si estudna2_control_dout.py
- upravte na začátku souboru vaše přihlašovací údaje a SN vaší eStudny.
- spusťte
- 
```
c:\> python estudna2_control_dout.py
Last telemetry for 'dout1_v': 0
Last telemetry for 'dout1': {"mode":"manual","str":0,"alternating":false,"regulation_source":"ain1","manual_override":false}
Toggled dout1 to ON
```
Pro PHP:
- stáhněte si estudna2_control_dout.php
- upravte na začátku souboru vaše přihlašovací údaje a SN vaší eStudny.
- spusťte

```
Last telemetry for 'dout1_v': 1

Last telemetry for 'dout1': {"mode":"manual","str":1,"alternating":false,"regulation_source":"ain1","manual_override":false}

Toggling dout1...

dout1 toggled successfully.
```

