<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UniqueWithSoftDelete implements Rule
{
    protected $table;
    protected $column;
    protected $except;

    public function __construct($table, $column, $except = null)
    {
        $this->table = $table;
        $this->column = $column;
        $this->except = $except;
    }

    public function passes($attribute, $value)
    {
        $query = DB::table($this->table)
            ->where($this->column, $value)
            ->whereNull('deleted_at');

        if ($this->except) {
            $query->where('id', '!=', $this->except);
        }

        return !$query->exists();
    }

    public function message()
    {
        return 'The :attribute has already been taken.';
    }
}