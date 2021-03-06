<?php

namespace SGalinski\DfTools\Tests\Unit\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) domainfactory GmbH (Stefan Galinski <stefan.galinsk@gmail.com>)
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

use SGalinski\DfTools\Domain\Model\LinkCheck;
use SGalinski\DfTools\Domain\Model\RecordSet;
use SGalinski\DfTools\Domain\Repository\LinkCheckRepository;
use SGalinski\DfTools\Domain\Repository\RecordSetRepository;
use SGalinski\DfTools\Tests\Unit\Controller\ControllerTestCase;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * Class UrlSynchronizeServiceTest
 */
class UrlSynchronizeServiceTest extends ControllerTestCase {
	/**
	 * @var \SGalinski\DfTools\Domain\Service\UrlSynchronizeService|object
	 */
	protected $fixture;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager|object
	 */
	protected $objectManager;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->fixture = $this->getAccessibleMock(
			'SGalinski\DfTools\Domain\Service\UrlSynchronizeService',
			array('fetchExistingRawRecordSets', 'fetchExistingRawUrls')
		);

		$this->objectManager = $this->getMock('TYPO3\CMS\Extbase\Object\ObjectManager', array('dummy'));
		$this->fixture->_set('objectManager', $this->objectManager);
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @return void
	 */
	protected function prepareTestSynchronizationLogic() {
		$existingRawRecordSets = array(
			'pagessubtitle1' => array(
				'uid' => 1,
				'table_name' => 'pages',
				'field' => 'subtitle',
				'identifier' => 1,
			),
			'tt_contentbodytext2' => array(
				'uid' => 2,
				'table_name' => 'tt_content',
				'field' => 'bodytext',
				'identifier' => 2,
			),
			'pagesfoo2' => array(
				'uid' => 3,
				'table_name' => 'pages',
				'field' => 'foo',
				'identifier' => 2,
			),
			'pagesurl3' => array(
				'uid' => 4,
				'table_name' => 'pages',
				'field' => 'url',
				'identifier' => 3,
			),
		);

		$this->fixture->expects($this->once())->method('fetchExistingRawRecordSets')
			->will($this->returnValue($existingRawRecordSets));

		$persistenceManager = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
		$backend = $this->getMock('Tx_Extbase_Persistence_BackendInterface');
		$persistenceManager->expects($this->any())->method('getBackend')->will($this->returnValue($backend));
		$this->fixture->expects($this->any())->method('getPersistenceManager')
			->will($this->returnValue($persistenceManager));
	}

	/**
	 * @return RecordSetRepository
	 */
	protected function prepareRecordSetRepository() {
		$recordSetRepository = $this->getMock(
			'SGalinski\DfTools\Domain\Repository\RecordSetRepository', [], [], '', FALSE
		);
		$this->fixture->_set('recordSetRepository', $recordSetRepository);

		return $recordSetRepository;
	}

	/**
	 * @return LinkCheckRepository
	 */
	protected function prepareLinkCheckRepository() {
		$linkCheckRepository = $this->getMock(
			'SGalinski\DfTools\Domain\Repository\LinkCheckRepository', [], [], '', FALSE
		);
		$this->fixture->_set('linkCheckRepository', $linkCheckRepository);

		return $linkCheckRepository;
	}

	/**
	 * @return QueryResult
	 */
	protected function getExistingUrls() {
		$relatedRecordSet = new RecordSet();
		$relatedRecordSet->setTableName('pages');
		$relatedRecordSet->setField('url');
		$relatedRecordSet->setIdentifier(3);

		/** @var $testRecord1 LinkCheck */
		$testRecord1 = $this->getAccessibleMock('SGalinski\DfTools\Domain\Model\LinkCheck', array('dummy'));
		$testRecord1->setTestUrl('http://foo.bar');
		$testRecord1->addRecordSet($relatedRecordSet);

		/** @var $testRecord2 LinkCheck */
		$testRecord2 = $this->getAccessibleMock('SGalinski\DfTools\Domain\Model\LinkCheck', array('dummy'));
		$testRecord2->setTestUrl('http://bar.foo');
		$testRecord2->addRecordSet($relatedRecordSet);

		/** @var $queryResult QueryResult */
		$queryResult = $this->getMockBuilder('TYPO3\CMS\Extbase\Persistence\Generic\QueryResult')
			->setMethods(array('initialize'))->disableOriginalConstructor()->getMock();
		$queryResult->offsetSet(0, $testRecord1);
		$queryResult->offsetSet(1, $testRecord2);

		return $queryResult;
	}

