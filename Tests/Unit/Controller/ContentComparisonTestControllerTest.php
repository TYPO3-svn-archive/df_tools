<?php

namespace SGalinski\DfTools\Tests\Unit\Controller;

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
 *  the Free Software Foundation; either version 2 of the License, or
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

use SGalinski\DfTools\Controller\ContentComparisonTestController;
use SGalinski\DfTools\Domain\Model\ContentComparisonTest;
use SGalinski\DfTools\Domain\Repository\ContentComparisonTestRepository;
use SGalinski\DfTools\UrlChecker\AbstractService;
use SGalinski\DfTools\View\ContentComparisonTestArrayView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class ContentComparisonTestControllerTest
 */
class ContentComparisonTestControllerTest extends ControllerTestCase {
	/**
	 * @var \SGalinski\DfTools\Controller\ContentComparisonTestController
	 */
	protected $fixture;

	/**
	 * @var \SGalinski\DfTools\Domain\Repository\ContentComparisonTestRepository
	 */
	protected $repository;

	/**
	 * @var \SGalinski\DfTools\View\ContentComparisonTestArrayView
	 */
	protected $view;

	/**
	 * @return void
	 */
	public function setUp() {
		$class = 'SGalinski\DfTools\Controller\ContentComparisonTestController';
		$this->fixture = $this->getAccessibleMock($class, array('forward', 'getUrlCheckerService'));
		$this->fixture->injectObjectManager($this->objectManager);

		/** @var $repository ContentComparisonTestRepository */
		$this->repository = $this->getMock(
			'SGalinski\DfTools\Domain\Repository\ContentComparisonTestRepository',
			array('findAll', 'findByUid', 'update', 'add', 'remove', 'countAll', 'findSortedAndInRange'),
			array($this->objectManager)
		);
		$this->fixture->injectContentComparisonTestRepository($this->repository);

		/** @noinspection PhpUndefinedMethodInspection */
		$this->view = $this->getMock('SGalinski\DfTools\View\ContentComparisonTestArrayView', array('assign'));
		$this->fixture->_set('view', $this->view);
	}

	/**
	 * @return ContentComparisonTest
	 */
	protected function getContentComparisonTest() {
		/** @var $contentComparisonTest ContentComparisonTest */
		$contentComparisonTest = $this->getMockBuilder('SGalinski\DfTools\Domain\Model\ContentComparisonTest')
			->setMethods(array('test', 'updateTestContent'))
			->disableOriginalClone()->getMock();
		$contentComparisonTest->setTestUrl('FooBar');
		$contentComparisonTest->setCompareUrl('FooBar');

		return $contentComparisonTest;
	}

