{**
 * plugins/generic/volumesForm/templates/volumeForm.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Form to edit or create a volume.
 *}

<script type="text/javascript">
    $(function() {ldelim}
    // Attach the form handler.
    $('#volumeForm').pkpHandler(
        '$.pkp.controllers.form.FileUploadFormHandler',
        {ldelim}
        publishChangeEvents: ['updateSidebar'],
        $uploader: $('#plupload'),
        uploaderOptions: {ldelim}
        uploadUrl: {url|json_encode op="uploadImage" escape=false},
        baseUrl: {$baseUrl|json_encode},
        filters: {ldelim}
        mime_types: [
            {ldelim} title : "Image files", extensions : "jpg,jpeg,png,svg" {rdelim}
        ]
        {rdelim}
        {rdelim}
        {rdelim}
    );
    {rdelim});
</script>

<form class="pkp_form" id="volumeForm" method="post"
    action="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.volumesForm.controllers.grid.VolumeGridHandler" op="updateVolume" volumeId=$volumeId}">
    {csrf}
    {include file="controllers/notification/inPlaceNotification.tpl" notificationId="volumeFormNotification"}

    {if $volumeId}
        <input type="hidden" name="volumeId" value="{$volumeId|escape}" />
    {/if}

    {fbvFormArea id="volumeDetails"}

    <h3>{translate key="grid.volume.volumeDetails"}</h3>

    {fbvFormSection title="grid.volume.name" for="title" required="true"}
    {fbvElement type="text" multilingual="true" name="title" value=$title id="title" required="true"}
    {/fbvFormSection}

    {* Path. *} 
    {fbvFormSection title="grid.volume.path" required=true for="path"}
    {capture assign="instruct"}
        {url router=$smarty.const.ROUTE_PAGE page="catalog" op="volume" path="path"}
        {translate key="grid.volume.urlWillBe" sampleUrl=$sampleUrl}
    {/capture}
    {fbvElement type="text" id="path" value=$path maxlength="32" label=$instruct subLabelTranslate=false}
    {/fbvFormSection}

    {fbvFormSection title="grid.volume.description" for="context"}
    {fbvElement type="textarea" multilingual="true" id="description" value=$description rich=true}
    {/fbvFormSection}

    {fbvFormSection title="grid.volume.ppn" for="context"}
    {fbvElement type="text" multilingual="false" id="ppn" value=$ppn rich=false}
    {/fbvFormSection}

    {fbvFormSection label="catalog.sortBy" description="catalog.sortBy.volumeDescription" for="sortOption"}
    {fbvElement type="select" id="sortOption" from=$sortOptions selected=$sortOption translate=false}
    {/fbvFormSection}

    {fbvFormSection title="volume.coverImage"}
    {include file="controllers/fileUploadContainer.tpl" id="plupload"}
    <input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
    {/fbvFormSection}

    {* Image. *}
    {if $image}
        {fbvFormSection}
        {capture assign="altTitle"}{translate key="submission.currentCoverImage"}{/capture}
        <img src="{$publicFilesDir}/volumes/{$image['name']}" alt="{$image.altText|escape|default:''}" />
        {/fbvFormSection}
    {/if}

    <p><span class="formRequired">{translate key="common.requiredField"}</span></p>
    {fbvFormButtons}
    {/fbvFormArea}
</form>