<?php


namespace App\Http\Controllers;


use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;

trait DatatableTrait
{
    protected abstract function getDataTableColumns() : array;
    protected abstract function getDataTableRows() : array;

    protected function injectDatatable(){
        View::share('dataTableColumns', json_encode($this->getDataTableColumns()));
    }

    private function exportDatable(){

    }

    public final function datatable(){
        $dt = DataTables::of($this->getDataTableRows())
            ->addIndexColumn();
        $actions = [];
        foreach ($this->getDataTableColumns() as $column){
            if(!empty($column['onAction'])){
                $dt->addColumn($column['data'],$column['onAction']);
                $actions[] = $column['data'];
            }
        }
        $dt->rawColumns($actions);
        return $dt->make(true);
    }
}
