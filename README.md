moodle-qformat_qtitwo
========================

IMS QTI 2.0 Moodle export format
This plugin allow export of questions using the IMS QTI 2.0 standard.

Written by Brian King (brian@mediagonal.ch)
Upgraded for Moodle 2.9, 3.0 by Jean-Michel Védrine (vedrine@vedrine.net)


KNOWN LIMITATIONS
* Multianswer (cloze) questions are not exported (this was broken in Moodle 1.5 and was not fixed since)
* The Smarty library was removed in Moodle recent versions, so this plugin has to contain a full version of this library. Smarty version 2 is still used to save me the
hassle to upgrade the code and templates to Smarty 3.

Note: This was done because I had a need for it and I share it just in case it could be usefull as it is but don't rely on a long term support.