	/**
	 * @test
	 * @return void
	 */
	public function testInjectRedirectTestCategoryRepository() {
		/** @var $repository ContentComparisonTestRepository */
		$repository = new ContentComparisonTestRepository($this->objectManager);
		$this->fixture->injectContentComparisonTestRepository($repository);

		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertSame($repository, $this->fixture->_get('contentComparisonTestRepository'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function readFetchesSortedRange() {
		/** @noinspection PhpUndefinedMethodInspection */
		$this->repository->expects($this->once())->method('findSortedAndInRange')
			->with(1, 2, array('test' => TRUE));
		$this->repository->expects($this->once())->method('countAll');
		$this->view->expects($this->exactly(2))->method('assign');
		$this->fixture->readAction(1, 2, 'test', TRUE);
	}

	/**
	 * @test
	 * @return void
	 */
	public function createActionCreatedNewRecord() {
		/** @noinspection PhpUndefinedMethodInspection */
		$this->addMockedCallToPersistAll();
		$contentComparisonTest = $this->getContentComparisonTest();
		$this->repository->expects($this->once())->method('add')->with($contentComparisonTest);
		$this->fixture->createAction($contentComparisonTest);
	}

	/**
	 * @test
	 * @return void
	 */
	public function updateActionUpdatesData() {
		/** @noinspection PhpUndefinedMethodInspection */
		$contentComparisonTest = $this->getContentComparisonTest();
		$this->repository->expects($this->once())->method('update')->with($contentComparisonTest);
		$this->fixture->updateAction($contentComparisonTest);
	}

	/**
	 * @test
	 * @return void
	 */
	public function destroyActionRemovesRedirectTests() {
		/** @noinspection PhpUndefinedMethodInspection */
		$contentComparisonTest = $this->getContentComparisonTest();
		$this->repository->expects($this->exactly(2))->method('remove')->with($contentComparisonTest);
		$this->repository->expects($this->exactly(2))->method('findByUid')
			->will($this->returnValue($contentComparisonTest))
			->with($this->isType('integer'));
		$this->fixture->destroyAction(array(10, 20));
	}

	/**
	 * @test
	 * @return void
	 */
	public function updateTestContentWorks() {
		/** @var $urlCheckerService AbstractService */
		$class = 'SGalinski\DfTools\UrlChecker\AbstractService';
		$urlCheckerService = $this->getMock($class, array('init', 'resolveURL'));

		/** @noinspection PhpUndefinedMethodInspection */
		$contentComparisonTest = $this->getContentComparisonTest();
		$this->repository->expects($this->once())->method('findByUid')
			->will($this->returnValue($contentComparisonTest))->with(1);

		$contentComparisonTest->expects($this->once())->method('updateTestContent')->with($urlCheckerService);
		$this->view->expects($this->once())->method('assign')->with('records', $this->isType('array'));
		$this->fixture->expects($this->once())->method('getUrlCheckerService')
			->will($this->returnValue($urlCheckerService));

		$this->fixture->updateTestContentAction(1);
	}

	/**
	 * @test
	 * @return void
	 */
	public function updateAllTestContentsWorks() {
		$contentComparisonTest1 = $this->getContentComparisonTest();
		$contentComparisonTest2 = $this->getContentComparisonTest();

		$testCollection = new ObjectStorage();
		$testCollection->attach($contentComparisonTest1);
		$testCollection->attach($contentComparisonTest2);

		/** @var $urlCheckerService AbstractService */
		$class = 'SGalinski\DfTools\UrlChecker\AbstractService';
		$urlCheckerService = $this->getMock($class, array('init', 'resolveURL'));

		/** @noinspection PhpUndefinedMethodInspection */
		$this->repository->expects($this->once())->method('findAll')
			->will($this->returnValue($testCollection));
		$this->view->expects($this->once())->method('assign')
			->with('records', $this->isInstanceOf('TYPO3\CMS\Extbase\Persistence\ObjectStorage'));
		$this->fixture->expects($this->once())->method('getUrlCheckerService')
			->will($this->returnValue($urlCheckerService));
		$contentComparisonTest1->expects($this->once())->method('updateTestContent')->with($urlCheckerService);
		$contentComparisonTest2->expects($this->once())->method('updateTestContent')->with($urlCheckerService);
		$this->fixture->updateAllTestContentsAction();
	}

	/**
	 * @test
	 * @return void
	 */
	public function runTestWorks() {
		/** @var $urlCheckerService AbstractService */
		$class = 'SGalinski\DfTools\UrlChecker\AbstractService';
		$urlCheckerService = $this->getMock($class, array('init', 'resolveURL'));

		/** @noinspection PhpUndefinedMethodInspection */
		$contentComparisonTest = $this->getContentComparisonTest();
		$this->repository->expects($this->once())->method('findByUid')
			->will($this->returnValue($contentComparisonTest))->with(1);

		$contentComparisonTest->expects($this->once())->method('test')->with($urlCheckerService);
		$this->fixture->expects($this->once())->method('forward');
		$this->fixture->expects($this->once())->method('getUrlCheckerService')
			->will($this->returnValue($urlCheckerService));

		$this->fixture->runTestAction(1);
	}

	/**
	 * @test
	 * @return void
	 */
	public function runAllTestsWorks() {
		$contentComparisonTest1 = $this->getContentComparisonTest();
		$contentComparisonTest2 = $this->getContentComparisonTest();

		$testCollection = new ObjectStorage();
		$testCollection->attach($contentComparisonTest1);
		$testCollection->attach($contentComparisonTest2);

		/** @var $urlCheckerService AbstractService */
		$class = 'SGalinski\DfTools\UrlChecker\AbstractService';
		$urlCheckerService = $this->getMock($class, array('init', 'resolveURL'));

		/** @noinspection PhpUndefinedMethodInspection */
		$this->repository->expects($this->once())->method('findAll')
			->will($this->returnValue($testCollection));
		$this->view->expects($this->once())->method('assign')
			->with('records', $this->isInstanceOf('TYPO3\CMS\Extbase\Persistence\ObjectStorage'));
		$this->fixture->expects($this->once())->method('getUrlCheckerService')
			->will($this->returnValue($urlCheckerService));
		$contentComparisonTest1->expects($this->once())->method('test')->with($urlCheckerService);
		$contentComparisonTest2->expects($this->once())->method('test')->with($urlCheckerService);
		$this->fixture->runAllTestsAction();
	}
}

?>