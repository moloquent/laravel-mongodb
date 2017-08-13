<?php

namespace Moloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne as EloquentHasOne;

class HasOne extends EloquentHasOne
{
    use HasOneOrManyTrait;

    /**
     * Add the constraints for a relationship count query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Builder $parent
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationCountQuery(Builder $query, Builder $parent)
    {
        $foreignKey = $this->getHasCompareKey();

        return $query->select($this->getHasCompareKey())->where($this->getHasCompareKey(), 'exists', true);
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Builder $parent
     * @param array|mixed                           $columns
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationQuery(Builder $query, Builder $parent, $columns = ['*'])
    {
        $query->select($columns);

        $key = $this->wrap($this->getQualifiedParentKeyName());

        return $query->where($this->getHasCompareKey(), 'exists', true);
    }

    /**
     * Get the plain foreign key.
     *
     * @return string
     */
    public function getPlainForeignKey()
    {
        $functionName = (method_exists($this, 'getForeignKey')) ? $this->getForeignKey : $this->getForeignKeyName() ;
        return $functionName;
    }
}
