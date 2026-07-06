<?php
define('KL_ERROR', 10204);

class IPSModule {
    public function Create() {}
    public function ApplyChanges() {}
    public function RegisterVariableString() {}
    public function RegisterVariableBoolean() {}
    public function EnableAction() {}
    public function ReadPropertyInteger() { return 0; }
    public function RegisterTimer() {}
}
require_once __DIR__ . '/AWMMuenchen/module.php';

class TestAWM extends AWMMuenchen {
    public $debug = [];
    public $values = [];

    public function __construct() {}

    public function ReadPropertyString($name) {
        return "https://fake.url";
    }
    
    protected function parseICS($url) {
        $data = file_get_contents('C:\Users\grass\.gemini\antigravity\brain\8e7ecb4b-d351-47b2-b8d1-95e5ae383fb9\.system_generated\steps\1742\content.md');
        // Extract just the ICS part
        $start = strpos($data, "BEGIN:VCALENDAR");
        $data = substr($data, $start);
        
        $lines = explode("\n", str_replace("\r", "", $data));
        $events = [];
        $currentEvent = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if ($line == 'BEGIN:VEVENT') {
                $currentEvent = [];
            } elseif ($line == 'END:VEVENT') {
                if ($currentEvent) $events[] = $currentEvent;
                $currentEvent = null;
            } elseif ($currentEvent !== null) {
                if (strpos($line, 'SUMMARY:') === 0) {
                    $currentEvent['summary'] = substr($line, 8);
                } elseif (strpos($line, 'DTSTART') === 0) {
                    $parts = explode(':', $line);
                    if (count($parts) >= 2) {
                        $dateStr = trim($parts[count($parts) - 1]);
                        if (strlen($dateStr) == 8) {
                            $currentEvent['dtstart'] = strtotime($dateStr . ' 00:00:00');
                        } else {
                            $currentEvent['dtstart'] = strtotime($dateStr);
                        }
                    }
                } elseif (strpos($line, 'RRULE:') === 0) {
                    $parts = explode(';', substr($line, 6));
                    foreach ($parts as $p) {
                        $kv = explode('=', $p);
                        if (count($kv) == 2) {
                            $k = $kv[0];
                            $v = $kv[1];
                            if ($k == 'UNTIL') {
                                $v = strtotime(substr($v, 0, 8) . ' 00:00:00');
                            }
                            $currentEvent['rrule'][$k] = $v;
                        }
                    }
                } elseif (strpos($line, 'EXDATE') === 0) {
                    $parts = explode(':', $line);
                    if (count($parts) >= 2) {
                        $dateStr = trim($parts[count($parts) - 1]);
                        if (strlen($dateStr) >= 8) {
                            $currentEvent['exdates'][] = strtotime(substr($dateStr, 0, 8) . ' 00:00:00');
                        }
                    }
                }
            }
        }
        return $events;
    }
    
    public function LogMessage($msg, $level) {
        $this->debug[] = "LOG: $msg";
    }

    public function SendDebug($name, $msg, $format) {
        $this->debug[] = "DEBUG: $name: $msg";
    }

    public function SetValue($ident, $val) {
        $this->values[$ident] = $val;
    }
}

$module = new TestAWM();
$module->UpdateCalendar();

print_r($module->values);
