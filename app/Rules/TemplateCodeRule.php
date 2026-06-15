<?php

namespace App\Rules;

use App\Models\TemplateCode;
use Illuminate\Contracts\Validation\Rule;

class TemplateCodeRule implements Rule
{
    protected $id;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        //
        $codeCount = count($value);
        $subscriptions = auth()->user()->hasSubscription('template') ;
        // if($codeCount > 3 && !$subscriptions){
        //     return false;
        // }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return  'no_subscription';
        return 'You can not add more than 3 Codes';
    }
}
