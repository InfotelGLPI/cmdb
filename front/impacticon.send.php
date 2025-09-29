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
use Glpi\Exception\Http\BadRequestHttpException;

Session::checkLoginUser();

$doc = new Document();

if (isset($_GET['idDoc'])) { // docid for document
    if (!$doc->getFromDB($_GET['idDoc'])) {
        throw new BadRequestHttpException(__('Unknown file'));
    }

    if (!file_exists(GLPI_DOC_DIR . "/" . $doc->fields['filepath'])) {
        throw new BadRequestHttpException(__('File not found')); // Not found
    } else {
        if ($doc->fields['sha1sum'] && $doc->fields['sha1sum'] != sha1_file(GLPI_DOC_DIR . "/" . $doc->fields['filepath'])) {
            throw new BadRequestHttpException(__('File is altered (bad checksum)')); // Doc alterated
        } else {
            return $doc->getAsResponse();
        }
    }
}
