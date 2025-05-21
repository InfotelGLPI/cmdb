<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 CMDB plugin for GLPI
 Copyright (C) 2015-2022 by the CMDB Development Team.

 https://github.com/InfotelGLPI/CMDB
 -------------------------------------------------------------------------

 LICENSE

 This file is part of CMDB.

 CMDB is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 CMDB is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with CMDB. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use Glpi\Controller\LegacyFileLoadController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Application\View\TemplateRenderer;


$dropdown = new PluginCmdbOperationprocessState();
if (!($this instanceof LegacyFileLoadController) || !($dropdown instanceof CommonDropdown)) {
    throw new LogicException();
}
if (!$dropdown::canView()) {
    throw new AccessDeniedHttpException();
}
$params = [
    'class' => $dropdown::class,
];

TemplateRenderer::getInstance()->display('pages/generic_list.html.twig', $params);
