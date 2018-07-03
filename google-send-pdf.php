<?php
const TEMPLATES_FOLDER = 'templates';
const PROPERTY_SUBJECT = '_subject';
const PROPERTY_TO = '_to';
const PROPERTY_REF = '_ref';
const FORM_FIELD_SENDPDF_DATA = "sendpdf_data";
const FORM_PARAM_SENDPDF_TEMPLATE = "sendpdf_template";
const FORM_PARAM_SENDPDF_AUTOIMPORT = "sendpdf_autoimport";
const ARCHIVE_FOLDER = 'archive';
require_once 'core-google-data.php';

$config = parse_ini_file('config.ini', true);

/**
 * Checksum (Luhn) class shamelessly copied from https://github.com/xi-project/xi-algorithm
 */
class Luhn
{
    /**
     * Returns the given number with luhn algorithm applied.
     *
     * For example 456 becomes 4564.
     *
     * @param  integer $number
     * @return integer
     */
    public function generate($number)
    {
        $stack = 0;
        $digits = str_split(strrev($number), 1);
        foreach ($digits as $key => $value) {
            if ($key % 2 === 0) {
                $value = array_sum(str_split($value * 2, 1));
            }
            $stack += $value;
        }
        $stack %= 10;
        if ($stack !== 0) {
            $stack -= 10;
        }
        return (int)(implode('', array_reverse($digits)) . abs($stack));
    }

    /**
     * Validates the given number.
     *
     * @param  integer $number
     * @return boolean
     */
    public function validate($number)
    {
        $original = substr($number, 0, strlen($number) - 1);
        return $this->generate($original) === $number;
    }
}

function createMail($pathAttachment, $recps, $subj, $pathMessage, $config)
{
    $mail = new PHPMailer;

    //$mail->SMTPDebug=3;
    $mail->isSMTP();                                        // Set mailer to use SMTP
    $mail->Host = $config['mail']['smtp_host'];                        // Specify main and backup SMTP servers
    $mail->SMTPAuth = $config['mail']['smtp_auth'];                                // Enable SMTP authentication
    $mail->Username = $config['mail']['smtp_username'];             // SMTP username
    $mail->Password = $config['mail']['smtp_password'];            // SMTP password
    //$mail->SMTPSecure = 'tls';                              // Enable TLS encryption, `ssl` also accepted
    $mail->Port = intval($config['mail']['smtp_port']);                                       // TCP port to connect to

    $mail->From = $config['mail']['smtp_from'];
    $mail->FromName = 'Mikael, scoutledare';
    foreach ($recps as $to) {
        $mail->addAddress($to);       // Add a recipient
    }
    //$mail->addAddress('ellen@example.com');                   // Name is optional
    //$mail->addReplyTo('info@example.com', 'Information');
    //$mail->addCC('cc@example.com');
    $mail->addBCC($config['mail']['smtp_from']);

    //$mail->WordWrap = 50;                                 // Set word wrap to 50 characters
    $mail->addAttachment($pathAttachment);         // Add attachments
    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
    $mail->isHTML(true);                                  // Set email format to HTML

    $mail->Subject = '=?utf-8?B?' . base64_encode($subj) . '?=';
    $mail->CharSet = 'UTF-8';
    $mail->Body = file_get_contents($pathMessage);
    return $mail;
    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
}

function createPDF($twig, $pathPdfTemplate, $templateData, $pathPdfFile)
{
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetFont('frederickathegreat', '', 10, '', false);
    $pdf->SetFont('cagliostro', '', 10, '', false);

//$pdf->SetCreator(PDF_CREATOR);
//$pdf->SetAuthor('Nicola Asuni');
//$pdf->SetTitle('TCPDF Example 006');
//$pdf->SetSubject('TCPDF Tutorial');
//$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

//                $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 006', PDF_HEADER_STRING);
//                $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
//                $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(20, 10, 20);
    $pdf->SetAutoPageBreak(TRUE, 10);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
//    if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
//        require_once(dirname(__FILE__) . '/lang/eng.php');
//        $pdf->setLanguageArray($l);
//    }
    $pdf->AddPage();
    $html = $twig->render($pathPdfTemplate, $templateData);
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->lastPage();
    $pdf->Output($pathPdfFile, 'F');
}

function createHTML($twig, $pathMessageTemplate, $templateData, $pathMessage)
{
    file_put_contents($pathMessage, $twig->render($pathMessageTemplate, $templateData));
}

use Symfony\Component\Yaml\Yaml;

