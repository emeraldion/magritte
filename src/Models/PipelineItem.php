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
 * @class PipelineItem
 * @short Edit this model's short description
 * @details Edit this model's detailed description
 */
class PipelineItem extends ActiveRecord implements Pipable
{
    public function get_identifier(): string
    {
        return $this->id;
    }

    public function get_label(): string
    {
        return $this->name;
    }

    public function get_status(): string
    {
        return $this->status;
    }
}
