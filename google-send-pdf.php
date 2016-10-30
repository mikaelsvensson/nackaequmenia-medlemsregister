<?php
const TEMPLATES_FOLDER = 'templates';
const PROPERTY_SUBJECT = '_subject';
const PROPERTY_TO = '_to';
const PROPERTY_REF = '_ref';
const FORM_FIELD_SENDPDF_DATA = "sendpdf_data";
const FORM_PARAM_SENDPDF_TEMPLATE = "sendpdf_template";
const ARCHIVE_FOLDER = 'archive';
require_once 'core-google-data.php';

$config = parse_ini_file('config.ini', true);

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
    return $twig;
}

if ($_POST['action'] == 'send-pdf') { ?>
    <?= implode("", array_map(function ($value) {
        return sprintf('<input type="hidden" name="%s" value="%s">', 'entry[]', $value);
    }, $_POST['entry'])) ?>
    <div class="row">
        <div class="col-xs-12">
            <h1>Skicka PDF - Välj brevtyp</h1>

            <p>Den här guiden använder du för att skicka e-post med PDF-bilagor till scouterna och deras föräldrar. I huvudsak används detta för att skicka fakturor.</p>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="radio">
            <?= implode("", array_map(
                function ($file) {
                    return sprintf('<label><input type="radio" value="%s" name="%s">%s</label>',
                        basename($file, ".yaml"),
                        FORM_PARAM_SENDPDF_TEMPLATE,
                        basename($file, ".yaml")
                        );
                }, array_filter(scandir(TEMPLATES_FOLDER), function ($file) { return substr($file, -5) == ".yaml";}))) ?>
            </div>
        </div>
    </div>
    <div>
        <button type="submit" name="action" value="" class="btn btn-default btn-sm">Tillbaka</button>
        <button type="submit" name="action" value="send-pdf-template-config" class="btn btn-primary">Nästa</button>
    </div>
<?php } elseif ($_POST['action'] == 'send-pdf-template-config') { ?>
    <?= sprintf('<input type="hidden" name="%s" value="%s">', FORM_PARAM_SENDPDF_TEMPLATE, $_POST["" . FORM_PARAM_SENDPDF_TEMPLATE . ""]) ?>
    <?= implode("", array_map(function ($value) {
        return sprintf('<input type="hidden" name="%s" value="%s">', 'entry[]', $value);
    }, $_POST['entry'])) ?>

    <?php
    $templateSampleData = Yaml::parse(file_get_contents(TEMPLATES_FOLDER . '/'.$_POST[FORM_PARAM_SENDPDF_TEMPLATE].'.yaml'));

    $allData = [];

    $twig = getTwig();

    $fileCounter = 0;

    foreach ($_POST['entry'] as $key) {
        $entry = $entries[$key];

        $recipients = array_unique(array_filter(array($entry[email], $entry[guardian_1_email], $entry[guardian_2_email]), function ($item) {
            return !empty($item);
        }));

        printf('<input type="hidden" name="entry[]" value="%s">', $key);


//    $entryData = array_combine(['namn', 'epost', 'malsman1_epost', 'malsman2_epost'], array_map(function ($n) use ($entry) {
//        return $entry[constant($n)];
//    }, ['name', 'email', 'guardian_1_email', 'guardian_2_email']));

        while (file_exists(sprintf('%s/%s/%s.pdf', __DIR__, ARCHIVE_FOLDER, $id = sprintf("%s%02d", date("ymd"), ++$fileCounter))));
        $entryData = [
            PROPERTY_SUBJECT => $twig->render($_POST[FORM_PARAM_SENDPDF_TEMPLATE].'.subject.html', ['namn' => $entry[name]]),
            PROPERTY_TO => $recipients,
            PROPERTY_REF => $id,
            'namn' => $entry[name]
        ];
        $templateEntryData = array_merge($entryData, $templateSampleData);
        $allData[] = $templateEntryData;
        $yaml = Yaml::dump($templateEntryData, 10, 2);
    }
    ?>


    <div class="row">
        <div class="col-xs-12">
            <h1>Skicka PDF - Fyll i data</h1>

            <p>PDF:erna och e-postmeddelandena har skapats men inte skickats. Här kan du titta på breven så att du vet vad som kommer skickas.</p>

            <p>
                <small>Ett bra tips är att kopiera texten nedan, redigera den i en textredigerar med "yaml-stöd", och sedan kopiera tillbaka texten till den stora textrutan nedan.</small>
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <textarea
                name="<?=FORM_FIELD_SENDPDF_DATA?>"
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
    <div class="row">
        <div class="col-xs-12">
            <h1>Skicka PDF - Förhandsgranskning</h1>

            <p>PDF:erna och e-postmeddelandena har skapats men inte skickats. Här kan du titta på breven så att du vet vad som kommer skickas.</p>

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
                    $_POST[FORM_PARAM_SENDPDF_TEMPLATE].'.pdf.html',
                    $mailData,
                    $pathAttachment);

                createHTML(
                    $twig,
                    $_POST[FORM_PARAM_SENDPDF_TEMPLATE].'.message.html',
                    $mailData,
                    $pathMessage);

                printf('<p>Ämne: %s</p>', $mailData[PROPERTY_SUBJECT]);
                printf('<p>Mottagare: %s</p>', implode(", ", $mailData[PROPERTY_TO]));
                printf('<p><a href="%s" target="_blank">Meddelande</a></p>', substr($pathMessage, strlen(__DIR__)+1));
                printf('<p><a href="%s" target="_blank">PDF</a></p>', substr($pathAttachment, strlen(__DIR__)+1));

            }
            ?>
        </div>
    </div>
    <div>
        <button type="submit" name="action" value="send-pdf" class="btn btn-default btn-sm">Tillbaka</button>
        <button type="submit" name="action" value="send-pdf-do" class="btn btn-primary">Skicka nu</button>
    </div>
<?php } elseif ($_POST['action'] == 'send-pdf-do') { ?>
    <?= sprintf('<input type="hidden" name="%s" value="%s">', FORM_PARAM_SENDPDF_TEMPLATE, $_POST["" . FORM_PARAM_SENDPDF_TEMPLATE . ""]) ?>
    <?= sprintf('<input type="hidden" name="%s" value="%s">', FORM_FIELD_SENDPDF_DATA, $_POST["" . FORM_FIELD_SENDPDF_DATA . ""]) ?>
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
