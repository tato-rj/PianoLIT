<?php

namespace App\Resources\DataTables;


class Builder
{
    protected $withDate, $escapedFields;

    public function __construct($data)
    {
        $this->datatable = \DataTables::of($data);
        $this->escapedFields = [];
    }

    public function make()
    {       
        return $this->datatable->rawColumns(array_merge(['action'], $this->escapedFields))->make(true);
    }

    public function withClass($callback)
    {
        $this->datatable = $this->datatable->setRowClass($callback);

        return $this;
    }

    public function withDate($dates = ['created_at'])
    {
        foreach ($dates as $date) {
            $this->datatable = $this->datatable->editColumn($date, function ($model) use ($date) {
               return $model->$date ? $this->addSort($model->$date->timestamp).$model->$date->toFormattedDateString() : null;
            });
        }

        $this->escape($dates);

        return $this;
    }

    public function withCount(array $fields)
    {
        foreach ($fields as $field) {
            $this->datatable = $this->datatable->editColumn($field, function ($model) use ($field) {
               return $model->$field ? count($model->$field) : null;
            });
        }

        return $this;
    }

    public function withBlade(array $fields)
    {
        foreach ($fields as $field => $view) {
            $this->datatable = $this->datatable->addColumn($field, function($item) use ($view) {
                return $view->with(compact('item'))->render();
            });
        }

        $this->escape(array_keys($fields));

        return $this;
    }

    public function checkable()
    {
        $this->datatable = $this->datatable->addColumn('checkbox', function($item) {
            return view('components.datatable.checkbox')->with(compact('item'))->render();
        });

        $this->escape(['checkbox']);

        return $this;
    }

    public function escape(array $fields)
    {
        $this->escapedFields = array_merge($this->escapedFields, $fields);

        return $this;
    }

    public function addSort($sort)
    {
        return '<span class="invisible position-absolute">'.$sort.'</span>';
    }
}