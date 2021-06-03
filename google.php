<?php
require_once 'core.php';

include 'core-page-start.php';

require_once 'ContactsDataSource.php';

$contactsDataSource = new \contacts\MergedContactDataSource();
//$contactsDataSource = new \contacts\InternalContactsDataSource();
$entries = $contactsDataSource->getEntries();
?>
<form action="google.php" method="post">
    <?php
    $action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];
    switch($action) {
        case 'send-links':
        case 'send-links-do':
            include 'google-send-links.php';
        break;
        case 'send-pdf':
        case 'send-pdf-preview':
        case 'send-pdf-template-config':
        case 'send-pdf-do':
            include 'google-send-pdf.php';
        break;
        case 'sync-contacts':
        case 'sync-contacts-preview':
        case 'sync-contacts-do':
            include 'google-sync-contacts.php';
        break;
        case 'show-map':
            include 'google-show-map.php';
        break;
        case 'print-list':
            include 'google-print-list.php';
        break;
        default:
            include 'google-list.php';
            break;
    }
    ?>
</form>
<?php
include 'core-page-end.php';
?>
