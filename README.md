PHP Trait: Populate
===================

A simple Trait for PHP classes enabling properties to be populated
and exported using the object's getters and setters but through a
single method.

`PopulateTrait` is great because it:

* Is simple and fast - no use of Reflection, code generation and such
* Uses getters and setters - always respects your public API
* Is bi-directional - populates *and* exports properties
* Can perform mapping - input's property names can be different from target's
* Can populate and export properties selectively with or without mapping

Usage
-----

Implemented as follows:

```php
namespace MyNamespace;

use Dkd\Populate\PopulateTrait;

/**
 * My populatable class
 */
class MyClassWhichUsesPopulateTrait {
	
	use PopulateTrait;
	
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var boolean
	 */
	protected $before = TRUE;

	/**
	 * @var boolean
	 */
	protected $after = FALSE;

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

Implemented in this class it allows the following to populate data:

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
$array = $source->export();

// export all properties but export "before" value as "after" key in array:
$array = $source->export(array('before' => 'after'));

// export only some properties and map their names to other names:
$array = $source->export(array('before' => 'after'), TRUE);

// export only some properties but keep their names:
$array = $source->export(array('before'), TRUE);
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

Supported input types
---------------------

The `populate()` method supports the following input types:

* Other `PopulateTrait`-implementing object instances
* String-indexed arrays with property=>value syntax
* Any Iterator+ArrayAccess combination

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
4. Valid setter names are (if property is `name`):
  1. `setName`
  2. `setIsName`
  
The additional `is`-style method names are only attemted if the
standard methods do not exist - this is done in order to accommodate
the standard PHP pattern of naming boolean getters/setters using `is`.

Handling an error from PopulateTrait
------------------------------------

Errors from `PopulateTrait` methods are thrown as `Dkd\Populate\Exception`
with a unique exception code for error types, which are:

* `1422045180` when an invalid input type given to `populate()`

And using a more specific `Dkd\Populate\AccessException` for:

* `1422021211` when attempting to populate a setter-less property
* `1422021212` when attempting to export a getter-less property

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

