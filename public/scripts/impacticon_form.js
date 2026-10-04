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

// Impact icon form (templates/impacticon_form.html.twig): the criteria row (type of the item)
// follows the chosen item type, and is filled on load for the current one. The form can be
// opened inside an AJAX response, hence the listener delegated to the document.
(function () {
    function loadCriterias(holder) {
        const select = holder.querySelector('select[name="itemtype"]');
        const target = document.getElementById('criteria_row');
        if (select === null || target === null) {
            return;
        }
        $(target).load(holder.dataset.url, {id: holder.dataset.id, itemtype: select.value});
    }

    $(document).on('change', '[data-cmdb-impacticon] select[name="itemtype"]', function () {
        loadCriterias(this.closest('[data-cmdb-impacticon]'));
    });

    $(function () {
        document.querySelectorAll('[data-cmdb-impacticon]').forEach(loadCriterias);
    });
})();
