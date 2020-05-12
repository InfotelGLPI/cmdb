/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


function addCriticity(param) {
   $('document').ready(function () {
      $(document).ajaxComplete(function (event, xhr, option) {

         var paramFinder = /[?&]?_glpi_tab=([^&]+)(&|$)/;

         var ajaxTab_param = paramFinder.exec(option.url);
         var is_good_tab = false;
         var itemtype;
         if (ajaxTab_param != undefined) {

            $.each(param, function (index, value) {
               var nameTab = value.concat("$main");

               if (nameTab == ajaxTab_param[1]) {
                  is_good_tab = true;
                  itemtype = value;
               }
            });
         }
         if (is_good_tab) {
            var urlAjax = "";
            paramFinder = /[?&]?id=([^&]+)(&|$)/;
            var paramId = paramFinder.exec(option.url);
            var id;
            if (paramId == null) {
               id = -1;
            } else {
               id = paramId[1];
            }

            if (location.pathname.indexOf('plugins') > 0) {
               urlAjax = "../../cmdb/ajax/criticity_values.php";
            } else {
               urlAjax = "../plugins/cmdb/ajax/criticity_values.php";
            }

            var hidden_fields = "<input type='hidden' name='plugin_cmdb_criticity_id' id='criticity' value='" + id + "'>" +
               "<input type='hidden' name='plugin_cmdb_criticity_itemtype' value='" + itemtype + "'>";

            $.ajax({
               url: urlAjax + "?itemtype=" + itemtype + "&id=" + id,
               success: function (data) {

                  if ($('tr[id="plugin_cmdb_tr"]').length == 0) {
                     if (itemtype == 'Computer') {
                        $('input[name="autoupdatesystems_id"]').closest('tr').after(data + hidden_fields);
                     } else {
                        $('table#mainformtable tr.tab_bg_1').last().after(data + hidden_fields);
                     }
                  }
               }
            });

         }

      }, this);
   });
}