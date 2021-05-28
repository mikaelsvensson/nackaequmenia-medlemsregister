<?php
require_once __DIR__.'/util/invoices.php';
require_once __DIR__.'/util/import.php';

$dbh = db_connect();

$invoices = invoices_get_all($dbh);

$people = db_get_people($dbh);

$dbh = null;

function get_person_basic($person_id) {
    global $people;
    if (isset($person_id)) {
        $person = current(array_filter($people, function ($p) use ($person_id) { return $p->person_id === $person_id; } ));
        if (isset($person)) {
            return [
                'person_id' => $person->person_id,
                'first_name' => $person->first_name,
                'sur_name' => $person->sur_name
            ];
        }
    }
    return null;
}

function get_time_object($timestamp_string) {
    $ts = new DateTime("@${timestamp_string}");
    return [
        'iso_8601' => $ts->format('c'),
        'unix_epoch' => $ts->getTimestamp()
];
}

$data = [
    'people' => array_map(function ($person) use ($people) {
        $result = clone $person;
        $result->pno = import_normalize_pno($result->pno);
        $result->guardian_1 = get_person_basic($result->guardian_1_person_id);
        unset($result->guardian_1_person_id);
        
        $result->guardian_2 = get_person_basic($result->guardian_2_person_id);
        unset($result->guardian_2_person_id);
        
        $result->created_at = get_time_object($result->created_at);
        return $result;
    }, $people),
    'invoices' => array_map(function ($invoice) {
        $result = clone $invoice;
        $result->reference = get_person_basic($result->reference_person_id);
        unset($result->reference_person_id);

        foreach ([
            'is_created',
            'is_ready',
            'is_sent',
            'is_paid',
            'is_invalidated'
        ] as $prop) {
            $result->{$prop} = $result->{$prop} === '1';
        }

        foreach ($result->log as $log) {
            $log->created_at = get_time_object($log->created_at);
            if (isset($log->action_data)){
                $log->action_data = @json_decode($log->action_data, false);
            }
        }
        return $result;
    }, $invoices),
];

if ($_POST['action'] === 'export_json') {
    $file_timestamp = date("Ymd-His");
    header('Content-type: application/json');
    header('Content-disposition: attachment;filename=nackaequmenia-administration-export-'.$file_timestamp.'.json');
    print json_encode($data, JSON_PRETTY_PRINT);
    return;
}

include './backup.html.php';