<?php
require_once __DIR__ . '/util/db.php';
require_once __DIR__ . '/util/invoices.php';
require_once __DIR__ . '/util/codes.php';

$dbh = db_connect();

$invoice = invoices_get($dbh, $_GET['id']);

echo invoices_render_html($dbh, $invoice);
