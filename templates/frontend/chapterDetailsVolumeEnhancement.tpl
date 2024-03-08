{* Volume *}
{if $volume}
    <div class="sub-item volume">
        <div class="sub_item">
            <h2 class="label">{translate key="plugins.generic.volumesForm.catalog.partOf"}</h2>
            <div class="value">
                <a href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="volume" path=$volume->getPath()}">
                    {$volumeTitle}
                </a>
            </div>
        </div>
        {if $volume->countPublishedParts() > 1}
            <div class="sub_item furtherParts">
                <h3 class="label">{translate key="volume.furtherParts"}</h3>
                {assign var=volumeParts value=$volume->getPublishedParts()}
                {assign var=currentPartSubmissionId value=$publication->getData('submissionId')}
                <ul class="value">
                    {foreach from=$volumeParts item=volumePart}
                        {if $volumePart->getData('submissionId') != $currentPartSubmissionId}
                            <li>
                                <a href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="book" path=$volumePart->getData('submissionId')}">
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