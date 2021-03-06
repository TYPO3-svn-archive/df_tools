<?php

namespace SGalinski\DfTools\Parser;

/***************************************************************
 *  Copyright notice
 *
 *  (c) Stefan Galinski <stefan.galinski@gmail.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\SingletonInterface;

/**
 * TCA Parser
 *
 * Fetch all text input fields from the TCA configuration and so on...
 */
class TcaParser implements SingletonInterface {
	/**
	 * List of allowed types
	 *
	 * @var array
	 */
	protected $allowedTypes = array();

	/**
	 * Excluded evaluation entries (like date, datetime for text input fields)
	 *
	 * @var array
	 */
	protected $excludedEvals = array();

	/**
	 * List of excluded fields
	 *
	 * Example structures of field entries:
	 *
	 * - All tables: t3ver_label
	 * - Specific table: tt_content<bodytext>
	 *
	 * @var array
	 */
	protected $excludedFields = array();

	/**
	 * List of excluded tables
	 *
	 * @var array
	 */
	protected $excludedTables = array();

	/**
	 * Setter for allowedTypes
	 *
	 * @param array $allowedTypes
	 * @return void
	 */
	public function setAllowedTypes(array $allowedTypes) {
		$this->allowedTypes = $allowedTypes;
	}

	/**
	 * Getter for allowedTypes
	 *
	 * @return array
	 */
	public function getAllowedTypes() {
		return $this->allowedTypes;
	}

	/**
	 * Setter for excludedEvals
	 *
	 * @param array $excludedEvals
	 * @return void
	 */
	public function setExcludedEvals(array $excludedEvals) {
		$this->excludedEvals = $excludedEvals;
	}

	/**
	 * Getter for excludedEvals
	 *
	 * @return array
	 */
	public function getExcludedEvals() {
		return $this->excludedEvals;
	}

	/**
	 * Setter for excludedFields
	 *
	 * @param array $excludedFields
	 * @return void
	 */
	public function setExcludedFields(array $excludedFields) {
		$this->excludedFields = $excludedFields;
	}

	/**
	 * Getter for excludedFields
	 *
	 * @return array
	 */
	public function getExcludedFields() {
		return $this->excludedFields;
	}

	/**
	 * Setter for excludedTables
	 *
	 * @param array $excludedTables
	 * @return void
	 */
	public function setExcludedTables($excludedTables) {
		$this->excludedTables = $excludedTables;
	}

	/**
	 * Getter for excludedTables
	 *
	 * @return array
	 */
	public function getExcludedTables() {
		return $this->excludedTables;
	}

	/**
	 * Returns all text based fields of the selected table
	 *
	 * @param \Closure $callback
	 * @return array
	 */
	public function findFields(\Closure $callback = NULL) {
		if (!is_array($GLOBALS['TCA'])) {
			return array();
		}

		$fields = array();
		foreach ($GLOBALS['TCA'] as $table => $_) {
			if (in_array($table, $this->excludedTables)) {
				continue;
			}

			$foundFields = $this->getFieldsFromTcaTable(
				$table,
				$this->allowedTypes,
				$this->excludedEvals,
				$this->excludedFields,
				$callback
			);

			if (count($foundFields)) {
				$fields[$table] = $foundFields;
			}
		}

		return $fields;
	}

	/**
	 * Checks if an excluded eval value was defined in the given comma-separated list.
	 *
	 * @param string $evalList comma-separated list of eval values
	 * @param array $excludedEvals excluded eval values must be the keys (performance!)
	 * @return bool
	 */
	protected function hasExcludedEval($evalList, $excludedEvals) {
		$excluded = FALSE;
		if (trim($evalList) === '') {
			return $excluded;
		}

		$evalList = explode(',', $evalList);
		foreach ($evalList as $eval) {
			if (isset($excludedEvals[trim($eval)])) {
				$excluded = TRUE;
				break;
			}
		}

		return $excluded;
	}

	/**
	 * Returns fields from a TCA table filtered by allowed types
	 *
	 * @param string $table
	 * @param array $allowedTypes
	 * @param array $excludedEvals
	 * @param array $excludedFields
	 * @param \Closure $callback
	 * @return array
	 */
	protected function getFieldsFromTcaTable(
		$table, $allowedTypes, $excludedEvals, $excludedFields, \Closure $callback = NULL
	) {
		if (!is_array($GLOBALS['TCA'][$table]['columns']) || !count($allowedTypes)) {
			return array();
		}

		$fields = array();
		$excludedEvals = array_flip($excludedEvals);
		$excludedFields = array_flip($excludedFields);
		foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $configuration) {
			$isAllowedType = in_array($configuration['config']['type'], $allowedTypes);
			$hasExcludedEval = $this->hasExcludedEval($configuration['config']['eval'], $excludedEvals);
			$isExcludedField = (isset($excludedFields[$field]) || isset($excludedFields[$table . '<' . $field . '>']));
			if ($isExcludedField || !$isAllowedType || $hasExcludedEval) {
				continue;
			}

			if ($callback !== NULL && is_callable($callback)) {
				if (call_user_func_array($callback, array($configuration, $field))) {
					continue;
				}
			}

			$fields[] = $field;
		}

		return $fields;
	}

	/**
	 * Returns a list of all tables
	 *
	 * @return array
	 */
	public function getAllTables() {
		$tables = array();
		foreach ($GLOBALS['TCA'] as $table => $_) {
			$tables[] = $table;
		}

		return $tables;
	}
}

?>