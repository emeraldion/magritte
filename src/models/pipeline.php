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

use Emeraldion\EmeRails\Db;
use Emeraldion\EmeRails\Models\ActiveRecord;

/**
 * @class Pipeline
 * @short Edit this model's short description
 * @details Edit this model's detailed description
 */
class Pipeline extends ActiveRecord
{
    public static function find_by_short_name(string $short_name): ?self
    {
        $conn = Db::get_connection();

        $factory = new self();
        $ret = $factory->find_one([
            'where_clause' => "`short_name` = '{$conn->escape($short_name)}'"
        ]);

        Db::close_connection($conn);

        return $ret;
    }

    public function get_items_for_stage(string $stage_name): array
    {
        return array_filter($this->items ?? [], function ($item) use ($stage_name) {
            return $item->stage == $stage_name;
        });
    }

    public function get_css_class(array $base_classes = ['pipeline']): string
    {
        $cls = $base_classes;
        if (!$this->enabled) {
            $cls[] = 'pipeline-disabled';
        }
        return implode(' ', $cls);
    }

    public function get_localized_name(): string
    {
        return l(sprintf('pipeline-name-%s', preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($this->short_name))));
    }

    public function get_localized_description(): string
    {
        return l(
            sprintf('pipeline-description-%s', preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($this->short_name)))
        );
    }
}
