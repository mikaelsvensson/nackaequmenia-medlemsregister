<?php
require_once __DIR__ . '/util/db.php';
require_once __DIR__ . '/util/invoices.php';

$dbh = db_connect();

$matches = array_filter(db_get_people($dbh), function ($p) {
    return $p->person_id == $_GET['id'];
});

$invoice = invoices_get($dbh, $_GET['id']);

$reference_person = db_get_person($dbh, $invoice->reference_person_id);

switch (@$_POST['action']) {
    case 'invoices_set_ready':
        invoices_set_ready($dbh, $invoice);
        $invoice = invoices_get($dbh, $invoice->invoice_id);
        break;
    case 'invoices_save':
        foreach ($_POST as $key => $value) {
            @list ($first, $line_number, $prop) = explode('__', $key);
            if ($first === 'item' && $prop === 'text') {
                if ($line_number === 'new') {
                    if (!empty($value)) {
                        invoices_create_item(
                            $dbh,
                            $invoice->invoice_id,
                            $value,
                            intval($_POST['item__' . $line_number . '__unit_count']),
                            invoices_to_cents($_POST['item__' . $line_number . '__unit_price'])
                        );
                    }
                } else {
                    if (empty($value)) {
                        invoices_delete_item(
                            $dbh,
                            $invoice->invoice_id,
                            intval($line_number));
                    } else {
                        invoices_update_item(
                            $dbh,
                            $invoice->invoice_id,
                            intval($line_number),
                            $value,
                            intval($_POST['item__' . $line_number . '__unit_count']),
                            invoices_to_cents($_POST['item__' . $line_number . '__unit_price'])
                        );
                    }
                }
            }
        }
        $invoice = invoices_get($dbh, $invoice->invoice_id);
        break;
}

$public_html_url = $invoice->is_ready ? invoices_file_pattern($invoice, $config['invoice']['public_html_url']) : '';
$public_pdf_url = $invoice->is_ready ? invoices_file_pattern($invoice, $config['invoice']['public_pdf_url']) : '';

$dbh = null;

include './invoice.html.php';