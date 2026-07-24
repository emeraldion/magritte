<?php
/**
 *                                _ __  __
 *    ____ ___  ____ _____ ______(_) /_/ /____
 *   / __ `__ \/ __ `/ __ `/ ___/ / __/ __/ _ \
 *  / / / / / / /_/ / /_/ / /  / / /_/ /_/  __/
 * /_/ /_/ /_/\__,_/\__, /_/  /_/\__/\__/\___/
 *                 /____/
 *
 * (c) Claudio Procida 2026
 *
 * @format
 */

use Emeraldion\EmeRails\Controllers\BaseController;
use Emeraldion\Magritte\Models\BasePipeline;
use Emeraldion\Magritte\Models\BasePipelineStage;

/**
 * @class PipelineController
 * @short Edit this controller's short description
 * @details Edit this controller's detailed description
 */
class PipelineController extends BaseController
{
    /**
     * @fn init
     * @short Performs specialized initialization
     * @details You should use this method to do your custom initialization.
     */
    protected function init()
    {
        parent::init();

        $this->accept_parameter(['stage', 'view'], 'id', ['type' => 'int', 'required' => true]);
    }

    /**
     * @fn index
     * @short This is the default action
     * @details This is the default action when the controller is invoked without an action
     */
    public function index()
    {
        $factory = new BasePipeline();
        $this->pipelines = $factory->find_all();
        $this->set_title(l('pipeline-index-title'));
    }

    /**
     * @fn view
     * @short Edit this actions's short description
     * @details Edit this actions's detailed description
     */
    public function view()
    {
        if ($pipeline = $this->pipeline = BasePipeline::find($this->parameters->id)) {
            // var_dump($pipeline);

            if ($pipeline->has_many(BasePipelineStage::class, ['as' => 'stages'])) {
                // var_dump($pipeline->pipeline_stages);
            }
        }

        $this->set_title(sprintf(l('pipeline-view-title-@1'), $this->pipeline->name));
    }

    /**
     * @fn stage
     * @short Edit this actions's short description
     * @details Edit this actions's detailed description
     */
    public function stage()
    {
        if ($stage = $this->stage = BasePipelineStage::find($this->parameters->id)) {
            // var_dump($stage);

            if ($stage->belongs_to(BasePipeline::class)) {
                // var_dump($pipeline->pipeline_stages);
            }
        }

        $this->set_title(sprintf(l('pipeline-stage-title-@1'), $this->stage->name));
    }
}