	/**
	 * @test
	 * @return void
	 */
	public function synchronizationLogicRemovesTwoUrls() {
		$this->prepareTestSynchronizationLogic();

		/** @var  $linkCheckRepository LinkCheckRepository|object */
		$linkCheckRepository = $this->prepareLinkCheckRepository();
		$linkCheckRepository->expects($this->never())->method('add');
		$linkCheckRepository->expects($this->never())->method('update');
		$linkCheckRepository->expects($this->exactly(2))->method('remove');

		/** @var  $recordSetRepository RecordSetRepository|object */
		$recordSetRepository = $this->prepareRecordSetRepository();
		$recordSetRepository->expects($this->never())->method('add');
		$recordSetRepository->expects($this->never())->method('remove');
		$recordSetRepository->expects($this->never())->method('findByUid');

		$queryResult = $this->getExistingUrls();
		$this->fixture->synchronize(array(), $queryResult);
	}

	/**
	 * @test
	 * @return void
	 */
	public function synchronizationLogicRemovesOneUrlAndAddsAnotherWithOneNewRecordSetAndOneExisting() {
		$this->prepareTestSynchronizationLogic();

		/** @var  $linkCheckRepository LinkCheckRepository|object */
		$linkCheckRepository = $this->prepareLinkCheckRepository();
		$linkCheckRepository->expects($this->once())->method('add');
		$linkCheckRepository->expects($this->never())->method('update');
		$linkCheckRepository->expects($this->once())->method('remove');

		/** @var  $recordSetRepository RecordSetRepository|object */
		$recordSetRepository = $this->prepareRecordSetRepository();
		$recordSetRepository->expects($this->once())->method('add');
		$recordSetRepository->expects($this->never())->method('remove');
		$recordSetRepository->expects($this->once())->method('findByUid')->will($this->returnValue(new RecordSet()));

		$queryResult = $this->getExistingUrls($linkCheckRepository);
		$this->fixture->synchronize(
			array(
				'http://bar.foo' => array(
					'pagesurl3' => array('pages', 'url', 3),
				),
				'http://ying.yang' => array(
					'pagessubtitle1' => array('pages', 'subtitle', 1),
					'be_userstext1' => array('be_users', 'text', 1),
				),
			), $queryResult
		);

		/** @var $linkCheck LinkCheck */
		$queryResult->rewind();
		$linkCheck = $queryResult->current();
		$recordSets = $linkCheck->getRecordSets();
		$this->assertSame(1, $recordSets->count());

		$recordSets->rewind();
		$recordSet = $recordSets->current();
		$this->assertSame('pages', $recordSet->getTableName());
		$this->assertSame('url', $recordSet->getField());
		$this->assertSame(3, $recordSet->getIdentifier());

		$queryResult->next();
		$linkCheck = $queryResult->current();
		$recordSets = $linkCheck->getRecordSets();
		$this->assertSame(1, $recordSets->count());

		$recordSets->rewind();
		$recordSet = $recordSets->current();
		$this->assertSame('pages', $recordSet->getTableName());
		$this->assertSame('url', $recordSet->getField());
		$this->assertSame(3, $recordSet->getIdentifier());
	}

