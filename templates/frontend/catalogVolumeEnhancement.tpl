{* Volume *}
{if $monograph->getCurrentPublication()->getData('volumeId') && $volumeDao}
    <div class="volume">
        {assign var="volumeId" value=$monograph->getCurrentPublication()->getData('volumeId')}
        {assign var="volume" value=$volumeDao->getById($volumeId)}
        <div class="volume_title">
            <span class="label">{translate key="plugins.generic.volumesForm.catalog.partOf"}</span>
            <a class="value" title="{translate key="plugins.generic.volumesForm.catalog.linkTitle"}" href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="volume" path=$volume->getPath()}">
                {$volume->getLocalizedTitle(null, 'html')|strip_unsafe_html}
            </a>
        </div>
    </div>
{/if}