{**
 * plugins/generic/citationStyleLanguage/templates/settings.tpl
 *
 * Copyright (c) 2017-2020 Simon Fraser University
 * Copyright (c) 2017-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Form to enable/disable CSL citation styles and define a primary citation style.
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#volumesFormSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="volumesFormSettingsForm" method="post" action="{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}

	{fbvFormArea id="volumesFormPluginSettings"}
		{fbvFormArea id="volumesFormPluginSettingsUserGroups" title="plugins.generic.volumesForm.settings.userGroups" class="pkpFormField--options"}
			<p>{translate key="plugins.generic.volumesForm.settings.userGroupsDescription"}</p>
			{fbvFormSection list=true label="plugins.generic.volumesForm.settings.chooseAuthor"}
				<p>{translate key='plugins.generic.volumesForm.settings.optionChooseAuthor'}</p>
				{foreach from=$allUserGroups item="group" key="id"}
					{fbvElement type="checkbox" id="groupAuthor[]" value=$id checked=in_array($id, $groupAuthor) label=$group translate=false}
				{/foreach}
			{/fbvFormSection}
			{fbvFormSection list=true label="plugins.generic.volumesForm.settings.chooseEditor"}
				<p>{translate key='plugins.generic.volumesForm.settings.optionChooseEditor'}</p>
				{foreach from=$allUserGroups item="group" key="id"}
					{fbvElement type="checkbox" id="groupEditor[]" value=$id checked=in_array($id, $groupEditor) label=$group translate=false}
				{/foreach}
			{/fbvFormSection}
		{/fbvFormArea}
	{/fbvFormArea}

	{fbvFormButtons}
</form>
