/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


var changeLink = function (idType) {
   $.ajax({
      url: '../ajax/change_link.php',
      type: 'POST',
      data: 'id=' + idType,
      dataType: 'json',
      success: function (json) {
         displayLink(json);
      }
   });
};

var displayLink = function (json) {
   var link = json.link;
   $("a#linkDisplay").attr("href", link);
};
