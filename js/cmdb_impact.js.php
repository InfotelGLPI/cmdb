<?php
    include('../../../inc/includes.php');
    header('Content-Type: text/javascript');
    echo 'let cmdbRootUrl = "'.PLUGIN_CMDB_WEBDIR.'"';
?>

function cmdbLoadInfos(event) {
    console.log('event',event);
    let itemtype = event.target.data('id')
        .split(GLPIImpact.NODE_ID_SEPERATOR)[0];
    let itemId = event.target.data('id')
        .split(GLPIImpact.NODE_ID_SEPERATOR)[1];

    $.ajax({
        type: "GET",
        url: cmdbRootUrl+'/ajax/impact_item_infos.php',
        data: {
            'itemtype': itemtype,
            'itemId': itemId
        },
        success: function(data){
            const encodedSVG = encodeURIComponent(data);
            const dataUrl = `data:image/svg+xml;charset=UTF-8,${encodedSVG}`;
            // plutôt renvoyer un json et afficher le contenu du JSON dans une popup ?
            GLPIImpact.cy.add({
                group: 'nodes',
                data: {
                    id: 'tooltip'+itemtype+itemId,
                    label: __('Information')+' : '+event.target.data('label'),
                    image: dataUrl
                },
                position: {
                    x: event.position.x+5,
                    y: event.position.y+5
                }
            })
        },
        error: function(){
            alert("error");
        },
    });
}

$(document).ajaxComplete(function(event, xhr, settings) {
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
                    content: '<i class="fas fa-link me-2"></i>' + __("Information"),
                    selector: 'node[link]',
                    onClickFunction: cmdbLoadInfos
                });

                GLPIImpact.cy.contextMenus(contextMenu);

                // TODO mettre un loader ?
                console.log('fait');
            }, 1000)
        }
    }
});





