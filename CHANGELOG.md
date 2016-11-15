Big Framework Change log
========================

1.2.2 October 15, 2016
-------------------------
- Bug #31: Big will only set the application default route when [[yii\web\Request::pathInfo]] is en empty string. 
- Bug: ConfigManagerObject::set now uses the correct method in ConfigManager when setting a property (incorrectly used 'add' instead of 'set' before).


1.2.1 April 04, 2016
-------------------------
- Bug #30: Fixes json decoding/encoding correctly in MenuManager and Menu model when params column is empty.


1.2.0 March 02, 2016
-------------------------
- Enh: Translations implemented properly
- Enh: 'Slug' of Category model is now unique
- Enh: Nested menus ready for yii\widgets\Menu can now be created with MenuManager
- Enh: TemplateManager will always use a template even if a requested template is deleted (an empty template is used)
- Upd: ApiDocs updated across Big
- New: ConfigManager added
- New: PluginManager added
- Enh: Category model now implements templates
- Enh: A lot of fixes and improvements
- Enh: Inactive menu items now throws an NotFoundHttpException when requested.


1.1.0 January 24, 2016
-------------------------
- Enh: BigSearch widget: Search result GridViews are now updated with ajax so the whole page doesn't reload
- Upd: FileManager - Elfinder updated to v2.1.6 (from 2.0 rc1)
- Enh #26: A Yii 2 layout can be selected when creating/editing templates
- Enh #22: TemplateEditor now renders block positions correctly
- Enh #25: Removed unnecessary use statement from BlockManager


1.0.1 November 25, 2015
-------------------------
- Manager object properties can now be changed (was read-only before)


1.0.0 September 17, 2015
-------------------------
- Production ready release


0.1.0 June 03, 2015
-------------------------
- New: Added extension manager
- New: removed "scope" from Big
- Enh: Block manager rewritten to use the extension manager
- Enh: Block refactored to be more flexible
- Enh: Further encapsulation of Big Framework


0.0.7 May 31, 2015
-------------------------
- New: Added template manager
- Enh: updated migrations


0.0.6 May 14, 2015
-------------------------

- New: Added category manager
- New: Added NestedSetAction
- Enh: Handling of theme positions rewritten to include the file "positions.php"
- Enh: Added ability to check for active position
- Enh: Added ability to delete various content
- Enh: Internal URL parsing optimized
- Enh: SEO updates
- All modules removed to offer a integrated development package without any request handling


0.0.4 May 11, 2015
-------------------------

- Initial release