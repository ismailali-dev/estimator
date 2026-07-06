<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SettingDocumentsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $documents;
    public $signingLink;
    public $description;


    public function __construct($documents, $signingLink, $description = null)
    {
        $this->documents   = $documents;
        $this->signingLink = $signingLink;
        $this->description = $description;
    }

    public function build()
    {
        $mail = $this->subject('Please Sign Your Documents')
                    ->view('emails.setting_documents')
                    ->with([
                        'documents' => $this->documents,
                        'signingLink' => $this->signingLink,
                        'description' => $this->description,
                    ]);

        // foreach ($this->documents as $doc) {
        //     $fullPath = storage_path('app/public/' . $doc->file_path);
        //     if (file_exists($fullPath)) {
        //         $mail->attach($fullPath, [
        //             'as'   => $doc->document_name . '.' . $doc->file_type,
        //             'mime' => mime_content_type($fullPath),
        //         ]);
        //     }
        // }

        return $mail;
    }
}
