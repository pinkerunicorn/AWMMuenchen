<?php

declare(strict_types=1);

class AWMMuenchen extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Properties
        $this->RegisterPropertyString('CalendarUrl', '');
        $this->RegisterPropertyInteger('UpdateInterval', 4);

        // Timer
        $this->RegisterTimer('UpdateTimer', 0, 'AWM_UpdateCalendar($_IPS[\'TARGET\']);');

        // Heutige Abholungen
        $this->RegisterVariableBoolean('RestmuellHeute', 'Restmülltonne (Heute)', '~Switch', 10);
        $this->RegisterVariableBoolean('PapierHeute', 'Papiertonne (Heute)', '~Switch', 20);
        $this->RegisterVariableBoolean('BioHeute', 'Biotonne (Heute)', '~Switch', 30);

        // Wochenübersicht
        $this->RegisterVariableString('Montag', 'Montag', '', 100);
        $this->RegisterVariableString('Dienstag', 'Dienstag', '', 110);
        $this->RegisterVariableString('Mittwoch', 'Mittwoch', '', 120);
        $this->RegisterVariableString('Donnerstag', 'Donnerstag', '', 130);
        $this->RegisterVariableString('Freitag', 'Freitag', '', 140);
        $this->RegisterVariableString('Samstag', 'Samstag', '', 150);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $interval = $this->ReadPropertyInteger('UpdateInterval');
        if ($interval > 0) {
            $this->SetTimerInterval('UpdateTimer', $interval * 3600 * 1000);
        } else {
            $this->SetTimerInterval('UpdateTimer', 0);
        }

        // Einmaliges Ausführen bei Übernehmen
        if (!empty($this->ReadPropertyString('CalendarUrl'))) {
            $this->UpdateCalendar();
        }
    }

    public function UpdateCalendar()
    {
        $url = $this->ReadPropertyString('CalendarUrl');
        if (empty($url)) {
            echo "Keine ICS URL konfiguriert.";
            return;
        }

        // Ersetze hardcodiertes Jahr durch aktuelles Jahr
        $currentYear = date('Y');
        $url = preg_replace('/tx_awmabfuhrkalender_abfuhrkalender(%5B|\[)year(%5D|\])=\d{4}/', 'tx_awmabfuhrkalender_abfuhrkalender$1year$2=' . $currentYear, $url);

        $events = $this->parseICS($url);
        if (empty($events)) {
            $msg = "Fehler beim Abrufen des Abfuhrkalenders für das Jahr $currentYear. Möglicherweise ist der generierte Link (cHash) abgelaufen. Bitte generiere auf der AWM Webseite einen neuen Link für das aktuelle Jahr und trage ihn in die Instanz ein.";
            $this->LogMessage($msg, KL_ERROR);
            echo $msg;
            return;
        }

        // Heute 00:00:00 Uhr
        $todayTs = strtotime('today');
        $restToday = false;
        $papierToday = false;
        $bioToday = false;

        // Montag dieser Woche finden
        $mondayTs = strtotime('monday this week', $todayTs);
        $weekdays = [
            'Montag' => $mondayTs,
            'Dienstag' => strtotime('+1 day', $mondayTs),
            'Mittwoch' => strtotime('+2 days', $mondayTs),
            'Donnerstag' => strtotime('+3 days', $mondayTs),
            'Freitag' => strtotime('+4 days', $mondayTs),
            'Samstag' => strtotime('+5 days', $mondayTs)
        ];

        $weekSummary = [];
        foreach ($weekdays as $dayName => $ts) {
            $weekSummary[$dayName] = [];
        }

        foreach ($events as $e) {
            $summary = strtolower($e['summary']);
            $type = '';
            
            if (strpos($summary, 'rest') !== false) $type = 'Restmüll';
            if (strpos($summary, 'papier') !== false) $type = 'Papier';
            if (strpos($summary, 'bio') !== false) $type = 'Bio';
            
            if (!$type) continue;

            // Prüfe für Heute
            if ($this->isEventActiveOnDay($e, $todayTs)) {
                if ($type == 'Restmüll') $restToday = true;
                if ($type == 'Papier') $papierToday = true;
                if ($type == 'Bio') $bioToday = true;
            }

            // Prüfe für die ganze Woche
            foreach ($weekdays as $dayName => $ts) {
                if ($this->isEventActiveOnDay($e, $ts)) {
                    if (!in_array($type, $weekSummary[$dayName])) {
                        $weekSummary[$dayName][] = $type;
                    }
                }
            }
        }

        // Heutige Variablen setzen
        $this->SetValue('RestmuellHeute', $restToday);
        $this->SetValue('PapierHeute', $papierToday);
        $this->SetValue('BioHeute', $bioToday);

        // Wochen-Variablen setzen
        foreach ($weekdays as $dayName => $ts) {
            $str = empty($weekSummary[$dayName]) ? '-' : implode(', ', $weekSummary[$dayName]);
            $this->SetValue($dayName, $str);
        }
        
        $this->SendDebug("AWM", "Kalender erfolgreich aktualisiert.", 0);
    }

    protected function parseICS($url)
    {
        // Sys_GetURLContent is IP-Symcon's robust internal method.
        if (function_exists('Sys_GetURLContent')) {
            $data = @Sys_GetURLContent($url);
        } else {
            // Fallback for tests
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            $data = @file_get_contents($url, false, $context);
        }
        if (!$data) return [];

        $lines = explode("\n", str_replace("\r", "", $data));
        $events = [];
        $currentEvent = null;

        foreach ($lines as $line) {
            if (strpos($line, 'BEGIN:VEVENT') === 0) {
                $currentEvent = ['exdates' => [], 'rrule' => [], 'dtend' => 0];
            } elseif (strpos($line, 'END:VEVENT') === 0) {
                if ($currentEvent && isset($currentEvent['dtstart'])) {
                    $events[] = $currentEvent;
                }
                $currentEvent = null;
            } elseif ($currentEvent !== null) {
                if (strpos($line, 'SUMMARY:') === 0) {
                    $currentEvent['summary'] = substr($line, 8);
                } elseif (strpos($line, 'DTSTART') === 0) {
                    if (preg_match('/:(\d{8})/', $line, $m)) {
                        $currentEvent['dtstart'] = strtotime($m[1] . ' 00:00:00');
                    }
                } elseif (strpos($line, 'DTEND') === 0) {
                    if (preg_match('/:(\d{8})/', $line, $m)) {
                        $currentEvent['dtend'] = strtotime($m[1] . ' 00:00:00');
                    }
                } elseif (strpos($line, 'EXDATE') === 0) {
                    if (preg_match('/:(\d{8})/', $line, $m)) {
                        $currentEvent['exdates'][] = strtotime($m[1] . ' 00:00:00');
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
                }
            }
        }
        return $events;
    }

    private function isEventActiveOnDay($event, $targetTs)
    {
        // 1. Ist das Datum eine bekannte Ausnahme (Urlaub, Feiertagsverschiebung)?
        if (isset($event['exdates']) && is_array($event['exdates'])) {
            foreach ($event['exdates'] as $exTs) {
                if ($exTs == $targetTs) {
                    return false;
                }
            }
        }

        // 2. Ohne RRULE (Einzeltermin, oft Feiertags-Ersatz)
        if (empty($event['rrule'])) {
            // AWM setzt oft DTEND auf denselben Tag wie DTSTART. Wir checken einfach auf Gleichheit.
            return ($targetTs == $event['dtstart']);
        }

        // 3. Mit RRULE
        $rrule = $event['rrule'];
        
        // Start und Ende prüfen
        if (isset($rrule['UNTIL']) && $targetTs > $rrule['UNTIL']) return false;
        if ($targetTs < $event['dtstart']) return false;

        // Wochentag prüfen
        $targetDayMap = ['0' => 'SU', '1' => 'MO', '2' => 'TU', '3' => 'WE', '4' => 'TH', '5' => 'FR', '6' => 'SA'];
        $targetWkday = $targetDayMap[date('w', $targetTs)];
        if (isset($rrule['BYDAY']) && strpos($rrule['BYDAY'], $targetWkday) === false) return false;

        // Intervall prüfen (z.B. alle 2 Wochen)
        $interval = isset($rrule['INTERVAL']) ? (int)$rrule['INTERVAL'] : 1;
        $diffDays = round(($targetTs - $event['dtstart']) / 86400);
        
        // Vergangene volle Wochen seit dem Startdatum berechnen
        $diffWeeks = floor($diffDays / 7);

        if ($diffWeeks % $interval == 0) {
            return true;
        }

        return false;
    }
}
