/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


var deleteField = function (id) {
   $(function () {
      $("#" + id).remove();
   });
};

var addHiddenDeletedField = function (id) {
   $("#fields").append("<input type='hidden' name='deletedField[]' value='" + id + "'/>");
};

function checkboxAction() {
   $(function () {
      if ($("#is_imported").is(':checked')) {
         $(".newItem").hide();
         $("tr[name='importedItem']").each(function () {
            $(this).show();
         });
      } else {
         $("tr[name='importedItem']").each(function () {
            $(this).hide();
         });
         $(".newItem").show();
      }
   });
}


var resetFields = function (id, tabType) {
   $("#fields tr.field").remove();
   $("#fields input[type='hidden']").remove();
   $.ajax({
      url: '../ajax/reset_fields_citypes.php',
      type: 'POST',
      data: 'id=' + id + '&tabType=' + tabType + '&action=reset',
      dataType: 'html',
      success: function (code_html) {
         $("#fields").append(code_html);
      }
   });
};

function getRandomInt(min, max) {
   return Math.floor(Math.random() * (max - min)) + min;
}

var addField = function (tabType) {
   var rows = getRandomInt(0, 1000000);
   $.ajax({
      url: '../ajax/reset_fields_citypes.php',
      type: 'POST',
      data: 'rows=' + rows + '&tabType=' + tabType + '&action=add',
      dataType: 'html',
      success: function (code_html) {
         $("#newfields").append(code_html);
      }
   });

};
