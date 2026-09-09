<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ImportedAccountCredentials extends Mailable
{
    public $accountEmail;
    public $accountName;
    public $generatedPassword;

    public function __construct(string $email, string $name, string $password)
    {
        $this->accountEmail = $email;
        $this->accountName = $name;
        $this->generatedPassword = $password;
    }

    public function build()
    {
        return $this->subject('Your EZEstimater account is ready')
            ->view('emails.imported_account_credentials');
    }
}
