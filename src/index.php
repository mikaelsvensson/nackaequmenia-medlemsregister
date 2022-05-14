<?php
require_once __DIR__.'/util/db.php';
require_once __DIR__.'/util/import.php';

$dbh = db_connect();

//try {
//} catch (PDOException $e) {
//    print "Error!: " . $e->getMessage() . "<br/>";
//    die();
//}

$people = db_get_people($dbh);

$dbh = null;

$people_groups = [
    '8-9' => [],
    '10-12' => [],
    '13-15' => [],
    '16-18' => [],
    '20-25' => [],
    '26-100' => [],
    '?' => []
];

foreach ($people as $person) {
    $group = '?';
    if (isset($person->pno)) {
        $pno = import_normalize_pno($person->pno);
        foreach (array_keys($people_groups) as $range) {
            @list ($min, $max) = explode('-', $range);
            if (isset($min) && isset($max)) {
                $age = intval(date('Y')) - intval(substr($pno, 0, 4));
                if ($min <= $age && $age <= $max) {
                    $group = $range;
                    error_log("${pno} ${min} <= ${age} <= ${max} in group ${group}");
                }
            }
        }
    }
    $people_groups[$group][] = $person;
}

include './index.html.php';