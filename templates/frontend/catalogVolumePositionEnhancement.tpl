{* Volume position *}
{if $monograph->getCurrentPublication()->getData('volumePosition')}
    {assign var="volumePosition" value=$monograph->getCurrentPublication()->getData('volumePosition')}
    <div class="volume_position">
        {translate key="plugins.generic.volumesForm.catalog.positionPrefix"}{$volumePosition}
    </div>
{/if}