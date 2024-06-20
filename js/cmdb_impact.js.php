<?php
include('../../../inc/includes.php');
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
            // activate tooltips created for long lists (see Html::showToolTip)
            const scripts = tooltipContainer.getElementsByTagName('script');
            for (let i = 0; i < scripts.length; i++) {
                eval(scripts[i].text);
            }
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





