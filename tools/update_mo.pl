#!/usr/bin/perl
#!/usr/bin/perl -w 

#
# -------------------------------------------------------------------------
# cmdb plugin for GLPI
# Copyright (C) 2020-2026 by the cmdb Development Team.
#
# https://github.com/InfotelGLPI/cmdb
# -------------------------------------------------------------------------
#
# LICENSE
#
# This file is part of cmdb.
#
# cmdb is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 3 of the License, or
# (at your option) any later version.
#
# cmdb is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with cmdb. If not, see <http://www.gnu.org/licenses/>.
# --------------------------------------------------------------------------
#

if (@ARGV!=0){
print "USAGE update_mo.pl\n\n";

exit();
}


opendir(DIRHANDLE,'locales')||die "ERROR: can not read current directory\n"; 
foreach (readdir(DIRHANDLE)){ 
	if ($_ ne '..' && $_ ne '.'){

            if(!(-l "$dir/$_")){
                     if (index($_,".po",0)==length($_)-3) {
                        $lang=$_;
                        $lang=~s/\.po//;
                        
                        `msgfmt locales/$_ -o locales/$lang.mo`;
                     }
            }

	}
}
closedir DIRHANDLE; 

#  
#  