function getTwig()
{
    $loader = new Twig_Loader_Filesystem(TEMPLATES_FOLDER);
    $twig = new Twig_Environment($loader, array(//	'cache' => '/path/to/compilation_cache',
    ));

    $swishQrFunction = new Twig_SimpleFunction('swish_qr_code_url', function ($amount, $message) {
        $http_request_payload = json_encode(array(
            'format' => 'png',
            'payee' => array(
                'editable' => false,
                'value' => '1233494234'
            ),
            'amount' => array(
                'editable' => false,
                'value' => $amount
            ),
            'message' => array(
                'editable' => false,
                'value' => $message
            ),
            'size' => 300,
            'border' => 0,
            'transparent' => false
        ));

        $hash = md5($http_request_payload);

        $temp_file_path = "../medlemsregister-temp/$hash.png";
        if (!file_exists($temp_file_path)) {
            $http_config = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => $http_request_payload
                )
            );

            $context = stream_context_create($http_config);

            $image_data = file_get_contents('https://mpc.getswish.net/qrg-swish/api/v1/prefilled', false, $context);
            $write_result = file_put_contents($temp_file_path, $image_data);
            chmod($temp_file_path, 0644);
            if ($write_result === false) {
                return 'error';
            }
        }
        return 'http://www.' . $_SERVER["SERVER_NAME"] . '/' . substr(realpath($temp_file_path), strlen($_SERVER["DOCUMENT_ROOT"]));
    });
    $twig->addFunction($swishQrFunction);

    $bankgiroQrFunction = new Twig_SimpleFunction('bankgiro_qr_code_url', function ($amount, $message, $dueDate) {

        $data_parameter = json_encode(array(
            'uqr' => 1,
            'tp' => 1,
            'pt' => 'BG',
            'acc' => '736-4318',
            'nme' => 'Nacka Equmenia/SMU',
            'cid' => '802505-4753',
            'iref' => strval($message),
            'ddt' => date('Ymd', strtotime($dueDate)),
            'due' => $amount
        ), JSON_UNESCAPED_SLASHES);

        $url = 'http://api.qrserver.com/v1/create-qr-code/?format=png&size=300x300&data=' . urlencode($data_parameter);

        $hash = md5($url);

        $temp_file_path = "../medlemsregister-temp/$hash.png";
        if (!file_exists($temp_file_path)) {
            $http_config = array(
                'http' => array(
                    'method' => 'GET'
                )
            );

            $context = stream_context_create($http_config);

            $image_data = file_get_contents($url, false, $context);
            $write_result = file_put_contents($temp_file_path, $image_data);
            chmod($temp_file_path, 0644);
            if ($write_result === false) {
                return 'error';
            }
        }
        return 'http://www.' . $_SERVER["SERVER_NAME"] . '/' . substr(realpath($temp_file_path), strlen($_SERVER["DOCUMENT_ROOT"]));
    });
    $twig->addFunction($bankgiroQrFunction);

    return $twig;
}

if (!is_array($_POST['select_contacts'])) {
    $_POST['select_contacts'] = [];
}

