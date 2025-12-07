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

    protected $order_id;

    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

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

        // Default content
        $default = [
            'title' => 'Your order has been placed successfully!',
            'body' => "Hello {user_name},\n\nYour order #{order_id} has been placed at {restaurant_name}.\n\nThank you for choosing us!",
            'footer_text' => 'If you have any questions, feel free to contact us.',
            'copyright_text' => '© ' . date('Y') . ' ' . $company_name . '. All rights reserved.',
            'button_name' => 'Track Order',
            'button_url' => route('track-order', ['id' => $order->id]),
        ];

        // Extract content from template
        $title = $template?->title ?? $default['title'];
        $body = $template?->body ?? $default['body'];
        $footer_text = $template?->footer_text ?? $default['footer_text'];
        $copyright_text = $template?->copyright_text ?? $default['copyright_text'];
        $button_name = $template?->button_name ?? $default['button_name'];
        $button_url = $template?->button_url ?? $default['button_url'];

        // Apply translations if needed
        if ($template && $local !== 'en' && $template->translations->isNotEmpty()) {
            foreach ($template->translations as $t) {
                if ($t->locale === $local && $t->value) {
                    switch ($t->key) {
                        case 'title': $title = $t->value; break;
                        case 'body': $body = $t->value; break;
                        case 'footer_text': $footer_text = $t->value; break;
                        case 'copyright_text': $copyright_text = $t->value; break;
                        case 'button_name': $button_name = $t->value; break;
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

        // Create data object with ALL required properties
        $data = (object)[
            'title' => $title,
            'body' => $body,
            'footer_text' => $footer_text,
            'copyright_text' => $copyright_text,
            'button_name' => $button_name,
            'button_url' => $button_url,
            'order' => $order,
            'company_name' => $company_name,
            
            // Additional properties for compatibility
            'privacy' => $template?->privacy ?? false,
            'refund' => $template?->refund ?? false,
            'cancelation' => $template?->cancelation ?? false,
            'contact' => $template?->contact ?? false,
        ];

        // Generate PDF Invoice
        try {
            $pdf = Facade\Pdf::loadView('email-templates.invoice', compact('order'));
            
            return $this->subject(translate('Order Placed - #') . $order->id)
                ->view('email-templates.new-email-format-' . $template_id, [
                    'company_name' => $company_name,
                    'title' => $title,
                    'body' => $body,
                    'footer_text' => $footer_text,
                    'copyright_text' => $copyright_text,
                    'order' => $order,
                    'data' => $data, // This fixes the undefined property error
                ])
                ->attachData($pdf->output(), 'Invoice_Order_' . $order->id . '.pdf', [
                    'mime' => 'application/pdf',
                ]);
        } catch (\Exception $e) {
            \Log::error('Failed to generate PDF invoice', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            // Send email without PDF if PDF generation fails
            return $this->subject(translate('Order Placed - #') . $order->id)
                ->view('email-templates.new-email-format-' . $template_id, [
                    'company_name' => $company_name,
                    'title' => $title,
                    'body' => $body,
                    'footer_text' => $footer_text,
                    'copyright_text' => $copyright_text,
                    'order' => $order,
                    'data' => $data,
                ]);
        }
    }
}