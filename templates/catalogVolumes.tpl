
 {include file="frontend/components/header.tpl" pageTitleTranslated=$volumeTitle}

 <div class="page page_catalog_volume">
 
     {* Breadcrumb *}
     {include file="frontend/components/breadcrumbs_catalog.tpl" type="volume" currentTitle=$volume->getLocalizedTitle()}

     {* Title *}
     <h1>{$volume->getLocalizedTitle()|escape}</h1>

     {* Course of publication *}
     {if $volume->getData('courseOfPublication')}
         <h3>{$volume->getData('courseOfPublication')|escape}</h3>
     {/if}

     {* Authors *}
     <div class="item authors">
         {if count($editors) > 0}
            {if count($editors) > 1}
                 <h3>
                     {translate key="plugins.generic.volumesForm.catalog.editors"}
                 </h3>
            {else}
                <h3>
                    {translate key="plugins.generic.volumesForm.catalog.editor"}
                </h3>
            {/if}
             {foreach name="editors" from=$editors item=editor}
                 {* strip removes excess white-space which creates gaps between separators *}
                 {strip}
                     <span class="label">{$editor|escape}</span>
                     {if !$smarty.foreach.editors.last}
                         {translate key="submission.authorListSeparator"}
                     {/if}
                 {/strip}
             {/foreach}
         {/if}
         {if count($editors) > 0}
             {if count($editors) > 1}
                 <h3>
                     {translate key="plugins.generic.volumesForm.catalog.authors"}
                 </h3>
             {else}
                 <h3>
                     {translate key="plugins.generic.volumesForm.catalog.author"}
                 </h3>
             {/if}
             {foreach name="authors" from=$authors item=author}
                 {* strip removes excess white-space which creates gaps between separators *}
                 {strip}
                     <span class="label">{$author|escape}</span>
                     {if !$smarty.foreach.authors.last}
                         {translate key="submission.authorListSeparator"}
                     {/if}
                 {/strip}
             {/foreach}
         {/if}
     </div>
     <br/>
     {* Count of monographs in this volume *}
     <div class="monograph_count">
         {translate key="volumes.browseTitles" numTitles=$total}
     </div>
 
     {* Description *}
     {assign var="image" value=$volume->getImage()}
     {assign var="description" value=$volumeDescription|strip_unsafe_html}
     <div class="about_section{if $image} has_image{/if}{if $description} has_description{/if}">
         <div class="image">
             <div class="cover">
                 {if $volume->getImage()}
                     <img src="{$volumeCover}" alt="{$volume->getLocalizedTitle()|escape|default: 'null'}" />
                 {/if}
             </div>
         </div>
         <div class="description">
             {$description|strip_unsafe_html}
         </div>
     </div>

     {* Publisher and location *}
     <h4>{$publisher|escape}, {$location|escape}</h4>

     {* ISBN *}
     {assign var=isbn10 value=$volume->getData('isbn10')}
     {assign var=isbn13 value=$volume->getData('isbn13')}
     {if $isbn10 || $isbn13}
         <div class="item isbn">
             <div class="sub_item">
                 <h3 class="label">
                     {translate key="plugins.generic.volumesForm.catalog.isbn"}
                 </h3>
                 {if $isbn10}
                     <div class="sub_item identification_code">
                         <div class="value">
                             {translate key="plugins.generic.volumesForm.catalog.isbn10"}{$isbn10|escape}
                         </div>
                     </div>
                 {/if}
                 {if $isbn13}
                     <div class="sub_item identification_code">
                         <div class="value">
                             {translate key="plugins.generic.volumesForm.catalog.isbn13"}{$isbn13|escape}
                         </div>
                     </div>
                 {/if}
             </div>
         </div>
     {/if}

     {* Series *}
     {if count($seriesArray) > 0}
         <div class="item series">
             <div class="sub_item">
                 <h2 class="label">
                     {translate key="series.series"}
                 </h2>
                 {foreach $seriesArray as $seriesItem}
                     {assign var=series value=$seriesItem['series']}
                     <div class="value">
                         <a href="{url page="catalog" op="series" path=$series->getPath()}">
                             {$series->getLocalizedFullTitle()|escape}
                         </a>
                     </div>
                     {if $seriesItem['positions']}
                         <span>{translate key="plugins.generic.volumesForm.catalog.volume"}: {$seriesItem['positions']|escape}</span>
                         <br/>
                     {/if}
                     {if $series->getOnlineISSN()}
                         <span>{translate key="catalog.manage.series.onlineIssn"}: {$series->getOnlineISSN()|escape}</span>
                         <br/>
                     {/if}
                     {if $series->getPrintISSN()}
                         <span>{translate key="catalog.manage.series.printIssn"}: {$series->getPrintISSN()|escape}</span>
                         <br/>
                     {/if}
                 {/foreach}
             </div>
         </div>
     {/if}
 
     {* No published titles in this category *}
     {if empty($publishedSubmissions)}
         <h2>
             {translate key="catalog.category.heading"}
         </h2>
         <p>{translate key="volumes.noTitlesSection"}</p>
 
     {else}
         {* All monographs *}
         {include file="frontend/components/monographList.tpl" monographs=$publishedSubmissions titleKey="catalog.category.heading"}

         {* Pagination *}
         {if $prevPage > 1}
             {capture assign=prevUrl}{url router=$smarty.const.ROUTE_PAGE page="catalog" op="volume" path=$volumePath|to_array:$prevPage}{/capture}
         {elseif $prevPage === 1}
             {capture assign=prevUrl}{url router=$smarty.const.ROUTE_PAGE page="catalog" op="volume" path=$volumePath}{/capture}
         {/if}
         {if $nextPage}
             {capture assign=nextUrl}{url router=$smarty.const.ROUTE_PAGE page="catalog" op="volume" path=$volumePath|to_array:$nextPage}{/capture}
         {/if}
         {include
         file="frontend/components/pagination.tpl"
         prevUrl=$prevUrl
         nextUrl=$nextUrl
         showingStart=$showingStart
         showingEnd=$showingEnd
         total=$total
         }
     {/if}
 
 </div><!-- .page -->
 
 {include file="frontend/components/footer.tpl"}
 