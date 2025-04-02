Description of ADOdb library import into Moodle

Source: https://github.com/ADOdb/ADOdb

This library will be probably removed sometime in the future
because it is now used only by enrol and auth db plugins.

Removed:
 * Any invisible file (dot suffixed)
 * composer.json
 * contrib/ (if present)
 * cute_icons_for_site/ (if present)
 * docs/
 * lang/* everything but adodb-en.inc.php (originally because they were not utf-8, now because of not used)
 * nbproject/ (if present)
 * pear/
 * server.php (if present)
 * session/

Added:
 * index.html - prevent directory browsing on misconfigured servers
 * readme_moodle.txt - this file ;-)
<<<<<<< HEAD
=======

Our changes:
 * Removed random seed initialization from lib/adodb/adodb.inc.php:216 (see 038f546 and MDL-41198).
 * MDL-52286 Added muting erros in ADORecordSet::__destruct().
   Check if fixed upstream during the next upgrade and remove this note. (8638b3f1441d4b928)
 * MDL-67034 Fixes to make the library php74 compliant.
 * MDL-67414 Fix to make the library PostgreSQL 12.x compliant (upstream: a4876f100602c2ce4).

skodak, iarenaza, moodler, stronk7, abgreeve, lameze, ankitagarwal, marinaglancy, matteo
>>>>>>> upstream/MOODLE_38_STABLE
