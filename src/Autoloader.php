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

class Autoloader
{
    protected $paths = [];

    /** @var array<string,true>|null lazily built allow-list of the generated class names */
    protected $allowed_classes = null;

    /** @var bool re-entrancy guard: building the allow-list runs a query, which autoloads */
    protected $is_resolving = false;

    public function __construct($options = null)
    {
        if (null !== $options) {
            $this->setOptions($options);
        }
    }

    public function setOptions($options)
    {
        if (!is_array($options) && !($options instanceof \Traversable)) {
            throw new \InvalidArgumentException();
        }

        foreach ($options as $path) {
            if (!in_array($path, $this->paths)) {
                $this->paths[] = $path;
            }
        }
        return $this;
    }

    public function processClassname($classname)
    {

        preg_match("/^GlpiPlugin\\\\([A-Z][a-z0-9]+)\\\\([A-Z]\w+)$/", $classname, $matches);

        if (count($matches) < 3) {
            return false;
        } else {
            return $matches;
        }

    }

    public function autoload($classname)
    {
        $matches = $this->processClassname($classname);

        if ($matches !== false) {

            if (isset($matches[1]) && isset($matches[2])) {
                $plugin_name = $matches[1];
                $class_name = $matches[2];

                if ($plugin_name !== "Cmdb") {
                    return false;
                }

                if (!$this->isAllowedClass($class_name)) {
                    return false;
                }

                $filename = implode(".", [
                    $class_name,
                    "php",
                ]);

                foreach ($this->paths as $path) {
                    $test = $path . DIRECTORY_SEPARATOR . $filename;
                    if (file_exists($test)) {
                        return include($test);
                    }
                }
            }
        }
        return false;
    }

    /**
     * Is this class name one of the CI type classes the plugin declares?
     *
     * The paths handed to this autoloader live under GLPI_PLUGIN_DOC_DIR, a directory the web
     * process writes to: any .php file dropped there — through an upload flaw anywhere else
     * in the instance, or left over by an older version — was included and executed on the
     * mere mention of a matching class name. The class name pattern already forbids a path
     * separator, so the missing piece is not traversal but the fact that nothing said which
     * files are legitimate. Rebuild that list from glpi_plugin_cmdb_citypes, the table the
     * generated classes were named after, and refuse everything else.
     *
     * Refusing is safe when the database is not reachable yet: the plugin's own classes are
     * loaded by GLPI from marketplace/cmdb/src, never from here.
     *
     * @param string $class_name
     *
     * @return bool
     */
    protected function isAllowedClass($class_name)
    {
        global $DB;

        if ($this->allowed_classes === null) {
            if ($this->is_resolving
                || !$DB->connected
                || !$DB->tableExists('glpi_plugin_cmdb_citypes')) {
                return false;
            }

            $this->is_resolving    = true;
            $this->allowed_classes = [];
            $iterator = $DB->request([
                'SELECT' => 'name',
                'FROM'   => 'glpi_plugin_cmdb_citypes',
                'WHERE'  => ['is_imported' => 0],
            ]);
            foreach ($iterator as $citype) {
                // Same transformation as CIType::getSystemName() followed by the ucfirst()
                // CIType::cleanDBonPurge() applies to build the file name.
                $system_name = preg_replace(
                    '/[^a-z0-9_]/',
                    '',
                    strtolower(substr((string) $citype['name'], strlen('GlpiPlugin\\Cmdb'))),
                );
                if ($system_name !== '') {
                    $this->allowed_classes[ucfirst($system_name)] = true;
                }
            }
            $this->is_resolving = false;
        }

        return isset($this->allowed_classes[$class_name]);
    }

    public function register()
    {
        spl_autoload_register([$this, 'autoload']);
    }
}
