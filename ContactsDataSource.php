<?php
namespace contacts;

require_once 'core-google-data.php';

class Contact
{
    public $name;
    public $troupe;
    public $ssn;
    public $age;
    public $grade;
    public $quit;
    public $email;
    public $phone_mobile;
    public $phone;
    public $address_street;
    public $address_postal;
    public $allergies;
    public $note;
    public $guardian_1_name;
    public $guardian_1_email;
    public $guardian_1_phone;
    public $guardian_1_phone_mobile;
    public $guardian_1_address_street;
    public $guardian_1_address_postal;
    public $guardian_2_name;
    public $guardian_2_email;
    public $guardian_2_phone;
    public $guardian_2_phone_mobile;
    public $guardian_2_address_street;
    public $guardian_2_address_postal;
    public $model_release_nacka_equmenia;
    public $model_release_scout_material;
    public $model_release_photographer;
    public $model_release_internet;
    public $model_release_name;
    public $id;
    public $name_given;
    public $guardian_1_name_given;
    public $guardian_2_name_given;
    public $name_surname_initial;
}

abstract class ContactsDataSource
{
    private $entries = null;

    /**
     * ContactsDataSource constructor.
     */
    public function __construct()
    {
    }

    function sanitize_phone_number($value)
    {
        $value = str_replace(['-', ' '], '', $value);
        $value = str_replace(['0046', '+46'], '0', $value);
        if (preg_match('/^[1-9][0-9]+$/', $value) == 1) {
            // Value starts with a non-zero digit. Add the zero.
            $value = "0$value";
        }
        // TODO: DRY... This list of prefixes is very similar to the list of mobile-phone prefixes.
        foreach (["08", "070", "072", "073", "076", "079"] as $prefix) {
            if (substr($value, 0, strlen($prefix)) == $prefix) {
                $value = $prefix . '-' . substr($value, strlen($prefix));
            }
        }
        return $value;
    }

    function getEntries()
    {
        if ($this->entries == null) {
            $entries = $this->loadEntries();

            $sort_by_name = function ($a, $b) {
                if ($a->name == $b->name) {
                    return 0;
                }
                return ($a->name < $b->name) ? -1 : 1;
            };

            usort($entries, $sort_by_name);

            foreach ($entries as $entry) {
                $entry->name_given = strtok($entry->name, ' ');
                $entry->name_surname_initial = strtoupper(preg_replace('/^\w+\s/i', '', $entry->name)[0]);
                $entry->guardian_1_name_given = strtok($entry->guardian_1_name, ' ');
                $entry->guardian_2_name_given = strtok($entry->guardian_2_name, ' ');
            }

            $this->entries = $entries;
        }
        return $this->entries;
    }

    abstract protected function loadEntries();

    protected function loadCsvFile($url)
    {
        $entries = array();
        $cacheFile = sys_get_temp_dir() . '/' . md5($url);
        if (!file_exists($cacheFile) || filemtime($cacheFile) < time() - CACHE_EXPIRATION_SECONDS) {
            file_put_contents($cacheFile, fopen($url, 'r'));
        }

        if (($handle = fopen($cacheFile, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
                $entries[] = array_map(function ($v) {
                    return mb_convert_encoding($v, mb_internal_encoding(), 'UTF-8');
                }, $data);
            }
            fclose($handle);
        }
        return $entries;
    }

}

class ExternalContactsDataSource extends ContactsDataSource
{

