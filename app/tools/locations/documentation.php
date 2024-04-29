<?php

/**
 * Script to print single location
 ***************************/

/* functions */
require_once(dirname(__FILE__) . '/../../../functions/functions.php');

# initialize user object
$Database = new Database_PDO;

# verify that user is logged in
$User->check_user_session();

# fetch location
if (!isset($location)) {
    $location = $Tools->fetch_object("locations", "id", $location_index);
}
# perm check
if ($User->get_module_permissions("locations") == User::ACCESS_NONE) {
    $Result->show("danger", _("You do not have permissions to access this module"), false);
}
# if none than print
elseif (!is_object($location)) {
    $Result->show("info", _("Invalid location"), false);
} else {
    # fetch documents
    $docs = $Tools->fetch_multiple_objects("documentations", "location_id", $location->id, "category", true);

    # set directory path for files
    $directoryPath = '/files';

    # check if there are documents
    if ($docs !== false && sizeof($docs) > 0) {
        # organize documents by category
        $docsByCategory = [];
        foreach ($docs as $doc) {
            $docsByCategory[$doc->category][] = $doc;
        }

        # print documents by category
        foreach ($docsByCategory as $category => $docs) {
            echo "<h3>" . $category . "</h3>";
            echo "<ul>";
            foreach ($docs as $doc) {
                $modalId = "fileModal" . $doc->id;
                echo "<li><a href=\"javascript:void(0);\" data-toggle=\"modal\" data-target=\"#" . $modalId . "\" onclick=\"openModal('" . $modalId . "', '" . $directoryPath . "/" . $doc->file_name . "')\">" . $doc->file_name . "</a></li>";
                
                // Modal structure
                echo "<div class=\"modal fade\" id=\"" . $modalId . "\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"" . $modalId . "Label\" aria-hidden=\"true\">";
                echo "<div class=\"modal-dialog modal-dialog-custom\" role=\"document\">"; // Added class modal-dialog-custom
                echo "<div class=\"modal-content\">";
                echo "<div class=\"modal-header\">";
                echo "<h5 class=\"modal-title\" id=\"" . $modalId . "Label\">" . $doc->file_name . "</h5>";
                echo "<button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">";
                echo "<span aria-hidden=\"true\">×</span>";
                echo "</button>";
                echo "</div>";
                echo "<div class=\"modal-body\">";
                echo "<iframe id=\"iframe" . $modalId . "\" width=\"100%\" height=\"800px\"></iframe>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            echo "</ul>";
        }
    } else {
        $Result->show("info", _("No documents available"), false);
    }
}
?>

<style>
.modal-dialog-custom {
    width: 70% !important; /* Define o tamanho do modal como 70% da tela */
}
</style>


<script>
// Função para abrir o modal e definir o src do iframe
function openModal(modalId, filePath) {
    var iframe = document.getElementById('iframe' + modalId);
    iframe.src = filePath;
}
</script>
