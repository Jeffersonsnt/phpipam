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


if ($_POST['action'] == "upload") {

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Verificar se um arquivo foi enviado
        if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {

            // Obter o ID da localização
            $location_id = isset($_POST['id']) ? $_POST['id'] : null;

            // Verificar se o ID da localização é válido
            if ($location_id !== null) {
                // Mover o arquivo para o diretório desejado
                $upload_dir = '/var/www/phpipam/files/';
                $file_name = $_FILES['file_upload']['name'];
                $file_path = $upload_dir . $file_name;
                $categoria = isset($_POST['categoria']) ? $_POST['categoria'] : 'categoria_padrao'; // Categoria padrão para o arquivo

                if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $file_path)) {

                    // Inserir informações do arquivo no banco de dados
                    // set values
                    $values = array(
                        "id"          => @$_POST['id'],
                        "location_id"        => $location_id,
                        "file_name"     => $file_name,
                        "category"         => $categoria
                    );

                    # execute update
                    if (!$Admin->object_modify("documentations", $_POST['action'], "id", $values)) {
                        $Result->show("danger", _("Documentation") . " " . $_POST["action"] . " " . _("failed"), false);
                    } else {
                        $Result->show("success", _("Documentation") . " " . $_POST["action"] . " " . _("successful"), false);
                    }

                    // Exibir mensagem de sucesso
                    $Result->show("success", "File uploaded successfully!", false);
                } else {
                    // Exibir mensagem de erro
                    echo '<div class="alert alert-danger">Error uploading file!</div>';
                    $Result->show("danger",  "Error uploading file!", true);
                }
            } else {
                // Exibir mensagem de erro se o ID da localização for inválido
                $Result->show("danger",  "Invalid location ID!", true);
            }
        } else {
            // Exibir mensagem de erro se nenhum arquivo for enviado ou ocorrer um erro durante o upload
            $Result->show("danger",  "No file uploaded or error occurred!", true);
        }
    }
} else {
    # validations
    if ($_POST['action'] == "delete" || $_POST['action'] == "edit") {
        if ($Admin->fetch_object('locations', "id", $_POST['id']) === false) {
            $Result->show("danger",  _("Invalid Location object identifier"), false);
        }
    }
    if ($_POST['action'] == "add" || $_POST['action'] == "edit") {
        // name
        if (is_blank($_POST['name'])) {
            $Result->show("danger",  _("Name must have at least 1 character"), true);
        }
        // lat, long
        if ($_POST['action'] !== "delete") {
            // lat
            if (!is_blank($_POST['lat'])) {
                if (!preg_match('/^(\-?\d+(\.\d+)?).\s*(\-?\d+(\.\d+)?)$/', $_POST['lat'])) {
                    $Result->show("danger",  _("Invalid Latitude"), true);
                }
            }
            // long
            if (!is_blank($_POST['long'])) {
                if (!preg_match('/^(\-?\d+(\.\d+)?).\s*(\-?\d+(\.\d+)?)$/', $_POST['long'])) {
                    $Result->show("danger",  _("Invalid Longitude"), true);
                }
            }

            // fetch latlng
            if (is_blank($_POST['lat']) && is_blank($_POST['long']) && !is_blank($_POST['address'])) {
                $OSM = new OpenStreetMap($Database);
                $latlng = $OSM->get_latlng_from_address($_POST['address']);
                if ($latlng['lat'] != NULL && $latlng['lng'] != NULL) {
                    $_POST['lat'] = $latlng['lat'];
                    $_POST['long'] = $latlng['lng'];
                } else {
                    if (!Config::ValueOf('offline_mode')) {
                        $Result->show("warning", _("Failed to update location lat/lng from Nominatim") . ".<br>" . escape_input($latlng['error']), false);
                    }
                }
            }
        }
    }


    # fetch custom fields
    $custom = $Tools->fetch_custom_fields('locations');
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
    }


    // set values
    $values = array(
        "id"          => @$_POST['id'],
        "name"        => $_POST['name'],
        "address"     => $_POST['address'],
        "lat"         => $_POST['lat'],
        "long"        => $_POST['long'],
        "description" => $_POST['description']
    );

    # custom fields
    if (isset($update)) {
        $values = array_merge($values, $update);
    }

    # execute update
    if (!$Admin->object_modify("locations", $_POST['action'], "id", $values)) {
        $Result->show("danger", _("Location") . " " . $_POST["action"] . " " . _("failed"), false);
    } else {
        $Result->show("success", _("Location") . " " . $_POST["action"] . " " . _("successful"), false);
    }

    // remove all references
    if ($_POST['action'] == "delete") {
        $Admin->remove_object_references("circuits", "location1", $values["id"]);
        $Admin->remove_object_references("circuits", "location2", $values["id"]);
        $Admin->remove_object_references("subnets", "location", $values["id"]);
        $Admin->remove_object_references("devices", "location", $values["id"]);
        $Admin->remove_object_references("racks", "location", $values["id"]);
    }
}
