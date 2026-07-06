<?php
$url = 'https://www.awm-muenchen.de/abfall-entsorgen/muelltonnen/abfuhrkalender?tx_awmabfuhrkalender_abfuhrkalender%5Bhausnummer%5D=2&tx_awmabfuhrkalender_abfuhrkalender%5Bleerungszyklus%5D%5BB%5D=1%2F2%3BU&tx_awmabfuhrkalender_abfuhrkalender%5Bleerungszyklus%5D%5BP%5D=1%2F2%3BG&tx_awmabfuhrkalender_abfuhrkalender%5Bleerungszyklus%5D%5BR%5D=001%3BG&tx_awmabfuhrkalender_abfuhrkalender%5Bsection%5D=ics&tx_awmabfuhrkalender_abfuhrkalender%5Bsinglestandplatz%5D=false&tx_awmabfuhrkalender_abfuhrkalender%5Bstandplatzwahl%5D=true&tx_awmabfuhrkalender_abfuhrkalender%5Bstellplatz%5D%5Bbio%5D=70117782&tx_awmabfuhrkalender_abfuhrkalender%5Bstellplatz%5D%5Bpapier%5D=70117782&tx_awmabfuhrkalender_abfuhrkalender%5Bstellplatz%5D%5Brestmuell%5D=70117782&tx_awmabfuhrkalender_abfuhrkalender%5Bstrasse%5D=Hans-St%C3%BCtzle-Str.&tx_awmabfuhrkalender_abfuhrkalender%5Byear%5D=2026&cHash=34d67b2134a776bc8cdd38c4580d4529';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$data = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);
echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Length: " . strlen((string)$data) . "\n";
