<?php
require_once 'core.php';

require_once 'core-google-data.php';

include 'core-page-start.php';

$entries = google_data_get();
?>
<form action="google.php" method="post">
    <?php
    switch($_POST['action']) {
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
