**volumesForm Plugin**

This plugin extends the press settings (series and categories) in OMP by adding the type "volume".

Compatible with OMP 3.4.0

Upon installation the plugin will:
* create two new database tables: volumes and volume_settings
* add a new tab under Press settings which lets you add new volumes
* add a select-menu to the submission form at step1 and the title-abstract-tab (optionally to the quicksubmit form...)
* show volumes on the catalog page in the frontend

You need to create a folder "cover" inside the main folder of this plugin.
Run: php lib/pkp/tools/installPluginVersion.php plugins/generic/volumesForm/version.xml

To get volume title and position on book landing page, integrate this line in your theme monograph_full.tpl:
{call_hook name="Templates::Catalog::Book::Volume"}  
To get volume title and position on chapter landing page, integrate this line in your theme chapter.tpl:
{call_hook name="Templates::Catalog::Chapter::Volume"}
To get volume titel and link to volume page in a catalog entry, integrate this line in your theme monograp_summary.tpl:
{call_hook name="Templates::Catalog::MonographSummary::Volume"}