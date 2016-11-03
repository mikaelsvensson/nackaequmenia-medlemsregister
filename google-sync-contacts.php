<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once 'google-util.php';

$config = parse_ini_file('config.ini', true);

$client = createGoogleClient(
    $config['google']['google_api_credentials_file'],
    $config['google']['google_api_oauthcallback_uri']);

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
} else {
    $redirect_uri = $config['google']['google_api_oauthcallback_uri'];
    printf('<a href="%s">Logga in med Google</a>', $redirect_uri);
    //header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

$client = $client->authorize();

function getGoogleContacts($client)
{
    $feedURL = "https://www.google.com/m8/feeds/contacts/default/full?max-results=1000&alt=json";

    $response = $client->get($feedURL, [
        'headers' => ['GData-Version' => '3.0']
    ]);

//    print_r($response);

    if ($response->getStatusCode() != 200) {
        printf('<a href="%s">Logga in med Google</a>', 'auth-google.php');
        return [];
    }

    $body = $response->getBody();

    $response = json_decode($body, true)['feed']['entry'];

//    print_r($response);

    $contacts = array_map(function ($contact) {
//        $aptAccessFromDateProps = is_array($contact['gContact$userDefinedField']) ? array_filter($contact['gContact$userDefinedField'],
//            function ($item) {
//                return strpos($item['key'], 'Tilltr') != -1;
//            }) : [];
        return [
            'name' => isset($contact['title']) ? $contact['title']['$t'] : null,
            'updated' => $contact['updated']['$t'],
            'note' => isset($contact['content']) ? $contact['content']['$t'] : null,
            'email' => isset($contact['gd$email']) ? $contact['gd$email'][0]['address'] : null,
            'phone' => isset($contact['gd$phoneNumber']) ? $contact['gd$phoneNumber'][0]['$t'] : null//,
//            'orgName' => isset($contact['gd$organization']) ? $contact['gd$organization'][0]['gd$orgName']['$t'] : null,
//            'orgTitle' => isset($contact['gd$organization']) ? $contact['gd$organization'][0]['gd$orgTitle']['$t'] : null,
//            'aptAccessFrom' => count($aptAccessFromDateProps) > 0 ? $aptAccessFromDateProps[0]['value'] : null,
//            'address' => isset($contact['gd$postalAddress']) ? explode("\n", $contact['gd$postalAddress'][0]['$t']) : null
        ];
    }, $response);
    return $contacts;
}

$processorDryrun = function ($contact) {
    print '<pre>';
    print_r($contact);
    print '</pre>';
}
?>
<?= implode("", array_map(function ($value) {
    return sprintf('<input type="hidden" name="%s" value="%s">', 'entry[]', $value);
}, $_POST['entry'])) ?>

    <div class="row">
        <div class="col-xs-12">
            <h1>Synka med Google-adressbok</h1>

            <p>Använd den här funktionen för att uppdatera din Google-adressbok med kontaktinformation.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <table class="table table-bordered table-condensed">
                <thead>
                <tr>
                    <th>Namn</th>
                    <th>E-post</th>
                    <th>Telefon</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>

                <?php
                $processor = $_POST['action'] == 'sync-contacts-do' ? $processorDryrun : $processorDryrun;
                $contacts = getGoogleContacts($client);

                foreach ($entries as $entry) {

                    $entryGivenName = substr($entry[name], 0, strpos($entry[name], ' '));
                    $entryFamilyName = substr($entry[name], strpos($entry[name], ' '));

                    printf('<tr><td colspan="4"><strong>%s</strong></td></tr>', $entry[name]);
                    $opts = [
                        [$entry[name], $entry[name], $entry[phone], $entry[email]],
                        [$entry[guardian_1_name], sprintf('%s, kontakt %s', $entry[name], strtok($entry[guardian_1_name], ' ')), $entry[guardian_1_phone], $entry[guardian_1_email]],
                        [$entry[guardian_2_name], sprintf('%s, kontakt %s', $entry[name], strtok($entry[guardian_2_name], ' ')), $entry[guardian_2_phone], $entry[guardian_2_email]]
                    ];
                    foreach ($opts as $opt) {
                        list($fullName, $displayName, $phone, $email) = $opt;
                        $matchingContacts = array_values(array_filter($contacts, function ($contact) use ($fullName) {
                            $isContactNameSet = !empty($contact['name']);
                            return $isContactNameSet && strpos($fullName, $contact['name']) !== false;
                        }));
                        $isAlreadyInContactList = count($matchingContacts) > 0;
                        if (!empty($email) || !empty($phone)) {
                            $existingContactName = $isAlreadyInContactList ? $matchingContacts[0]["name"] : "";
                            $existingContactEmail = $isAlreadyInContactList ? $matchingContacts[0]["email"] : "";
                            $existingContactPhone = $isAlreadyInContactList ? $matchingContacts[0]["phone"] : "";

                            $rowClasses = $isAlreadyInContactList ? "warning" : "success";

                            printf('<tr class="%s"><td>%s <del>%s</del></td><td>%s <del>%s</del></td><td>%s <del>%s</del></td><td>%s</td></tr>',
                                $rowClasses,
                                $displayName,
                                $existingContactName,
                                $email,
                                $existingContactEmail,
                                $phone,
                                $existingContactPhone,
                                $isAlreadyInContactList ? "GAMMAL" : "NY");
                        }
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php if ($_POST['action'] == 'sync-contacts') { ?>
    <div>
        <button type="submit" name="action" value="" class="btn btn-default btn-sm">Tillbaka</button>
        <button type="submit" name="action" value="sync-contacts-do" class="btn btn-primary">Synka</button>
    </div>
<?php } elseif ($_POST['action'] == 'sync-contacts-do') { ?>
    <div>
        <button type="submit" name="action" value="" class="btn btn-primary">Stäng</button>
    </div>
<?php } ?>