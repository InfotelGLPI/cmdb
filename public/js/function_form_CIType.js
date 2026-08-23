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