	/**
	 * @test
	 * @return void
	 */
	public function synchronizationLogicEditsAnUrlByRemovingARecordSetAndAddingAnother() {
		$this->prepareTestSynchronizationLogic();

		/** @var  $linkCheckRepository LinkCheckRepository|object */
		$linkCheckRepository = $this->prepareLinkCheckRepository();
		$linkCheckRepository->expects($this->never())->method('add');
		$linkCheckRepository->expects($this->once())->method('update');
		$linkCheckRepository->expects($this->never())->method('remove');

		/** @var  $recordSetRepository RecordSetRepository|object */
		$recordSetRepository = $this->prepareRecordSetRepository();
		$recordSetRepository->expects($this->once())->method('add');
		$recordSetRepository->expects($this->never())->method('remove');
		$recordSetRepository->expects($this->never())->method('findByUid')
			->will($this->returnValue(new RecordSet()));

		$queryResult = $this->getExistingUrls($linkCheckRepository);
		$this->fixture->synchronize(
			array(
				'http://foo.bar' => array(
					'pagesurl3' => array('pages', 'url', 3),
				),
				'http://bar.foo' => array(
					'pagessubtitle4' => array('pages', 'subtitle', 4),
				),
			), $queryResult
		);

		/** @var $linkCheck LinkCheck */
		$queryResult->rewind();
		$linkCheck = $queryResult->current();
		$recordSets = $linkCheck->getRecordSets();
		$this->assertSame(1, $recordSets->count());

		$recordSets->rewind();
		$recordSet = $recordSets->current();
		$this->assertSame('pages', $recordSet->getTableName());
		$this->assertSame('url', $recordSet->getField());
		$this->assertSame(3, $recordSet->getIdentifier());

		$queryResult->next();
		$linkCheck = $queryResult->current();
		$recordSets = $linkCheck->getRecordSets();
		$this->assertSame(1, $recordSets->count());

		$recordSets->rewind();
		$recordSet = $recordSets->current();
		$this->assertSame('pages', $recordSet->getTableName());
		$this->assertSame('subtitle', $recordSet->getField());
		$this->assertSame(4, $recordSet->getIdentifier());
	}

	/**
	 * @test
	 * @return void
	 */
	public function synchronizationLogicAddsARecordSetForAnUrlAndAddsAnotherUrlWithTheSimilarRecordSet() {
		$this->prepareTestSynchronizationLogic();

		/** @var  $linkCheckRepository LinkCheckRepository|object */
		$linkCheckRepository = $this->prepareLinkCheckRepository();
		$linkCheckRepository->expects($this->once())->method('add');
		$linkCheckRepository->expects($this->once())->method('update');
		$linkCheckRepository->expects($this->never())->method('remove');

		/** @var  $recordSetRepository RecordSetRepository|object */
		$recordSetRepository = $this->prepareRecordSetRepository();
		$recordSetRepository->expects($this->once())->method('add');
		$recordSetRepository->expects($this->never())->method('remove');
		$recordSetRepository->expects($this->never())->method('findByUid');

		$queryResult = $this->getExistingUrls($linkCheckRepository);
		$this->fixture->synchronize(
			array(
				'http://foo.bar' => array(
					'pagesurl3' => array('pages', 'url', 3),
				),
				'http://bar.foo' => array(
					'pagesurl3' => array('pages', 'url', 3),
					'be_userstext1' => array('be_users', 'text', 1),
				),
				'http://ying.yang' => array(
					'be_userstext1' => array('be_users', 'text', 1),
				),
			), $queryResult
		);

		/** @var $linkCheck LinkCheck */
		$queryResult->rewind();
		$linkCheck = $queryResult->current();
		$recordSets = $linkCheck->getRecordSets();
		$this->assertSame(1, $recordSets->count());

		$recordSets->rewind();
		$recordSet = $recordSets->current();
		$this->assertSame('pages', $recordSet->getTableName());
		$this->assertSame('url', $recordSet->getField());
		$this->assertSame(3, $recordSet->getIdentifier());

		$queryResult->next();
		$linkCheck = $queryResult->current();
		$recordSets = $linkCheck->getRecordSets();
		$this->assertSame(2, $recordSets->count());

		$recordSets->rewind();
		$recordSet = $recordSets->current();
		$this->assertSame('pages', $recordSet->getTableName());
		$this->assertSame('url', $recordSet->getField());
		$this->assertSame(3, $recordSet->getIdentifier());

		$recordSets->next();
		$recordSet = $recordSets->current();
		$this->assertSame('be_users', $recordSet->getTableName());
		$this->assertSame('text', $recordSet->getField());
		$this->assertSame(1, $recordSet->getIdentifier());
	}

