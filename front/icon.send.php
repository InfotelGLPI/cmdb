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

use Glpi\Exception\Http\BadRequestHttpException;

Session::checkLoginUser();

$doc = new Document();

if (isset($_GET['idDoc'])) { // docid for document
   if (!$doc->getFromDB($_GET['idDoc'])) {
       throw new BadRequestHttpException(__('Unknown file'), true);
   }

   if (!file_exists(GLPI_DOC_DIR . "/" . $doc->fields['filepath'])) {
       throw new BadRequestHttpException(__('File not found'));
   } else if ($doc->canViewFile($_GET)) {
      if ($doc->fields['sha1sum'] && $doc->fields['sha1sum'] != sha1_file(GLPI_DOC_DIR . "/" . $doc->fields['filepath'])) {
          throw new BadRequestHttpException(__('File is altered (bad checksum)'));
      } else {
         return $doc->getAsResponse();
      }
   } else {
       throw new BadRequestHttpException(__('Unauthorized access to this file'));
   }
}
