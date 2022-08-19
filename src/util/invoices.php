<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/checksum.php';
require_once __DIR__ . '/codes.php';
require_once __DIR__ . '/config.php';

const INVOICE_ACTION_CREATED = 'created';
const INVOICE_ACTION_READY = 'ready';
const INVOICE_ACTION_RENDERED = 'rendered';
const INVOICE_ACTION_SENT = 'sent';
const INVOICE_ACTION_PAID = 'paid';
const INVOICE_ACTION_INVALIDATED = 'invalidated';

function invoices_to_cents($value)
{
    $value = str_replace(
        [',', ' '],
        ['.', ''],
        $value
    );
    return floor(floatval($value) * 100);
}

function invoices_from_cents($value)
{
    return number_format($value / 100, 2, ',', ' ');
}

function invoices_get_for_person(PDO $dbh, string $person_id)
{
    $stmt = $dbh->prepare('
        SELECT 
            inv.*, 
            EXISTS(SELECT invlog_created.invoice_id FROM invoices_log AS invlog_created WHERE invlog_created.action = :action_created AND invlog_created.invoice_id = inv.invoice_id) is_created, 
            EXISTS(SELECT invlog_ready.invoice_id FROM invoices_log AS invlog_ready WHERE invlog_ready.action = :action_ready AND invlog_ready.invoice_id = inv.invoice_id) is_ready, 
            EXISTS(SELECT invlog_sent.invoice_id FROM invoices_log AS invlog_sent WHERE invlog_sent.action = :action_sent AND invlog_sent.invoice_id = inv.invoice_id) is_sent, 
            EXISTS(SELECT invlog_paid.invoice_id FROM invoices_log AS invlog_paid WHERE invlog_paid.action = :action_paid AND invlog_paid.invoice_id = inv.invoice_id) is_paid, 
            EXISTS(SELECT invlog_invalidated.invoice_id FROM invoices_log AS invlog_invalidated WHERE invlog_invalidated.action = :action_invalidated AND invlog_invalidated.invoice_id = inv.invoice_id) is_invalidated 
        FROM 
            invoices AS inv 
        WHERE 
            inv.reference_person_id = :person_id');
    $stmt->bindValue(':person_id', $person_id);
    $stmt->bindValue(':action_created', INVOICE_ACTION_CREATED);
    $stmt->bindValue(':action_ready', INVOICE_ACTION_READY);
    $stmt->bindValue(':action_sent', INVOICE_ACTION_SENT);
    $stmt->bindValue(':action_paid', INVOICE_ACTION_PAID);
    $stmt->bindValue(':action_invalidated', INVOICE_ACTION_INVALIDATED);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    return $result;
}

function invoices_create_item(PDO $dbh, string $invoice_id, string $text, int $unit_count, int $unit_price)
{
    $stmt = $dbh->prepare('
        INSERT INTO 
            invoices_lines (invoice_id, line_number, text, unit_count, unit_price) 
        VALUES 
        (
            :invoice_id, 
            COALESCE((SELECT MAX(line_number) + 1 FROM invoices_lines WHERE invoice_id = :invoice_id), 1), 
            :text, 
            :unit_count, 
            :unit_price
        )');
    $stmt->bindValue(':invoice_id', $invoice_id);
    $stmt->bindValue(':text', $text);
    $stmt->bindValue(':unit_count', $unit_count);
    $stmt->bindValue(':unit_price', $unit_price);
    if ($stmt->execute() === false) {
        die('Failed to create invoice');
    };
}

function invoices_update_item(PDO $dbh, string $invoice_id, int $line_number, string $text, int $unit_count, int $unit_price)
{
    $stmt = $dbh->prepare('
        UPDATE 
            invoices_lines 
        SET 
            text = :text, 
            unit_count = :unit_count, 
            unit_price = :unit_price
        WHERE
            invoice_id = :invoice_id AND
            line_number = :line_number
        ');
    $stmt->bindValue(':invoice_id', $invoice_id);
    $stmt->bindValue(':line_number', $line_number);
    $stmt->bindValue(':text', $text);
    $stmt->bindValue(':unit_count', $unit_count);
    $stmt->bindValue(':unit_price', $unit_price);
    if ($stmt->execute() === false) {
        die('Failed to create invoice');
    };
}

function invoices_delete_item(PDO $dbh, string $invoice_id, int $line_number)
{
    $stmt = $dbh->prepare('
        DELETE FROM 
            invoices_lines 
        WHERE
            invoice_id = :invoice_id AND
            line_number = :line_number
        ');
    $stmt->bindValue(':invoice_id', $invoice_id);
    $stmt->bindValue(':line_number', $line_number);
    if ($stmt->execute() === false) {
        die('Failed to create invoice');
    };
}

function invoices_get(PDO $dbh, string $invoice_id)
{
    return current(invoices_get_all($dbh, 'inv.invoice_id = :invoice_id', ['invoice_id' => $invoice_id]));
}

function invoices_get_all(PDO $dbh, string $sql_where = '', array $sql_params = [])
{
    $stmt = $dbh->prepare('
        SELECT 
            inv.*, 
            EXISTS(SELECT invlog_created.invoice_id     FROM invoices_log AS invlog_created     WHERE invlog_created.action = :action_created         AND invlog_created.invoice_id = inv.invoice_id    ) is_created, 
            EXISTS(SELECT invlog_ready.invoice_id       FROM invoices_log AS invlog_ready       WHERE invlog_ready.action = :action_ready             AND invlog_ready.invoice_id = inv.invoice_id      ) is_ready, 
            EXISTS(SELECT invlog_sent.invoice_id        FROM invoices_log AS invlog_sent        WHERE invlog_sent.action = :action_sent               AND invlog_sent.invoice_id = inv.invoice_id       ) is_sent, 
            EXISTS(SELECT invlog_paid.invoice_id        FROM invoices_log AS invlog_paid        WHERE invlog_paid.action = :action_paid               AND invlog_paid.invoice_id = inv.invoice_id       ) is_paid, 
            EXISTS(SELECT invlog_invalidated.invoice_id FROM invoices_log AS invlog_invalidated WHERE invlog_invalidated.action = :action_invalidated AND invlog_invalidated.invoice_id = inv.invoice_id) is_invalidated 
        FROM 
            invoices AS inv 
        ' . (!empty($sql_where) ? " WHERE " . $sql_where : ''));
    foreach ($sql_params as $param => $value) {
        $stmt->bindValue(":" . $param, $value);
    }
    $stmt->bindValue(':action_created', INVOICE_ACTION_CREATED);
    $stmt->bindValue(':action_ready', INVOICE_ACTION_READY);
    $stmt->bindValue(':action_sent', INVOICE_ACTION_SENT);
    $stmt->bindValue(':action_paid', INVOICE_ACTION_PAID);
    $stmt->bindValue(':action_invalidated', INVOICE_ACTION_INVALIDATED);
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_OBJ);

    foreach ($invoices as $result) {
        $invoice_id = $result->invoice_id;
        $stmt = $dbh->prepare('
            SELECT 
                created_at,
                action,
                action_data 
            FROM 
                invoices_log 
            WHERE 
                invoice_id = :invoice_id');
        $stmt->bindValue(':invoice_id', $invoice_id);
        $stmt->execute();

        $result->log = $stmt->fetchAll(PDO::FETCH_OBJ);

        $stmt = $dbh->prepare('
            SELECT 
                line_number,
                text,
                unit_count,
                unit_price 
            FROM 
                invoices_lines 
            WHERE 
                invoice_id = :invoice_id');
        $stmt->bindValue(':invoice_id', $invoice_id);
        $stmt->execute();

        $result->items = $stmt->fetchAll(PDO::FETCH_OBJ);
        $result->is_readonly = $result->is_ready ||
            $result->is_sent ||
            $result->is_paid ||
            $result->is_invalidated;

        $result->reference_person = db_get_person($dbh, $result->reference_person_id);
    }

    return $invoices;
}

function invoices_create_for_person(PDO $dbh, string $person_id)
{
    $invoice_id = db_generate_id();

    $stmt = $dbh->prepare('INSERT INTO invoices (invoice_id, reference_person_id, external_invoice_id) VALUES (:invoice_id, :reference_person_id, :external_invoice_id)');
    $stmt->bindValue(':invoice_id', $invoice_id);
    $stmt->bindValue(':reference_person_id', $person_id);
    $stmt->bindValue(':external_invoice_id', invoiced_get_next_external_invoice_id($dbh));
    if ($stmt->execute() === false) {
        die('Failed to create invoice');
    };

    invoices_log_action($dbh, $invoice_id, INVOICE_ACTION_CREATED, []);

    return $invoice_id;
}

/**
 * @param PDO $dbh
 * @param string $invoice_id
 */
function invoices_log_action(PDO $dbh, string $invoice_id, string $action, array $action_data): void
{
    $stmt = $dbh->prepare('INSERT INTO invoices_log (invoice_id, created_at, action, action_data) VALUES (:invoice_id, :created_at, :action, :action_data)');
    $stmt->bindValue(':invoice_id', $invoice_id);
    $stmt->bindValue(':created_at', time());
    $stmt->bindValue(':action', $action);
    $stmt->bindValue(':action_data', json_encode($action_data, JSON_UNESCAPED_SLASHES));
    if ($stmt->execute() === false) {
        die('Failed to create invoice metadata');
    };
}

function invoices_set_ready(PDO $dbh, object $invoice)
{
    invoices_log_action($dbh, $invoice->invoice_id, INVOICE_ACTION_READY, []);

    $invoice = invoices_get($dbh, $invoice->invoice_id);

    $html = invoices_render_html($dbh, $invoice);

    $relative_path_html = invoices_save_as_html($dbh, $invoice, $html);

    $relative_path_pdf = invoices_save_as_pdf($dbh, $invoice, $html);

    invoices_log_action($dbh, $invoice->invoice_id, INVOICE_ACTION_RENDERED, [
        'html_path' => $relative_path_html,
        'pdf_path' => $relative_path_pdf
    ]);
}

function invoices_set_invalidated(PDO $dbh, object $invoice, string $note = null)
{
    $data = !empty($note) 
        ? ['note' => $note]
        : [];
    invoices_log_action($dbh, $invoice->invoice_id, INVOICE_ACTION_INVALIDATED, $data);
}

function invoices_set_paid(PDO $dbh, object $invoice, DateTime $payment_date = null)
{
    $data = isset($payment_date) 
        ? ['payment_date' => $payment_date->getTimestamp()]
        : [];
    invoices_log_action($dbh, $invoice->invoice_id, INVOICE_ACTION_PAID, $data);
}

/**
 * @param string $invoice_id
 * @param string $html
 * @return string
 */
function invoices_save_as_pdf(PDO $dbh, object $invoice, string $html): string
{
    global $config;

    $payload = http_build_query([
        'html' => $html,
        'apiKey' => $config['html2pdf']['key'],
        'media' => 'print',
        'landscape' => 'false',
        'format' => 'A4',
        'marginTop' => 65,
        'marginRight' => 65,
        'marginBottom' => 65,
        'marginLeft' => 65
    ]);

    $opts = array('http' =>
        array(
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $payload
        )
    );

    $context = stream_context_create($opts);

    $result = file_get_contents($config['html2pdf']['endpoint'], false, $context);

    return invoices_save_file($dbh, $invoice, $result, $config['invoice']['rendered_pdf_file_path']);
}

/**
 * @param string $invoice_id
 * @param string $html
 * @return string
 */
function invoices_save_as_html(PDO $dbh, object $invoice, string $html): string
{
    global $config;
    return invoices_save_file($dbh, $invoice, $html, $config['invoice']['rendered_html_file_path']);
}

/**
 * @param string $invoice_id
 * @param $data
 * @param string $relative_path_pattern
 * @return string
 */
function invoices_save_file(PDO $dbh, object $invoice, $data, string $relative_path_pattern): string
{
    $relative_path = invoices_file_pattern($dbh, $invoice, $relative_path_pattern);
    $absolute_path = __DIR__ . '/../' . $relative_path;
    if (!is_dir(dirname($absolute_path)) && mkdir(dirname($absolute_path), 0755, true) === false) {
        die('Could not create directory');
    }
    file_put_contents($absolute_path, $data);
    return $relative_path;
}


function invoiced_get_next_external_invoice_id(PDO $dbh)
{
    $prefix = date('ymd');
    $stmt = $dbh->prepare('
        SELECT 
            SUBSTR(inv.external_invoice_id, 7, 2) AS num 
        FROM 
            invoices AS inv 
        WHERE 
            SUBSTR(inv.external_invoice_id, 1, 6) = :prefix
        ORDER BY
            num DESC');
    $stmt->bindValue(':prefix', $prefix);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    if ($result === false) {
        return checksum_generate(intval($prefix . '01'));
    } else {
        $max = intval($result->num);
        return checksum_generate(intval($prefix . sprintf('%02d', $max + 1)));
    }
}

function invoices_render_html(PDO $dbh, object $invoice, string $template = 'invoice-view.html.php')
{
    $reference_person = db_get_person($dbh, $invoice->reference_person_id);

    $config = parse_ini_file('./config.ini', true, INI_SCANNER_TYPED);

    $sum = array_sum(array_map(function ($item) {
        return $item->unit_count * $item->unit_price;
    }, $invoice->items));

    $ready_log_action = current(array_filter($invoice->log, function ($log) {
        return $log->action === INVOICE_ACTION_READY;
    }));

    if ($ready_log_action) {
        $ready_date_obj = new DateTime("@" . $ready_log_action->created_at);

        $ready_date = $ready_date_obj->getTimestamp();
        $due_date = $invoice->is_ready ? date_modify($ready_date_obj, sprintf("+%s days", $config['invoice']['payment_days']))->getTimestamp() : 0;

        $bankgiro_qr_code_url = code_bankgiro_qr_code_url(
            $sum,
            $invoice->external_invoice_id,
            date('Y-m-d', $due_date));

        $swish_qr_code_url = code_swish_qr_code_url(
            $sum,
            $invoice->external_invoice_id);

        $public_html_url = $invoice->is_ready ? invoices_file_pattern($dbh, $invoice, $config['invoice']['public_html_url']) : '';
        $public_pdf_url = $invoice->is_ready ? invoices_file_pattern($dbh, $invoice, $config['invoice']['public_pdf_url']) : '';
    } else {
        $ready_date = null;
        $due_date = null;

        $bankgiro_qr_code_url = '';
        $swish_qr_code_url = '';
        $public_html_url = '';
        $public_pdf_url = '';
    }

    $dbh = null;

    ob_start();
    include __DIR__ . '/../templates/' . $template;
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
}

function invoices_file_pattern(PDO $dbh, object $invoice, string $pattern)
{
    $ready_log_action = current(array_filter($invoice->log, function ($log) {
        return $log->action === INVOICE_ACTION_READY;
    }));

    $reference_person = db_get_person($dbh, $invoice->reference_person_id);

    return strtr($pattern, [
        '$year' => date('Y', $ready_log_action ? $ready_log_action->created_at : time()),
        '$invoice_id' => $invoice->invoice_id,
        '$external_invoice_id' => $invoice->external_invoice_id,
        '$name' => preg_replace('/[^a-zåäöA-ZÅÄÖ -]+/', ' ', join(' ', [$reference_person->first_name, $reference_person->sur_name]))
    ]);
}