/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaStudio UNA Studio
 * @{
 */
function BxDolStudioFormsSearchFields(oOptions) {
	this.sActionsUrl = oOptions.sActionUrl;
	this.sPageUrl = oOptions.sPageUrl;
	this.sObjNameGrid = oOptions.sObjNameGrid;
    this.sObjName = oOptions.sObjName == undefined ? 'oBxDolStudioFormsSearchFields' : oOptions.sObjName;
    this.sAnimationEffect = oOptions.sAnimationEffect == undefined ? 'fade' : oOptions.sAnimationEffect;
    this.iAnimationSpeed = oOptions.iAnimationSpeed == undefined ? 'slow' : oOptions.iAnimationSpeed;
    this.sParamsDivider = oOptions.sParamsDivider == undefined ? '#-#' : oOptions.sParamsDivider;

    this.sTextSearchInput = oOptions.sTextSearchInput == undefined ? '' : oOptions.sTextSearchInput;
}

BxDolStudioFormsSearchFields.prototype.onChangeModule = function() {
	var $this = this;
	var oDate = new Date();
	var sModule = $('#bx-grid-module-' + this.sObjNameGrid).val();

	this.reloadGrid(sModule);

	bx_loading($('body'), true);

	$.post(
		this.sPageUrl,
		{
			form_action: 'get_search_forms',
			form_module: sModule,
			_t: oDate.getTime()
		},
		function(oData) {
			bx_loading($('body'), false);

			if(oData.code != 0) {
				bx_alert(oData.message);
				return;
			}

			$('#' + $(oData.content).attr('id')).replaceWith(oData.content);
		},
		'json'
	);
};

BxDolStudioFormsSearchFields.prototype.onChangeForm = function() {
	this.reloadGrid($('#bx-grid-module-' + this.sObjNameGrid).val(), $('#bx-grid-form-sys_studio_search_forms_fields').val());
};

BxDolStudioFormsSearchFields.prototype.reloadGrid = function(sModule, sObject) {
	var bReload = false;
	if(!sModule) 
		sObject = '';

    console.log(sModule);
    console.log(sObject);
    console.log($('#bx-grid-form-' + this.sObjNameGrid).val());
      console.log('#bx-grid-form-' + this.sObjNameGrid);
    
	var oSearch = $('#bx-form-element-keyword');
	var oActions = $("[bx_grid_action_independent]");
	if(!sObject) {
		oSearch.hide();
		oActions.addClass('bx-btn-disabled');
	}
	else {
		oSearch.show();
		oActions.removeClass('bx-btn-disabled');
	}

	if(glGrids[this.sObjNameGrid]._oQueryAppend['module'] != sModule) {
		glGrids[this.sObjNameGrid]._oQueryAppend['module'] = sModule;
		bReload = true;
	}
    
	if(glGrids[this.sObjNameGrid]._oQueryAppend['form'] != sObject) {
		glGrids[this.sObjNameGrid]._oQueryAppend['form'] = sObject;
		bReload = true;
	}
    console.log(glGrids[this.sObjNameGrid]._oQueryAppend);
	if(bReload)
		glGrids[this.sObjNameGrid].reload(0);
};

/** @} */
