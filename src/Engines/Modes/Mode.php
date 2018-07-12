<?php

namespace Yab\MySQLScout\Engines\Modes;

use Laravel\Scout\Builder;
use Yab\MySQLScout\Services\ModelService;

abstract class Mode
{
    protected $whereParams = [];

    protected $modelService;

    public function __construct()
    {
        $this->modelService = resolve(ModelService::class);
    }

    abstract public function buildWhereRawString(Builder $builder);

    abstract public function buildParams(Builder $builder);

    abstract public function isFullText();

    protected function buildWheres(Builder $builder)
    {
        $this->whereParams = null;

        $queryString = '';

        $parsedWheres = $this->parseWheres($builder->wheres);

        foreach ($parsedWheres as $parsedWhere) {
            $field = $parsedWhere[0];
            $operator = $parsedWhere[1];
            $value = $parsedWhere[2];

            $this->whereParams[$field] = $value;

            $queryString .= "$field $operator ? AND ";
        }

        return $queryString;
    }

    private function parseWheres($wheres)
    {
        $pattern = '/([A-Za-z_]+[A-Za-z_0-9]?)[ ]?(<>|!=|=|<=|<|>=|>)/';

        $result = array();
        foreach ($wheres as $field => $value) {
            preg_match($pattern, $field, $matches);
            $result [] = !empty($matches) ? array($matches[1], $matches[2], $value) : array($field, '=', $value);
        }

        return $result;
    }

    public function getRelations(Builder $builder)
    {
        $relations = [];

        $this->fields = $this->modelService->setModel($builder->model)->getSearchableFields();


        foreach ($this->fields as $field) {
            $data = explode(':', $field);
            if(count($data) > 1){
                $relations[] = $data[0];
            }
        }

        return array_unique($relations);
    }
}
