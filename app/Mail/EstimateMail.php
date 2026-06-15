<?php

namespace App\Mail;

use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EstimateMail extends Mailable
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

    public function __construct(Estimate $estimate,$documentPath,$isEstimate=true)
    {
        //
        $this->estimate = $estimate;
        $this->documentPath = $documentPath;
        $this->isEstimate = $isEstimate;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if($this->isEstimate){
            return $this->view('emails.estimate-mail')
                ->subject("EZ Estimater")
                ->with('estimate', $this->estimate)
                ->with('isEstimate', $this->isEstimate)
                ->attach($this->documentPath, [
                    'as' => 'document.docx', // Custom name for the attachment
                    'mime' => 'application/msword',
                ]);
        }else{
            return $this->view('emails.estimate-mail')
                ->with('estimate', $this->estimate)
                ->subject("EZ Estimater")
                ->with('isEstimate', $this->isEstimate)
                ->attach($this->documentPath, [
                    'as' => 'document.xlsx', // Custom name for the attachment
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
        }


    }
}
