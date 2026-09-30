# Version 0.1.0 - PHP 8.3

### Changes

* Requires PHP 8.3 or newer, supports Nextcloud 30 – 33
* Updated PHP libraries: dompdf 3, league/commonmark 2
* PDF export is rendered as one document (one page per entry) without libmergepdf/TCPDF
* Markdown export now separates entries with an empty line
* Controllers use PHP attributes instead of docblock annotations
* Tests updated to PHPUnit 10
