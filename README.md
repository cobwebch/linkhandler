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

**TODO: describe the other options**

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

### Pass on parent typolink configuration

*This feature is experimental and might change in the future!* Testing and suggestions welcome :)

A config option called ```overrideParentTypolinkConfiguration``` is available. When this option is enabled for a tab configuration,
the original configuration passed to the ```typolink()``` method will be used as the basis for the final typolink configuration that
is used in linkhandler. Here is an example:

Imagine you have a typolink like this:

```
lib.myobj = TEXT
lib.myobj {
	value = Hello World
	typolink.parameter = record:tx_news_news:tx_news_domain_model_news:1
	typolink.no_cache = 1
}
```

When you now configure for example the news records like this, the ```no_cache``` option from the ```lib.myobj.typolink``` block
will be passed on to the typolink call used by linkhandler:

```
plugin.tx_linkhandler.tx_news_news {
	overrideParentTypolinkConfiguration = 1
}
```

You can still override this behavior in the linkhandler configuration though because the ```typolink`` block is merged into
the parent configuration:

```
plugin.tx_linkhandler.tx_news_news {
	overrideParentTypolinkConfiguration = 1
	typolink.no_cache = 0
}
```

The only value that is **always** overwritten is the ```parameter``` configuration.


### Additional goodies

* When editing a link the correct tab will open automatically.
* The searchbox below the record list can be disabled by setting ```enableSearchBox = 0``` in the tab configuration in TSConfig.
* SoftReference handling using signal slots, TYPO3 patch pending: https://review.typo3.org/27746/
* The current link is displayed in a nice label consisting of the localized table label and the linked record title.

## Tips & Tricks

### Link browser width

You can use TSConfig to increase the with of the link browser windows in the Backend to prevent problem with the styles:

```
RTE {
	default {
		buttons {
			link {
				dialogueWindow {
					width = 600
				}
			}
		}
	}
}
```
