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

Forces the link browser to open directly in the page with the given id.

#### configuration.hidePageTree

Set to 1 to complete hide the page tree. This is particularly useful
in conjunction with the `storagePid` option above, since it makes it
possible to work with just a list of records without having to click
around the page tree.

#### configuration.pageTreeMountPoints

Numbered array of page uid's which will be used instead of the full page tree.

The syntax is a numbered array:

```
pageTreeMountPoints {
	1 = 18
	2 = 91
}
```


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


## Hooks

A single hook is provided. It can be used to manipulate most of the data from
the `\Cobweb\Linkhandler\TypolinkHandler` class before the typolink is actually
generated. An example usage could be to change the link target pid dynamically
based on some values from the record being linked to.

Hook usage should be declared in an extension's `ext_localconf.php`file:

```
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkhandler']['generateLink'][] = '\Foo\Bar\MyParameterProcessor';
```

The declared class must implement interface `\Cobweb\Linkhandler\ProcessLinkParameterInterface`.
It can use the many getters and setters of `\Cobweb\Linkhandler\TypolinkHandler`
to read and write data.


## Soft reference handling

Extension "linkhandler" provides a soft reference parser which will pick any
record being linked to and update the system references accordingly.


## Linkvalidator support

This extension hooks into linkvalidator for checking record links. It is automatically activated.
In case you want to disable it, you can use the following Page TSconfig:

To use it you have to adjust your linkvalidator TSConfig:

```
mod.linkvalidator.linktypes := removeFromList(tx_linkhandler)
}
```

There is an additional configuration option that allows the reporting of links that point to
disabled records (hidden, start tieme not met yet, etc):

```
mod.linkvalidator {
	tx_linkhandler.reportHiddenRecords = 1
}
```


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
