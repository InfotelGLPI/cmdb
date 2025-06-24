/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


var changeField = function (idType, id) {
   $.ajax({
      url: '../ajax/change_field.php',
      type: 'POST',
      data: 'idCIType=' + idType.value + '&id=' + id,
      dataType: 'html',
      success: function (code_html) {
         $("tr.field").remove();
         $("tr.fieldCI").after(code_html);
      },
      error: function (resultat, statut, erreur) {
         alert(erreur);
      }
   });
};

var showDatepicker = function () {
   $(".datepicker").datepicker();
};