	/**
	 * @test
	 * @return void
	 */
	public function synchronizationLogicRemovesARecordSetWithEmptyField() {
		$this->prepareTestSynchronizationLogic();

		/** @var  $linkCheckRepository LinkCheckRepository|object */
		$linkCheckRepository = $this->prepareLinkCheckRepository();
		$linkCheckRepository->expects($this->never())->method('add');
		$linkCheckRepository->expects($this->once())->method('update');
		$linkCheckRepository->expects($this->never())->method('remove');

		/** @var  $recordSetRepository RecordSetRepository|object */
		$recordSetRepository = $this->prepareRecordSetRepository();
		$recordSetRepository->expects($this->never())->method('add');
		$recordSetRepository->expects($this->never())->method('remove');
		$recordSetRepository->expects($this->never())->method('findByUid');

		$queryResult = $this->getExistingUrls($linkCheckRepository);
		$this->fixture->synchronize(
			array(
				'http://foo.bar' => array(
					'pagesurl3' => array('pages', 'url', 3),
				),
				'http://bar.foo' => array(
					'pages3' => array('pages', '', 3),
				),
			), $queryResult
		);

		/** @var $linkCheck LinkCheck */
		$queryResult->rewind();
		$linkCheck = $queryResult->current();
		$recordSets = $linkCheck->getRecordSets();
		$this->assertSame(1, $recordSets->count());

		$recordSets->rewind();
		$recordSet = $recordSets->current();
		$this->assertSame('pages', $recordSet->getTableName());
		$this->assertSame('url', $recordSet->getField());
		$this->assertSame(3, $recordSet->getIdentifier());

		$queryResult->next();
		$linkCheck = $queryResult->current();
		$recordSets = $linkCheck->getRecordSets();
		$this->assertSame(0, $recordSets->count());
	}

	/**
	 * @test
	 * @return void
	 */
	public function synchronizeGroupOfUrlsWithOneUnknownOneExistingAndOneRemovableLinkTests() {
		/** @var \SGalinski\DfTools\Domain\Service\UrlSynchronizeService|object $fixture */
		$this->fixture = $fixture = $this->getAccessibleMock(
			'SGalinski\DfTools\Domain\Service\UrlSynchronizeService', array('synchronize')
		);

		$rawUrls = array(
			'http://foo.bar' => array(
				'pagesurl3' => array('pages', 'url', 3),
			),
			'http://bar.foo' => array(
				'be_userstext1' => array('be_users', 'text', 1),
			),
			'http://ying.yang' => array(),
		);

		/** @var $testRecord1 LinkCheck */
		$testRecord1 = $this->getAccessibleMock('SGalinski\DfTools\Domain\Model\LinkCheck', array('dummy'));
		$testRecord1->setTestUrl('http://foo.bar');

		/** @var $testRecord2 LinkCheck */
		$testRecord2 = $this->getAccessibleMock('SGalinski\DfTools\Domain\Model\LinkCheck', array('dummy'));
		$testRecord2->setTestUrl('http://ying.yang');

		/** @var $queryResult QueryResult */
		$queryResult = $this->getMockBuilder('TYPO3\CMS\Extbase\Persistence\Generic\QueryResult')
			->setMethods(array('initialize'))->disableOriginalConstructor()->getMock();
		$queryResult->offsetSet(0, $testRecord1);
		$queryResult->offsetSet(1, $testRecord2);

		$expectedRawUrls = $rawUrls;
		unset($expectedRawUrls['http://ying.yang']);
		$expectedQueryResult = clone $queryResult;
		$expectedQueryResult->offsetUnset(1);
		/** @noinspection PhpUndefinedMethodInspection */
		$fixture->expects($this->once())->method('synchronize')->with($expectedRawUrls, $expectedQueryResult);

		/** @var  $linkCheckRepository LinkCheckRepository|object */
		$linkCheckRepository = $this->prepareLinkCheckRepository();
		$linkCheckRepository->expects($this->once())->method('remove');
		$linkCheckRepository->expects($this->once())->method('findInListByTestUrl')
			->will($this->returnValue($queryResult));

		$fixture->synchronizeGroupOfUrls($rawUrls);
	}

