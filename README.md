# FatFree Swoole

In order to get this to work, clone this repo, then run `composer install` then do the following:
1. Open up base.php in the Fatfree framework. Remove the "final" keyword from `final class Base extends Prefab implements ArrayAccess {`
1. Down around line 134, change all the `private $hive, $init, $languages` etc to `protected`
1. Around line 2286, comment out the `private function __clone() {}` so the base can be cloned.