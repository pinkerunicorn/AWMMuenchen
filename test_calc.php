<?php
$targetTs = strtotime('2026-07-09 00:00:00');
$dtstart = strtotime('20260108 00:00:00');
$diffDays = round(($targetTs - $dtstart) / 86400);
$diffWeeks = floor($diffDays / 7);
echo "Papier (Interval 2): diffDays: $diffDays, diffWeeks: $diffWeeks, mod: " . ($diffWeeks % 2) . "\n";

$targetTs = strtotime('2026-07-07 00:00:00');
$dtstart = strtotime('20260106 00:00:00');
$diffDays = round(($targetTs - $dtstart) / 86400);
$diffWeeks = floor($diffDays / 7);
echo "Restmuell (Interval 1): diffDays: $diffDays, diffWeeks: $diffWeeks, mod: " . ($diffWeeks % 1) . "\n";