	/**
	 * @test
	 * @return void
	 */
	public function synchronizeGroupOfUrlsWithOneRemovableLinkTest() {
		/** @var \SGalinski\DfTools\Domain\Service\UrlSynchronizeService|object $fixture */
		$this->fixture = $fixture = $this->getAccessibleMock(
			'SGalinski\DfTools\Domain\Service\UrlSynchronizeService', array('synchronize')
		);

		$rawUrls = array(
			'http://ying.yang' => array(),
		);

		/** @var $testRecord1 LinkCheck */
		$testRecord1 = $this->getAccessibleMock('SGalinski\DfTools\Domain\Model\LinkCheck', array('dummy'));
		$testRecord1->setTestUrl('http://ying.yang');

		/** @var $queryResult QueryResult */
		$queryResult = $this->getMockBuilder('TYPO3\CMS\Extbase\Persistence\Generic\QueryResult')
			->setMethods(array('initialize'))->disableOriginalConstructor()->getMock();
		$queryResult->offsetSet(0, $testRecord1);

		/** @noinspection PhpUndefinedMethodInspection */
		$fixture->expects($this->never())->method('synchronize');

		/** @var  $linkCheckRepository LinkCheckRepository|object */
		$linkCheckRepository = $this->prepareLinkCheckRepository();
		$linkCheckRepository->expects($this->once())->method('remove');
		$linkCheckRepository->expects($this->once())->method('findInListByTestUrl')
			->will($this->returnValue($queryResult));

		$fixture->synchronizeGroupOfUrls($rawUrls);
	}

	/**
	 * @test
	 * @return void
	 */
	public function synchronizeGroupOfUrlsWithOneAddedLinkTest() {
		/** @var \SGalinski\DfTools\Domain\Service\UrlSynchronizeService|object $fixture */
		$this->fixture = $fixture = $this->getAccessibleMock(
			'SGalinski\DfTools\Domain\Service\UrlSynchronizeService', array('synchronize')
		);

		$rawUrls = array(
			'http://bar.foo' => array(
				'be_userstext1' => array('be_users', 'text', 1),
			),
		);

		/** @var $queryResult QueryResult */
		$queryResult = $this->getMockBuilder('TYPO3\CMS\Extbase\Persistence\Generic\QueryResult')
			->setMethods(array('initialize'))->disableOriginalConstructor()->getMock();
		$queryResult->offsetSet(0, NULL);
		$queryResult->offsetUnset(0);

		/** @noinspection PhpUndefinedMethodInspection */
		$fixture->expects($this->once())->method('synchronize')->with($rawUrls, $queryResult);

		/** @var  $linkCheckRepository LinkCheckRepository|object */
		$linkCheckRepository = $this->prepareLinkCheckRepository();
		$linkCheckRepository->expects($this->never())->method('remove');
		$linkCheckRepository->expects($this->once())->method('findInListByTestUrl')
			->will($this->returnValue($queryResult));

		$fixture->synchronizeGroupOfUrls($rawUrls);
	}
}

?>