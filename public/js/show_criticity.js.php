<?php
use Glpi\Event;
header('Content-Type: text/javascript');

?>
var root = "<?php echo PLUGIN_CMDB_WEBDIR; ?>";
var citype = "<?php echo json_encode(PluginCmdbCriticity_Item::getCIType()); ?>";

addCriticity(citype, root);

