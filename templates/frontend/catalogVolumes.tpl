
 {include file="frontend/components/header.tpl" pageTitleTranslated=$volumeTitle}

 <div class="page page_catalog_volume">
 
     {* Breadcrumb *}
     {include file="frontend/components/breadcrumbs_catalog.tpl" type="volume" currentTitle=$volume->getLocalizedTitle()}

     {* Title *}
     <h2 class="title">{$volume->getLocalizedTitle()|escape}</h2>

     {* Course of publication *}
     {if $volume->getData('courseOfPublication')}
         <div class="courseOfPublication">{$volume->getData('courseOfPublication')|escape}</div>
     {/if}

     {* Contributors *}
     {if count($editors) > 0 || count($authors) > 0}
         <div class="item contributors">
             {if count($editors) > 0}
                 <div class="sub_item editors">
                     <h3 class="label">
                        {if count($editors) > 1}
                            {translate key="plugins.generic.volumesForm.catalog.editors"}
                        {else}
                            {translate key="plugins.generic.volumesForm.catalog.editor"}
                         {/if}
                     </h3>
                     {foreach name="editors" from=$editors item=editor}
                         {strip}
                             <span class="label">{$editor|escape}</span>
                             {if !$smarty.foreach.editors.last}
                                 {translate key="submission.authorListSeparator"}
                             {/if}
                         {/strip}
                     {/foreach}
                 </div>
             {/if}
             {if count($authors) > 0}
                 <div class="sub_item authors">
                     {foreach name="authors" from=$authors item=author}
                         {strip}
                             <span class="label">{$author|escape}</span>
                             {if !$smarty.foreach.authors.last}
                                 {translate key="submission.authorListSeparator"}
                             {/if}
                         {/strip}
                     {/foreach}
                 </div>
             {/if}
         </div>
     {/if}

     {* ISBN *}
     {assign var=isbn10 value=$volume->getData('isbn10')}
     {assign var=isbn13 value=$volume->getData('isbn13')}
     {if $isbn10 || $isbn13}
         <div class="item isbn">
             {if $isbn10}
                 <div class="identification_code isbn10">
                     {translate key="plugins.generic.volumesForm.catalog.isbn10"}{$isbn10|escape}
                 </div>
             {/if}
             {if $isbn13}
                 <div class="identification_code isbn13">
                     {translate key="plugins.generic.volumesForm.catalog.isbn13"}{$isbn13|escape}
                 </div>
             {/if}
         </div>
     {/if}

     {* Description *}
     {assign var="image" value=$volume->getImage()}
     {assign var="description" value=$volumeDescription|strip_unsafe_html}
     <div class="about_section{if $image} has_image{/if}{if $description} has_description{/if}">
         {if $volume->getImage()}
            <div class="cover">
                <div class="cover">
                    <img src="{$volumeCover}" alt="{$volume->getLocalizedTitle()|escape|default: 'null'}" />
                </div>
            </div>
         {/if}
         {if $description}
             <div class="description">
                 {$description|strip_unsafe_html}
             </div>
         {/if}
     </div>

     {* Count of monographs in this volume *}
     {if $total}
         <div class="monograph_count_volume">
             {translate key="volumes.browseTitles" numTitles=$total}
         </div>
     {/if}

     {* Series *}
     {if count($seriesArray) > 0}
         <div class="item series">
             <h3 class="label">{translate key="series.series"}</h3>
             {foreach $seriesArray as $seriesItem}
                 {assign var=series value=$seriesItem['series']}
                 <div class="value sub_item">
                     <h4 class="label series_title">
                         <a title="{translate key='plugins.generic.volumesForm.catalog.toTheSeries'}" href="{url page="catalog" op="series" path=$series->getPath()}">{$series->getLocalizedFullTitle()|escape}</a>
                     </h4>
                     {if $seriesItem['positions'] || $series->getOnlineISSN() || $series->getPrintISSN()}
                         <ul class="value">
                             {if $seriesItem['positions']}
                                 <li>{translate key="plugins.generic.volumesForm.catalog.volume"}: {$seriesItem['positions']|escape}</li>
                             {/if}
                             {if $series->getOnlineISSN()}
                                 <li>{translate key="catalog.manage.series.onlineIssn"}: {$series->getOnlineISSN()|escape}</li>
                             {/if}
                             {if $series->getPrintISSN()}
                                 <li>{translate key="catalog.manage.series.printIssn"}: {$series->getPrintISSN()|escape}</li>
                             {/if}
                         </ul>
                     {/if}
                 </div>
             {/foreach}
         </div>
     {/if}
 
     {* No published titles in this category *}
     {if empty($publishedSubmissions)}
         <h3 class="label">
             {translate key="catalog.category.heading"}
         </h3>
         <p class="value">{translate key="volumes.noTitlesSection"}</p>
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
 