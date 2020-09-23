<?php

namespace Haxibiao\Helpers\Traits;


use Haxibiao\Helpers\Traits\Relations\BelongsToManyCustom;
use Haxibiao\Helpers\Traits\Relations\MorphToManyCustom;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

Trait PivotEventTrait
{
    public function getObservableEvents()
    {
        return array_merge(
            parent::getObservableEvents(),
            [
                'pivotAttaching', 'pivotAttached',
                'pivotDetaching', 'pivotDetached',
                'pivotUpdating', 'pivotUpdated',
            ],
            $this->observables
        );
    }

    public static function pivotAttaching($callback, $priority = 0)
    {
        static::registerModelEvent('pivotAttaching', $callback, $priority);
    }

    public static function pivotAttached($callback, $priority = 0)
    {
        static::registerModelEvent('pivotAttached', $callback, $priority);
    }

    public static function pivotDetaching($callback, $priority = 0)
    {
        static::registerModelEvent('pivotDetaching', $callback, $priority);
    }

    public static function pivotDetached($callback, $priority = 0)
    {
        static::registerModelEvent('pivotDetached', $callback, $priority);
    }

    public static function pivotUpdating($callback, $priority = 0)
    {
        static::registerModelEvent('pivotUpdating', $callback, $priority);
    }

    public static function pivotUpdated($callback, $priority = 0)
    {
        static::registerModelEvent('pivotUpdated', $callback, $priority);
    }

    protected function newMorphToMany(Builder $query, Model $parent, $name, $table, $foreignPivotKey,
                                      $relatedPivotKey, $parentKey, $relatedKey,
                                      $relationName = null, $inverse = false)
    {
        return new MorphToManyCustom($query, $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey,
            $relationName, $inverse);
    }

    protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey,
                                        $parentKey, $relatedKey, $relationName = null)
    {
        return new BelongsToManyCustom($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }

    public function fireModelEvent($event, $halt = true, $relationName = null, $ids = [], $idsAttributes = [])
    {
        if (!isset(static::$dispatcher)) {
            return true;
        }

        $method = $halt ? 'until' : 'dispatch';

        $result = $this->filterModelEventResults(
            $this->fireCustomModelEvent($event, $method)
        );

        if (false === $result) {
            return false;
        }

        $payload = [$this, $relationName, $ids, $idsAttributes];

        return !empty($result) ? $result : static::$dispatcher->{$method}(
            "eloquent.{$event}: ".static::class, $payload
        );
    }
}