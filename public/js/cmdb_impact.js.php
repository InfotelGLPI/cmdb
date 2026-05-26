<?php

/*
 -------------------------------------------------------------------------
 cmdb plugin for GLPI
 Copyright (C) 2020-2026 by the cmdb Development Team.

 https://github.com/InfotelGLPI/cmdb
 -------------------------------------------------------------------------

 LICENSE

 This file is part of cmdb.

 cmdb is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 cmdb is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with cmdb. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

include('../../../../inc/includes.php');
header('Content-Type: text/javascript');
echo 'let cmdbRootUrl = "' . PLUGIN_CMDB_WEBDIR . '"';
?>

function cmdbLoadInfos(event) {
    let itemtype = event.target.data('id')
        .split(GLPIImpact.NODE_ID_SEPERATOR)[0];
    let itemId = event.target.data('id')
        .split(GLPIImpact.NODE_ID_SEPERATOR)[1];

    let tooltipContainer = document.getElementById('cmdb-tooltip');
    if (!tooltipContainer) {
        tooltipContainer = document.createElement('div');
        tooltipContainer.id = 'cmdb-tooltip';
        tooltipContainer.style.position = 'absolute';
        tooltipContainer.style.maxWidth = '100%';
        tooltipContainer.style.backgroundColor = '#FFF';
        tooltipContainer.style.bottom = 0;
        tooltipContainer.style.left = 0;
        tooltipContainer.style.zIndex = 1052; // 1051 = value from the impact analysis sidebar when fullscreened
        tooltipContainer.classList = 'border rounded px-1';
        document.querySelector("td[class='network-parent']").append(tooltipContainer);
    }
    tooltipContainer.innerHTML = "<i class=\"fas fa-3x fa-spinner fa-pulse m-2\"></i>";
    $.ajax({
        type: "GET",
        url: cmdbRootUrl + '/ajax/impact_item_infos.php',
        data: {
            'itemtype': itemtype,
            'itemId': itemId
        },
        success: function (data) {
            tooltipContainer.innerHTML = data;
            document.getElementById('close-cmdb-tooltip').addEventListener('click', e => {
                tooltipContainer.parentNode.removeChild(tooltipContainer);
            })
            // Initialize Bootstrap tooltips inserted by the AJAX response
            tooltipContainer.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                new bootstrap.Tooltip(el);
            });
        },
        error: function () {
            alert("error");
        }
    });
}

$(document).ajaxComplete(function (event, xhr, settings) {
    if (settings.url.includes('common.tabs.php')) {
        if (settings.url.includes('_glpi_tab=Impact')) {
            // let GLPIImpact the time to initiate cy before doing any modifications to it
            setTimeout(() => {
                let contextMenu = {
                    menuItems: GLPIImpact.getContextMenuItems(),
                    menuItemClasses: [],
                    contextMenuClasses: []
                }

                contextMenu.menuItems.push({
                    id: 'Test',
                    content: '<i class="fa fa-question me-2"></i>' + __("Informations", 'cmdb'),
                    selector: 'node[link]',
                    onClickFunction: cmdbLoadInfos
                });

                GLPIImpact.cy.contextMenus(contextMenu);
            }, 1000)
        }
    }
});





