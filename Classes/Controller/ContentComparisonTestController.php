<?php

namespace SGalinski\DfTools\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) domainfactory GmbH (Stefan Galinski <stefan.galinski@gmail.com>)
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

use SGalinski\DfTools\Domain\Model\ContentComparisonTest;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Controller for the ContentComparisonTest domain model
 */
class ContentComparisonTestController extends AbstractController {
	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'SGalinski\DfTools\View\ContentComparisonTestArrayView';

	/**
	 * contentComparisonTestRepository
	 *
	 * @inject
	 * @var \SGalinski\DfTools\Domain\Repository\ContentComparisonTestRepository
	 */
	protected $contentComparisonTestRepository;

	/**
	 * @return void
	 */
	public function initializeIndexAction() {
		$this->defaultViewObjectName = 'TYPO3\CMS\Fluid\View\TemplateView';
	}

	/**
	 * Displays all content comparison tests
	 *
	 * @return string
	 */
	public function indexAction() {
		$this->setLastCalledControllerActionPair();
	}

	/**
	 * Reads all existing content comparison tests
	 *
	 * @param int $offset
	 * @param int $limit
	 * @param string $sortingField
	 * @param boolean $sortAscending
	 * @return void
	 */
	public function readAction($offset, $limit, $sortingField, $sortAscending) {
		/** @var $linkChecks ObjectStorage */
		$records = $this->contentComparisonTestRepository->findSortedAndInRange(
			$offset, $limit, array($sortingField => $sortAscending)
		);

		$this->view->assign('records', $records);
		$this->view->assign('totalRecords', $this->contentComparisonTestRepository->countAll());
	}

	/**
	 * Creates a new content comparison test
	 *
	 * @param ContentComparisonTest $newContentComparisonTest
	 * @return void
	 */
	public function createAction(ContentComparisonTest $newContentComparisonTest) {
		$this->contentComparisonTestRepository->add($newContentComparisonTest);

		/** @var $persistenceManager PersistenceManager */
		$persistenceManager = $this->objectManager->get('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
		$persistenceManager->persistAll();

		$this->view->assign('records', array($newContentComparisonTest));
	}

	/**
	 * Updates an existing content comparison test
	 *
	 * @param ContentComparisonTest $contentComparisonTest
	 * @return void
	 */
	public function updateAction(ContentComparisonTest $contentComparisonTest) {
		$this->contentComparisonTestRepository->update($contentComparisonTest);
		$this->view->assign('records', array($contentComparisonTest));
	}

	/**
	 * Removes all content comparison tests that can be found with the given identifiers
	 *
	 * @param array $identifiers
	 * @return void
	 */
	public function destroyAction(array $identifiers) {
		$this->view = NULL;

		foreach ($identifiers as $identifier) {
			$contentComparisonTest = $this->contentComparisonTestRepository->findByUid(intval($identifier));
			$this->contentComparisonTestRepository->remove($contentComparisonTest);
		}
	}

	/**
	 * Updates the test content of an action
	 *
	 * @param int $identity
	 * @return void
	 */
	public function updateTestContentAction($identity) {
		/** @var $contentComparisonTest ContentComparisonTest */
		$contentComparisonTest = $this->contentComparisonTestRepository->findByUid($identity);
		$urlCheckerService = $this->getUrlCheckerService();
		$contentComparisonTest->updateTestContent($urlCheckerService);
		$this->contentComparisonTestRepository->update($contentComparisonTest);
		$this->view->assign('records', array($contentComparisonTest));
	}

	/**
	 * Runs a single test
	 *
	 * @param int $identity
	 * @return void
	 */
	public function runTestAction($identity) {
		/** @var $contentComparisonTest ContentComparisonTest */
		$contentComparisonTest = $this->contentComparisonTestRepository->findByUid($identity);
		$contentComparisonTest->test($this->getUrlCheckerService());
		$this->forward('saveTest', NULL, NULL, array('contentComparisonTest' => $contentComparisonTest));
	}

	/**
	 * Saves an content comparison test (just exists for validation issues)
	 *
	 * @dontverifyrequesthash
	 * @param ContentComparisonTest $contentComparisonTest
	 * @return void
	 */
	protected function saveTestAction(ContentComparisonTest $contentComparisonTest) {
		$this->contentComparisonTestRepository->update($contentComparisonTest);
		$this->handleExceptionalTest($contentComparisonTest);
		$this->view->assign('records', array($contentComparisonTest));
	}
}

?>