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

    {fbvFormSection title="volume.coverImage"}
        {include file="controllers/fileUploadContainer.tpl" id="plupload"}
        <input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
    {/fbvFormSection}

     {* Image. *}
    {if $image}
        {fbvFormSection}
        {capture assign="altTitle"}{translate key="submission.currentCoverImage"}{/capture}
            <img class="pkp_helpers_container_center" src="{$baseUrl}/plugins/generic/volumesForm/cover/{$image['thumbnailName']}" alt="{$volume->getLocalizedTitle()|escape|default: 'null'}" />
        {/fbvFormSection}
    {/if}

    {fbvFormSection title="grid.volume.name" for="title" required="true"}
    {fbvElement type="text" multilingual="true" name="title" value=$title id="title" required="true"}
    {/fbvFormSection}



    {fbvFormSection title="grid.volume.description" for="context"}
    {fbvElement type="textarea" multilingual="true" id="description" value=$description rich=true}
    {/fbvFormSection}

    {fbvFormSection title="grid.volume.ppn" for="ppn"}
    {fbvElement type="text" id="ppn" value=$ppn rich=false}
    {/fbvFormSection}

    {fbvFormSection for="isbn" title="grid.catalogEntry.isbn"}
    {fbvElement type="text" label="grid.catalogEntry.isbn13.description" value=$isbn13 id="isbn13" size=$fbvStyles.size.MEDIUM inline=true}
    {fbvElement type="text" label="grid.catalogEntry.isbn10.description" value=$isbn10 id="isbn10" size=$fbvStyles.size.MEDIUM inline=true}
    {/fbvFormSection}

    {fbvFormSection label="grid.volume.volumeOrder" description="catalog.sortBy.volumeDescription" for="sortOption"}
    {fbvElement type="select" id="sortOption" from=$sortOptions selected=$sortOption translate=false}
    {/fbvFormSection}

    {fbvFormSection title="grid.volume.courseOfPublication" for="courseOfPublication"}
    {fbvElement type="text" id="courseOfPublication" value=$courseOfPublication rich=false}
    {/fbvFormSection}

     {* Path. *}
    {fbvFormSection title="grid.volume.path" required=true for="path"}
    {capture assign="instruct"}
        {translate key="grid.volume.urlWillBe" sampleUrl=$sampleUrl}
        {if $path}
            {url router=$smarty.const.ROUTE_PAGE page="catalog" op="volume" path=$path}
        {else}
            {url router=$smarty.const.ROUTE_PAGE page="catalog" op="volume" path="path"}
        {/if}

    {/capture}
    {fbvElement type="text" id="path" value=$path maxlength="32" label=$instruct subLabelTranslate=false}
    {/fbvFormSection}

    <p><span class="formRequired">{translate key="common.requiredField"}</span></p>
    {fbvFormButtons}
    {/fbvFormArea}
</form>