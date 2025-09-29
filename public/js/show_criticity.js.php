<?php
global $CFG_GLPI;

use GlpiPlugin\Cmdb\Criticity_Item;
header('Content-Type: text/javascript');

?>
var root = "<?php echo $CFG_GLPI['root_doc'] . "/plugins/cmdb"; ?>";
var citype = "<?php echo json_encode(Criticity_Item::getCIType()); ?>";

addCriticity(citype, root);

