<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once 'google-util.php';

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
//    $doc->save("temp/_feed.cache.xml");
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
//    $doc->save("temp/_groups.cache.xml");
//    }
    return $doc;
}

$updateContactDryrun = function ($requestBody, $requestUri, $logFileName, $client) {
//    $requestBody->save("$logFileName-update.preview.xml");
    return [true, "Existerande kontakt kommer att uppdateras"];
};

$createContactDryrun = function ($requestBody, $logFileName, $client) {
//    $requestBody->save("$logFileName-create.preview.xml");
    return [true, "Ny kontakt kommer att skapas"];
};

$createContact = function ($requestBodyDocument, $logFileName, $client) {
    $requestBody = $requestBodyDocument->saveXML();

//    file_put_contents("$logFileName-create.requestbody.xml", $requestBody);

    $response = $client->post("https://www.google.com/m8/feeds/contacts/default/full", [
        'headers' => [
            'GData-Version' => '3.0',
            'Content-Type' => 'application/atom+xml'
        ],
        'body' => $requestBody
    ]);

//    file_put_contents("$logFileName-create.log", print_r($response, true));
//    file_put_contents("$logFileName-create.responsebody.xml", $response->getBody());

    if ($response->getStatusCode() != 201) {
        return [false, "Fel uppstod: " . $response->getReasonPhrase()];
    }
    return [true, "Skapad"];
};