    protected function loadEntries()
    {
        $entries = array_slice(
            $this->loadCsvFile('https://docs.google.com/spreadsheets/d/1zCXwCl4420FiTEmpOjC3ObW8U3e-GXP8QyUNIWJStSQ/pub?gid=357434992&single=true&output=tsv'),
            1);

        $contacts = array_map(function ($entry) {
            $contact = new Contact();
            list (,
                $contact->name,
                $contact->ssn,
                $contact->email,
                $contact->phone_mobile,
                $contact->address_street,
                $contact->allergies,
                $contact->guardian_1_name,
                $contact->guardian_1_email,
                $contact->guardian_1_phone_mobile,
                $contact->guardian_1_phone,
                $contact->guardian_1_address_street,
                $contact->guardian_2_name,
                $contact->guardian_2_email,
                $contact->guardian_2_phone_mobile,
                $contact->guardian_2_phone,
                $contact->guardian_2_address_street,
                $contact->model_release_nacka_equmenia,
                $contact->model_release_scout_material,
                $contact->model_release_photographer,
                ,
                $contact->model_release_internet,
                $contact->model_release_name,
                $contact->address_postal,
                $contact->guardian_1_address_postal,
                $contact->guardian_2_address_postal,
                $contact->id,
                ) = $entry;

            $contact->phone = $this->sanitize_phone_number($contact->phone);
            $contact->phone_mobile = $this->sanitize_phone_number($contact->phone_mobile);
            $contact->guardian_1_phone = $this->sanitize_phone_number($contact->guardian_1_phone);
            $contact->guardian_1_phone_mobile = $this->sanitize_phone_number($contact->guardian_1_phone_mobile);
            $contact->guardian_2_phone = $this->sanitize_phone_number($contact->guardian_2_phone);
            $contact->guardian_2_phone_mobile = $this->sanitize_phone_number($contact->guardian_2_phone_mobile);

            $contact->email = strtolower($contact->email);
            $contact->guardian_1_email = strtolower($contact->guardian_1_email);
            $contact->guardian_2_email = strtolower($contact->guardian_2_email);

            return $contact;
        }, $entries);

        $most_recent_contacts = array_values(array_combine(array_map(function ($contact) {
            return $contact->name;
        }, $contacts), $contacts));

        return $most_recent_contacts;
    }
}

class InternalContactsDataSource extends ContactsDataSource
{
    protected function loadEntries()
    {
        $entries = $this->loadCsvFile("https://docs.google.com/spreadsheets/d/1Kr2X17DX5N9MQvNFXLjrfuVTAh6Q7HcxALxkfmBwZJ4/pub?gid=0&single=true&output=tsv");

        $contacts = array_map(function ($entry) {
            $contact = new Contact();

            list ($contact->name,
                $contact->troupe,
                $contact->ssn,
                $contact->age,
                $contact->grade,
                $contact->quit,
                $contact->email,
                $contact->phone_mobile,
                $contact->phone,
                $contact->address_street,
                $contact->address_postal,
                $contact->allergies,
                $contact->note,
                $contact->guardian_1_name,
                $contact->guardian_1_email,
                $contact->guardian_1_phone,
                $contact->guardian_1_address_street,
                $contact->guardian_1_address_postal,
                $contact->guardian_2_name,
                $contact->guardian_2_email,
                $contact->guardian_2_phone,
                $contact->guardian_2_address_street,
                $contact->guardian_2_address_postal,
                $contact->model_release_nacka_equmenia,
                $contact->model_release_scout_material,
                $contact->model_release_photographer,
                $contact->model_release_internet,
                $contact->model_release_name,
                $contact->id) = $entry;

            $contact->model_release_nacka_equmenia = tristate_form_value($contact->model_release_nacka_equmenia);
            $contact->model_release_scout_material = tristate_form_value($contact->model_release_scout_material);
            $contact->model_release_photographer = tristate_form_value($contact->model_release_photographer);
            $contact->model_release_internet = tristate_form_value($contact->model_release_internet);
            $contact->model_release_name = tristate_form_value($contact->model_release_name);

            $contact->phone = $this->sanitize_phone_number($contact->phone);
            $contact->phone_mobile = $this->sanitize_phone_number($contact->phone_mobile);
            $contact->guardian_1_phone = $this->sanitize_phone_number($contact->guardian_1_phone);
            $contact->guardian_2_phone = $this->sanitize_phone_number($contact->guardian_2_phone);

            $contact->email = strtolower($contact->email);
            $contact->guardian_1_email = strtolower($contact->guardian_1_email);
            $contact->guardian_2_email = strtolower($contact->guardian_2_email);

            return $contact;
        }, $entries);

        $filter_only_active = function ($entry) {
            return strlen(trim($entry->quit)) == 0;
        };
        $contacts = array_filter($contacts, $filter_only_active);

        $filter_has_name = function ($entry) {
            return strlen(trim($entry->name)) > 0;
        };
        $contacts = array_filter($contacts, $filter_has_name);

        return $contacts;
    }
}

class MergedContactDataSource extends ContactsDataSource
{

    private $ext_ds;
    private $int_ds;
    private $props;

    public function __construct()
    {
        $this->ext_ds = new ExternalContactsDataSource();
        $this->int_ds = new InternalContactsDataSource();
        $this->props = array_keys(get_class_vars(get_class(new Contact())));
    }

    function is_valid_ssn($ext_value)
    {
        return preg_match('/^[0-9]{6,8}-?[0-9]{4}$/', $ext_value) == 0;
    }

    protected function loadEntries()
    {
        $ext_contacts = $this->ext_ds->getEntries();
        $int_contacts = $this->int_ds->getEntries();

        $merged_contacts = array_map(function ($int_contact) use ($ext_contacts) {
            $matches = array_filter($ext_contacts, function ($c) use ($int_contact) {
                return $c->id == $int_contact->id;
            });

            if ($matches != null && count($matches) == 1) {
                $ext_contact = reset($matches); // Get first object in array without having to know its index

                foreach ($this->props as $prop) {
                    $ext_value = $ext_contact->$prop;
                    if (!empty($ext_value)) {
                        if ('ssn' == $prop && $this->is_valid_ssn($ext_value)) {
                            // Do not copy value since it's not correctly formatted.
                            continue;
                        }
                        $int_contact->$prop = $ext_value;
                    }
                }
            }

            return $int_contact;
        }, $int_contacts);

        return $merged_contacts;
    }
}