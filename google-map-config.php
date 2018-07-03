<?php
require_once 'core.php';

include 'core-page-start.php';

require_once 'core-google-data.php';
require_once 'ContactsDataSource.php';


$contactsDataSoure = new \contacts\MergedContactDataSource();
$entries = $contactsDataSoure->getEntries();
//$entries = google_data_get();
?>
<script type="application/javascript">
    function openMap() {
        var markers = [];
        var opts = document.getElementById('people').options;
        for (var i = 0; i < opts.length; i++) {
            if (opts[i].selected) {
                markers.push(opts[i].value);
            }
        }
        var q = markers.join("|");
        console.log(q);
        window.open("google-map-view.html?" + q);
    }
</script>
    <form>
        <div class="form-group">
            <label for="people">Personer</label>
            <select id="people" multiple="multiple" class="form-control" size="30">
                <?php foreach ($entries as $key => $entry) { ?>
                    <?php printf('<option value="%s">%s, %s, %s</option>',
                        rawurlencode($entry->name . ', ' . $entry->address_street . ';' . $entry->address_street . ',' . $entry->address_postal . ",SWEDEN;;"),
                        $entry->name,
                        $entry->address_street,
                        $entry->address_postal) ?>
                <?php } ?>
            </select>
        </div>
        <div class="form-group">
            <input id="submit" class="btn btn-default" type="button" value="Ge mig kartan!"
                   onclick="openMap()">
        </div>
    </form>
<?php
include 'core-page-end.php';
?>
