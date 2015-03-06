PHP Trait: Populate
===================

[![Build Status](https://api.travis-ci.org/dkd/php-populate.svg)](https://travis-ci.org/dkd/php-populate)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dkd/php-populate/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/dkd/php-populate/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/dkd/php-populate/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/dkd/php-populate/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/dkd/php-populate/v/stable.svg)](https://packagist.org/packages/dkd/php-populate)
[![Total Downloads](https://poser.pugx.org/dkd/php-populate/downloads.svg)](https://packagist.org/packages/dkd/php-populate)
[![Latest Unstable Version](https://poser.pugx.org/dkd/php-populate/v/unstable.svg)](https://packagist.org/packages/dkd/php-populate)
[![License](https://poser.pugx.org/dkd/php-populate/license.svg)](https://packagist.org/packages/dkd/php-populate)

A simple Trait for PHP classes enabling properties to be populated
and exported using the object's getters and setters but through a
single method.

`PopulateTrait` is great because it:

* Is simple and fast - no use of code generation and such
* Uses getters and setters - always respects your public API
* Does not add overloaded methods - avoids magic behavior
* Is bi-directional - populates *and* exports properties
* Can perform mapping - input's property names can be different from target's
* Can populate and export properties selectively with or without mapping

Usage
-----

Implemented as follows:

```php
namespace MyNamespace;

use Dkd\Populate\PopulateTrait;
use Dkd\Populate\PopulateInterface;

/**
 * My populatable class
 */
class MyClassWhichUsesPopulateTrait implements PopulateInterface
{
	use PopulateTrait;
	
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var boolean
	 */
	protected $before = true;

	/**
	 * @var boolean
	 */
	protected $after = false;

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param boolean $before
	 */
	public function setIsBefore($before) {
		$this->before = $before;
	}

	/**
	 * @return boolean
	 */
	public function isBefore() {
		return $this->before;
	}

	/**
	 * @param boolean $after
	 */
	public function setIsAfter($after) {
		$this->after = $after;
	}

	/**
	 * @return boolean
	 */
	public function isAfter() {
		return $this->after;
	}
}

```

The `PopulateInterface` is optional - it is included to enable you to
use type hinting in methods, and to be able to pass other instances as
source data when populating. If you plan to pass your objects as data
source to other objects, you **must** either implement the interface
**or** manually call `$source->exportGettableProperties();` to extract
the values before you pass them to `populate()`. The following examples
all illustrate usages where `$source` implements the interface.

Implemented in the class, the Trait allows the following to populate data:

```php
$source = someFakeMethodWhichRetrievesAnObjectImplementingPopulate(); 
$copy = new MyClassWhichUsesPopulateTrait();

// copy all properties:
$copy->populate($source);

// copy properties from one property name to another:
$copy->populate($source, array('before' => 'after'));
// ...$copy's "after" property now contains $source's "before" property value

// copy only a few properties:
$copy->populate($source, array('before'), TRUE);
// ...$copy was only populated with the value of $source's "before" property.
```

And the following to export data to a simple array:

```php
$source = someFakeMethodWhichRetrievesAnObjectImplementingPopulate();

// export all properties:
$array = $source->exportGettableProperties();

// export all properties but export "before" value as "after" key in array:
$array = $source->exportGettableProperties(array('before' => 'after'));

// export only some properties and map their names to other names:
$array = $source->exportGettableProperties(array('before' => 'after'), TRUE);

// export only some properties but keep their names:
$array = $source->exportGettableProperties(array('before'), TRUE);
```

Note that in both examples when no mapping of properties' names is
desired, the array acts as a list of names and not a map as such.
Both methods will intelligently detect if you used a numerically
indexed array or a string indexed array and behave accordingly;
given that no PHP version allows class properties whose names are
an integer value. When encountering a numerically indexed array,
a new property name "map" (in quotes) is created using `array_combine`
with the input as both keys and values.

Because of this the two arrays `array('before' => 'before')` and
`array('before')` have the same meaning when populating or exporting.

Populating with objects: reference or clone?
--------------------------------------------

When you populate an object and the input data contains other object
instances, your expectation may be that `clone` is used on each object
in order to populate with a clone, not a reference.

However, the default behavior of `Populate` is to *populate any object
values with references*. If you wish to have objects cloned instead,
switch to the alternative method:

```php
$copy->populateWithClones($source);
```

This causes **all** object-type values to be cloned before being set
on `$copy`.

If your requirement is that some properties be populated with clones
and others with references, there are two ways to reach your goal:

```php
// Solution #1: populate everything with references, then overwrite
// those properties that require clones by calling the alternative
// cloning method with a list of property names and the "only map
// specified properties" flag set to `true`:
$copy->populate($source);
$copy->populateWithClones($source, array('cloneProperty1', 'cloneProperty2'), true);

// Solution #2: the reverse of the above; populate everything with
// clones then overwrite those properties requiring references:
$copy->populateWithClones($source);
$copy->populate($source, array('referenceProperty1', 'referenceProperty2'), true);
```

Naturally, you would select the method that requires the least number
of property names to be passed in the second populate operation.

The other, more obvious way is outside the scope of `Populate` because
it manually post-processes the values to use clones/references, but
it works just the same:

```
// Manual way #1: populate with references then clone selected properties:
$copy->populate($source);
$copy->setCloneProperty1(clone $copy->getCloneProperty1());
$copy->setCloneProperty2(clone $copy->getCloneProperty2());

// Manual way #2: populate with clones then overwrite selected properties
// with references to the original input value:
$copy->populateWithClones($source);
$copy->setReferenceProperty1($source['referenceProperty1']);
$copy->setReferenceProperty2($source['referenceProperty2']);
```

You can use whichever method fits your application design best. `Populate`
provides methods which are suitable for generic usage but does not prevent
you from using the existing setters and getters in any way.

**Common pitfall**

When populating objects with other objects as source and these other
objects contain nested objects, the cloning that is performed by
`Populate` *does not happen recursively*. To gain control over the
cloning behavior for each object you are advised to define
`__clone` methods on each object you need to control.

See the [official PHP documentation, cloning chapter](http://php.net/manual/en/language.oop5.cloning.php)

Supported input types
---------------------

The `populate()` method supports the following input types:

* Other `PopulateTrait`-implementing object instances of any type
* String-indexed arrays with property=>value syntax
* Any `Iterator`+`ArrayAccess` combination is fully supported
* `ArrayAccess` without Iterator is partially supported

Note that the final type, an object implementing `ArrayAccess` but
*not* implementing `Iterator`, is only supported when using a manual
list of property names along with the "only selected properties" flag.

Supported getters and setters
-----------------------------

`PopulateTrait` does not care about the value types you are setting,
but it does care about how your getters and setters are constructed.

In order to properly use your getters and setters, `PopulateTrait`
requires the following to be fulfilled:

1. Getters must have no mandatory arguments
2. Setters must have no more than one mandatory argument
3. Valid getter names are (if property is `name`):
  1. `getName`
  2. `isName`
  3. `getIsName`
  4. `name`
4. Valid setter names are (if property is `name`):
  1. `setName`
  2. `setIsName`
  
The additional `is`-style method names are only attemted if the
standard methods do not exist - this is done in order to accommodate
the standard PHP pattern of naming boolean getters/setters using `is`.
The getter can also be just the property name. This could be useful if
the property is named like `$isFinished`.

**Special requirements**

There must be only one getter for each property. If multiple getters
are found for a property, based on the rules above, an exception is
thrown. This is required to ensure that the getter for a property always
acts the same way.

Handling an error from PopulateTrait
------------------------------------

Errors from `PopulateTrait` methods are thrown as `Dkd\Populate\Exception`
with a unique exception code for error types, which are:

* `1422045180` when an invalid input type given to `populate()`

And using a more specific `Dkd\Populate\AccessException` for:

* `1422021211` when attempting to populate a setter-less property
* `1422021212` when attempting to export a getter-less property
* `1424776261` when multiple property accessor methods are found

The `Exception` type is caught as usual:

```php
try {
	$populatable = new MyClassWhichUsesPopulateTrait();
	$populatable->populate($valuesWithPotentialErrors);
} catch (\Dkd\Populate\AccessException $error) {
	// attempt at illegal access - could be a security issue.
} catch (\Dkd\Populate\Exception $error) {
	// general failure - do something about it.
}
```

Edge cases
----------

Being very compact and not using Reflection or any configuration of
any kind, there are some edge cases that `Populate` cannot handle.

The edge cases and their workarounds:

* `Populate` does not set public properties on classes implementing
  the Trait. To work around this, handle your public properties
  manually. If your properties are already public then you do not need
  the logic provided by `PopulateTrait` in the first place.
* `Populate` does not support overloaded methods for getters and setters.
  The only "workaround" is to implement proper getters and setters.
* `Populate` is not recursive; `populate()` does not get called on
  child property values. To work around this, convert any values
  before passing them to `populate()`. You can create recursive
  methods that consume `PopulateInterface` instances and manually
  use `export()` and recurse those values before calling `populate()`.
  This also means that any nested object instances will not be cloned
  automatically when you call `populateWithClones`. To work around this
  when using arrays as source, your custom recursive methods should take
  care of the cloning. When using objects as source, you can implement
  the `__clone` method to control the cloning beahavior for each object.
* `Populate` can only use proper PHP type hints to determine an
  expected input type. This means that your `@param` annotations are
  **not** taken into consideration. Setters which perform additional
  validation of input arguments may still throw errors. In other words,
  `Populate()` does not attempt to catch errors from any method it calls.
  To work around this, manually remove any invalid values from the input
  data before you pass it to `populate()`.
