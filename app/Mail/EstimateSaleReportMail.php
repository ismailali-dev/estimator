<?php

namespace App\Mail;

use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EstimateSaleReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $estimate;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $documentPath;
    public $isEstimate;

    public function __construct($documentPath)
    {
        //
        $this->documentPath = $documentPath;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.estimate-sales-report-mail')
            ->attach($this->documentPath, [
                'as' => 'document.docx', // Custom name for the attachment
                'mime' => 'application/msword',
            ]);
    }
}
