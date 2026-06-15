<?php

namespace App\Mail;

use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EstimatePayMail extends Mailable
{
    use Queueable, SerializesModels;

    public $estimate;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $link;

    public function __construct(Estimate $estimate,$link)
    {
        //
        $this->estimate = $estimate;
        $this->link = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.estimatepay-mail')
            ->subject("EZ Estimater payment link")
            ->with('link', $this->link);

    }
}
