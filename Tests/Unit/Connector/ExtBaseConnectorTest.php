<?php

namespace SGalinski\DfTools\Tests\Unit\Connector;

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

use SGalinski\DfTools\Connector\ExtBaseConnector;
use SGalinski\DfTools\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class ExtBaseConnectorTest
 */
class ExtBaseConnectorTest extends BaseTestCase {
	/**
	 * @var \SGalinski\DfTools\Connector\ExtBaseConnector
	 */
	protected $fixture;

	/**
	 * @return void
	 */
	public function setUp() {
		$class = 'SGalinski\DfTools\Connector\ExtBaseConnector';

		/** @var ExtBaseConnector $fixture */
		$fixture = $this->getAccessibleMock($class, array('initialize', 'handleWebRequest'));

		$fixture->setExtensionKey('Foo');
		$fixture->setModuleOrPluginKey('tools_FooTools');
		$this->fixture = $fixture;
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 * @return void
	 */
	public function setExtensionKeyWorks() {
		$this->fixture->setExtensionKey('FooBar');

		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertSame('FooBar', $this->fixture->_get('extensionKey'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function setModuleOrPluginKeyWorks() {
		$this->fixture->setModuleOrPluginKey('FooBar');

		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertSame('FooBar', $this->fixture->_get('moduleOrPluginKey'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function setParametersWorks() {
		$this->fixture->setParameters(array('FooBar'));

		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertSame(array('FooBar'), $this->fixture->_get('parameters'));
	}

	/**
	 * @return void
	 */
	protected function prepareRunControllerAndActionTests() {
		$extensionService = $this->getMock('TYPO3\CMS\Extbase\Service\ExtensionService');
		$extensionService->expects($this->once())->method('getPluginNamespace')
			->will($this->returnValue('tx_foo_tools_footools'));

		$objectManager = $this->getMock('TYPO3\CMS\Extbase\Object\ObjectManager');
		$objectManager->expects($this->once())->method('get')->will($this->returnValue($extensionService));

		/** @noinspection PhpUndefinedMethodInspection */
		$this->fixture->_set('objectManager', $objectManager);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testExecutionOfControllerAndAction() {
		$this->prepareRunControllerAndActionTests();

		/** @noinspection PhpUndefinedMethodInspection */
		$this->fixture->expects($this->once())->method('initialize')->with(
			array(
				'extensionName' => 'Foo',
				'pluginName' => 'tools_FooTools',
				'switchableControllerActions' => array(
					'TestController' => array('TestAction')
				),
			)
		);
		$this->fixture->runControllerAction('TestController', 'TestAction');
	}

	/**
	 * @return array
	 */
	public function testExecutionOfControllerAndActionWithIncorrectParametersDataProvider() {
		return array(
			array('Foo', ''),
			array('', 'Bar'),
			array('', ''),
			array('', NULL),
		);
	}

	/**
	 * @dataProvider testExecutionOfControllerAndActionWithIncorrectParametersDataProvider
	 * @expectedException \InvalidArgumentException
	 * @test
	 *
	 * @param string $controller
	 * @param string $action
	 * @return void
	 */
	public function testExecutionOfControllerAndActionWithIncorrectParameters($controller, $action) {
		$this->fixture->runControllerAction($controller, $action);
	}

	/**
	 * @test
	 * @return void
	 */
	public function parametersCanBeSet() {
		$this->prepareRunControllerAndActionTests();
		$parameters = array('foo' => 'bar', 'my' => 'cat');
		$this->fixture->setParameters($parameters);

		$this->fixture->runControllerAction('Foo', 'Bar');
		$this->assertSame($_POST['tx_foo_tools_footools'], $parameters);
	}
}

?>