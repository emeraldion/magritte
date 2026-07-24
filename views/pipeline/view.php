<h1><?php printf(l('pipeline-view-header-@1'), $this->pipeline->name); ?></h1>

<?php if ($this->pipeline->stages) { ?>
<h2><?php print l('pipeline-view-stages-header'); ?></h2>
<ul>
<?php foreach ($this->pipeline->stages as $stage) { ?>
    <li><?php $this->link_to($stage->name, ['action' => 'stage', 'id' => $stage->id]); ?></li>
<?php } ?>
</ul>
<?php } ?>
