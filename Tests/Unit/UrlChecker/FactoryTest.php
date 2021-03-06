<?php

namespace SGalinski\DfTools\Tests\Unit\UrlChecker;

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

use SGalinski\DfTools\Tests\Unit\Controller\ControllerTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class FactoryTest
 */
class FactoryTest extends ControllerTestCase {
	/**
	 * @var \SGalinski\DfTools\UrlChecker\Factory|object
	 */
	protected $fixture;

	/**
	 * @var boolean
	 */
	protected $backupCurlUse;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->backupCurlUse = $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'];

		/** @noinspection PhpUndefinedMethodInspection */
		$proxyClass = $this->buildAccessibleProxy('SGalinski\DfTools\UrlChecker\Factory');
		$this->fixture = $this->getMockBuilder($proxyClass)
			->setMethods(array('dummy'))
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'] = $this->backupCurlUse;
		unset($this->fixture);
	}

	/**
	 * @return array
	 */
	public function getReturnsUrlCheckerServiceDataProvider() {
		return array(
			'no type' => array(
				'', 'SGalinski\DfTools\UrlChecker\StreamService'
			),
			'native type' => array(
				FALSE, 'SGalinski\DfTools\UrlChecker\StreamService'
			),
			'curl type' => array(
				TRUE, 'SGalinski\DfTools\UrlChecker\CurlService'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getReturnsUrlCheckerServiceDataProvider
	 *
	 * @param boolean $type
	 * @param string $expectedClass
	 * @return void
	 */
	public function getReturnsUrlCheckerService($type, $expectedClass) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'] = $type;

		/** @var $objectManager ObjectManager */
		$objectManager = $this->getMock('TYPO3\CMS\Extbase\Object\ObjectManager', array('get'));
		$this->fixture->_set('objectManager', $objectManager);

		$proxyClass = $this->buildAccessibleProxy('SGalinski\DfTools\UrlChecker\AbstractService');
		$service = $this->getMock($proxyClass, array('init', 'resolveUrl'));

		/** @noinspection PhpUndefinedMethodInspection */
		$objectManager->expects($this->once())->method('get')
			->with($expectedClass)->will($this->returnValue($service));

		$service->expects($this->once())->method('init');

		$this->fixture->get($type);
	}
}

?>