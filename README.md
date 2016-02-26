# Linkhandler

This extensions uses the TYPO3 CMS Core API to provide the possibility
to define additional tabs in the link browser to create links directly
to specific records.

Originally the "linkhandler" was created by AOE GmbH. This fork progressively
drifted away from the original and has now been nearly fully rewritten for
TYPO3 CMS 7 LTS, using its new API.

Default configurations are provided for both "news" and "tt_news"
extensions.

For a version compatible with TYPO3 CMS 6.2, please use the TYPO3_6-2 branch.

## Configuration

The configuration comes in two parts. TSconfig is used to define the
tabs for the link browser and TypoScript is used to define how to build
the typolinks when rendering the links.

### TSconfig

Default configurations can be included in any page using the
new "Include Page TSConfig (from extensions):" feature when
editing the "Resources" tab of a page.

This is the TSconfig used for linking to news records of extension "news":

```
// Page TSconfig for registering the linkhandler for "news" records
TCEMAIN.linkHandler.tx_news {
	handler = Cobweb\Linkhandler\RecordLinkHandler
	label = LLL:EXT:linkhandler/Resources/Private/Language/locallang.xlf:tab.news
	configuration {
		table = tx_news_domain_model_news
	}
	scanBefore = page
}
```

If you would like to link to any other type of record, just duplicate that
configuration and change the `label` and `configuration.table` options.

You will also need to change the key used in the definition, i.e.
`tx_news` in the above example. Make sure you use a uniquer key,
otherwise your new configuration will override another one.

Leave the other options untouched.

Additional options exist and are described below:

#### configuration.storagePid

Makes it possible to reference a single storage pid. The link browser will
automatically open in that page/folder.

#### configuration.hidePageTree

Set to 1 to complete hide the page tree. This is particularly useful
in conjunction with the `storagePid` option above, since it makes it
possible to work with just a list of records without having to click
around the page tree.


### TypoScript

Include TS static templates as needed.

Again let's consider the configuration for "news" as an example

```
plugin.tx_linkhandler.tx_news {

	// Do not force link generation when the news records are hidden or deleted.
	forceLink = 0

	typolink {
		parameter = {$plugin.tx_linkhandler.news.singlePid}
		additionalParams = &tx_news_pi1[news]={field:uid}&tx_news_pi1[controller]=News&tx_news_pi1[action]=detail
		additionalParams.insertData = 1
		useCacheHash = 1
	}
}
```

Note that the configuration key (i.e. `tx_news`) needs to be the same as the one
used for the TSconfig part. The configuration is straight TS using the
"typolink" function.

#### Special configuration options

##### forceLink

Set to 1 to force the link generation even if the record is hidden,
deleted or the user is not allowed to access it.

##### typolink.mergeWithLinkhandlerConfiguration

This configuration is needed when creating a link directly with TypoScript and not
in a content element. For example, with such a code:

```
lib.foo {
	10 = TEXT
	10 {
		typolink {
			mergeWithLinkhandlerConfiguration = 1
			parameter = record:tx_news:tx_news_domain_model_news:11 - foo "Link from TS menu"
			returnLast = url
		}
	}
}
```

In this case we want the `returnLast = url` parameter to be merged with the default
rendering configuration. With the `mergeWithLinkhandlerConfiguration = 1` we tell
"linkhandler" to do just that.

## Tips & Tricks

### Link browser width

You can use TSConfig to increase the with of the link browser windows in the backend.
The default size is a bit too small especially when have those extra tabs.

```
RTE {
	default {
		buttons {
			link {
				dialogueWindow {
					width = 800
				}
			}
		}
	}
}
```


**TODO: all the feature below are untested with TYPO3 CMS 7 LTS. Some may even have been removed during the cleanup but could be introduced again.**

## Linkvalidator support

This linkhandler version comes with its own linkvalidator link type that supports the new link format with four parameters
and provides some additional features that are not merged yet to the core.

To use it you have to adjust your linkvalidator TSConfig:

```
mod.linkvalidator {
	linktypes = db,external,tx_linkhandler
}
```

Please not that you need to use ```tx_linkhandler``` instead of ```linkhandler``` which is the default link type that comes with the core.

This link type comes with an additional configuration option that allows the reporting of links that point to  hidden records:

```
mod.linkvalidator {
	tx_linkhandler.reportHiddenRecords = 1
}
```

For this additional option to work this pending TYPO3 patch is required: https://review.typo3.org/#/c/26499/ (Provide TSConfig to link checkers).
There is a TYPO3 6.2 fork that already implements the required patches (and some more) at Github: https://github.com/Intera/TYPO3.CMS


### Additional goodies

* SoftReference handling using signal slots, TYPO3 patch pending: https://review.typo3.org/27746/
