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
use Emeraldion\EmeRails\Models\Relationship;

/**
 * @class BasePipeline
 * @short Edit this model's short description
 * @details Edit this model's detailed description
 */
class BasePipeline extends ActiveRecord
{
    use ByShortName;

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

    /**
     * @override
     */
    public function set_has_and_belongs_to_many(array $items, array $params = []): ?array
    {
        $conn = Db::get_connection();

        $itemclasses = array_map(function ($item) {
            return get_class($item);
        }, $items);
        if (count(array_unique($itemclasses)) > 1) {
            throw new \Exception(sprintf('Mixed classes: %s', implode(', ', $itemclasses)));
        }

        // Normalize params to the form ['item:pk' => ['key1' => 'value1', 'key2' => 'value2', ...]]
        $params_count = count($params);
        $items_count = count($items);
        $item = first($items);
        if ($params_count > 1 && $params_count != $items_count) {
            throw new \Exception(
                sprintf(
                    'Number of relationship attributes (%d) does not match number of items: %d',
                    $params_count,
                    $items_count
                )
            );
        } elseif ($params_count === 1) {
            $p = [];
            // They should all be the same right?
            $other_pk_name = $item->get_primary_key_name();
            for ($i = 0; $i < $items_count; $i += 1) {
                $p[$items[$i]->$other_pk_name] = $params;
            }
            $pk_name = $this->get_primary_key_name();
            $pk_value = $this->$pk_name;
            $params = [$pk_value => $p];
        } else {
            // TODO: ensure the array has the correct structure
        }

        $itemclass = first($itemclasses);
        $update = false;
        $update_only = false;
        $item_fk_values = implode(
            ',',
            array_map(function ($item) use ($conn) {
                return "'" . $conn->escape($item->{$item->get_primary_key_name()}) . "'";
            }, $items)
        );
        if (
            $existing_rel = $this->has_and_belongs_to_many($itemclass, [
                'where_clause' => "`{$item->get_table_name()}`.`{$item->get_foreign_key_name()}` IN ({$item_fk_values})"
            ])
        ) {
            $update = true;
            $existing_rr = $existing_rel[$pk_value];
            if (count($existing_rel) == $items_count) {
                $update_only = true;
            }
        }
        if (!$update_only) {
            $rel = Relationship::many_to_many(get_class($this), $itemclass)->among([$this], $items, $params);
            $rr = $rel[$pk_value];
        }

        $success = true;
        $ret = [];
        foreach ($items as $item) {
            $item_pk_value = $item->{$item->get_primary_key_name()};
            if ($update && array_key_exists($item_pk_value, $existing_rr)) {
                $r = $existing_rr[$item_pk_value];
                $status = 'updated';
            } else {
                $r = $rr[$item_pk_value];
                $status = 'created';
            }
            $item_params = $params[$pk_value][$item_pk_value];
            foreach ($item_params as $key => $value) {
                $r->$key = $value;
            }
            $s = $r->save();
            $ret[$item_pk_value] = [
                'status' => $status,
                'success' => $s
            ];
            $success = $success && $s;
        }

        Db::close_connection($conn);

        return $success ? $ret : null;
    }
}
