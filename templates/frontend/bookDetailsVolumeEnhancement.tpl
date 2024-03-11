{* Volume *}
{if $volume}
    <div class="item volume">
        <div class="sub_item">
            <div class="label">{if $isChapterRequest}{translate key="plugins.generic.volumesForm.catalog.partOf"}{else}{translate key="volume.volume"}{/if}</div>
            <a class="value" title="{translate key="plugins.generic.volumesForm.catalog.linkTitle"}" href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="volume" path=$volume->getPath()}">
                {$volumeTitle}
            </a>
        </div>
        {if $volume->countPublishedParts() > 1}
            <div class="sub_item furtherParts">
                <div class="label">{translate key="volume.furtherParts"}</div>
                {assign var=volumeParts value=$volume->getPublishedParts()}
                {assign var=currentPartSubmissionId value=$publication->getData('submissionId')}
                <ul class="value">
                    {foreach from=$volumeParts item=volumePart}
                        {if $volumePart->getData('submissionId') != $currentPartSubmissionId}
                            <li>
                                <a title="{translate key="plugins.generic.volumesForm.catalog.linkPart"} '{$volumePart->getLocalizedTitle(null, 'html')|strip_unsafe_html}'" href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="book" path=$volumePart->getData('submissionId')}">
                                    {if $volumePart->getData('volumePosition')}
                                        {$volumePart->getData('volumePosition')}: {$volumePart->getLocalizedTitle(null, 'html')|strip_unsafe_html}
                                    {else}
                                        {$volumePart->getLocalizedTitle(null, 'html')|strip_unsafe_html}
                                    {/if}
                                </a>
                            </li>
                        {/if}
                    {/foreach}
                </ul>
            </div>
        {/if}
    </div>
{/if}