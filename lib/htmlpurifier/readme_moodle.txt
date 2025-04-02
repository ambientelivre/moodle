Description of HTML Purifier library import into Moodle

* Make new (or delete contents of) /lib/htmlpurifier/
* Copy everything from /library/ folder to /lib/htmlpurifier/
* Copy CREDITS, LICENSE from root folder to /lib/htmlpurifier/
* Delete unused files:
    HTMLPurifier.auto.php
    HTMLPurifier.autoload.php
    HTMLPurifier.autoload-legacy.php
    HTMLPurifier.composer.php
    HTMLPurifier.func.php
    HTMLPurifier.includes.php
    HTMLPurifier.kses.php
    HTMLPurifier.path.php
* add locallib.php with Moodle specific extensions to /lib/htmlpurifier/
* add this readme_moodle.txt to /lib/htmlpurifier/
<<<<<<< HEAD
=======

Modifications:
(verify if we need to apply them on every upgrade, remove when not needed)
* MDL-60337 use correct IDN variant for converting domain names to ascii
  Check status of https://github.com/ezyang/htmlpurifier/pull/148
* MDL-67030 applied https://github.com/ezyang/htmlpurifier/pull/224 towards
  php74 compatibility.
* MDL-67115 applied https://github.com/ezyang/htmlpurifier/pull/243 towards
  php74 compatibility.
>>>>>>> upstream/MOODLE_38_STABLE
