Big Framework for Yii 2
===================================

Big Framework for Yii 2 offers basic functionality used in common web applications. This includes a file manager based on [elFinder](http://elfinder.org/), an editor based on [TinyMCE 4](http://www.tinymce.com/), a dynamic menu system with multiple menus and nested menu items and SEO optimized content.

Big provides dynamically loaded module url rules. If the file *UrlRule.php* is placed in the same namespace as the main module file (often Module.php) this url rule will dynamically loaded when creating and parsing urls in Yii. This includes a search system that modules can plug into.

Big adds dynamic functionality to Yii 2 themes by the concept of *Blocks*. Within a theme (or a layout file) include statements can be added.
An include statement defines a position within a layout where one or more blocks will be dynamically loaded into. Creating a block is just like creating a regular Yii 2 widget with the need of one extra method to enable backend management of blocks.

Big Framework is used in [BIG CMS](https://github.com/bigbrush/yii2-bigcms).

Big is built with help from the following libraries:
[Yii](https://github.com/yiisoft/yii2)
[TinyMCE 4](http://www.tinymce.com)
[elFinder](http://elfinder.org)
[Yii2 Nested Sets](https://github.com/creocoder/yii2-nested-sets)

Why is Big so small?
--------------------

The concept behind Big is to provide a toolset for common web applications that can be integrated into any application. Big provides its features without restrictions or rules for extending certain classes. Big provides the following features: 

**Big Module**

Big module handles the following functionality in Big:
  - Menu system
  - Templates
  - Blocks
  - Editor
  - Media manager

**Widgets**
  - Editor based on TinyMCE 4
  - File manager based on elFinder 2.1 (integrated with the editor widget)
  - Search widget (integrated with the editor widget). The search can be plugged into to integrate your own modules.
  - Recorder (used to dynamically add blocks)
  - Template editor (used to create a template based on your layout file). This can be included in your own modules.

**Managers**
  - Block manager responsible for loading, creating and registering blocks.
  - Menu manager responsible for nested set menu system
  - Url manager responsible for the SEO friendly url system

**Search system**
  - A search system that can be plugged into by modules. The search system is used by the editor when inserting links.

**Template**
  - Create multiple templates that can be applied to modules.
  - Can be integrated into existing modules

**Parser**
  - Responsible for parsing Yii 2 layout files
  - Creates SEO optimized content created with the editor

**TODO**
  - How to install
  - How to add positions to a layout file
  - How to create blocks
  - How to plug into the search system
  - How to create module URL rules
  - How to use the widgets