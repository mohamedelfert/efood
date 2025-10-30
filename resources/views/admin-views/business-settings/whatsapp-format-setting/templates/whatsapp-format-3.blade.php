<div class="whatsapp-template-preview template-3" style="max-width: 400px; margin: 0 auto; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 15px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
    <!-- Header Image (full-width for template 3) -->
    @if($template && $template->image)
        <div class="template-header-image">
            <img src="{{ asset('storage/app/public/whatsapp_template/'.$template->image) }}" 
                alt="" style="width: 100%; height: 150px; object-fit: cover; display: block;">
        </div>
    @endif

    <div style="padding: 25px; position: relative;">
        <!-- Logo (top-right corner for template 3) -->
        @if($template && $template->logo)
            <div class="text-right mb-2">
                <img id="logoViewer" src="{{ asset('storage/app/public/whatsapp_template/'.$template->logo) }}" 
                    alt="" style="width: 60px; height: 60px; border-radius: 10px; object-fit: cover; border: 2px solid #25D366;">
            </div>
        @endif

        <!-- Title (with green accent) -->
        <h4 id="whatsapp-title" class="text-center mb-4" style="color: #25D366; font-weight: bold; font-size: 22px; margin-bottom: 15px; position: relative;">
            {{ $template?->title ?? translate('Wallet_Topped_Up_Successfully!') }}
            <span style="display: block; font-size: 12px; color: #666; margin-top: 5px;">{date}</span>
        </h4>

        <!-- Body (with subtle border) -->
        <div id="whatsapp-body" style="color: #333; line-height: 1.5; margin-bottom: 20px; padding: 15px; background: white; border-radius: 10px; border-left: 4px solid #25D366; font-size: 14px;">
            {{ $template?->body ?? translate('Your_wallet_has_been_topped_up_successfully._New_balance:_{new_balance}_Amount:_{amount}_Transaction_ID:_{transaction_id}') }}
        </div>

        <!-- Button (rounded pill style) -->
        @if($template?->button_name || $template?->button_url)
            <div class="text-center mb-4">
                <a href="{{ $template?->button_url ?? '#' }}" 
                    style="display: inline-block; background: linear-gradient(45deg, #25D366, #128C7E); color: white; padding: 12px 40px; 
                    text-decoration: none; border-radius: 25px; font-weight: bold; box-shadow: 0 2px 10px rgba(37, 211, 102, 0.3); transition: transform 0.2s;">
                    <span id="whatsapp-button">{{ $template?->button_name ?? translate('View_Wallet') }}</span>
                </a>
            </div>
        @endif

        <!-- Footer Text (italic) -->
        @if($template?->footer_text)
            <div id="whatsapp-footer" style="color: #666; font-size: 13px; text-align: center; margin-bottom: 15px; font-style: italic;">
                {{ $template->footer_text }}
            </div>
        @endif

        <!-- Page Links (horizontal row) -->
        <div class="text-center mb-3" style="font-size: 12px; border-top: 1px solid #eee; padding-top: 10px;">
            @if($template?->privacy)
                <a href="#" class="whatsapp-privacy-check d-inline-block mx-1" style="color: #25D366; text-decoration: none;">
                    {{translate('Privacy_Policy')}}
                </a> |
            @endif
            @if($template?->refund)
                <a href="#" class="whatsapp-refund-check d-inline-block mx-1" style="color: #25D366; text-decoration: none;">
                    {{translate('Refund_Policy')}}
                </a> |
            @endif
            @if($template?->cancelation)
                <a href="#" class="whatsapp-cancelation-check d-inline-block mx-1" style="color: #25D366; text-decoration: none;">
                    {{translate('Cancelation_Policy')}}
                </a> |
            @endif
            @if($template?->contact)
                <a href="#" class="whatsapp-contact-check d-inline-block mx-1" style="color: #25D366; text-decoration: none;">
                    {{translate('Contact_Us')}}
                </a>
            @endif
        </div>

        <!-- Social Media Links (icons in a row) -->
        <div class="text-center mb-3">
            @if($template?->facebook)
                <a href="#" class="whatsapp-facebook-check d-inline-block mx-2" style="color: #3b5998; font-size: 18px;">
                    <i class="tio-facebook"></i>
                </a>
            @endif
            @if($template?->instagram)
                <a href="#" class="whatsapp-instagram-check d-inline-block mx-2" style="color: #E1306C; font-size: 18px;">
                    <i class="tio-instagram"></i>
                </a>
            @endif
            @if($template?->twitter)
                <a href="#" class="whatsapp-twitter-check d-inline-block mx-2" style="color: #1DA1F2; font-size: 18px;">
                    <i class="tio-twitter"></i>
                </a>
            @endif
            @if($template?->linkedin)
                <a href="#" class="whatsapp-linkedin-check d-inline-block mx-2" style="color: #0077b5; font-size: 18px;">
                    <i class="tio-linkedin"></i>
                </a>
            @endif
            @if($template?->pinterest)
                <a href="#" class="whatsapp-pinterest-check d-inline-block mx-2" style="color: #bd081c; font-size: 18px;">
                    <i class="tio-pinterest"></i>
                </a>
            @endif
        </div>

        <!-- Copyright (bottom subtle) -->
        @if($template?->copyright_text)
            <div id="whatsapp-copyright" style="color: #999; font-size: 11px; text-align: center; padding-top: 10px; border-top: 1px dashed #eee;">
                {{ $template->copyright_text }}
            </div>
        @endif
    </div>
</div>