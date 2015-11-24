// Page TSconfig for registering the linkhandler for "news" records
TCEMAIN.linkHandler.tx_news {
	handler = Cobweb\Linkhandler\RecordLinkHandler
	label = LLL:EXT:linkhandler/Resources/Private/Language/locallang.xlf:tab.news
	configuration {
		table = tx_news_domain_model_news
	}
	scanBefore = page
}
