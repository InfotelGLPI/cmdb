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

// Impact tooltip configuration (templates/impactinfo_itemtype.html.twig and
// templates/impactinfo_fields.html.twig): the field list follows the item type, a field chosen
// in a column selector is appended to that column, the cross removes it; the selector is then
// reloaded without the fields already listed. The lists are loaded over AJAX, hence the
// listeners delegated to the document.
(function () {
    const spinner = '<td colspan="2"><div class="d-flex justify-content-center"><i class="ti ti-loader-2 fs-1 m-2"></i></div></td>';

    function loadFields(holder, itemtype) {
        const target = document.getElementById('fieldsForm');
        if (target === null || !itemtype) {
            return;
        }
        target.innerHTML = spinner;
        $(target).load(holder.dataset.url, {id: holder.dataset.id, itemtype: itemtype});
    }

    function usedFieldIds(column) {
        return Array.from(column.querySelectorAll('[data-cmdb-field]')).map((row) => row.dataset.cmdbField);
    }

    function reloadSelector(column) {
        $(column.querySelector('[data-cmdb-fields-select]')).load(column.dataset.dropdownUrl, {
            key: column.dataset.key,
            itemtype: column.dataset.itemtype,
            used: usedFieldIds(column),
        });
    }

    function nextOrder(column) {
        let order = 1;
        column.querySelectorAll('[data-cmdb-field] input[name$="[order]"]').forEach((input) => {
            const value = parseInt(input.value, 10);
            if (!isNaN(value) && value >= order) {
                order = value + 1;
            }
        });
        return order;
    }

    // Same markup as templates/impactinfo_field_row.html.twig
    function buildRow(key, field_id, label, order) {
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center justify-content-between border rounded m-1 p-2';
        row.dataset.cmdbField = field_id;

        const span = document.createElement('span');
        const order_label = document.createElement('label');
        order_label.textContent = __('Order', 'cmdb');
        const order_input = document.createElement('input');
        order_input.type = 'number';
        order_input.name = key + '-fields[' + field_id + '][order]';
        order_input.value = order;
        order_input.style.maxWidth = '5rem';
        order_input.className = 'ms-2';
        span.append(order_label, order_input);

        const name = document.createElement('strong');
        name.textContent = label;

        const type = document.createElement('input');
        type.type = 'hidden';
        type.name = key + '-fields[' + field_id + '][type]';
        type.value = key;

        const field = document.createElement('input');
        field.type = 'hidden';
        field.name = key + '-fields[' + field_id + '][field_id]';
        field.value = field_id;

        const remove = document.createElement('i');
        remove.className = 'ti ti-x mx-2 fs-2';
        remove.setAttribute('aria-hidden', 'true');
        remove.setAttribute('role', 'button');
        remove.title = __('Delete');
        remove.dataset.cmdbFieldRemove = '';

        row.append(span, name, type, field, remove);
        return row;
    }

    // Item type of a new tooltip
    $(document).on('change', '[data-cmdb-impactinfo] select[name="itemtype"]', function () {
        loadFields(this.closest('[data-cmdb-impactinfo]'), this.value);
    });

    $(document).on('change', '[data-cmdb-fields-column] [data-cmdb-fields-select] select', function () {
        const column = this.closest('[data-cmdb-fields-column]');
        const option = this.options[this.selectedIndex];
        if (option === undefined || !option.value || option.value === '0') {
            return;
        }
        column.querySelector('[data-cmdb-fields-list]').append(
            buildRow(column.dataset.key, option.value, option.textContent, nextOrder(column)),
        );
        reloadSelector(column);
    });

    $(document).on('click', '[data-cmdb-fields-column] [data-cmdb-field-remove]', function () {
        const column = this.closest('[data-cmdb-fields-column]');
        this.closest('[data-cmdb-field]').remove();
        reloadSelector(column);
    });

    // Existing tooltip: its item type is fixed, the list is loaded right away
    $(function () {
        document.querySelectorAll('[data-cmdb-impactinfo]').forEach((holder) => {
            if (holder.querySelector('select[name="itemtype"]') === null) {
                loadFields(holder, holder.dataset.itemtype);
            }
        });
    });
})();
