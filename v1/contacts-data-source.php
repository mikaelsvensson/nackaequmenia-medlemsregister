<?php
error_reporting(E_ALL);

require_once 'ContactsDataSource.php';

$dataSources = array(
    "internal" => new \contacts\InternalContactsDataSource(),
    "external" => new \contacts\ExternalContactsDataSource(),
    "merged" => new \contacts\MergedContactDataSource()
);
?>
<html>
<body>
<?php

if (isset($dataSources[$_GET["data_source"]])) {
    $contact_props = get_class_vars(get_class(new contacts\Contact()));
    printf('<table border="1"><tbody>');
    printf('<tr>');
    print join(array_map(function ($prop) {
        return "<td>$prop</td>";
    }, array_keys($contact_props)));
    printf('</tr>');
    $contacts = $dataSources[$_GET["data_source"]]->getEntries();

    foreach ($contacts as $contact) {
        printf('<tr>');
        foreach (array_keys($contact_props) as $prop) {
            printf('<td>%s</td>', $contact->$prop);
        }
        printf('</tr>');
    }
    printf('</tbody></table>');
} else {
    printf('<p>Unknown data source. Specify one of these instead: data_source={%s}</p>', join(",", array_keys($dataSources)));
}
?>
</body>
</html>
