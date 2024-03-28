<?php

namespace App\Extensions\Servers\TenantOS;

use App\Models\Order;
use App\Models\User;
use App\Models\OrderProduct;

use Illuminate\Bus\Queueable;
use App\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use App\Models\EmailTemplate;

class NewDedicatedServerSetup extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\Order
     */
    public $order;

    /**
     * The products instance.
     *
     * @var \App\Models\Product
     */
    public $products;

    public $orderProduct;
    public $user;
    /**
     * Create a new message instance.
     *
     * @param \App\Models\Order $invoice
     *
     * @return void
     */
    public function __construct(User $user, Order $order, OrderProduct $orderProduct)
    {
        $this->order = $order;
        $this->products = $order->products()->get();
        $this->orderProduct = $orderProduct;
        $this->user = $user;
    }
    public function build()
    {
        return $this->content();
    }
    public function content(): Content
    {
        $content = EmailTemplate::where('mailable', \App\Extensions\Servers\TenantOS\NewDedicatedServerSetup::class)->first()->html_template;
        $content = str_replace('-$productName-', $this->orderProduct->name, $content);
        $content = str_replace('-$name-', $this->user->first_name, $content);
        return new Content(
            view: 'emails.base',
            with: [
                'content' => $content,
            ],
        );
    }
}