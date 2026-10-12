# Version 0.3.0 - New editor

### Changes

* New editor based on CodeMirror 6 instead of the unmaintained SimpleMDE (2016).
  Selecting text and placing the cursor now works with the native touch handling of
  Android and iOS; spell checking and auto-capitalization of the on-screen keyboard work.
* Rebuilt toolbar (bold, italic, strikethrough, heading, quote, lists, link, preview,
  Markdown guide) with larger buttons for touch screens; Ctrl+B / Ctrl+I shortcuts
* Larger writing area: tapping anywhere below the text puts the cursor into the entry
* Pending changes are saved to the right day immediately when switching days
* Undo no longer reaches into the text of a previously opened day
* App info now names the new maintainer and points to
  https://github.com/amagdanz-bit/nextcloud_diary

### Compatibility

* Nextcloud 30 – 35, PHP 8.3 or newer
