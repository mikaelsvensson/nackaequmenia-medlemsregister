<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once 'google-util.php';

$patternsOptions = [
    'Minimal' => [
        '{name}',
        '{name}',
        'Kontakter: {guardian_1_name}, {guardian_2_name}',
        '{guardian_1_name}',
        '{name}, kontakt {guardian_1_name_given}',
        'Kontakt för {name}',
        '{guardian_2_name}',
        '{name}, kontakt {guardian_2_name_given}',
        'Kontakt för {name}'
    ],
    'Scout först' => [
        'Scout {name}',
        'Scout {name}',
        'Nacka Equmenia. Föräldrar: {guardian_1_name}, {guardian_2_name}.',
        'Scout {name} - {guardian_1_name_given}',
        'Scout {name} - {guardian_1_name_given}',
        'Nacka Equmenia. Kontakt för {name}.',
        'Scout {name} - {guardian_2_name_given}',
        'Scout {name} - {guardian_2_name_given}',
        'Nacka Equmenia. Kontakt för {name}.'
    ],
    'Scout efter namn' => [
        '{name} scout',
        '{name} scout',
        'Nacka Equmenia. Föräldrar: {guardian_1_name}, {guardian_2_name}.',
        '{name_given} {name_surname_initial} scout - {guardian_1_name_given}',
        '{name_given} {name_surname_initial} scout - {guardian_1_name_given}',
        'Nacka Equmenia. Kontakt för {name}.',
        '{name_given} {name_surname_initial} scout - {guardian_2_name_given}',
        '{name_given} {name_surname_initial} scout - {guardian_2_name_given}',
        'Nacka Equmenia. Kontakt för {name}.'
    ]
];

$config = parse_ini_file('config.ini', true);

$googleClient = createGoogleClient(
    $config['google']['google_api_credentials_file'],
    $config['google']['google_api_oauthcallback_uri']);

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $googleClient->setAccessToken($_SESSION['access_token']);
    $client = $googleClient->authorize();
//} else {
//    printf('<a href="%s">Logga in med Google</a>', 'auth-google.php?action=sync-contacts');
}

$startTime = time();
$maxExecutionTime = intval(ini_get("max_execution_time"));
$abortTime = $startTime + ($maxExecutionTime * 0.90);

//print_r([$startTime, $maxExecutionTime, $abortTime]);

function saveXml($doc, $filename)
{
    $isSaveEnabled = $_SERVER['SERVER_NAME'] == 'localhost';
    if ($isSaveEnabled) {
        $doc->save($filename);
    }
}

