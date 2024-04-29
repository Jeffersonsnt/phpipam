<?php

/**
 *	Print all available locations
 ************************************************/

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
    $User->check_module_permissions("locations", User::ACCESS_RW, true, true);
} else {
    $User->check_module_permissions("locations", User::ACCESS_RWA, true, true);
}

# create csrf token
$csrf = $User->Crypto->csrf_cookie("create", "location");

# validate action
$Admin->validate_action($_POST['action'], true);

// Array de categorias
$categorias = array(
    'WiFi',
    'Disitivos',
    'Rack',
    'Topologia',
    'Docs',
    // Adicione mais categorias conforme necessário
);

?>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('Location'); ?></div>

<!-- content -->
<div>

    <form id="editLocation">
        <div class="pContent">
            <table id="editLocation" class="table table-noborder table-condensed">

                <tbody>
                    <!-- name -->
                    <tr>
                        <th><?php print _('Upload File'); ?></th>
                        <td colspan="2">
                            <input type="file" name="file_upload">
                            <input type="hidden" name="csrf_cookie" value="<?php print $csrf; ?>">
                            <input type="hidden" name="id" value="<?php print $_POST['id']; ?>">
                            <input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
                        </td>
                    </tr>

                    </br>

                    <!-- description -->
                    <tr>
                        <th><?php print _('Tipo'); ?></th>
                        <td colspan="2">
                            <select class="form-control input-sm" name="categoria">
                                <?php foreach ($categorias as $categoria) {
                                    print "<option value=" . $categoria . ">" . $categoria . "</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </tbody>

            </table>
        </div>
        <!-- footer -->
        <div class="pFooter">
            <div class="btn-group">
                <button class="btn btn-sm btn-default hidePopupsReload"><?php print _('Cancel'); ?></button>
                <button class="btn btn-sm btn-default btn-success" id="editLocationSubmit"><i class="fa fa-upload"></i> <?php print ucwords(_($_POST['action'])); ?></button>
            </div>
            <!-- result -->
            <div class="editLocationResult"></div>
        </div>
    </form>
</div>
