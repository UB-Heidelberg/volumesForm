{* Volume *}
{if $volume}
    <div class="item volume">
        <h2 class="label">
            {translate key="volume.volume"}
        </h2>
        <a href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="volume" path=$volume->getPath()}">
            {$volume->getTitle($locale)}
        </a>
        <div class="value">
            <span>{$volume->getData('courseOfPublication')}</span>
            <br/>
            {if $volume->countPublishedParts() > 1}
                <span>
                    {translate key="volumes.browseTitles" numTitles=$volume->countPublishedParts()|escape}
                </span>
                <br/><br/>
                <h3 class="label">
                    {translate key="volume.furtherParts"}
                </h3>
                {assign var=volumeParts value=$volume->getPublishedParts()}
                {assign var=currentPartSubmissionId value=$publication->getData('submissionId')}
                {foreach from=$volumeParts item=volumePart}
                    {if $volumePart->getData('submissionId') != $currentPartSubmissionId}
                        <a href="{url router=$smarty.const.ROUTE_PAGE page="catalog" op="book" path=$volumePart->getData('submissionId')}">
                            {$volumePart->getData('volumePosition')}: {$volumePart->getLocalizedTitle(null, 'html')|strip_unsafe_html}
                        </a>
                        <br/><br/>
                    {/if}
                {/foreach}
            {/if}
        </div>
    </div>
{/if}