<?php
require_once 'core-google-data.php';

$config = parse_ini_file('config.ini', true);

?>
<div class="row">
    <div class="col-xs-12">
        <h1>Skicka PDF</h1>

        <p>Beskrivning</p>

        <p>
            <small>Notering</small>
        </p>
    </div>
</div>
<?php

use Symfony\Component\Yaml\Yaml;

$templateSampleData = Yaml::parse(file_get_contents('templates/faktura.yaml'));

$allData = [];

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, array(//	'cache' => '/path/to/compilation_cache',
));

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

    while (file_exists(sprintf('%s/archive/%s.pdf', __DIR__, $id = sprintf("%s%02d", date("ymd"), ++$fileCounter))));
    $entryData = [
        '_subject' => $twig->render('faktura.subject.html', ['namn' => $entry[name]]),
        '_to' => $recipients,
        '_ref' => $id,
        'namn' => $entry[name]
    ];
    $templateEntryData = array_merge($entryData, $templateSampleData);
    $allData[] = $templateEntryData;
    $yaml = Yaml::dump($templateEntryData, 10, 2);

        ?>
<?php } ?>
<?php if ($_POST['action'] == 'send-pdf') { ?>
    <div class="row">
        <div class="col-xs-12">
            <textarea
                name="sendpdf_data"
                class="form-control"
                rows="30"><?= Yaml::dump($allData, 10, 2) ?></textarea>
        </div>
    </div>
<?php } elseif ($_POST['action'] == 'send-pdf-preview') { ?>
    <div class="row">
        <div class="col-xs-12">
            <?php

            printf('<input type="hidden" name="sendpdf_data" value="%s">', $_POST["sendpdf_data"]);

            $fileCounter = 0;

            $mailsData = Yaml::parse($_POST['sendpdf_data']);
            foreach ($mailsData as $i => $mailData) {
                printf('<p><strong>Brev %d</strong></p>', $i + 1);

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
                if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
                    require_once(dirname(__FILE__) . '/lang/eng.php');
                    $pdf->setLanguageArray($l);
                }
                $pdf->AddPage();
                $html = $twig->render('faktura.pdf.html', $mailData);
                $pdf->writeHTML($html, true, false, true, false, '');
                $pdf->lastPage();
                $path = sprintf('%s/archive/%s.pdf', __DIR__, $mailData["_ref"]);
                $pdf->Output($path, 'F');

                $msgPath = sprintf('%s/archive/%s.html', __DIR__, $mailData["_ref"]);

                file_put_contents($msgPath, $twig->render('faktura.message.html', $mailData));

                printf('<p>Ämne: %s</p>', $mailData["_subject"]);
                printf('<p>Mottagare: %s</p>', implode(", ", $mailData["_to"]));
                printf('<p><a href="%s" target="_blank">Meddelande</a></p>', substr($msgPath, strlen(__DIR__)+1));
                printf('<p><a href="%s" target="_blank">PDF</a></p>', substr($path, strlen(__DIR__)+1));

            }
            ?>
        </div>
    </div>
<?php } elseif ($_POST['action'] == 'send-pdf-do') { ?>
    <div class="row">
        <div class="col-xs-12">
            <?php

            $fileCounter = 0;

            $mailsData = Yaml::parse($_POST['sendpdf_data']);
            foreach ($mailsData as $i => $mailData) {
                printf('<p><strong>Brev %d</strong></p>', $i + 1);
                printf('<p>Mottagare: %s</p>', implode(", ", $mailData["_to"]));

                $path = sprintf('%s/archive/%s.pdf', __DIR__, $mailData["_ref"]);
                $msgPath = sprintf('%s/archive/%s.html', __DIR__, $mailData["_ref"]);

                file_put_contents($msgPath, $twig->render('faktura.message.html', $mailData));

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
                foreach ($mailData["_to"] as $to) {
                    $mail->addAddress($to);       // Add a recipient
                }
                //$mail->addAddress('ellen@example.com');                   // Name is optional
                //$mail->addReplyTo('info@example.com', 'Information');
                //$mail->addCC('cc@example.com');
                $mail->addBCC($config['mail']['smtp_from']);

                //$mail->WordWrap = 50;                                 // Set word wrap to 50 characters
                $mail->addAttachment($path);         // Add attachments
                //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
                $mail->isHTML(true);                                  // Set email format to HTML

                $mail->Subject = '=?utf-8?B?' . base64_encode($mailData["_subject"]) . '?=';
                $mail->CharSet = 'UTF-8';
                $mail->Body = file_get_contents($msgPath);
                //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

                if (!$mail->send()) {
                    printf('<p><span class="label label-warning">%s</span></p>', 'Fel: ' . $mail->ErrorInfo);
                } else {
                    printf('<p><span class="label label-success">Skickat</span></p>');
                }

            }
            ?>
        </div>
    </div>
<?php } ?>
<div>
    <?php if ($_POST['action'] == 'send-pdf') { ?>
        <button type="submit" name="action" value="" class="btn btn-default btn-sm">Tillbaka</button>
        <button type="submit" name="action" value="send-pdf-preview" class="btn btn-primary">Förhandsgranska</button>
    <?php } elseif ($_POST['action'] == 'send-pdf-preview') { ?>
        <button type="submit" name="action" value="send-pdf" class="btn btn-default btn-sm">Tillbaka</button>
        <button type="submit" name="action" value="send-pdf-do" class="btn btn-primary">Skicka nu</button>
    <?php } else { ?>
        <button type="submit" name="action" value="" class="btn btn-primary">Stäng</button>
    <?php } ?>

</div>