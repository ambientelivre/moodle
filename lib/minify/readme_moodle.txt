Description of MatthiasMullie\Minify import into Moodle

1) Go to https://github.com/matthiasmullie/minify/releases

Download the latest minify "Source code (zip)" and unzip it:

mv minify-X.Y.ZZ/src /path/to/moodle/lib/minify/matthiasmullie-minify/
mv minify-X.Y.ZZ/data /path/to/moodle/lib/minify/matthiasmullie-minify/

<<<<<<< HEAD
2) Go to https://github.com/matthiasmullie/path-converter/releases/ and unzip

Download the latest path-converter Source code (zip) and unzip it:

mv path-converter-A.B.C/src/ /path/to/moodle/lib/minify/matthiasmullie-pathconverter/

3) Apply the following patches:

N/A
=======
mv path-converter-1.1.0/src/ /path/to/moodle/lib/minify/matthiasmullie-pathconverter/

Local changes applied:

MDL-67115: php 74 compliance - implode() params order. Note this has been fixed upstream
  by https://github.com/matthiasmullie/minify/pull/300 so, whenever this library is updated
  check if the fix is included and remove this note.

MDL-68191: https://github.com/matthiasmullie/minify/issues/317 is a bug that stops
  large sections of the CSS from being minimised, and also is a huge performance drain.
  We have applied the fix sent upstream because the performance win is so big.
  (E.g. one case I measured, with the bug was 40 seconds to minify CSS, with the fix was
  a few seconds. This is one of the reasons Behat runs in the browser are so slow.)
  Whenever this library is updated check if the fix is included and remove this note.
>>>>>>> upstream/MOODLE_38_STABLE
