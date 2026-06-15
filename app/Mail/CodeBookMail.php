<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CodeBookMail extends Mailable
{
    use Queueable, SerializesModels;

    public $documentPath;
    public $user;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user,$documentPath)
    {

        $this->user = $user;
        $this->documentPath = $documentPath;
    }


    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.codebook-mail')
            ->with("user",$this->user)
            ->subject("EZ Estimater Codebook")
            ->attach($this->documentPath, [
                        'as' => 'document.docx', // Custom name for the attachment
                        'mime' => 'application/msword',
                    ]);
    }

}
