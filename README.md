Extension linkhandler
=====================

Friendly fork of TYPO3 extension "linkhandler" with support for TYPO3 CMS 6+.

This version is just meant to share the changes I did to linkhandler to
make it work with TYPO3 6.0 to 6.2 until the official version is updated.

Note that this version is strictly for TYPO3 6.0 or more and is not compatible
with lower versions (I didn't bother with adding a compatibility layer).

The official project is located at http://forge.typo3.org/projects/extension-linkhandler

The extension manual can be found here: http://docs.typo3.org/typo3cms/extensions/linkhandler/0.3.1/

Local changes
^^^^^^^^^^^^^

A hook was added to make it possible to change the "parameter" property
of the typolink on the fly.

Declare it with something like::

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkhandler']['parameterChanger'][] = 'Foo\\Bar\\Hook\\Linkhandler';

inside your extension's `ext_localconf.php` file. The corresponding class must
have a `parameterChanger` method::

	class Linkhandler {
		/**
		 * Changes target page based on ...
		 *
		 * @param \tx_linkhandler_handler $pObj Back-reference to calling object
		 * @param string $recordTableName Name of the table being linked to
		 * @param integer $recordUid Id of the record being linked to
		 * @param integer $currentParameter Id of current target page
		 * @return mixed
		 */
		public function parameterChanger($pObj, $recordTableName, $recordUid, $currentParameter) {
			...
			return $foo;
		}
	}

The method is expected to return the parameter property, whether modified
or not.
