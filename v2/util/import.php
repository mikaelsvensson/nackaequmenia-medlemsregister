<?php
require_once __DIR__ . '/db.php';

function import_find_person_by_pno(PDO $dbh, $pno)
{
    return current(array_filter(db_get_people($dbh), function ($person) use ($pno) {
        return import_normalize_pno($person->pno) === import_normalize_pno($pno);
    }));
}

function import_normalize_name($str)
{
    return preg_replace('/[^a-z]/', '', strtolower(trim($str)));
}

function import_normalize_email($str)
{
    return strtolower(trim($str));
}

function import_normalize_phone($str)
{
    return strtolower(trim($str));
}

function import_normalize_pno($str)
{
    $only_digits = preg_replace('/[^0-9]/', '', $str);
    switch (strlen($only_digits)) {
        case 12: //yyyymmddnnnn
            return $only_digits;
        case 10:  //yymmddnnnn
            if (intval(substr($only_digits, 0, 2)) < 70) {
                return '20' . $only_digits;
            } else {
                return '19' . $only_digits;
            }
        default:
            return 'invalid pno';
    }
}

/**
 * @param PDO $dbh
 * @param $file_path
 * @return array
 */
function import_csv(PDO $dbh, $file_path)
{
    if (($handle = fopen($file_path, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            list (
                $pno,
                $phone,
                $email,
                $address_street,
                $address_postarea,
                $medical_note,
                $guardian_1_first_name,
                $guardian_1_sur_name,
                $guardian_1_email,
                $guardian_1_phone,
                $guardian_2_first_name,
                $guardian_2_sur_name,
                $guardian_2_email,
                $guardian_2_phone
                ) = $data;
            if ($pno == 'pno' || empty($pno)) {
                continue;
            }

            $person = import_find_person_by_pno($dbh, $pno);

            if ($person === false) {
                $person_id = db_create_person($dbh, $pno);
                error_log("✨ Creating new person ${person_id} for ${pno}");
            } else {
                $person_id = $person->person_id;
                error_log("😐 Using person ${person_id} for ${pno}");
            }

            foreach ([1, 2] as $num) {
                $guardian_first_name = trim(${"guardian_${num}_first_name"});
                $guardian_sur_name = trim(${"guardian_${num}_sur_name"});
                $guardian_email = trim(${"guardian_${num}_email"});
                $guardian_phone = trim(${"guardian_${num}_phone"});

                if (!empty($guardian_first_name) || !empty($guardian_sur_name) || !empty($guardian_email) || !empty($guardian_phone)) {
                    error_log("👩‍👧 Guardian ${num} is defined");

                    // Does person exist? Is guardianN already set for person? Does at least one of name, email or phone match details for already-existing guardian?
                    // Yes -> modify existing
                    // No -> create new person

                    $current_guardian_id = $person !== false ? $person->{"guardian_${num}_person_id"} : null;
                    $matched_guardian_id = null;
                    if (isset($current_guardian_id) && !empty($current_guardian_id)) {
                        // Pre-existing person with pre-existing guardian
                        $current_guardian = db_get_person($dbh, $current_guardian_id);
                        if ($current_guardian !== false && (
                                import_normalize_email($current_guardian->email) === import_normalize_email($guardian_email) ||
                                import_normalize_phone($current_guardian->phone) === import_normalize_phone($guardian_phone) ||
                                import_normalize_name($current_guardian->first_name . $current_guardian->sur_name) === import_normalize_name($guardian_first_name . $guardian_sur_name)
                            )) {
                            // Pre-existing guardian is _probably_ same as guardian in import file. Don't create new one.
                            $matched_guardian_id = $current_guardian->person_id;
                        }
                    } else {
                        // New person. Look for guardian in database.
                        $matched_guardian = current(array_filter(db_get_people($dbh), function ($person) use ($guardian_email, $guardian_phone, $guardian_first_name, $guardian_sur_name) {
                            return import_normalize_email($person->email) === import_normalize_email($guardian_email) ||
                                import_normalize_phone($person->phone) === import_normalize_phone($guardian_phone) ||
                                import_normalize_name($person->first_name . $person->sur_name) === import_normalize_name($guardian_first_name . $guardian_sur_name);
                        }));
                        if ($matched_guardian !== false) {
                            $matched_guardian_id = $matched_guardian->person_id;
                        }
                    }

                    if ($matched_guardian_id !== null) {
                        $id = $matched_guardian_id;
                        error_log("😐 Using person ${id} as guardian ${num}");
                    } else {
                        $id = db_create_person($dbh, '');

                        error_log("✨ Could not find guardian ${num}. Creating person ${id}.");
                    }

                    db_set_person_props($dbh, $id, [
                        'first_name' => $guardian_first_name,
                        'sur_name' => $guardian_sur_name,
                        'email' => $guardian_email,
                        'phone' => $guardian_phone
                    ]);

                    ${"guardian_${num}_person_id"} = $id;
                } else {
                    ${"guardian_${num}_person_id"} = null;
                }
            }

            error_log("✅ Updating ${person_id}. Guardian 1: $guardian_1_person_id. Guardian 2: $guardian_2_person_id.");

            db_set_person_props($dbh, $person_id, [
                'email' => $email,
                'phone' => $phone,
                'guardian_1_person_id' => $guardian_1_person_id,
                'guardian_2_person_id' => $guardian_2_person_id,
            ]);
        }
        fclose($handle);
    }
}

/**
 * @param PDO $dbh
 * @param $file_path
 * @return array
 */
function import_repet(PDO $dbh, $file_path)
{
    $PREFIX = "base64\r\n\r\n";
    $SUFFIX = "\r\n\r\n";
    $raw = file_get_contents($file_path);
    $start = strpos($raw, $PREFIX) + strlen($PREFIX);
    if ($start === false) {
        die('Not an MHTML file');
    }
    $stop = strpos($raw, $SUFFIX, $start);
    if ($stop === false) {
        die('Not an MHTML file');
    }
    $data = str_replace("\r\n", "", substr($raw, $start, $stop - $start));
    $html = base64_decode($data);
    if ($html === false) {
        die('Base64 decoding failed');
    }

    $doc = new DOMDocument();
    $doc->loadHTML($html);
    $xml = simplexml_import_dom($doc);
    if ($xml === false) {
        die('XML parser failed');
    }

    $xml_people = $xml->xpath('//tr[td/@class="a165cl"]');
    foreach ($xml_people as $xml_person) {
        $name = $xml_person->td[0]->div;
        $phone = $xml_person->td[1]->div;
        $pno = $xml_person->td[2]->div;
        $street_address = $xml_person->td[3]->div;
        $post_code = $xml_person->td[4]->div;
        $post_area = $xml_person->td[5]->div;
        $is_female = $xml_person->td[6]->div == 'Kvinna';
        $is_paid_membership = $xml_person->td[7]->div == 'A';

        $person_row = import_find_person_by_pno($dbh, $pno);
        error_log('🦊 Existing person: ' . var_export($person_row, true));
        if ($person_row === false) {
            $person_id = db_create_person($dbh, $pno);
        } else {
            $person_id = $person_row->person_id;
        }

        list($all_sur_names, $all_first_names) = explode(', ', $name, 2);
        $first_names = explode(' ', $all_first_names, 2);

        $first_name = trim($first_names[0]);
        $sur_name = trim($all_sur_names);
        $other_names = count($first_names) > 1 ? trim($first_names[1]) : null;

        db_set_person_props($dbh, $person_id, [
            'first_name' => $first_name,
            'sur_name' => $sur_name,
            'other_names' => $other_names
        ]);
    }
}
