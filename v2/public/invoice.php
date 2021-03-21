<?php
require_once __DIR__ . '/../util/db.php';
require_once __DIR__ . '/../util/invoices.php';

$dbh = db_connect();

$invoice = invoices_get($dbh, $_GET['id']);

if ($invoice === false) {
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
    return;
}

$ready_log_action = current(array_filter($invoice->log, function ($log) {
    return $log->action === INVOICE_ACTION_RENDERED && strlen($log->action_data) > 2;
}));

if ($ready_log_action) {
    $data = json_decode($ready_log_action->action_data, true);
    if ($_GET['format'] == 'pdf') {
        $path = __DIR__ . '/../' . $data['pdf_path'];
        $content_type = 'application/pdf';
    } elseif ($_GET['format'] == 'html') {
        $path = __DIR__ . '/../' . $data['html_path'];
        $content_type = 'text/html';
    } else {
        header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
        return;
    }
    if (file_exists($path) && is_file($path)) {
        header('Content-Type: ' . $content_type);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename=' . $invoice->external_invoice_id . '.' . $_GET['format']);
        readfile($path, false, null);
    } else {
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        return;
    }
} else {
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
    return;
}
