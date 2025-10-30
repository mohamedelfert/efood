<div class="whatsapp-template-preview template-2" style="max-width: 400px; margin: 0 auto; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 5px rgba(0,0,0,0.05);">
    <!-- Header Image (compact for template 2) -->
    @if($template && $template->image)
        <div class="template-header-image">
            <img src="{{ asset('storage/app/public/whatsapp_template/'.$template->image) }}" 
                alt="" style="width: 100%; height: 120px; object-fit: cover; display: block;">
        </div>
    @endif

    <div style="padding: 20px;">
        <!-- Logo (centered, smaller for compact look) -->
        @if($template && $template->logo)
            <div class="text-center mb-3">
                <img id="logoViewer" src="{{ asset('storage/app/public/whatsapp_template/'.$template->logo) }}" 
                    alt="" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6;">
            </div>
        @endif

        <!-- Title (simple bold) -->
        <h5 id="whatsapp-title" class="text-center mb-3" style="color: #212529; font-weight: bold; font-size: 18px;">
            {{ $template?->title ?? translate('Wallet_Topped_Up_Successfully!') }}
        </h5>

        <!-- Body (vertical list style for template 2) -->
        <div id="whatsapp-body" style="color: #495057; line-height: 1.4; margin-bottom: 20px; font-size: 13px;">
            <ul style="padding-right: 20px; margin: 0; list-style-type: none;">
                <li style="margin-bottom: 8px; border-bottom: 1px solid #e9ecef; padding-bottom: 5px;">
                    <strong>المبلغ:</strong> {amount} {currency}
                </li>
                <li style="margin-bottom: 8px; border-bottom: 1px solid #e9ecef; padding-bottom: 5px;">
                    <strong>رقم العملية:</strong> {transaction_id}
                </li>
                <li style="margin-bottom: 8px; border-bottom: 1px solid #e9ecef; padding-bottom: 5px;">
                    <strong>الرصيد الجديد:</strong> {new_balance}
                </li>
                <li style="margin-bottom: 10px;">
                    {{ $template?->body ?? translate('Your_wallet_has_been_topped_up_successfully._Check_details_above.') }}
                </li>
            </ul>
        </div>

        <!-- Button (outlined style) -->
        @if($template?->button_name || $template?->button_url)
            <div class="text-center mb-3">
                <a href="{{ $template?->button_url ?? '#' }}" 
                    style="display: inline-block; background: transparent; color: #25D366; padding: 10px 25px; 
                    text-decoration: none; border: 1px solid #25D366; border-radius: 20px; font-weight: bold; font-size: 14px;">
                    <span id="whatsapp-button">{{ $template?->button_name ?? translate('View_Wallet') }}</span>
                </a>
            </div>
        @endif

        <!-- Footer Text (small) -->
        @if($template?->footer_text)
            <div id="whatsapp-footer" style="color: #6c757d; font-size: 12px; text-align: center; margin-bottom: 10px; padding-top: 10px; border-top: 1px solid #e9ecef;">
                {{ $template->footer_text }}
            </div>
        @endif

        <!-- Page Links (vertical stack) -->
        <div class="text-center mb-3" style="font-size: 11px;">
            @if($template?->privacy)
                <div class="whatsapp-privacy-check mb-1">
                    <a href="#" style="color: #6c757d; text-decoration: none;">{{translate('Privacy_Policy')}}</a>
                </div>
            @endif
            @if($template?->refund)
                <div class="whatsapp-refund-check mb-1">
                    <a href="#" style="color: #6c757d; text-decoration: none;">{{translate('Refund_Policy')}}</a>
                </div>
            @endif
            @if($template?->cancelation)
                <div class="whatsapp-cancelation-check mb-1">
                    <a href="#" style="color: #6c757d; text-decoration: none;">{{translate('Cancelation_Policy')}}</a>
                </div>
            @endif
            @if($template?->contact)
                <div class="whatsapp-contact-check">
                    <a href="#" style="color: #6c757d; text-decoration: none;">{{translate('Contact_Us')}}</a>
                </div>
            @endif
        </div>

        <!-- Social Media Links (small icons vertical) -->
        <div class="text-center mb-3">
            @if($template?->facebook)
                <a href="#" class="whatsapp-facebook-check d-block mb-1" style="color: #3b5998; font-size: 16px;">
                    <i class="tio-facebook"></i>
                </a>
            @endif
            @if($template?->instagram)
                <a href="#" class="whatsapp-instagram-check d-block mb-1" style="color: #E1306C; font-size: 16px;">
                    <i class="tio-instagram"></i>
                </a>
            @endif
            @if($template?->twitter)
                <a href="#" class="whatsapp-twitter-check d-block mb-1" style="color: #1DA1F2; font-size: 16px;">
                    <i class="tio-twitter"></i>
                </a>
            @endif
            @if($template?->linkedin)
                <a href="#" class="whatsapp-linkedin-check d-block mb-1" style="color: #0077b5; font-size: 16px;">
                    <i class="tio-linkedin"></i>
                </a>
            @endif
            @if($template?->pinterest)
                <a href="#" class="whatsapp-pinterest-check d-block" style="color: #bd081c; font-size: 16px;">
                    <i class="tio-pinterest"></i>
                </a>
            @endif
        </div>

        <!-- Copyright (very small) -->
        @if($template?->copyright_text)
            <div id="whatsapp-copyright" style="color: #adb5bd; font-size: 10px; text-align: center; padding-top: 5px; border-top: 1px dotted #dee2e6;">
                {{ $template->copyright_text }}
            </div>
        @endif
    </div>
</div>