/**
 * -------------------------------------------------------------------------
 * cmdb plugin for GLPI
 * Copyright (C) 2020-2026 by the cmdb Development Team.
 *
 * https://github.com/InfotelGLPI/cmdb
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of cmdb.
 *
 * cmdb is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * cmdb is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with cmdb. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

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


// The field types are read server side from CIType::getTypeFields(); they are no longer
// carried by the request.
var resetFields = function (id) {
   $("#fields tr.field").remove();
   $("#fields input[type='hidden']").remove();
   $.ajax({
      url: '../ajax/reset_fields_citypes.php',
      type: 'POST',
      data: 'id=' + id + '&action=reset',
      dataType: 'html',
      success: function (code_html) {
         $("#fields").append(code_html);
      }
   });
};

// Trash icon of a custom field row (templates/citype_field_row.html.twig): the row is removed,
// and an existing field is posted as deleted
$(document).on('click', '[data-cmdb-delete-field]', function () {
   var id = this.dataset.cmdbDeleteField;
   if (this.dataset.cmdbDeleted === '1') {
      addHiddenDeletedField(id);
   }
   $(this).closest('tr').remove();
});

// CI type form (templates/citype_form_rows.html.twig): the rows follow the "imported" checkbox,
// and the field buttons call the helpers above
$(document).on('change', '#is_imported', checkboxAction);
$(document).on('click', '[data-cmdb-add-field]', function () {
   addField();
});
$(document).on('click', '[data-cmdb-reset-fields]', function () {
   resetFields(this.dataset.cmdbResetFields);
});
// The form is the main tab, loaded over AJAX after the page itself: the rows are switched
// once the form is in place (the inline call this replaces ran when the tab was injected)
function initCITypeForm() {
   document.querySelectorAll('[data-cmdb-citype-form]:not([data-cmdb-ready])').forEach(function (marker) {
      marker.dataset.cmdbReady = '1';
      checkboxAction();
   });
}
$(initCITypeForm);
$(document).on('ajaxComplete', initCITypeForm);

function getRandomInt(min, max) {   return Math.floor(Math.random() * (max - min)) + min;
}

var addField = function () {
   var rows = getRandomInt(0, 1000000);
   $.ajax({
      url: '../ajax/reset_fields_citypes.php',
      type: 'POST',
      data: 'rows=' + rows + '&action=add',
      dataType: 'html',
      success: function (code_html) {
         $("#newfields").append(code_html);
      }
   });

};
