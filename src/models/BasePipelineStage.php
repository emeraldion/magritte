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

namespace Emeraldion\Magritte\Models;

use Emeraldion\EmeRails\Models\ActiveRecord;

/**
 * @class BasePipelineStage
 * @short Edit this model's short description
 * @details Edit this model's detailed description
 */
class BasePipelineStage extends ActiveRecord
{
    use ByShortName;

    public function get_localized_name(): string
    {
        return l(
            sprintf('pipeline-stage-name-%s', preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($this->short_name)))
        );
    }
}
