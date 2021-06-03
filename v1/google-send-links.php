<?php
//require_once 'lib/phpmailer/PHPMailerAutoload.php';
error_reporting(E_ALL);

require_once 'core-google-data.php';

$config = parse_ini_file('config.ini', true);

function file_get_contents_utf8($fn)
{
    return file_get_contents($fn);
}

$mailBody = file_get_contents_utf8('google-scoutinfoeditlink-mail-body.txt');

?>
<div class="row">
    <div class="col-xs-12">
        <h1>Skicka scoutinfo</h1>

        <p>Skicka ett e-post med den information vi har om respektive scout till scouten själv och till hens
            föräldrar. Brevet innehåller även en länk för att komplettera med information som saknas.
        </p>

        <p><small>Observera att kompletterande information som skickas in <em>inte</em> ersätter den information som vi redan
            har. Ny information måste manuellt kopieras till Kontaktinformation.</small></p>
    </div>
</div>
<?php
print get_selected_contacts_form_field();
foreach ($_POST['select_contacts'] as $key) {
    $entry = $entries[$key];

    $recipients = array_unique(array_filter(array($entry->email, $entry->guardian_1_email, $entry->guardian_2_email), function ($item) {
        return !empty($item);
    }));

    $editLinkUrl = sprintf('http://www.nackasmu.se/medlemsregister/%s', $entry->id);
    ?>
    <div class="row">
        <div class="col-xs-12">
            <p><strong><?= $entry->name ?>, <a href="<?= $editLinkUrl ?>"><?= $editLinkUrl ?></a></strong></p>
            <ul class="list-unstyled">
                <?= implode(array_map(function ($str) {
                    return '<li>' . $str . '</li>';
                }, $recipients)) ?>
            </ul>
            <?php
            if ($_POST['action'] == 'send-links-do') {
                $body = $mailBody;
                $body = str_replace('{link}', $editLinkUrl, $body);
                $body = formatString($body, $entry);

                $subject = sprintf('Stämmer vår information om %s?', $entry->name_given);

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
                //$mail->FromName = 'Mikael, scoutledare';
                foreach ($recipients as $to) {
                    $mail->addAddress($to);       // Add a recipient
                }
                //$mail->addAddress('ellen@example.com');                   // Name is optional
                //$mail->addReplyTo('info@example.com', 'Information');
                //$mail->addCC('cc@example.com');
                //$mail->addBCC('bcc@example.com');

                //$mail->WordWrap = 50;                                 // Set word wrap to 50 characters
                //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
                //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
                //$mail->isHTML(true);                                  // Set email format to HTML

                $mail->Subject = '=?utf-8?B?' . base64_encode($subject) . '?=';
                $mail->CharSet = 'UTF-8';
                $mail->Body = $body;
                //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

                if (!$mail->send()) {
                    printf('<span class="label label-warning">%s</span>', 'Fel: ' . $mail->ErrorInfo);
                } else {
                    printf('<span class="label label-success">Skickat</span>');
                }
            }
            ?>
        </div>
    </div>
<?php } ?>
<div>
    <button type="submit" name="action" value="" class="btn btn-default btn-sm">Tillbaka</button>
    <button type="submit" name="action" value="send-links-do" class="btn btn-primary">Skicka nu</button>
</div>