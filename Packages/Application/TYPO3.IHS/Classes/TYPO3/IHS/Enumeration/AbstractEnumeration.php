<?php
namespace TYPO3\IHS\Enumeration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Neos\Flow\Annotations as Flow;

/**
 * Class AbstractEnumeration
 * @Flow\Proxy(false)
 */
class AbstractEnumeration {

	/**
	 * @var array
	 */
	private static $enumConstants;

	/**
	 * @param mixed $value
	 * @throws \InvalidArgumentException
	 */
	public function __construct($value = NULL) {
		if ($value === NULL && !defined('static::__default')) {
			throw new \InvalidArgumentException(
				sprintf('A value for %s is required if no __default is defined.', get_class($this)),
				1381512753
			);
		}
		if ($value === NULL) {
			$value = static::__default;
		}
		$this->loadValues();
		if (!$this->isValid($value)) {
			throw new \InvalidArgumentException(
				sprintf('Invalid value %s for %s', $value, get_class($this)),
				1381512761
			);
		}
		$this->setValue($value);
	}

	/**
	 * @throws \Exception
	 * @internal param string $class
	 */
	private function loadValues() {
		$class = get_class($this);

		if (isset(self::$enumConstants[$class])) {
			return;
		}

		$reflection = new \ReflectionClass($class);
		$constants = $reflection->getConstants();
		$defaultValue = NULL;
		if (isset($constants['__default'])) {
			$defaultValue = $constants['__default'];
			unset($constants['__default']);
		}
		if (empty($constants)) {
			throw new \InvalidArgumentException(
				sprintf(
					'No enumeration constants defined for "%s"', $class
				),
				1381512807
			);
		}
		foreach ($constants as $constant => $value) {
			if (!is_int($value) && !is_string($value)) {
				throw new \InvalidArgumentException(
					sprintf(
						'Constant value must be of type integer or string; constant=%s; type=%s',
						$constant,
						is_object($value) ? get_class($value) : gettype($value)
					),
					1381512797
				);
			}
		}
		$constantValueCounts = array_count_values($constants);
		arsort($constantValueCounts, SORT_NUMERIC);
		$constantValueCount = current($constantValueCounts);
		$constant = key($constantValueCounts);
		if ($constantValueCount > 1) {
			throw new \InvalidArgumentException(
				sprintf(
					'Constant value is not unique; constant=%s; value=%s; enum=%s',
					$constant, $constantValueCount, $class
				),
				1381512859
			);
		}
		if ($defaultValue !== NULL) {
			$constants['__default'] = $defaultValue;
		}
		self::$enumConstants[$class] = $constants;
	}

	/**
	 * Set the Enumeration value to the associated enumeration value by a loose comparison.
	 * The value, that is used as the enumeration value, will be of the same type like defined in the enumeration
	 *
	 * @param mixed $value
	 * @throws \InvalidArgumentException
	 */
	private function setValue($value) {
		$enumKey = array_search($value, self::$enumConstants[get_class($this)]);
		if ($enumKey === FALSE) {
			throw new \InvalidArgumentException(
				sprintf('Invalid value %s for %s', $value, __CLASS__),
				1381615295
			);
		}
		$this->value = self::$enumConstants[get_class($this)][$enumKey];
	}

	/**
	 * Check if the value on this enum is a valid value for the enum
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	private function isValid($value) {
		$value = (string) $value;
		foreach (self::$enumConstants[get_class($this)] as $constantValue) {
			if ($value === (string) $constantValue) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Get the valid values for this enum
	 * Defaults to constants you define in your subclass
	 * override to provide custom functionality
	 *
	 * @param boolean $include_default
	 * @return array
	 */
	public function getConstants($include_default = FALSE) {
		$enumConstants = self::$enumConstants[get_class($this)];
		if (!$include_default) {
			unset($enumConstants['__default']);
		}
		return $enumConstants;
	}

	/**
	 * Cast value to enumeration type
	 *
	 * @param mixed $value Value that has to be casted
	 * @return AbstractEnumeration
	 */
	public static function cast($value) {
		if ($value instanceof \Doctrine\ORM\Proxy\Proxy) {
			$value->__load();
		}
		$currentClass = get_called_class();
		if (is_subclass_of($currentClass, '\Doctrine\ORM\Proxy\Proxy')) {
			$parents = class_parents($currentClass);
			$currentClass = array_shift($parents);
		}
		if (!is_object($value) || get_class($value) !== $currentClass) {
			$value = new $currentClass($value);
		}
		return $value;
	}

	/**
	 * Compare if the value of the current object value equals the given value
	 *
	 * @param mixed $value default
	 * @return boolean
	 */
	public function equals($value) {
		$value = static::cast($value);
		return (string)$this === (string)$value;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return (string)$this->value;
	}
} 