if ($_POST['action'] == 'send-pdf') { ?>
    <?= get_selected_contacts_form_field() ?>
    <div class="row">
        <div class="col-xs-12">
            <h1>Skicka PDF - Välj brevtyp</h1>

            <p>Den här guiden använder du för att skicka e-post med PDF-bilagor till scouterna och deras föräldrar. I
                huvudsak används detta för att skicka fakturor.</p>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <?= implode("", array_map(
                function ($file) {
                    return sprintf('<div class="radio"><label><input type="radio" value="%s" name="%s">Jag vill använda mallen <em>%s</em></label></div>',
                        basename($file, ".yaml"),
                        FORM_PARAM_SENDPDF_TEMPLATE,
                        basename($file, ".yaml")
                    );
                }, array_filter(scandir(TEMPLATES_FOLDER), function ($file) {
                return substr($file, -5) == ".yaml";
            }))) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="checkbox">
                <label>
                    <input type="checkbox" value="true" name="sendpdf_autoimport">Hämta fakturaunderlag från dokumentet
                    <a href="https://docs.google.com/spreadsheets/d/12cpqahyBxr-8PirnM0yCR2PUzxNEsaUQKTMEzzh8JAA/edit#gid=0"
                       target="_blank">Skulder och inbetalningar</a>.
                    <br/>
                    <small>Denna funktion hämtar information om obetalda skulder och försöker para ihop information med
                        rätt person i kontaktlistan.
                    </small>
                </label>
            </div>
        </div>
    </div>
    <div>
        <button type="submit" name="action" value="" class="btn btn-default btn-sm">Tillbaka</button>
        <button type="submit" name="action" value="send-pdf-template-config" class="btn btn-primary">Nästa</button>
    </div>
<?php } elseif ($_POST['action'] == 'send-pdf-template-config') { ?>
    <?= sprintf('<input type="hidden" name="%s" value="%s">', FORM_PARAM_SENDPDF_TEMPLATE, $_POST["" . FORM_PARAM_SENDPDF_TEMPLATE . ""]) ?>
    <?= get_selected_contacts_form_field() ?>

    <?php

    if ($_POST[FORM_PARAM_SENDPDF_AUTOIMPORT] == 'true') {
        $debtData = file_get_contents('https://docs.google.com/spreadsheets/d/12cpqahyBxr-8PirnM0yCR2PUzxNEsaUQKTMEzzh8JAA/pub?gid=0&single=true&output=tsv');

        $debtDataLines = explode("\n", $debtData);

        $debtObjects = array_map(function ($str) {
            list ($text, $amount, $person, $dateAdded, $datePaid, $reference) = explode("\t", $str);
            return array(
                "text" => trim($text),
                "amount" => intval(str_replace("−", "-", trim($amount))),
                "person" => trim($person),
                "datePaid" => trim($datePaid),
                "reference" => trim($reference));
        }, $debtDataLines);

        $debtsToInvoice = array_filter($debtObjects, function ($obj) {
            return empty($obj["datePaid"]) && empty($obj["reference"]);
        });

        foreach ($debtsToInvoice as $debtToInvoice) {
            foreach ($entries as $key => $entry) {
                if ($entry->name == $debtToInvoice["person"]) {
                    $_POST['select_contacts'][] = $key;
                }
            }
        }
    } else {
        $debtsToInvoice = [];
    }

    $templateSampleData = Yaml::parse(file_get_contents(TEMPLATES_FOLDER . '/' . $_POST[FORM_PARAM_SENDPDF_TEMPLATE] . '.yaml'));

    $allData = [];

    $twig = getTwig();

    $fileCounter = 0;

    $_POST['select_contacts'] = array_unique($_POST['select_contacts']);

    foreach ($_POST['select_contacts'] as $key) {
        $entry = $entries[$key];

        $recipients = array_unique(array_filter(
            array(
                $entry->age >= 16 ? $entry->email : null,
                $entry->age < 19 ? $entry->guardian_1_email : null,
                $entry->age < 19 ? $entry->guardian_2_email : null),
            function ($item) {
                return !empty($item);
            }));

        $luhn = new Luhn();
        while (file_exists(sprintf('%s/%s/%s.pdf', __DIR__, ARCHIVE_FOLDER, $id = $luhn->generate(intval(sprintf("%s%02d", date("ymd"), ++$fileCounter)))))) ;

        $entryData = [
            PROPERTY_SUBJECT => $twig->render($_POST[FORM_PARAM_SENDPDF_TEMPLATE] . '.subject.html', ['namn' => $entry->name]),
            PROPERTY_TO => $recipients,
            PROPERTY_REF => $id,
            'namn' => $entry->name,
            'datum_idag' => date_format(date_create(), 'Y-m-d'),
            'datum_om14dagar' => date_format(date_add(date_create(), date_interval_create_from_date_string('14 days')), 'Y-m-d'),
            'datum_om30dagar' => date_format(date_add(date_create(), date_interval_create_from_date_string('30 days')), 'Y-m-d')
        ];
        foreach ($debtsToInvoice as $debtToInvoice) {
            if ($entry->name == $debtToInvoice["person"]) {
                $entryData['rader'][] = array('text' => $debtToInvoice['text'], 'belopp' => $debtToInvoice['amount'], 'antal' => 1);
            }
        }
        $templateEntryData = array_merge($templateSampleData, $entryData);
        $allData[] = $templateEntryData;
    }
    ?>


    <div class="row">
        <div class="col-xs-12">
            <h1>Skicka PDF - Fyll i data</h1>

            <p>PDF:erna och e-postmeddelandena har skapats men inte skickats. Här kan du titta på breven så att du vet
                vad som kommer skickas.</p>

            <p>
                <small>Ett bra tips är att kopiera texten nedan, redigera den i en textredigerar med "yaml-stöd", och
                    sedan kopiera tillbaka texten till den stora textrutan nedan.
                </small>
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <textarea
                    name="<?= FORM_FIELD_SENDPDF_DATA ?>"
                    class="form-control"
                    rows="30"><?= Yaml::dump($allData, 10, 2) ?></textarea>
        </div>
    </div>
    <div>
        <button type="submit" name="action" value="send-pdf" class="btn btn-default btn-sm">Tillbaka</button>
        <button type="submit" name="action" value="send-pdf-preview" class="btn btn-primary">Förhandsgranska</button>
    </div>
<?php } elseif ($_POST['action'] == 'send-pdf-preview') { ?>
    <?= sprintf('<input type="hidden" name="%s" value="%s">', FORM_PARAM_SENDPDF_TEMPLATE, $_POST["" . FORM_PARAM_SENDPDF_TEMPLATE . ""]) ?>
    <?= sprintf('<input type="hidden" name="%s" value="%s">', FORM_FIELD_SENDPDF_DATA, $_POST["" . FORM_FIELD_SENDPDF_DATA . ""]) ?>
    <?= get_selected_contacts_form_field() ?>
    <div class="row">
        <div class="col-xs-12">
            <h1>Skicka PDF - Förhandsgranskning</h1>

            <p>PDF:erna och e-postmeddelandena har skapats men inte skickats. Här kan du titta på breven så att du vet
                vad som kommer skickas.</p>

            <p>
                <small>Du kan alltid gå ett steg tillbaka om du upptäcker fel.</small>
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <?php

            $twig = getTwig();

            $fileCounter = 0;

            $mailsData = Yaml::parse($_POST[FORM_FIELD_SENDPDF_DATA]);
            foreach ($mailsData as $i => $mailData) {
                printf('<p><strong>Brev %d</strong></p>', $i + 1);

                $pathAttachment = sprintf('%s/%s/%s.pdf', __DIR__, ARCHIVE_FOLDER, $mailData[PROPERTY_REF]);
                $pathMessage = sprintf('%s/%s/%s.html', __DIR__, ARCHIVE_FOLDER, $mailData[PROPERTY_REF]);

                createPDF(
                    $twig,
                    $_POST[FORM_PARAM_SENDPDF_TEMPLATE] . '.pdf.html',
                    $mailData,
                    $pathAttachment);

                createHTML(
                    $twig,
                    $_POST[FORM_PARAM_SENDPDF_TEMPLATE] . '.message.html',
                    $mailData,
                    $pathMessage);

                printf('<p>Ämne: %s</p>', $mailData[PROPERTY_SUBJECT]);
                printf('<p>Mottagare: %s</p>', implode(", ", $mailData[PROPERTY_TO]));
                printf('<p><a href="%s" target="_blank">Meddelande</a></p>', substr($pathMessage, strlen(__DIR__) + 1));
                printf('<p><a href="%s" target="_blank">PDF</a></p>', substr($pathAttachment, strlen(__DIR__) + 1));

            }
            ?>
        </div>
    </div>
    <div>
        <button type="submit" name="action" value="send-pdf-template-config" class="btn btn-default btn-sm">Tillbaka
        </button>
        <button type="submit" name="action" value="send-pdf-do" class="btn btn-primary">Skicka nu</button>
    </div>
<?php } elseif ($_POST['action'] == 'send-pdf-do') { ?>
    <?= sprintf('<input type="hidden" name="%s" value="%s">', FORM_PARAM_SENDPDF_TEMPLATE, $_POST["" . FORM_PARAM_SENDPDF_TEMPLATE . ""]) ?>
    <?= sprintf('<input type="hidden" name="%s" value="%s">', FORM_FIELD_SENDPDF_DATA, $_POST["" . FORM_FIELD_SENDPDF_DATA . ""]) ?>
    <?= get_selected_contacts_form_field() ?>
    <div class="row">
        <div class="col-xs-12">
            <h1>Skicka PDF</h1>

            <p>Nu har breven skickats.</p>

            <p>
                <small>Om något gick fel så ser du det här nedan.</small>
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <?php

            $fileCounter = 0;

            $mailsData = Yaml::parse($_POST[FORM_FIELD_SENDPDF_DATA]);
            foreach ($mailsData as $i => $mailData) {
                printf('<p><strong>Brev %d</strong></p>', $i + 1);
                printf('<p>Mottagare: %s</p>', implode(", ", $mailData[PROPERTY_TO]));

                $pathAttachment = sprintf('%s/%s/%s.pdf', __DIR__, ARCHIVE_FOLDER, $mailData[PROPERTY_REF]);
                $pathMessage = sprintf('%s/%s/%s.html', __DIR__, ARCHIVE_FOLDER, $mailData[PROPERTY_REF]);
                $pathLog = sprintf('%s/%s/%s.log.yaml', __DIR__, ARCHIVE_FOLDER, $mailData[PROPERTY_REF]);

                $recps = $mailData[PROPERTY_TO];
                $subj = $mailData[PROPERTY_SUBJECT];

                $mail = createMail($pathAttachment, $recps, $subj, $pathMessage, $config);

//                print_r($mail);
                $sendResult = $mail->send();
                if (!$sendResult) {
                    printf('<p><span class="label label-warning">%s</span></p>', 'Fel: ' . $mail->ErrorInfo);
                } else {
                    printf('<p><span class="label label-success">Skickat</span></p>');
                }
                $logData = array_merge($mailData, ['_errors' => $mail->ErrorInfo]);
                file_put_contents($pathLog, Yaml::dump($logData, 10, 2));
            }
            ?>
        </div>
    </div>
    <div>
        <button type="submit" name="action" value="" class="btn btn-primary">Stäng</button>
    </div>
<?php } ?>
