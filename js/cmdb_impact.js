$(document).ajaxComplete(function(event, xhr, settings) {
    if (settings.url.includes('common.tabs.php')) {
        if (settings.url.includes('_glpi_tab=Impact')) {

            function loadInfos(target) {
                console.log(target);

                // $.ajax({
                //     type: "GET",
                //     url: $(GLPIImpact.selectors.form).prop('action'),
                //     data: {
                //         'action'  : 'search',
                //         'itemtype': itemtype,
                //         'used'    : used,
                //         'filter'  : filter,
                //         'page'    : page,
                //     },
                //     success: function(data){
                //         $.each(data.items, function(index, value) {
                //             var graph_id = itemtype + GLPIImpact.NODE_ID_SEPERATOR + value['id'];
                //             var isHidden = hidden.indexOf(graph_id) !== -1;
                //             var cssClass = "";
                //
                //             if (isHidden) {
                //                 cssClass = "impact-res-disabled";
                //             }
                //
                //             var str = '<p class="' + cssClass + '" data-id="' + value['id'] + '" data-type="' + itemtype + '">';
                //             str += `<img src='${value.image}'></img>`;
                //             str += value["name"];
                //
                //             if (isHidden) {
                //                 str += '<i class="fas fa-eye-slash impact-res-hidden"></i>';
                //             }
                //
                //             str += "</p>";
                //             $(GLPIImpact.selectors.sideSearchResults).append(str);
                //         });
                //
                //         // All data was loaded, hide "More..."
                //         if (data.total <= ((page + 1) * 20)) {
                //             $(GLPIImpact.selectors.sideSearchMore).hide();
                //         } else {
                //             $(GLPIImpact.selectors.sideSearchMore).show();
                //         }
                //
                //         // No results
                //         if (data.total == 0 && page == 0) {
                //             $(GLPIImpact.selectors.sideSearchNoResults).show();
                //         }
                //
                //         $(GLPIImpact.selectors.sideSearchSpinner).hide();
                //     },
                //     error: function(){
                //         alert("error");
                //     },
                // });
            }

            setTimeout(() => {
                let contextMenu = {
                    menuItems: window.GLPIImpact.getContextMenuItems(),
                    menuItemClasses: [],
                    contextMenuClasses: []
                }

                contextMenu.menuItems.push({
                    id: 'Test',
                    content: '<i class="fas fa-link me-2"></i>' + __("Information"),
                    selector: 'node[link]',
                    onClickFunction: loadInfos
                });

                console.log(contextMenu);

                window.GLPIImpact.cy.contextMenus(contextMenu);

                console.log('fait');
            }, 1000)
        }
    }
});





