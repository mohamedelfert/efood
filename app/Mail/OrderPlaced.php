<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Model\CustomerAddress;
use App\Models\EmailTemplate;
use App\Model\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

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
        try {
            $order = Order::with(['customer', 'branch', 'delivery_man', 'details'])->find($this->order_id);

            if (!$order) {
                Log::error('Order not found for email', ['order_id' => $this->order_id]);
                throw new \Exception('Order not found');
            }

            $address = $order->delivery_address ?? (
                $order->delivery_address_id ? CustomerAddress::find($order->delivery_address_id) : null
            );
            $order->address = $address;

            $company_name = BusinessSetting::where('key', 'restaurant_name')->first()?->value ?? 'eFood';

            // Get email template
            $template = EmailTemplate::with('translations')
                ->where('type', 'user')
                ->where('email_type', 'new_order')
                ->first();

            $template_id = $template?->email_template ?? 3;
            $local = $order->customer?->language_code ?? 'en';

            // Default values
            $defaults = [
                'title' => 'Order Placed Successfully!',
                'body' => "Hello {user_name},\n\nYour order #{order_id} has been placed successfully.\n\nThank you for choosing {restaurant_name}!",
                'footer_text' => 'If you have any questions, please contact our support team.',
                'copyright_text' => '© ' . date('Y') . ' ' . $company_name . '. All rights reserved.',
            ];

            // Get content from template or use defaults
            $title = $template?->title ?? $defaults['title'];
            $body = $template?->body ?? $defaults['body'];
            $footer_text = $template?->footer_text ?? $defaults['footer_text'];
            $copyright_text = $template?->copyright_text ?? $defaults['copyright_text'];

            // Apply translations if available
            if ($template && $local !== 'en' && $template->translations->isNotEmpty()) {
                foreach ($template->translations as $t) {
                    if ($t->locale === $local && $t->value) {
                        switch ($t->key) {
                            case 'title':
                                $title = $t->value;
                                break;
                            case 'body':
                                $body = $t->value;
                                break;
                            case 'footer_text':
                                $footer_text = $t->value;
                                break;
                            case 'copyright_text':
                                $copyright_text = $t->value;
                                break;
                        }
                    }
                }
            }

            // Replace variables in content
            $customerName = $order->customer?->name ?? 'Valued Customer';
            $branchName = $order->branch?->name ?? $company_name;

            $title = Helpers::text_variable_data_format(
                value: $title,
                user_name: $customerName,
                restaurant_name: $branchName,
                order_id: $order->id
            );

            $body = Helpers::text_variable_data_format(
                value: $body,
                user_name: $customerName,
                restaurant_name: $branchName,
                order_id: $order->id
            );

            $footer_text = Helpers::text_variable_data_format(value: $footer_text);
            $copyright_text = Helpers::text_variable_data_format(value: $copyright_text);

            // ✅ Create data array with NO button
            $data = [
                'title' => $title,
                'body' => $body,
                'footer_text' => $footer_text,
                'copyright_text' => $copyright_text,
                'button_name' => null, // ✅ Explicitly set to null
                'button_url' => null,  // ✅ Explicitly set to null
                'logo' => '',
                'privacy' => $template?->privacy ?? 0,
                'refund' => $template?->refund ?? 0,
                'cancelation' => $template?->cancelation ?? 0,
                'contact' => $template?->contact ?? 1,
            ];

            // Prepare view data
            $viewData = [
                'company_name' => $company_name,
                'title' => $title,
                'body' => $body,
                'footer_text' => $footer_text,
                'copyright_text' => $copyright_text,
                'order' => $order,
                'data' => $data,
            ];

            // Try to generate and attach PDF
            try {
                $pdf = Pdf::loadView('email-templates.invoice', compact('order'));

                return $this->subject(translate('Order Placed - #') . $order->id)
                    ->view('email-templates.new-email-format-' . $template_id, $viewData)
                    ->attachData($pdf->output(), 'Invoice_Order_' . $order->id . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            } catch (\Exception $pdfError) {
                Log::warning('PDF generation failed, sending email without PDF', [
                    'order_id' => $order->id,
                    'error' => $pdfError->getMessage()
                ]);

                // Send without PDF if PDF generation fails
                return $this->subject(translate('Order Placed - #') . $order->id)
                    ->view('email-templates.new-email-format-' . $template_id, $viewData);
            }

        } catch (\Exception $e) {
            Log::error('OrderPlaced mail build failed', [
                'order_id' => $this->order_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}