function createGoogleContactGroup($requestBodyDocument, $logFileName, $client)
{
    $requestBody = $requestBodyDocument->saveXML();

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

function getIndividualContacts($entries)
{
    $desiredContacts = [];
    foreach ($entries as $entry) {

        $opts = [
            [
                $entry[name],
                $entry[name],
                $entry[address_street],
                $entry[address_postal],
                $entry[phone],
                $entry[email],
                (empty($entry[allergies]) ? false : "Allergier: " . $entry[allergies] . ". ") .
                (empty($entry[note]) ? false : $entry[note] . ". ") .
                "Kontakter: " . $entry[guardian_1_name] . "," . $entry[guardian_2_name],
                getSimpleId($entry[name]) . "-self"
            ],
            [
                $entry[guardian_1_name],
                sprintf('%s, kontakt %s', $entry[name], strtok($entry[guardian_1_name], ' ')),
                $entry[guardian_1_address_street],
                $entry[guardian_1_address_postal],
                $entry[guardian_1_phone],
                $entry[guardian_1_email],
                "Kontakt för " . $entry[name],
                getSimpleId($entry[name]) . "-guardian-1"
            ],
            [
                $entry[guardian_2_name],
                sprintf('%s, kontakt %s', $entry[name], strtok($entry[guardian_2_name], ' ')),
                $entry[guardian_2_address_street],
                $entry[guardian_2_address_postal],
                $entry[guardian_2_phone],
                $entry[guardian_2_email],
                "Kontakt för " . $entry[name],
                getSimpleId($entry[name]) . "-guardian-2"
            ]
        ];
        foreach ($opts as $opt) {
            list($fullName, $displayName, $addressStreet, $addressPostal, $phone, $email, $note, $displayNameId) = $opt;
            $contactId = getSimpleId($fullName);
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
        $res->nodeValue = $text;
    }
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


?>
<div class="row">
    <div class="col-xs-12">
        <h1>Synka med Google-adressbok</h1>

        <p>Använd den här funktionen för att uppdatera din Google-adressbok med kontaktinformation.</p>
    </div>
</div>

<?php if (isset($client)) { ?>
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

                //                print_r($appContactsGroupId);

                $desiredContacts = getIndividualContacts($entries);

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

                    $entryGivenName = substr($fullName, 0, strpos($fullName, ' '));
                    $entryFamilyName = substr($fullName, strpos($fullName, ' ') + 1);
                    $entryPostCode = preg_replace('/[^0-9]/', '', $addressStreet);
                    $entryPostArea = trim(preg_replace('/[0-9]/', '', $addressPostal));

                    printf('<tr><td colspan="4"><strong>%s</strong></td></tr>', $fullName);

                    foreach (array_unique($desiredContact['displayName']) as $displayNameId => $displayName) {

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
                        removeChildren($entryNode, $log, "http://schemas.google.com/g/2005", "phoneNumber");
                        removeChildren($entryNode, $log, "http://schemas.google.com/g/2005", "email");

                        foreach ($emailAddresses as $emailAddress) {
                            $newEmailElement = $doc->createElementNS("http://schemas.google.com/g/2005", "email");

                            $newEmailElement->setAttribute("address", $emailAddress);
                            $newEmailElement->setAttribute("rel", "http://schemas.google.com/g/2005#other");

                            $entryNode->appendChild($newEmailElement);
//                            $log[] = sprintf("L&auml;gger till e-post %s", $email);
                        }

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

//                        setNodeValue($doc, $displayName, "a:title");
//                        setNodeValue($doc, $fullName, "gd:name/gd:fullName");
                        setNodeValue($doc, $displayName, "gd:name/gd:fullName");
                        setNodeValue($doc, substr($fullName, 0, strpos($fullName, ' ')), "gd:name/gd:givenName");
                        setNodeValue($doc, substr($fullName, strpos($fullName, ' ') + 1), "gd:name/gd:familyName");

                        setNodeValue($doc, implode(". ", $notes), "a:content");

                        setNodeValue($doc, $addressStreet, "gd:structuredPostalAddress/gd:street");
                        setNodeValue($doc, $entryPostArea, "gd:structuredPostalAddress/gd:city");
                        setNodeValue($doc, $entryPostCode, "gd:structuredPostalAddress/gd:postcode");
                        setNodeValue($doc, $addressStreet . ", " . $addressPostal, "gd:structuredPostalAddress/gd:formattedAddress");

                        setNodeValue($doc, $contactId, "gd:extendedProperty[@name='http://www.nackasmu.se/schemas/medlemsregister#contact-id']/@value");
                        setNodeValue($doc, $displayNameId, "gd:extendedProperty[@name='http://www.nackasmu.se/schemas/medlemsregister#displayname-id']/@value");

                        setNodeValue($doc, $globalContactsGroupId, "gContact:groupMembershipInfo[@href='ALL_CONTACTS_GROUP_ID']/@href");
                        setNodeValue($doc, $appContactsGroupId, "gContact:groupMembershipInfo[@href='APP_CONTACTS_GROUP_ID']/@href");

                        $isDryrun = $action != 'sync-contacts-do';
                        $processingResult = [];
                        if ($isAlreadyInContactList) {
                            $updater = $isDryrun ? $updateContactDryrun : $updateContactDryrun;
                            $processingResult = $updater($doc, $existingContactEditHref, $fileName, $client);
                        } else {
                            $creator = $isDryrun ? $createContactDryrun : $createContact;
                            $processingResult = $creator($doc, $fileName, $client);
                        }

                        list($success, $message) = $processingResult;

                        printf('<tr><td>%s <!--<br><small>%s</small>--></td><td>%s</td><td>%s</td><td><span class="label label-%s">%s</span></td></tr>',
                            $displayName,
                            count($log) > 0 ? implode("<br>", $log) : "",
                            implode("<br>", $emailAddresses),
                            implode("<br>", $phoneNumbers),
                            $isDryrun ? "default" : ($success ? "success" : "warning"),
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

    <?php if ($action == 'sync-contacts') { ?>
        <div>
            <button type="submit" name="action" value="" class="btn btn-default btn-sm">Tillbaka</button>
            <button type="submit" name="action" value="sync-contacts-do" class="btn btn-primary">Synka</button>
        </div>
    <?php } elseif ($action == 'sync-contacts-do') { ?>
        <div>
            <button type="submit" name="action" value="sync-contacts" class="btn btn-default btn-sm">Tillbaka</button>
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
