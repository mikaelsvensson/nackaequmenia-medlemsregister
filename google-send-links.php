<?php
//require_once 'lib/phpmailer/PHPMailerAutoload.php';

require_once 'core-google-data.php';

$config = parse_ini_file('config.ini', true);

const LINK_PATTERN = "https://docs.google.com/forms/d/e/1FAIpQLSffGvyxRxcWw90DXj5z7SNX1DZzekakMDGCmAy1FPj6WT1nLg/viewform?" .
    "entry.772550625={name}&" .
    "entry.758769741={ssn}&" .
    "entry.1260081139={email}&" .
    "entry.1408734283={phone_mobile}&" .
    "entry.160546750={address_street},{address_postal}&" .
    "entry.466376640={allergies}&" .
    "entry.2076160580={guardian_1_name}&" .
    "entry.647263911={guardian_1_email}&" .
    "entry.232776408={guardian_1_phone}&" .
    "entry.878250299={phone}&" .
    "entry.1275442685={guardian_1_address_street},{guardian_1_address_postal}&" .
    "entry.1205032770={guardian_2_name}&" .
    "entry.794787331={guardian_2_email}&" .
    "entry.1090313649={guardian_2_phone}&" .
    "entry.2025099321={phone}&" .
    "entry.1879882957={guardian_2_address_street},{guardian_2_address_postal}";

function file_get_contents_utf8($fn)
{
    return file_get_contents($fn);
}

$mailBody = file_get_contents_utf8('google-scoutinfoeditlink-mail-body.txt');

function replace_data_placeholders($entry, $str, $urlEncode = false)
{
    $matches = [];
    $pattern = '/\{([a-z0-9_]+)\}/';
    preg_match_all($pattern, $str, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $str = str_replace(
            $match[0],
            $urlEncode ? urlencode($entry[constant($match[1])]) : $entry[constant($match[1])],
            $str);
    }
    return $str;
}

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
foreach ($_POST['entry'] as $key) {
    $entry = $entries[$key];

    $recipients = array_unique(array_filter(array($entry[email], $entry[guardian_1_email], $entry[guardian_2_email]), function ($item) {
        return !empty($item);
    }));
    ?>
    <input type="hidden" name="entry[]" value="<?= $key ?>">
    <div class="row">
        <div class="col-xs-12">
            <p><strong><?= $entry[name] ?></strong></p>
            <ul class="list-unstyled">
                <?= implode(array_map(function ($str) {
                    return '<li>' . $str . '</li>';
                }, $recipients)) ?>
            </ul>
            <?php
            if ($_POST['action'] == 'send-links-do') {
                $editLinkUrl = str_replace("=,", "=", replace_data_placeholders($entry, LINK_PATTERN, true));
                $body = $mailBody;
                $body = str_replace('{link}', $editLinkUrl, $body);
                $body = replace_data_placeholders($entry, $body);

                $subject = sprintf('Stämmer vår information om %s?', $entry[name]);

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