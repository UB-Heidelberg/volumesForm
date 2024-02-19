{fbvFormSection label="volume.volume"}
    {fbvElement type="select" id="volumeId" from=$volumeOptions selected=$volumeId translate=false size=$fbvStyles.size.MEDIUM}
{/fbvFormSection}

{fbvFormSection title="volume.volumePosition" for="volumePosition"}
{fbvElement type="text" label="submission.submit.seriesPosition.description" id="volumePosition" value=$volumePosition rich=false}
{/fbvFormSection}