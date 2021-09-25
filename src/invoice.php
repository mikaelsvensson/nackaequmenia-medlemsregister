<?php
require_once __DIR__ . '/util/db.php';
require_once __DIR__ . '/util/invoices.php';
require_once __DIR__ . '/util/email.php';
require_once __DIR__ . '/util/config.php';

$dbh = db_connect();

$matches = array_filter(db_get_people($dbh), function ($p) {
    return $p->person_id == $_GET['id'];
});

$invoice = invoices_get($dbh, $_GET['id']);

$reference_person = db_get_person($dbh, $invoice->reference_person_id);
$guardian_1 = isset($reference_person->guardian_1_person_id) ? db_get_person($dbh, $reference_person->guardian_1_person_id) : null;
$guardian_2 = isset($reference_person->guardian_2_person_id) ? db_get_person($dbh, $reference_person->guardian_2_person_id) : null;

switch (@$_POST['action']) {
    case 'invoices_send':
        if ($invoice->is_ready) {

            $rendered_log_action = current(array_filter($invoice->log, function ($log) {
                return $log->action === INVOICE_ACTION_RENDERED;
            }));

            $data = json_decode($rendered_log_action->action_data, true);

            $path = __DIR__ . '/' . $data['pdf_path'];

            try {
                $recipients = @$_POST['email_recipient'];

                $attachment_name = invoices_file_pattern($dbh, $invoice, $config['invoice']['email_attachment_name']);

                $attachments = [
                    $attachment_name => $path
                ];

                email_send(
                    $recipients,
                    invoices_file_pattern($dbh, $invoice, $config['invoice']['email_subject']),
                    utf8_decode(invoices_render_html($dbh, $invoice, 'invoice-mail.html.php')),
                    $attachments);

                invoices_log_action($dbh, $invoice->invoice_id, INVOICE_ACTION_SENT, [
                    'recipients' => $recipients
                ]);
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }
        $invoice = invoices_get($dbh, $invoice->invoice_id);
        break;
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

$public_html_url = $invoice->is_ready ? invoices_file_pattern($dbh, $invoice, $config['invoice']['public_html_url']) : '';
$public_pdf_url = $invoice->is_ready ? invoices_file_pattern($dbh, $invoice, $config['invoice']['public_pdf_url']) : '';

$dbh = null;

include './invoice.html.php';