/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 domainfactory GmbH (Stefan Galinski <stefan@sgalinski.de>)
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

Ext.ns('TYPO3.Backend.DfTools', 'TYPO3.DfTools.BackLinkTest');

/**
 * Main Application Code For The Back Link Test App
 *
 * @author Stefan Galinski <stefan@sgalinski.de>
 * @class TYPO3.DfTools.BackLinkTest.App
 * @extends TYPO3.DfTools.AbstractApp
 * @namespace TYPO3.DfTools.BackLinkTest
 */
TYPO3.DfTools.BackLinkTest.App = Ext.extend(TYPO3.DfTools.AbstractApp, {
	/**
	 * @private
	 * @type {TYPO3.DfTools.BackLinkTest.PopUpForm}
	 */
	popUpForm: null,

	/**
	 * Initializes the component
	 *
	 * @return {void}
	 */
	initComponent: function() {
		this.dataProvider = TYPO3.DfTools.BackLinkTest.DataProvider;
		this.popUpForm = new TYPO3.DfTools.BackLinkTest.PopUpForm();
		this.gridStore = new TYPO3.DfTools.BackLinkTest.Store({
			remoteSort: true,
			baseParams: {
				start: 0,
				limit: 200,
				sort: 'testResult',
				dir: 'DESC'
			}
		});

		this.grid = new TYPO3.DfTools.Grid({
			renderTo: 'tx_dftools',

			store: this.gridStore,
			cm: this.getColumnModel(),
			fetchRowClass: this.fetchRowClass.createDelegate(this),

			viewConfiguration: {
				hideGroupedColumn: true
			},

			tbar: [
				{
					id: 'tx_dftools-button-runTest',
					iconCls: '',
					text: '<span class="' + TYPO3.settings.DfTools.Sprites.run + '"></span>'
						+ '<span class="tx_dftools-button-text">'
						+ TYPO3.lang['tx_dftools_domain_model_backlinktest.runTests'] + '</span>',
					scope: this,
					handler: this.onRunTests
				},
				{
					id: 'tx_dftools-button-createRecord',
					iconCls: '',
					text: '<span class="' + TYPO3.settings.DfTools.Sprites.create + '"></span>'
						+ '<span class="tx_dftools-button-text">'
						+ TYPO3.lang['tx_dftools_domain_model_backlinktest.create'] + '</span>',
					scope: this,
					handler: this.onAddRecord
				}
			],

			groupActions: [
				{
					iconCls: TYPO3.settings.DfTools.Sprites.run,
					qtip: TYPO3.lang['tx_dftools_domain_model_backlinktest.runTests'],
					scope: this,
					callback: this.onRunTestsOfGroup
				}
			]
		});

		TYPO3.DfTools.BackLinkTest.App.superclass.initComponent.apply(this, arguments);
	},

	/**
	 * Adds a new back link test
	 *
	 * @return {void}
	 */
	onAddRecord: function() {
		var backLinkTest = new TYPO3.DfTools.BackLinkTest.Model({
			__identity: 0,
			testResult: 0,
			testMessage: '',
			testUrl: '/',
			expectedUrl: '/'
		});

		this.grid.addRecord(backLinkTest);
	},

	/**
	 * Open the edit form pop-up window
	 *
	 * @param {TYPO3.DfTools.Grid} grid
	 * @param {int} rowIndex
	 * @return {void}
	 */
	onEditComment: function(grid, rowIndex) {
		grid.rowEditorPlugin.stopEditing();
		this.popUpForm.open(this.categoryStore, grid.getStore().getAt(rowIndex));
	},

	/**
	 * Returns the column model for the back link test grid
	 *
	 * @private
	 * @return {Ext.grid.ColumnModel}
	 */
	getColumnModel: function() {
		return new Ext.grid.ColumnModel({
			defaults: {
				sortable: true
			},

			columns: [
				new Ext.grid.RowNumberer({
					width: 30
				}), {
					id: 'testUrl',
					header: TYPO3.lang['tx_dftools_domain_model_backlinktest.test_url'],
					dataIndex: 'testUrl',
					groupable: false,
					width: 200,

					scope: this,
					renderer: this.setValueAsCellToolTipRenderer,

					editor: {
						xtype: 'textfield',
						allowBlank: false
					}
				}, {
					id: 'expectedUrl',
					header: TYPO3.lang['tx_dftools_domain_model_backlinktest.expected_url'],
					dataIndex: 'expectedUrl',
					groupable: false,
					width: 200,

					scope: this,
					renderer: this.setValueAsCellToolTipRenderer,

					editor: {
						xtype: 'textfield',
						allowBlank: false
					}
				}, {
					id: 'testResult',
					header: TYPO3.lang['tx_dftools_domain_model_backlinktest.test_result'],
					dataIndex: 'testResult',
					width: 50,
					editable: false,
					scope: this,
					renderer: this.renderTestResult
				}, {
					id: 'actions',
					xtype: 'actioncolumn',
					header: TYPO3.lang['tx_dftools_common.actions'],
					dataIndex: 'actions',
					sortable: false,
					groupable: false,
					hideable: false,
					menuDisabled: true,
					editable: false,
					width: 55,
					align: 'right',

					app: this,
					items: [
						{
							getClass: this.observeTestState
						},
						{
							iconCls: TYPO3.settings.DfTools.Sprites.comment,
							tooltip: TYPO3.lang['tx_dftools_domain_model_backlinktest.editComment'],
							scope: this,
							handler: this.onEditComment
						},
						{
							iconCls: TYPO3.settings.DfTools.Sprites.destroy,
							tooltip: TYPO3.lang['tx_dftools_common.delete'],
							scope: this,
							handler: function() {
								this.grid.deleteRecord.apply(this.grid, arguments);
							}
						},
						{
							iconCls: TYPO3.settings.DfTools.Sprites.run,
							tooltip: TYPO3.lang['tx_dftools_domain_model_backlinktest.runTest'],
							scope: this,
							handler: this.onRunSingleTest
						},
						{
							iconCls: TYPO3.settings.DfTools.Sprites.edit,
							tooltip: TYPO3.lang['tx_dftools_domain_model_backlinktest.edit'],
							scope: this,
							handler: this.onEditRecord
						}
					]
				}
			]
		});
	}
});

Ext.onReady(function() {
	TYPO3.Backend.DfTools.App = new TYPO3.DfTools.BackLinkTest.App().run();
});