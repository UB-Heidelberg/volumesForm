{* Volume *}
{if $volume}
    <div class="item volume">
        <div class="sub_item volume_title">
            <a class="value" title="{translate key="plugins.generic.volumesForm.catalog.linkTitle"}" href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="volume" path=$volume->getPath()}">
                {$volumeTitle}
            </a>
        </div>
        {if $volumePosition}
            <div class="sub_item volume_position">
                <div class="value">{$volumePosition}</div>
            </div>
        {/if}
    </div>
{/if}