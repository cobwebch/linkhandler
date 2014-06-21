
# Linkhandler

This is a fork of the linkhandler TYPO3 extension.

## About this fork

This is an experimental version of the linkhandler extension that aims
to minimize the amount of duplicate code.

Additionally, all legacy code was removed, the goal is to provide
a version that is compatible with TYPO3 6.2.

## Required TYPO3 patches

Unfortunately not all isses in TYPO3 are merged yet. These issues are still pending and need review / testing for this Extension to work correctly:

* Treat linkhandler links as internal URLs: https://review.typo3.org/#/c/27680/
* Required soft reference parser hooks: https://review.typo3.org/#/c/27746/

There is a TYPO3 6.2 fork that already implements the required patches (and some more) at Github: https://github.com/Intera/TYPO3.CMS

## Additional features

This Extension offers some additional features compared to the original version.

### Multiple configurations for the same table

It is now possible to create links to the same table within different configurations. E.g. you could link
to different content types to different target pages.

For this to work the links now consists of four parts instead of three. The old link block looked like this:

```
record:<table_name>:<uid>
```

The new link block looks like this:

```
record:<config_key>:<table_name>:<uid>
```

This feature is backward compatible. If an old link is detected that consists only of three parts the first
configuration found for the linked table will be used.

### Record filtering

The records that are displayed in the list can be filtered by SQL queries. You can define a search query
for each table configured in ```listTables```. This is an example configuration for using the
```additionalSearchQueries``` option in the TSConfig:

```
mod.tx_linkhandler.tx_myext_imagelinks {
	label = Image link
	listTables = tt_content
	additionalSearchQueries {
		tt_content = AND tt_content.CType='image'
	}
}
```

### Page tree mount points

You can configure mount points for the page tree that is displayed in the element browser.

For example if you only want to diplay the pages where your news records are located
(in this example PID 123 and 234) you can use the following Page TSConfig:

```
mod.tx_linkhandler.tx_news_news.pageTreeMountPoints {
	1 = 123
	2 = 234
}
RTE.default.tx_linkhandler.tx_news_news.pageTreeMountPoints < mod.tx_linkhandler.tx_news_news.pageTreeMountPoints
```

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

## Missing Feature

The last missing feature in this version is the handling of the "Save and show" button.

