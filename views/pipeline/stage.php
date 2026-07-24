<h1><?php printf(l('pipeline-stage-header-@1'), $this->stage->name); ?></h1>

<?php if ($this->stage->pipeline) { ?>
<h2><?php printf(l('pipeline-stage-pipeline-header-@1'), $this->stage->pipeline->name); ?></h2>
<?php } ?>
