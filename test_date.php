<?php
$todayTs = strtotime('2026-07-06 00:00:00');
$mondayTs = strtotime('monday this week', $todayTs);
echo date('Y-m-d', $todayTs) . " -> " . date('Y-m-d', $mondayTs) . "\n";
