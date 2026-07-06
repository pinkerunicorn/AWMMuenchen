<?php
define('KL_ERROR', 10204);

class IPSModule {
    public function Create() {}
    public function ApplyChanges() {}
}
require_once __DIR__ . '/AWMMuenchen/module.php';

// Stub out IPSModule methods so we can test the logic
class TestAWM extends AWMMuenchen {
    public $debug = [];
    public $values = [];

    public function __construct() {}

    public function ReadPropertyString($name) {
        return "https://www.awm-muenchen.de/abfall-entsorgen/muelltonnen/abfuhrkalender?tx_awmabfuhrkalender_abfuhrkalender%5Bhausnummer%5D=2&tx_awmabfuhrkalender_abfuhrkalender%5Bleerungszyklus%5D%5BB%5D=1%2F2%3BU&tx_awmabfuhrkalender_abfuhrkalender%5Bleerungszyklus%5D%5BP%5D=1%2F2%3BG&tx_awmabfuhrkalender_abfuhrkalender%5Bleerungszyklus%5D%5BR%5D=001%3BG&tx_awmabfuhrkalender_abfuhrkalender%5Bsection%5D=ics&tx_awmabfuhrkalender_abfuhrkalender%5Bsinglestandplatz%5D=false&tx_awmabfuhrkalender_abfuhrkalender%5Bstandplatzwahl%5D=true&tx_awmabfuhrkalender_abfuhrkalender%5Bstellplatz%5D%5Bbio%5D=70117782&tx_awmabfuhrkalender_abfuhrkalender%5Bstellplatz%5D%5Bpapier%5D=70117782&tx_awmabfuhrkalender_abfuhrkalender%5Bstellplatz%5D%5Brestmuell%5D=70117782&tx_awmabfuhrkalender_abfuhrkalender%5Bstrasse%5D=Hans-St%C3%BCtzle-Str.&tx_awmabfuhrkalender_abfuhrkalender%5Byear%5D=2026&cHash=34d67b2134a776bc8cdd38c4580d4529";
    }
    
    public function parseICS($url) {
        $res = parent::parseICS($url);
        if (empty($res)) print_r(error_get_last());
        return $res;
    }
    
    public function SendDebug($name, $msg, $format) {
        $this->debug[] = "DEBUG [$name]: $msg";
    }

    public function LogMessage($msg, $level) {
        $this->debug[] = "LOG: $msg";
    }

    public function SetValue($ident, $val) {
        $this->values[$ident] = $val;
    }
}

$module = new TestAWM();
$module->UpdateCalendar();

print_r($module->values);
print_r($module->debug);