function getGoogleContacts($client)
{
    $feedURL = "https://www.google.com/m8/feeds/contacts/default/full?max-results=1000&alt=json";

    $response = $client->get($feedURL, [
        'headers' => ['GData-Version' => '3.0']
    ]);

//    print_r($response);

    if ($response->getStatusCode() != 200) {
        printf('<a href="%s">Logga in med Google</a>', 'auth-google.php?action=sync-contacts');
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

        // https://www.google.com/m8/feeds/contacts/{userEmail}/full/{contactId}
//                            [href] => https://www.google.com/m8/feeds/contacts/mikael.kjell.svensson%40gmail.com/full/a0ca41a8fd8f710

        return [
            'editHref' => isset($contact['link']) ? array_values(array_filter($contact['link'], function ($item) {
                return $item['rel'] == 'edit';
            }))[0]['href'] : null,
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

function getGoogleContactsXml($client)
{
    $doc = new DOMDocument();
//    if (file_exists("temp/_feed.cache.xml")) {
//        $doc->load("temp/_feed.cache.xml");
//    } else {
    $feedURL = "https://www.google.com/m8/feeds/contacts/default/full?max-results=1000";

    $response = $client->get($feedURL, [
        'headers' => ['GData-Version' => '3.0']
    ]);

    if ($response->getStatusCode() != 200) {
        printf('<a href="%s">Logga in med Google</a>', 'auth-google.php?action=sync-contacts');
        return null;
    }

    $body = $response->getBody();

    $doc->loadXML($body);
    saveXml($doc, "temp/_feed.cache.xml");
//    }
    return $doc;
}

function getGoogleContactGroupsXml($client)
{
    $doc = new DOMDocument();
//    if (file_exists("temp/_groups.cache.xml")) {
//        $doc->load("temp/_groups.cache.xml");
//    } else {
    $feedURL = "https://www.google.com/m8/feeds/groups/default/full?max-results=1000";

    $response = $client->get($feedURL, [
        'headers' => ['GData-Version' => '3.0']
    ]);

    if ($response->getStatusCode() != 200) {
        printf('<a href="%s">Logga in med Google</a>', 'auth-google.php?action=sync-contacts');
        return null;
    }

    $body = $response->getBody();

    $doc->loadXML($body);
    saveXml($doc, "temp/_groups.cache.xml");
//    }
    return $doc;
}

$updateContactDryrun = function ($requestBodyDocument, $requestUri, $logFileName, $client) {
    saveXml($requestBodyDocument, "$logFileName-update.preview.xml");
    return [true, "Existerande kontakt kommer att uppdateras"];
};

$createContactDryrun = function ($requestBodyDocument, $logFileName, $client) {
    saveXml($requestBodyDocument, "$logFileName-create.preview.xml");
    return [true, "Ny kontakt kommer att skapas"];
};

$createContact = function ($requestBodyDocument, $logFileName, $client) {
    $requestBody = $requestBodyDocument->saveXML();

    saveXml($requestBodyDocument, "$logFileName-create.requestbody.xml");

    $response = $client->post("https://www.google.com/m8/feeds/contacts/default/full", [
        'headers' => [
            'GData-Version' => '3.0',
            'Content-Type' => 'application/atom+xml'
        ],
        'body' => $requestBody
    ]);

    //file_put_contents("$logFileName-create.log", print_r($response, true));
    //file_put_contents("$logFileName-create.responsebody.xml", $response->getBody());

    if ($response->getStatusCode() != 201) {
        return [false, "Fel uppstod: " . $response->getReasonPhrase()];
    }
    return [true, "Skapad"];
};

$updateContact = function ($requestBodyDocument, $requestUri, $logFileName, $client) {
    $etag = $requestBodyDocument->documentElement->attributes["etag"]->nodeValue;

    $requestBody = $requestBodyDocument->saveXML();

    saveXml($requestBodyDocument, "$logFileName-update.requestbody.xml");
    //file_put_contents("$logFileName-update.requestbody.xml", $requestBody);

    $response = $client->put($requestUri, [
        'headers' => [
            'GData-Version' => '3.0',
            'Content-Type' => 'application/atom+xml',
            'If-Match' => $etag
        ],
        'body' => $requestBody
    ]);

    //file_put_contents("$logFileName-update.log", print_r($response, true));
    //file_put_contents("$logFileName-update.responsebody.xml", $response->getBody());

    if ($response->getStatusCode() != 200) {
        return [false, "[$etag]Fel uppstod: " . $response->getReasonPhrase()];
    }
    return [true, "Uppdaterad"];
};

function createGoogleContactGroup($requestBodyDocument, $logFileName, $client)
{
    $requestBody = $requestBodyDocument->saveXML();

    saveXml($requestBodyDocument, "$logFileName-create.requestbody.xml");
//    file_put_contents("$logFileName-create.requestbody.xml", $requestBody);

    $response = $client->post("https://www.google.com/m8/feeds/groups/default/full", [
        'headers' => [
            'GData-Version' => '3.0',
            'Content-Type' => 'application/atom+xml'
        ],
        'body' => $requestBody
    ]);

//    file_put_contents("$logFileName-create.log", print_r($response, true));
    $responseBody = $response->getBody();

//    file_put_contents("$logFileName-create.responsebody.xml", $responseBody);

    if ($response->getStatusCode() == 201) {
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($responseBody);
        $groupId = getElementText($responseDoc, 'a:id');
        return [$groupId, null];
    } else {
        return [null, $response->getReasonPhrase()];
    }
}

function getIndividualContacts($entries, $patterns)
{
    list (
        $selfFullNamePattern,
        $selfDisplayNamePattern,
        $selfNotePattern,
        $guardian1FullNamePattern,
        $guardian1DisplayNamePattern,
        $guardian1NotePattern,
        $guardian2FullNamePattern,
        $guardian2DisplayNamePattern,
        $guardian2NotePattern
        ) = $patterns;

    $desiredContacts = [];
    foreach ($entries as $entry) {

        $entry->name_given = strtok($entry->name, ' ');
        $entry->name_surname_initial = preg_replace('/^\w+\s/i', '', $entry->name)[0];
        $entry->guardian_1_name_given = strtok($entry->guardian_1_name, ' ');
        $entry->guardian_2_name_given = strtok($entry->guardian_2_name, ' ');

        $selfNote = (empty($entry->allergies) ? false : formatString("Allergier: {allergies}.", $entry)) .
            (empty($entry->note) ? false : formatString("{note}.", $entry)) .
            formatString($selfNotePattern, $entry);

        $opts = [
            [
                formatString($selfFullNamePattern, $entry),
                formatString($selfDisplayNamePattern, $entry),
                $entry->address_street,
                $entry->address_postal,
                $entry->phone,
                $entry->email,
                $selfNote,
                getSimpleId($entry->name) . "-self",
                getSimpleId($entry->name)
            ],
            [
                formatString($guardian1FullNamePattern, $entry),
                formatString($guardian1DisplayNamePattern, $entry),
                $entry->guardian_1_address_street,
                $entry->guardian_1_address_postal,
                $entry->guardian_1_phone,
                $entry->guardian_1_email,
                formatString($guardian1NotePattern, $entry),
                getSimpleId($entry->name) . "-guardian-1",
                getSimpleId($entry->guardian_1_name)
            ],
            [
                formatString($guardian2FullNamePattern, $entry),
                formatString($guardian2DisplayNamePattern, $entry),
                $entry->guardian_2_address_street,
                $entry->guardian_2_address_postal,
                $entry->guardian_2_phone,
                $entry->guardian_2_email,
                formatString($guardian2NotePattern, $entry),
                getSimpleId($entry->name) . "-guardian-2",
                getSimpleId($entry->guardian_2_name)
            ]
        ];
        foreach ($opts as $opt) {
            list($fullName, $displayName, $addressStreet, $addressPostal, $phone, $email, $note, $displayNameId, $contactId) = $opt;
            if (!empty($contactId)) {
                $desiredContacts[$contactId]['name'] = $fullName;
                $desiredContacts[$contactId]['displayName'][$displayNameId] = $displayName;
                $desiredContacts[$contactId]['email'][] = $email;
                $desiredContacts[$contactId]['notes'][] = $note;
                $desiredContacts[$contactId]['addressStreet'] = $addressStreet;
                $desiredContacts[$contactId]['addressPostal'] = $addressPostal;

                preg_match_all('/[0-9\s+-]+/', $phone, $phones);
                $desiredContacts[$contactId]['phone'] = array_merge(
                    is_array($desiredContacts[$contactId]['phone']) ?
                        $desiredContacts[$contactId]['phone']
                        :
                        [],
                    $phones[0]);
            }
        }
    }
    return $desiredContacts;
}

function getSimpleId($fullName)
{
    $contactId = strtolower(preg_replace('/[^A-Za-z]/', '', $fullName));
    return $contactId;
}

function removeChildren($parent, &$log, $namespace, $elementName)
{
    $nodeList = $parent->getElementsByTagNameNS($namespace, $elementName);
    for ($i = $nodeList->length - 1; $i >= 0; $i--) {
        $item = $nodeList->item($i);
        $log[] = sprintf("Tar bort %s %s", $elementName, empty($item->textContent) ? $item->getAttribute("address") : $item->textContent);
        $parent->removeChild($item);
    }
}

function queryDoc($doc, $query, $contextNode = null)
{
    $xpath = new DOMXPath($doc);
    $xpath->registerNamespace("gd", "http://schemas.google.com/g/2005");
    $xpath->registerNamespace("a", "http://www.w3.org/2005/Atom");
    $xpath->registerNamespace("gContact", "http://schemas.google.com/contact/2008");
    if ($contextNode != null) {
        return $xpath->evaluate($query, $contextNode);
    } else {
        return $xpath->evaluate($query);
    }
}

function setElementText($doc, $text, $xpath, $contextNode = null)
{
    $res = getFirstXpathMatch($doc, $xpath, $contextNode);
    $res->textContent = $text;
}

function setNodeValue($doc, $text, $xpath, $contextNode = null)
{
    $res = getFirstXpathMatch($doc, $xpath, $contextNode);
    if ($res != null) {
        if ($res->nodeValue != $text) {
            $res->nodeValue = $text;
            return true;
        }
    }
    return false;
}

function getElementText($doc, $xpath, $contextNode = null)
{
    $res = getFirstXpathMatch($doc, $xpath, $contextNode);
    if ($res != null) {
        return $res->nodeValue;
    } else {
        return null;
    }
}

function getFirstXpathMatch($doc, $xpath, $contextNode = null)
{
    $res = queryDoc($doc, $xpath, $contextNode);
    if ($res->length > 0) {
        return $res->item(0);
    } else {
        return null;
    }
}

function getAllXpathMatches($doc, $xpath, $contextNode = null)
{
    $matches = [];
    $res = queryDoc($doc, $xpath, $contextNode);
    for ($i = 0; $i < $res->length; $i++) {
        $item = $res->item($i);
        $matches[] = $item->nodeValue;
    }
    return $matches;
}


?>
<div class="row">
    <div class="col-xs-12">
        <h1>Synka med Google-adressbok</h1>

        <p>Använd den här funktionen för att uppdatera din Google-adressbok med kontaktinformation.</p>
    </div>
</div>

<?php function setEmailAddresses($doc, $entryNode, $emailAddresses, &$log)
{
    $emailAddressesBefore = getAllXpathMatches($doc, 'gd:email/@address', $entryNode);
    removeChildren($entryNode, $log, "http://schemas.google.com/g/2005", "email");
    foreach ($emailAddresses as $emailAddress) {
        $newEmailElement = $doc->createElementNS("http://schemas.google.com/g/2005", "email");

        $newEmailElement->setAttribute("address", $emailAddress);
        $newEmailElement->setAttribute("rel", "http://schemas.google.com/g/2005#other");

        $entryNode->appendChild($newEmailElement);
//                            $log[] = sprintf("L&auml;gger till e-post %s", $email);
    }
    $emailAddressesAfter = getAllXpathMatches($doc, 'gd:email/@address', $entryNode);

    sort($emailAddressesBefore);
    sort($emailAddressesAfter);
    $emailAddressesUpdated = implode(",", $emailAddressesBefore) != implode(",", $emailAddressesAfter);
    return $emailAddressesUpdated;
}

function setPhoneNumbers($doc, $entryNode, $phoneNumbers, &$log)
{
    $phoneNumbersBefore = getAllXpathMatches($doc, 'gd:phoneNumber/@uri', $entryNode);
    removeChildren($entryNode, $log, "http://schemas.google.com/g/2005", "phoneNumber");
    foreach ($phoneNumbers as $phoneNumber) {
        $newPhoneElement = $doc->createElementNS("http://schemas.google.com/g/2005", "phoneNumber");

        $isMobile = in_array(substr($phoneNumber, 0, 3), ["070", "072", "073", "076", "079"]);
        $type = $isMobile ? "http://schemas.google.com/g/2005#mobile" : "http://schemas.google.com/g/2005#home";
        $newPhoneElement->textContent = $phoneNumber;
        $newPhoneElement->setAttribute("uri", "tel:+46-" . ($phoneNumber[0] == "0" ? substr($phoneNumber, 1) : $phoneNumber));
        $newPhoneElement->setAttribute("rel", $type);

        $entryNode->appendChild($newPhoneElement);
//                            $log[] = sprintf("L&auml;gger till telefon %s", $phone);
    }
    $phoneNumbersAfter = getAllXpathMatches($doc, 'gd:phoneNumber/@uri', $entryNode);

    sort($phoneNumbersBefore);
    sort($phoneNumbersAfter);
    $phoneNumbersUpdated = implode(",", $phoneNumbersBefore) != implode(",", $phoneNumbersAfter);
    return $phoneNumbersUpdated;
}

if (isset($client)) {
    if ($action == 'sync-contacts') {
        ?>
        <div class="row">
            <div class="col-xs-12">
                <p>Börja med att välja hur du vill att kontakternas namn och anteckningar ska sparas i din
                    kontaktlista.</p>
                <p>Oavsett vad du väljer så kommer alla kontakter läggas i en grupp av kontakter som heter "Nacka
                    Equmenia".</p>
            </div>
        </div>
        <?php
        $sampleIndexes = array_keys($entries);
        shuffle($sampleIndexes);
        foreach ($patternsOptions as $key => $options) {
            ?>
            <div class="row">
                <div class="col-xs-12">
                    <div class="checkbox">
                        <label>
                            <input type="radio" name="sync-contacts-template" value="<?= $key ?>">
                            <?= $key ?>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-offset-2 col-xs-10">
                    <small>
                        <small>
                            <p>Exempel på hur kontakternas information kommer att se ut med den här mallen:</p>
                            <table class="table table-bordered table-condensed ">
                                <thead>
                                <tr>
                                    <th>Fullständigt namn</th>
                                    <th>Visningsnamn</th>
                                    <th>Anteckning</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $samples = getIndividualContacts(array($entries[$sampleIndexes[0]], $entries[$sampleIndexes[1]], $entries[$sampleIndexes[2]]), $options);
                                foreach ($samples as $firstKey => $sample) {
                                    $firstDisplayNameKey = array_keys($sample['displayName'])[0];
                                    printf('<tr><td>%s</td><td>%s</td><td>%s</td></tr>', $sample['name'], $sample['displayName'][$firstDisplayNameKey], join('', $sample['notes']));
                                }
                                ?>
                                </tbody>
                            </table>
                        </small>
                    </small>
                </div>
            </div>
            <?php
        }
    } else {
        ?>
        <?= sprintf('<input type="hidden" name="%s" value="%s">', 'sync-contacts-template', $_POST["" . 'sync-contacts-template' . ""]) ?>
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

                    $contacts = [];//getGoogleContacts($client);

                    $googleContactsXml = getGoogleContactsXml($client);
                    $googleContactGroupsXml = getGoogleContactGroupsXml($client);
                    //                print_r($googleContactGroupsXml);
                    $globalContactsGroupId = getElementText($googleContactGroupsXml, "a:entry[gContact:systemGroup/@id = 'Contacts']/a:id");
                    $appContactsGroupId = getElementText($googleContactGroupsXml, "a:entry[gd:extendedProperty/@name='http://www.nackasmu.se/schemas/medlemsregister#contact-group-id']/a:id");
                    if ($appContactsGroupId == null) {
                        $createGroupRequestBodyDocument = new DOMDocument();
                        $createGroupRequestBodyDocument->load("google-sync-contacts-creategroup.xml");
                        list($appContactsGroupId, $createGroupError) = createGoogleContactGroup($createGroupRequestBodyDocument, "temp/_contacts-creategroup", $client);
                    }

                    $patterns = $patternsOptions[$_POST['sync-contacts-template']];
                    $desiredContacts = getIndividualContacts($entries, $patterns);

                    /**
                     * @param $client
                     * @param $uri
                     * @return array
                     */
                    function readGoogleContact($client, $uri)
                    {
                        $response = $client->get($uri, [
                            'headers' => ['GData-Version' => '3.0']
                        ]);

                        $success = $response->getStatusCode() == 200;
                        if ($success) {
                            $body = $response->getBody();
                            return array($body, null);
                        } else {
                            $message = $response->getReasonPhrase();
                            return array(null, $message);
                        }
                    }

                    $loopStart = time();
                    $loopIterations = 0;
                    foreach ($desiredContacts as $contactId => $desiredContact) {
                        $fullName = $desiredContact['name'];
//                    $displayName = implode(", ", array_unique($desiredContact['displayName']));
                        $phoneNumbers = array_unique($desiredContact['phone']);
                        $emailAddresses = array_unique($desiredContact['email']);
                        $addressStreet = $desiredContact['addressStreet'];
                        $addressPostal = $desiredContact['addressPostal'];
                        $notes = $desiredContact['notes'];

                        $entryPostCode = preg_replace('/[^0-9]/', '', $addressStreet);
                        $entryPostArea = trim(preg_replace('/[0-9]/', '', $addressPostal));

                        printf('<tr><td colspan="4"><strong>%s</strong></td></tr>', $fullName);

                        foreach (array_unique($desiredContact['displayName']) as $displayNameId => $displayName) {
                            $modified = false;

//                        $displayNameId = "$contactId-$displayNameId";
                            $existingContactElement = getFirstXpathMatch($googleContactsXml, "" .
                                "a:entry[" .
                                "gd:extendedProperty[@name='http://www.nackasmu.se/schemas/medlemsregister#contact-id' and @value = '" . $contactId . "'] and " .
                                "gd:extendedProperty[@name='http://www.nackasmu.se/schemas/medlemsregister#displayname-id' and @value = '" . $displayNameId . "']" .
                                "]");

                            $isAlreadyInContactList = $existingContactElement != null;// count($matchingContacts) > 0;
//                        $existingContactName = $isAlreadyInContactList ? getElementText($googleContactsXml, 'gd:name/gd:fullName/text()', $existingContactElement) : "";
//                        $existingContactEmail = $isAlreadyInContactList ? getElementText($googleContactsXml, 'gd:email/@address', $existingContactElement) : "";
//                        $existingContactPhone = $isAlreadyInContactList ? getElementText($googleContactsXml, 'gd:phoneNumber/text()', $existingContactElement) : "";
                            $existingContactSelfHref = $isAlreadyInContactList ? getElementText($googleContactsXml, "a:link[@rel='self']/@href", $existingContactElement) : "";
                            $existingContactEditHref = $isAlreadyInContactList ? getElementText($googleContactsXml, "a:link[@rel='edit']/@href", $existingContactElement) : "";

                            $log = [];
                            $isExistingGoogleContact = !empty($existingContactEditHref);
                            $fileName = "temp/$displayNameId";

                            $doc = new DOMDocument();
                            if ($isExistingGoogleContact) {
                                list($body, $message) = readGoogleContact($client, $existingContactSelfHref);
                                if ($body != null) {
                                    $doc->loadXML($body);
                                } else {
                                    printf('<tr><td colspan="4"><span class="label label-warning">%s</span></td></tr>', $message);
                                    continue;
                                }
//                            $doc->save("$fileName-loaded.xml");
                            } else {
                                // Create new contact
                                $doc->load("google-sync-contacts-empty.xml");
                            }


                            $entryNode = $doc->getElementsByTagNameNS("http://www.w3.org/2005/Atom", "entry")->item(0);

                            $modified |= setEmailAddresses($doc, $entryNode, $emailAddresses, $log);

                            $modified |= setPhoneNumbers($doc, $entryNode, $phoneNumbers, $log);

                            $modified |= setNodeValue($doc, $displayName, "gd:name/gd:fullName");

                            $spacePos = strpos($fullName, ' ');
                            if ($spacePos !== false) {
                                $modified |= setNodeValue($doc, substr($fullName, 0, $spacePos), "gd:name/gd:givenName");
                                $modified |= setNodeValue($doc, substr($fullName, $spacePos + 1), "gd:name/gd:familyName");
                            } else {
                                $modified |= setNodeValue($doc, $fullName, "gd:name/gd:givenName");
                                $modified |= setNodeValue($doc, "", "gd:name/gd:familyName");
                            }

                            $modified |= setNodeValue($doc, implode(". ", $notes), "a:content");

                            $modified |= setNodeValue($doc, $addressStreet, "gd:structuredPostalAddress/gd:street");
                            $modified |= setNodeValue($doc, $entryPostArea, "gd:structuredPostalAddress/gd:city");
                            $modified |= setNodeValue($doc, $entryPostCode, "gd:structuredPostalAddress/gd:postcode");
                            $modified |= setNodeValue($doc, $addressStreet . ", " . $addressPostal, "gd:structuredPostalAddress/gd:formattedAddress");

                            $modified |= setNodeValue($doc, $contactId, "gd:extendedProperty[@name='http://www.nackasmu.se/schemas/medlemsregister#contact-id']/@value");
                            $modified |= setNodeValue($doc, $displayNameId, "gd:extendedProperty[@name='http://www.nackasmu.se/schemas/medlemsregister#displayname-id']/@value");

                            $modified |= setNodeValue($doc, $globalContactsGroupId, "gContact:groupMembershipInfo[@href='ALL_CONTACTS_GROUP_ID']/@href");
                            $modified |= setNodeValue($doc, $appContactsGroupId, "gContact:groupMembershipInfo[@href='APP_CONTACTS_GROUP_ID']/@href");

                            $isDryrun = $action != 'sync-contacts-do';

                            $processingResult = [];
                            if ($modified) {
                                if ($isAlreadyInContactList) {
                                    $updater = $isDryrun ? $updateContactDryrun : $updateContact;
                                    $processingResult = $updater($doc, $existingContactEditHref, $fileName, $client);
                                } else {
                                    $creator = $isDryrun ? $createContactDryrun : $createContact;
                                    $processingResult = $creator($doc, $fileName, $client);
                                }
                                list($success, $message) = $processingResult;
                                $result = $isDryrun ? "default" : ($success ? "success" : "warning");
                            } else {
                                $result = "default";
                                $message = "Inga f&ouml;r&auml;ndringar";
                            }

                            printf('<tr><td>%s <!--<br><small><small>%s</small></small>--></td><td>%s</td><td>%s</td><td><span class="label label-%s">%s</span></td></tr>',
                                $displayName,
                                count($log) > 0 ? implode("<br>", $log) : "",
                                implode("<br>", $emailAddresses),
                                implode("<br>", $phoneNumbers),
                                $result,
                                $message
                            );
                        }

                        $loopIterations++;
                        $avgLoopIterationTime = 1.0 * (time() - $loopStart) / $loopIterations;
                        $estimatedNextLoopEndTime = time() + $avgLoopIterationTime;

//                    print_r([$loopIterations, $avgLoopIterationTime, $estimatedNextLoopEndTime]);

                        if ($abortTime < $estimatedNextLoopEndTime) {
                            // Given the average execution time of a loop iteration, we should abort now in order to not
                            // exceed the maximum script execution time.
                            printf('<tr><td colspan="4"><span class="label label-warning">Oj, det här verkar ta lite för lång tid. Alla kontakter ovan har tagits om hand men för att slutföra synkroniseringen så måste du göra det här steget en gång till.</span><br><button type="submit" name="action" value="sync-contacts-do" class="btn btn-primary">Gör det här steget en gång till</button></td></tr>');
                            break;
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php } ?>

    <?php if ($action == 'sync-contacts') { ?>
        <div>
            <button type="submit" name="action" value="" class="btn btn-default btn-sm">Tillbaka</button>
            <button type="submit" name="action" value="sync-contacts-preview" class="btn btn-primary">Förhandsgranska
            </button>
        </div>
    <?php } elseif ($action == 'sync-contacts-preview') { ?>
        <div>
            <button type="submit" name="action" value="sync-contacts" class="btn btn-default btn-sm">Tillbaka</button>
            <button type="submit" name="action" value="sync-contacts-do" class="btn btn-primary">Synka</button>
        </div>
    <?php } elseif ($action == 'sync-contacts-do') { ?>
        <div>
            <button type="submit" name="action" value="sync-contacts-preview" class="btn btn-default btn-sm">Tillbaka
            </button>
            <button type="submit" name="action" value="" class="btn btn-primary">Stäng</button>
        </div>
    <?php } ?>

<?php } else { ?>
    <div class="row">
        <div class="col-xs-12">
            <p>För att kunna använda den här funktionen måste du först
                <a href="auth-google.php?action=sync-contacts">logga in med Google</a>.</p>
        </div>
    </div>
    <div>
        <button type="submit" name="action" value="sync-contacts" class="btn btn-default btn-sm">Tillbaka</button>
    </div>
<?php } ?>

