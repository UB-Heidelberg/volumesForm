
 {include file="frontend/components/header.tpl" pageTitleTranslated="navigation.catalog"}

 <div class="page page_catalog_volume">
 
     {* Breadcrumb *}
     {include file="frontend/components/breadcrumbs_catalog.tpl" type="volume" currentTitle=$volumeTitle}
     <h1>{$volumeTitle|escape}</h1>
 
     {* Count of monographs in this volume *}
     <div class="monograph_count">
         {translate key="volumes.browseTitles" numTitles=$total}
     </div>
 
     {* Description *}
     {assign var="description" value=$volumeDescription|strip_unsafe_html}
     <div class="about_section{if $image} has_image{/if}{if $description} has_description{/if}">
         <div class="description">
             {$description|strip_unsafe_html}
         </div>
     </div>
 
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
 