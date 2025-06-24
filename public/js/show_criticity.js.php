<?php
global$CFG_GLPI; use Glpi\Event;
header('Content-Type: text/javascript');

?>
var root = "<?php echo $CFG_GLPI['root_doc'] . "/plugins/cmdb"; ?>";
var citype = "<?php echo json_encode(PluginCmdbCriticity_Item::getCIType()); ?>";

addCriticity(citype, root);

