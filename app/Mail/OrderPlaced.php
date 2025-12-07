<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Model\CustomerAddress;
use App\Models\EmailTemplate;
use App\Model\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade;


class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $order_id;

    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $order = Order::with(['customer', 'branch', 'delivery_man'])->findOrFail($this->order_id);

        $address = $order->delivery_address ?? ($order->delivery_address_id ? CustomerAddress::find($order->delivery_address_id) : null);
        $order->address = $address;

        $company_name = BusinessSetting::where('key', 'restaurant_name')->first()?->value ?? 'Your Restaurant';

        // Safely get email template
        $template = EmailTemplate::with('translations')
            ->where('type', 'user')
            ->where('email_type', 'new_order')
            ->first();

        $template_id = $template?->email_template ?? 3;
        $local = $order->customer?->language_code ?? 'en';

        // Default content in case no template
        $default = [
            'title' => 'Your order has been placed successfully!',
            'body' => "Hello {user_name},\n\nYour order #{order_id} has been placed at {restaurant_name}.\n\nThank you for choosing us!",
            'footer_text' => 'If you have any questions, feel free to contact us.',
            'copyright_text' => '© ' . date('Y') . ' ' . $company_name . '. All rights reserved.',
        ];

        // Extract translated content safely
        $title = $default['title'];
        $body = $default['body'];
        $footer_text = $default['footer_text'];
        $copyright_text = $default['copyright_text'];

        if ($template) {
            $title = $template->title ?? $default['title'];
            $body = $template->body ?? $default['body'];
            $footer_text = $template->footer_text ?? $default['footer_text'];
            $copyright_text = $template->copyright_text ?? $default['copyright_text'];

            if ($local !== 'en' && $template->translations->isNotEmpty()) {
                foreach ($template->translations as $t) {
                    if ($t->locale === $local && $t->value) {
                        switch ($t->key) {
                            case 'title': $title = $t->value; break;
                            case 'body': $body = $t->value; break;
                            case 'footer_text': $footer_text = $t->value; break;
                            case 'copyright_text': $copyright_text = $t->value; break;
                        }
                    }
                }
            }
        }

        // Replace variables
        $title = Helpers::text_variable_data_format(
            value: $title,
            user_name: $order->customer?->name ?? 'Customer',
            restaurant_name: $order->branch?->name ?? $company_name,
            order_id: $order->id
        );

        $body = Helpers::text_variable_data_format(
            value: $body,
            user_name: $order->customer?->name ?? 'Customer',
            restaurant_name: $order->branch?->name ?? $company_name,
            order_id: $order->id
        );

        $footer_text = Helpers::text_variable_data_format(value: $footer_text);
        $copyright_text = Helpers::text_variable_data_format(value: $copyright_text);

        // Generate PDF Invoice
        $pdf = Facade\Pdf::loadView('email-templates.invoice', compact('order'));

        // return $this->subject(translate('Order Placed - #') . $order->id)
        //     ->view('email-templates.new-email-format-' . $template_id, [
        //         'company_name' => $company_name,
        //         'title' => $title,
        //         'body' => $body,
        //         'footer_text' => $footer_text,
        //         'copyright_text' => $copyright_text,
        //         'order' => $order,
        //     ])
        //     ->attachData($pdf->output(), 'Invoice_Order_' . $order->id . '.pdf', [
        //         'mime' => 'application/pdf',
        //     ]);

        return $this->subject(translate('Order Placed - #') . $order->id)
            ->view('email-templates.new-email-format-' . $template_id, [
                'company_name'   => $company_name,
                'title'          => $title,
                'body'           => $body,
                'footer_text'    => $footer_text,
                'copyright_text' => $copyright_text,
                'order'          => $order,

                // This fixes the error immediately
                'data'           => (object)[
                    'title'          => $title,
                    'body'           => $body,
                    'footer_text'    => $footer_text,
                    'copyright_text' => $copyright_text,
                    'order'          => $order,
                    'company_name'   => $company_name,
                ],
            ])
            ->attachData($pdf->output(), 'Invoice_Order_' . $order->id . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
