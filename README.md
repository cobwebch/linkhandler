
# Linkhandler

This is a fork of the linkhandler TYPO3 extension.

## About this fork

This is an experimental version of the linkhandler extension that aims
to minimize the amount of duplicate code.

Additionally, all legacy code was removed, the goal is to provide
a version that is compatible with TYPO3 6.2.


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

### Additional goodies

* When editing a link the correct tab will open automatically.
* The searchbox below the record list can be disabled by setting ```enableSearchBox = 0``` in the tab configuration in TSConfig.

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

## Warning

Warning!
Some of the new features are still missing, like softrefs and the
modification of the "Save and show" button.
