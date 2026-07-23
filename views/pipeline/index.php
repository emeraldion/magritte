<h1><?php print l('pipeline-index-heading'); ?></h1>
<?php if ($this->pipelines) { ?>
<ul>
<?php foreach ($this->pipelines as $pipeline) { ?>
    <li><?php $this->link_to($pipeline->name, ['action' => 'view', 'id' => $pipeline->id]); ?></li>
<?php } ?>
</ul>
<?php }
