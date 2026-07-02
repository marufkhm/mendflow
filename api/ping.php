<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
echo json_encode(['ok' => true, 'service' => 'mendflow']);
