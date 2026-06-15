<?php


namespace App\Providers;


use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Services\DataTable;

class CustomDatatable extends DataTable
{
    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->parameters([
                'buttons' => ['postExcel', 'postCsv', 'postPdf'],
            ]);
    }
}
