<?php
if (!defined ('TYPO3_MODE'))
	die ('Access denied.');

t3lib_extMgm::addStaticFile($_EXTKEY,'static/link_handler/', 'link handler');

	// hide the button saveDocView for tt_news categories
t3lib_extMgm::addUserTSconfig('
	options.saveDocView.tt_news = 1
');

t3lib_extMgm::addPageTSConfig('
RTE.default.tx_linkhandler {
	tt_news {
		label=News
		listTables=tt_news
	}
}

mod.tx_linkhandler {
	tt_news {
		label=News
		listTables=tt_news
		previewPageId = 1
	}
}
');

?>