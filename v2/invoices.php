<?php
require_once __DIR__.'/util/invoices.php';

$dbh = db_connect();

//try {
//} catch (PDOException $e) {
//    print "Error!: " . $e->getMessage() . "<br/>";
//    die();
//}

$invoices = invoices_get_all($dbh);

$dbh = null;

$invoice_groups = [
    'is_invalidated' => [],
    'is_paid' => [],
    'is_sent' => [],
    'is_ready' => [],
    'is_created' => []
];

foreach ($invoices as $invoice) {
    foreach (array_keys($invoice_groups) as $prop) {
        if ($invoice->{$prop}) {
            $invoice_groups[$prop][] = $invoice;
            break;
        }
    }
}

include './invoices.html.php';