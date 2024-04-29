<?php

/* functions */
require_once(dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database     = new Database_PDO;
$User         = new User($Database);
$Admin         = new Admin($Database, false);
$Tools         = new Tools($Database);
$Result     = new Result();

# verify that user is logged in
$User->check_user_session();

# perm check popup
if ($_POST['action'] == "edit") {
    $User->check_module_permissions("locations", User::ACCESS_RW, true, false);
} else {
    $User->check_module_permissions("locations", User::ACCESS_RWA, true, false);
}

# check maintaneance mode
$User->check_maintaneance_mode();
# strip input tags
$_POST = $Admin->strip_input_tags($_POST);

# validate csrf cookie
$User->Crypto->csrf_cookie("validate", "location", $_POST['csrf_cookie']) === false ? $Result->show("danger", _("Invalid CSRF cookie"), true) : "";

# validations
if ($_POST['action'] == "add") {
    
    // name
    if (is_blank($_POST['file'])) {
        $Result->show("danger",  _("Name must have at least 1 character " . $_POST['file']), true);
    }
}

# fetch custom fields
/*$custom = $Tools->fetch_custom_fields('documentations');
if (sizeof($custom) > 0) {
    foreach ($custom as $myField) {
        //booleans can be only 0 and 1!
        if ($myField['type'] == "tinyint(1)") {
            if ($_POST[$myField['name']] > 1) {
                $_POST[$myField['name']] = 0;
            }
        }
        //not null!
        if ($myField['Null'] == "NO" && is_blank($_POST[$myField['name']])) { {
                $Result->show("danger", $myField['name'] . " " . _("can not be empty!"), true);
            }
        }
        # save to update array
        $update[$myField['name']] = $_POST[$myField['name']];
    }
}*/

// set values
$values = array(
    "id"          => @$_POST['id'],
    "location_id"        => $location_id,
    "file_name"     => $file_name,
    "category"         => $categoria
);


# custom fields
/*if (isset($update)) {
    $values = array_merge($values, $update);
}*/

# execute update
if (!$Admin->object_modify("locations", $_POST['action'], "id", $values)) {
    $Result->show("danger", _("Location") . " " . $_POST["action"] . " " . _("failed"), false);
} else {
    $Result->show("success", _("Location") . " " . $_POST["action"] . " " . _("successful"), false);
}

