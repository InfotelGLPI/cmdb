<?php
use Glpi\Event;
include('../../../inc/includes.php');
header('Content-Type: text/javascript');

?>
var root = "<?php echo PLUGINCMDB_WEBDIR; ?>";
var citype = "<?php echo json_encode(PluginCmdbCriticity_Item::getCIType()); ?>";

addCriticity(citype, root);

