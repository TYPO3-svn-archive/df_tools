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

use SGalinski\DfTools\Domain\Model\RedirectTest;
use SGalinski\DfTools\Domain\Model\RedirectTestCategory;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Controller for the RedirectTest domain model
 */
class RedirectTestController extends AbstractController {
	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'SGalinski\DfTools\View\RedirectTestArrayView';

	/**
	 * Instance of the redirect test repository
	 *
	 * @inject
	 * @var \SGalinski\DfTools\Domain\Repository\RedirectTestRepository
	 */
	protected $redirectTestRepository;

	/**
	 * Instance of the redirect test category repository
	 *
	 * @inject
	 * @var \SGalinski\DfTools\Domain\Repository\RedirectTestCategoryRepository
	 */
	protected $redirectTestCategoryRepository;

	/**
	 * @return void
	 */
	public function initializeIndexAction() {
		$this->defaultViewObjectName = 'TYPO3\CMS\Fluid\View\TemplateView';
	}

	/**
	 * Displays all redirect tests
	 *
	 * @return string
	 */
	public function indexAction() {
		$this->setLastCalledControllerActionPair();
	}

	/**
	 * Returns all redirect test records
	 *
	 * @param int $offset
	 * @param int $limit
	 * @param string $sortingField
	 * @param boolean $sortAscending
	 * @return void
	 */
	public function readAction($offset, $limit, $sortingField, $sortAscending) {
		/** @var $linkChecks ObjectStorage */
		$records = $this->redirectTestRepository->findSortedAndInRangeByCategory(
			$offset, $limit, array($sortingField => $sortAscending)
		);

		$this->view->assign('records', $records);
		$this->view->assign('totalRecords', $this->redirectTestRepository->countAll());
	}

	/**
	 * Updates an existing redirect test
	 *
	 * @param RedirectTest $redirectTest
	 * @param RedirectTestCategory $newCategory optional
	 * @return void
	 */
	public function updateAction(RedirectTest $redirectTest, RedirectTestCategory $newCategory = NULL) {
		if ($newCategory !== NULL) {
			$this->redirectTestCategoryRepository->add($newCategory);
			$redirectTest->setCategory($newCategory);

			/** @var $persistenceManager PersistenceManager */
			$persistenceManager = $this->objectManager->get('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
			$persistenceManager->persistAll();
		}

		$this->redirectTestRepository->update($redirectTest);
		$this->view->assign('records', array($redirectTest));
	}

	/**
	 * Creates a redirect test
	 *
	 * @param RedirectTest $newRedirectTest
	 * @return void
	 */
	public function createAction(RedirectTest $newRedirectTest) {
		$this->redirectTestRepository->add($newRedirectTest);

		/** @var $persistenceManager PersistenceManager */
		$persistenceManager = $this->objectManager->get('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
		$persistenceManager->persistAll();

		$this->view->assign('records', array($newRedirectTest));
	}

	/**
	 * Removes all redirect tests that can be found with the given identifiers
	 *
	 * @param array $identifiers
	 * @return void
	 */
	public function destroyAction($identifiers) {
		$this->view = NULL;

		foreach ($identifiers as $identifier) {
			$redirectTest = $this->redirectTestRepository->findByUid(intval($identifier));
			$this->redirectTestRepository->remove($redirectTest);
		}
	}

	/**
	 * Runs a single test
	 *
	 * @param int $identity
	 * @return void
	 */
	public function runTestAction($identity) {
		/** @var $redirectTest RedirectTest */
		$redirectTest = $this->redirectTestRepository->findByUid($identity);
		$redirectTest->test($this->getUrlCheckerService());
		$this->forward('saveTest', NULL, NULL, array('redirectTest' => $redirectTest));
	}

	/**
	 * Saves an redirect test (just exists for validation issues)
	 *
	 * @dontverifyrequesthash
	 * @param RedirectTest $redirectTest
	 * @return void
	 */
	protected function saveTestAction(RedirectTest $redirectTest) {
		$this->redirectTestRepository->update($redirectTest);
		$this->handleExceptionalTest($redirectTest);
		$this->view->assign('records', array($redirectTest));
	}
}

?>