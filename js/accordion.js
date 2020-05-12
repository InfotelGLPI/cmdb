/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


function accordion(id, openall) {
   if (id == undefined) {
       id  = 'accordion';
   }
    jQuery(document).ready(function () {
        $("#"+id).accordion({
            collapsible: true,
            //active:[0, 1, 2, 3],
            heightStyle: "content"
         });
      if (openall) {
          $('#'+id +' .ui-accordion-content').show();
      }
    });
};