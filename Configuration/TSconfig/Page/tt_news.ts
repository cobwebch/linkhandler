// Page TSconfig for registering the linkhandler for "tt_news" records
TCEMAIN.linkHandler.tx_ttnews {
	handler = Cobweb\Linkhandler\RecordLinkHandler
	label = LLL:EXT:linkhandler/Resources/Private/Language/locallang.xlf:tab.news
	configuration {
		table = tt_news
	}
	scanBefore = page
}
