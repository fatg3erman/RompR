# Browser Compatibility

As web technologies are generally based on good standards, Rompr should work in most browsers. But as web standards are open to interpretation there are always things that differ slightly. Testing everything in every browser is hugely time-consuming and very tedious so I don't do it.

All development is done in Firefox. I do some testing on Chrome and Safari. In day to day use I usually use an iPad or iPhone so they get good coverage.

RompR makes use of some emerging standards to increase performance. These are not supported on all browsers but will be in the future. In most cases RompR can detect this and will fall back to less-optimal behaviour. The main two ways these are used is for lazy-loading images - so that not all images on a page have to be loaded at once (really speeds up the album art manager and the sort by Album/Albums by Artist modes), and monitoring resizing of dropdown menus in the UI. Where the latter is not supported you will notice that menus may flicker or jump.

## List of Browsers

* Firefox : Tested in version 72 on macOS. Lazy loading is supported from version 55. Dropdown menu resizing is supported from version 69.
* Chrome : Tested in version 79 on macOS. Lazy loading is supported from version 51. Dropdown menu resizing is supported from version 64.
* Chrome for Android : Should be same as Chrome but I have not tested it.
* Android WebView : Might work but I've no way to test it.
* Safari : Tested in version 13 on macOS. Lazy loading is supported from version 12.1. Dropdown menu resizing is not currently supported.
* Safari for iPadOS : Tested in version 13. Lazy loading is an experimental feature which is enabled by default.
Resizing is disabled by default but is not required in the phone skin.
* Safari for iOS : Should be the same as iPadOS.
* ** Note, in iPadOS 13 or later Safari will identify as a desktop browser instead of a mobile browser, and so some of RompR's optimisations
for touch will not work. You can turn this behaviour off in Settings->Safari->Request Desktop Website **
* Opera : Not tested. Lazy loading is supported though there are no details of which version. Dropdown menu resizing is supported from version 51. Opera is now based on Chrome anyway so should work the same way.
* Edge : Not tested. Lazy loading is supported from version 15. Dropdown menu resizing is not currently supported. Future versions of Edge will be based on Chrome, so should work.
* Internet Explorer: Ha. Hahahahahahaha. Hahahahahaha. Are you from the 1990s?
* Samsung Internet : Good luck to you, really.
* Netscape : No, I'm joking.