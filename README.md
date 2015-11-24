Big Framework for Yii 2
===================================

[![Yii2](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](http://www.yiiframework.com/)

Big Framework for Yii 2 offers functionality used in common Yii 2 applications. This includes a file manager based on [elFinder](http://elfinder.org/), an editor based on [TinyMCE 4](http://www.tinymce.com/) and a dynamic menu system with multiple menus, nested menu items and SEO optimized content. Finally it offers a nested set category system that can be integrated (quite easily) into Yii 2 modules.

Big is an integrated development kit that provides SEO optimized content. It does so by the following features:
  - Dynamically loaded module url rules. If the file *UrlRule.php* is placed in the same namespace as the main module file (often Module.php) this url rule will be loaded automatically when Big creates and parses urls. The url rule it self is just a regular Yii 2 url rule.
  - Integrated editor, file manager and menu system
  - Open search system (Yii 2 event based) that modules can hook into. This can be done through the application configuration file or from a module (i.e. during [bootstrapping](http://www.yiiframework.com/doc-2.0/guide-runtime-bootstrapping.html)).

Big also provides dynamic functionality to Yii 2 themes by the concept of *Blocks*. Within a theme (a layout file) include statements can be added. An include statement defines a position, within a layout, where one or more blocks will be dynamically loaded. Creating a block is just like creating a regular Yii 2 widget with the need of one extra method needed to enable administration of blocks.

Big Framework is a development kit (and therefore doesn't come with an UI). You can check out its implementation at [BIG CMS](https://github.com/bigbrush/yii2-bigcms) where it is used as a development kit.


Installing via Composer <span id="installing-via-composer"></span>
-----------------------------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).
If you do not already have Composer installed, you may do so by following the instructions at [Yii Docs](https://github.com/yiisoft/yii2/blob/master/docs/guide/start-installation.md#installing-via-composer-).

With Composer installed, you can install Big Cms like so:

Either run

```bash
$ composer require "bigbrush/yii2-big=~1.0"
```

or add

```
"bigbrush/yii2-big": "~1.0"
```

to the `require` section of your `composer.json` file.


Why is Big so small?
--------------------

The concept behind Big is to provide a toolset for common Yii 2 applications that can be integrated into any application. Big provides its features without restrictions or rules for extending certain classes. Big provides the following features: 
  - Menu system
  - Templates
  - Block system
  - Editor
  - File manager
  - Pluggable search system
  - SEO optimized content
  - Extension system
  - Category system
  - Url manager (integrated as an url rule that can be disabled)
  - Nested set menus and categories

Most features are provided through different managers accessible through Big.

**Managers**
  - Block manager responsible for loading, creating and registering blocks.
  - Category manager responsible for nested set categories that can be integrated in to modules
  - Menu manager responsible for the nested set menu system
  - Url manager responsible for the SEO optimized url system
  - Extension manager used to install custom blocks
  - Template manager repsonsible for registering blocks to positions


Widgets
--------------------

Big comes with 5 different widgets:
  - **Editor** based on TinyMCE 4
  - **File manager** based on elFinder 2.1. (integrated with the editor widget)
  - **Search widget** which can be plugged into to integrate your own modules. (integrated with the editor widget)
  - **Template editor** used to create templates based a layout file. This can be included in your own modules. (integrated with the editor widget)
  - **Recorder** used to dynamically add blocks with code


Other tools
--------------------

**Template**
  - Create multiple templates that can be applied to modules.
  - Can be integrated into existing modules


**Parser**
  - Responsible for parsing Yii 2 layout files
  - Creates SEO optimized content created with the editor


Built with
--------------------

Big is built with help from the following libraries:
  - [Yii 2](https://github.com/yiisoft/yii2)
  - [TinyMCE 4](http://www.tinymce.com)
  - [elFinder 2.1](http://elfinder.org)
  - [Yii2 Nested Sets](https://github.com/creocoder/yii2-nested-sets)


TODO
--------------------

  - Create flexible action classes (and views?) so Big is easier to integrate. Some managers (menu manager) are quite complex to integrate.
  - How to install
  - How to add positions to a layout file
  - How to create blocks
  - How to plug into the search system
  - How to create module URL rules
  - How to use the widgets