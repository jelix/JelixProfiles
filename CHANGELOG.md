Changelog
=========

Version 1.3.1
-------------

- Fix deprecation notice with PHP 8.4

Version 1.3.0
-------------

* New interface `ProfilePluginInterface` for plugins.
  Plugins should implement this interface. This is already the case for plugins inheriting from ReaderPlugin.
* New interface `ProfileInstancePluginInterface` for plugins. It allows plugins to implement methods to 
  instantiate and terminate connector objects. It is an alternative to the callback given to `ProfilesContainer::getOrStoreInPool()`
* New method `ProfilesContainer::getConnector()`, used to retrieve a connector object from plugins.
* New method `ProfilesContainer::getConnectorFromCallback()`, it replaces `ProfilesContainer::getOrStoreInPool()` which is deprecated
* New method `ProfilesContainer::storeConnectorInPool()`, it replaces `ProfilesContainer::storeInPool()` which is deprecated
* New method `ProfilesContainer::getConnectorFromPool()`, it replaces `ProfilesContainer::getFromPool()` which is deprecated


Version 1.2.1
-------------

- Fix some regressions
- Fix a performance issue: reader plugins should not be loaded several times per category

Version 1.2.0
-------------

Rework internal storage to be more efficient. Format of the cache file has changed.

Version 1.1.0
-------------

- ProfilesReader: possibility to indicate a callback instead of a list of plugins
  so plugins can be managed as we want.


version 1.0.0
-------------

Initial code, retrieved from Jelix 1.7, and reworked with namespaces.
