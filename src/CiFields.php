<?php

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

namespace GlpiPlugin\Cmdb;

use Glpi\Application\View\TemplateRenderer;
use CommonDBTM;
use DbUtils;
use Dropdown;
use Html;
use Session;

/**
 * Class CiFields
 */
class CiFields extends CommonDBTM
{
    public static string $rightname = "plugin_cmdb_cis";

    /**
     * add Fields of an item
     *
     * @param   $input
     *
     * @global  $DB
     *
     */
    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
    }

    /**
     * typefield drives the rendering and the reading of every custom value of the type (the
     * switch of showField() and setFieldInput() below), and only the keys of
     * CIType::getTypeFields() are meaningful. Neither addCIFields() nor updateCIFields()
     * checked it, so an out-of-domain value was persisted and every field of that type then
     * fell through to the default branch, making the data already captured unreadable without
     * any error being raised. Validating here covers both writers and any future one.
     *
     * @param array $input
     *
     * @return array|false
     */
    private function prepareInput($input)
    {
        if (isset($input['typefield'])) {
            if (!array_key_exists((int) $input['typefield'], CIType::getTypeFields())) {
                Session::addMessageAfterRedirect(__('Invalid field type.', 'cmdb'), false, ERROR);
                return false;
            }
            $input['typefield'] = (int) $input['typefield'];
        }

        return $input;
    }

    /**
     * A custom field owns the values captured for it on every CI of the type. updateCIFields()
     * deletes them next to each deleteByCriteria() it issues, but nothing did when the row was
     * purged from anywhere else — CIType::cleanDBonPurge() included, which drops the fields of
     * the type through deleteCIFields(). The orphan values then kept a foreign key to a field
     * id free to be reused by the next one created.
     *
     * @return void
     */
    public function cleanDBonPurge()
    {
        $values = new CiValues();
        $values->deleteByCriteria(['plugin_cmdb_cifields_id' => $this->fields['id']], true);
    }

    public function addCIFields($input)
    {

        if (!isset($input["nameNewField"], $input['plugin_cmdb_citypes_id'])
            || !is_array($input["nameNewField"])) {
            return;
        }

        foreach ($input["nameNewField"] as $i => $name) {
            // The two posted arrays were indexed blindly against the length of the first one.
            // A payload carrying one without the other raised "Undefined array key" and then
            // inserted a row whose typefield was null, which prepareInput() cannot reject
            // since it only validates the key when it is set. Skip the odd index rather than
            // write half a row; the value itself is confronted with CIType::getTypeFields()
            // there, for this writer and any future one.
            if (!isset($input['typeNewField'][$i])) {
                continue;
            }

            $this->add([
                'name'                   => $name,
                'typefield'              => $input['typeNewField'][$i],
                'plugin_cmdb_citypes_id' => $input['plugin_cmdb_citypes_id'],
            ]);
        }
    }


    /**
     * Update fields of an no imported item
     *
     * @param   $input
     *
     * @global  $DB
     *
     */
    public function updateCIFields($input)
    {

        // Both loops below are indexed by the row id the form posted back, and neither
        // checked that the row belongs to the type being edited: a forged id renamed,
        // retyped or deleted a field of any other CI type, and deleted its stored values with
        // it. CiFields has no entities_id either, so checkEntity() is a no-op on it. The type
        // id is not read from the payload — CIType::post_updateItem() overwrites it with the
        // row actually being updated — so it is the one trustworthy value here.
        $owned = [];
        if (isset($input['plugin_cmdb_citypes_id'])) {
            $owned = $this->find(['plugin_cmdb_citypes_id' => (int) $input['plugin_cmdb_citypes_id']]);
        }

        if (isset($input["nameField"])) {
            foreach ($input["nameField"] as $key => $value) {
                if (!isset($owned[$key])) {
                    continue;
                }
                // Mirror of addCIFields(): the two posted arrays are indexed independently,
                // so a payload carrying nameField[] without its typeField[] counterpart wrote
                // a null type that prepareInput() cannot reject -- it only validates the key
                // when it is set. Skip the incomplete row rather than blank out its type.
                if (!isset($input['nameField'][$key], $input['typeField'][$key])) {
                    continue;
                }
                $values['name']      = $input['nameField'][$key];
                $values['typefield'] = $input['typeField'][$key];
                $values['id']        = $key;
                $this->update($values);
            }
        }
        if (isset($input["deletedField"])) {
            foreach ($input["deletedField"] as $key => $data) {
                if (!isset($owned[$data])) {
                    continue;
                }
                $this->deleteByCriteria(['id' => $data]);
                $temp = new CiValues();
                $temp->deleteByCriteria(['plugin_cmdb_cifields_id' => $data]);
            }
        }

        $this->addCIFields($input);
    }

    /**
     * @param $typefield
     * @param $value
     *
     * @return string
     */
    public static function setValue($typefield, $value)
    {
        switch ($typefield) {
            case 4:
                if ($value) {
                    return __('Yes');
                } else {
                    return __('No');
                }
                break;
            default:
                return $value;
                break;
        }
    }

    /**
     * @param $idType
     * @param $id
     * @param $itemtype
     */
    public function setFieldByType($idType, $id, $itemtype = CI::class)
    {
        echo $this->renderFieldsByType($idType, $id, $itemtype);
    }

    /**
     * Rows of the custom fields of a CI type (templates/cifield_rows.html.twig)
     */
    public function renderFieldsByType($idType, $id, $itemtype = CI::class): string
    {
        $fields = [];
        foreach ($this->find(['plugin_cmdb_citypes_id' => $idType]) as $data) {
            $fields[] = [
                'label' => (string) $data['name'],
                'input' => self::getFieldInput($data, $id, $itemtype),
            ];
        }

        return TemplateRenderer::getInstance()->render('@cmdb/cifield_rows.html.twig', [
            'fields' => $fields,
        ]);
    }

    /**
     * @param $field
     * @param $idCi
     */
    public static function setFieldInput($field, $idCi, $itemtype = CI::class)
    {
        TemplateRenderer::getInstance()->display('@cmdb/cifield_rows.html.twig', [
            'fields' => [['label' => (string) $field['name'], 'input' => self::getFieldInput($field, $idCi, $itemtype)]],
        ]);
    }

    /**
     * Input of a custom field, matching its type (core widgets, escaped by the core)
     */
    public static function getFieldInput($field, $idCi, $itemtype = CI::class): string
    {
        $value = "";
        $id    = $field["id"];
        $name  = "newfield[$id]";
        if ($idCi != -1 && $idCi != "") {
            $civalues = new CiValues();
            if ($civalues->getFromDBByCrit(['items_id'      => $idCi,
                'itemtype'      => $itemtype,
                'plugin_cmdb_cifields_id' => $field['id']])) {
                $value = $civalues->fields["value"];
                $id    = $civalues->fields["id"];
                $name  = "field[$id]";
            }
        }
        switch ($field['typefield']) {
            case 0:
                return Html::input($name, ['value' => $value, 'style' => 'width: 200px']);
            case 1:
                return (string) Html::textarea(['name'              => $name,
                    'value'             => $value,
                    'cols'              => '100',
                    'rows'              => '8',
                    'enable_richtext'   => false,
                    'enable_fileupload' => false,
                    'display'           => false]);
            case 2:
                return (string) Html::showDateField($name, ['value' => $value, 'display' => false]);
            case 3:
                return Html::input($name, ['value' => $value, 'size' => 40]);
            case 4:
                return (string) Dropdown::showFromArray($name, ['0' => __('No'),
                    '1' => __('Yes')], ["value" => $value,
                        "width" => 100,
                        'display' => false]);
        }

        return '';
    }

    /**
     * @param $idCI
     * @param $CIType
     */
    public function getContentFieldsCI($idCI, $CIType)
    {
        global $DB;

        // Label and value (text) of each field, rendered by templates/cifield_content.html.twig
        $lines = [];

        if ($CIType['is_imported']) {
            $ciType = new CIType();
            $ciType->getFromDB($CIType['id']);

            $listCIFields = explode(',', $ciType->getField('fields'));

            // The name of an imported type is a free text column used here as a class name.
            // Resolve it through getItemForItemtype(), which refuses anything that is not a
            // loadable GLPI class, instead of instantiating the stored string: a row written
            // before CIType::prepareInputForAdd() validated that name — or by any other write
            // path — turned the display of every CI of that type into a fatal Error.
            $target = getItemForItemtype($CIType['name']);
            if (!$target) {
                return;
            }
            $itemclass = getItemForItemtype($CIType['name']);
            $itemclass->getFromDB($idCI);

            foreach ($listCIFields as $field) {
                if (($field != 'id' && strrpos($field, "_id", -3) == false) || $field == 'id') {
                    if ($itemclass->isField($field)) {
                        if (!empty($itemclass->fields[$field])) {
                            $searchOption = $target->getSearchOptionByField('field', $field);

                            switch ($searchOption['datatype']) {
                                case 'bool':
                                    $lines[] = ['label' => (string) $searchOption['name'], 'value' => (string) Dropdown::getYesNo($itemclass->fields[$field])];
                                    break;
                                case 'datetime':
                                    $lines[] = ['label' => (string) $searchOption['name'], 'value' => (string) Html::convDateTime($itemclass->fields[$field])];
                                    break;
                                case 'date':
                                    $lines[] = ['label' => (string) $searchOption['name'], 'value' => (string) Html::convDate($itemclass->fields[$field])];
                                    break;
                                case 'string':
                                case 'itemlink':
                                    $lines[] = ['label' => (string) $searchOption['name'], 'value' => (string) $itemclass->fields[$field]];
                                    break;
                            }
                        }
                    }
                } else {
                    $dbu          = new DbUtils();
                    $searchOption = $target->getSearchOptionByField('field', $field);
                    if (empty($searchOption)) {
                        if ($table = $dbu->getTableNameForForeignKeyField($field)) {
                            $searchOption = $target->getSearchOptionByField('field', 'name', $table);
                        }
                    }

                    if (!empty($searchOption) && $itemclass->isField($field)) {
                        if ($itemclass->fields[$field] != '0') {
                            $itemtype_rel = $dbu->getItemTypeForTable($searchOption['table']);
                            $item_rel     = $dbu->getItemForItemtype($itemtype_rel);
                            $item_rel->getFromDB($itemclass->fields[$field]);
                            $lines[] = ['label' => (string) $searchOption['name'], 'value' => (string) $item_rel->fields['name']];
                        }
                    }
                }
            }
        } else {
            $iterator = $DB->request(
                ['glpi_plugin_cmdb_civalues', 'glpi_plugin_cmdb_cifields'],
                ['WHERE' => ['glpi_plugin_cmdb_cifields.plugin_cmdb_citypes_id' => $CIType['id'],
                    'glpi_plugin_cmdb_civalues.items_id'               => $idCI,
                    'glpi_plugin_cmdb_civalues.itemtype'               => [CI::class, '']],
                    'FKEY'  => ['glpi_plugin_cmdb_cifields' => 'id',
                        'glpi_plugin_cmdb_civalues' => 'plugin_cmdb_cifields_id'],
                ],
            );

            foreach ($iterator as $data) {
                $lines[] = [
                    'label' => (string) $data['name'],
                    'value' => (string) CiFields::setValue($data['typefield'], $data['value']),
                ];
            }
        }

        TemplateRenderer::getInstance()->display('@cmdb/cifield_content.html.twig', ['lines' => $lines]);
    }
}
