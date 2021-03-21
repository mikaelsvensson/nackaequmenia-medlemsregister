<?php
function db_connect()
{
    $dbh = new PDO('sqlite:' . __DIR__ . '/../db.sqlite3', 'sa', '');

    db_migrate($dbh);

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $dbh;
}

function db_generate_id()
{
    return bin2hex(random_bytes(10));
}

function db_migrate(PDO $dbh)
{
    $processed_migrations = [];
    $db_migrations_result = $dbh->query('SELECT * FROM db_migrations');
    if ($db_migrations_result === false) {
        $init_table_result = $dbh->exec('CREATE TABLE db_migrations (id VARCHAR(100) PRIMARY KEY)');
        if ($init_table_result === false) {
            die('Could not create table for database migrations.');
        }
    } else {
        foreach ($db_migrations_result as $row) {
            $processed_migrations[] = $row['id'];
        }
    }
    $migration_scripts = array_filter(scandir(__DIR__ . '/../db'), function ($path) {
        return substr($path, -4) === '.sql';
    });
    foreach ($migration_scripts as $migration_script) {
        if (in_array($migration_script, $processed_migrations)) {
            continue;
        }
        $raw_migration_file = file_get_contents(__DIR__ . "/../db/$migration_script");
        $statements = array_filter(array_map('trim', explode(';', $raw_migration_file)));
        foreach ($statements as $statement) {
            $statement_result = $dbh->exec($statement);
            if ($statement_result === false) {
                die('Could not run ' . $statement);
            }
        }
        $stmt = $dbh->prepare("INSERT INTO db_migrations (id) VALUES (:id)");
        $stmt->bindParam(':id', $migration_script);
        if ($stmt->execute() === false) {
            die('Failed to record migration.');
        }
    }
}

/**
 * @param PDO $dbh
 * @param string $sql_where
 * @param array $sql_params
 * @return array
 */
function db_get_people(PDO $dbh, string $sql_where = '', array $sql_params = []): array
{
    $stmt = $dbh->prepare('SELECT * FROM people AS p LEFT JOIN people_properties AS props ON p.person_id = props.person_id ' . (!empty($sql_where) ? " WHERE " . $sql_where : '') . ' ORDER BY p.person_id, created_at DESC');
    foreach ($sql_params as $param => $value) {
        $stmt->bindValue(":" . $param, $value);
    }
    $stmt->execute();
    $all_people = $stmt->fetchAll(PDO::FETCH_OBJ);

    $last_id = null;
    $people = [];
    foreach ($all_people as $person) {
        if ($person->person_id != $last_id) {
            $people[] = $person;
        }
        $last_id = $person->person_id;
    }
    usort($people, function ($a, $b) {
        return strcmp($a->first_name, $b->first_name);
    });
    return $people;
}

/**
 * @param PDO $dbh
 * @param string $person_id
 * @return object
 */
function db_get_person(PDO $dbh, string $person_id)
{
    return current(db_get_people($dbh, 'p.person_id = :person_id', ['person_id' => $person_id]));
}

/**
 * @param PDO $dbh
 * @param string $person_id
 * @return object
 */
function db_get_children(PDO $dbh, string $person_id): array
{
    return db_get_people($dbh, 'p.person_id IN (SELECT subselect.person_id FROM people_properties AS subselect WHERE subselect.guardian_1_person_id = :person_id OR subselect.guardian_2_person_id = :person_id)', ['person_id' => $person_id]);
}

function db_create_person(PDO $dbh, string $pno): string
{
    $stmt = $dbh->prepare("INSERT INTO people (person_id, pno) VALUES (:person_id, :pno)");
    $person_id = db_generate_id();
    $stmt->bindValue(':person_id', $person_id);
    $stmt->bindValue(':pno', !empty($pno) ? $pno : null);
    if ($stmt->execute() === false) {
        die('Failed to save data about person.');
    }
    return $person_id;
}

function db_set_person_props(PDO $dbh, string $person_id, array $new_props)
{
    $current_props = get_object_vars(db_get_person($dbh, $person_id));

    $next_props = [];
    $allowed_props = [
        'first_name',
        'sur_name',
        'other_names',
        'phone',
        'email',
        'source',
        'guardian_1_person_id',
        'guardian_2_person_id'
    ];
    foreach ($allowed_props as $prop) {
        $next_props[$prop] = isset($new_props[$prop]) ? $new_props[$prop] : ($current_props !== false ? $current_props[$prop] : null);
    }
    $next_props['person_id'] = $person_id;
    $next_props['created_at'] = time();

    error_log('😐 Current values for ' . $person_id . ': ' . var_export($current_props, true));
    error_log('😐 New values for ' . $person_id . ': ' . var_export($next_props, true));

    $stmt = $dbh->prepare(
        sprintf(
            "INSERT INTO people_properties (%s) VALUES (%s)",
            join(', ', array_keys($next_props)),
            join(', ', array_map(function ($prop) {
                return ':' . $prop;
            }, array_keys($next_props)))
        )
    );
    foreach ($next_props as $prop => $value) {
        $stmt->bindValue(':' . $prop, $value);
    }
    if ($stmt->execute() === false) {
        die('Failed to save data about person.');
    }

}