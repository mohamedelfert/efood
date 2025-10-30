<div class="whatsapp-template-preview template-1" style="max-width: 400px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <!-- Header Image -->
    @if($template && $template->image)
        <div class="template-header-image">
            <img src="{{ asset('storage/app/public/whatsapp_template/'.$template->image) }}" 
                alt="" style="width: 100%; height: auto; display: block;">
        </div>
    @endif

    <div style="padding: 20px;">
        <!-- Logo -->
        @if($template && $template->logo)
            <div class="text-center mb-3">
                <img id="logoViewer" src="{{ asset('storage/app/public/whatsapp_template/'.$template->logo) }}" 
                    alt="" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
            </div>
        @endif

        <!-- Title -->
        <h4 id="whatsapp-title" class="text-center mb-3" style="color: #25D366; font-weight: bold;">
            {{ $template?->title ?? translate('Wallet_Topped_Up_Successfully!') }}
        </h4>

        <!-- Body -->
        <div id="whatsapp-body" style="color: #333; line-height: 1.6; margin-bottom: 20px;">
            {{ $template?->body ?? translate('Your_wallet_has_been_topped_up_successfully._New_balance:_{new_balance}_Amount:_{amount}') }} {{-- أضفت مثال placeholders --}}
        </div>

        <!-- Button -->
        @if($template?->button_name || $template?->button_url)
            <div class="text-center mb-3">
                <a href="{{ $template?->button_url ?? '#' }}" 
                    style="display: inline-block; background: #25D366; color: white; padding: 12px 30px; 
                    text-decoration: none; border-radius: 5px; font-weight: bold;">
                    <span id="whatsapp-button">{{ $template?->button_name ?? translate('View_Wallet') }}</span>
                </a>
            </div>
        @endif

        <!-- Footer Text -->
        @if($template?->footer_text)
            <div id="whatsapp-footer" style="color: #666; font-size: 14px; text-align: center; margin-bottom: 15px;">
                {{ $template->footer_text }}
            </div>
        @endif

        <!-- Page Links -->
        <div class="text-center mb-3" style="font-size: 13px;">
            @if($template?->privacy)
                <a href="#" class="whatsapp-privacy-check" style="color: #25D366; text-decoration: none; margin: 0 5px;">
                    {{translate('Privacy_Policy')}}
                </a>
            @endif
            @if($template?->refund)
                <a href="#" class="whatsapp-refund-check" style="color: #25D366; text-decoration: none; margin: 0 5px;">
                    {{translate('Refund_Policy')}}
                </a>
            @endif
            @if($template?->cancelation)
                <a href="#" class="whatsapp-cancelation-check" style="color: #25D366; text-decoration: none; margin: 0 5px;">
                    {{translate('Cancelation_Policy')}}
                </a>
            @endif
            @if($template?->contact)
                <a href="#" class="whatsapp-contact-check" style="color: #25D366; text-decoration: none; margin: 0 5px;">
                    {{translate('Contact_Us')}}
                </a>
            @endif
        </div>

        <!-- Social Media Links -->
        <div class="text-center mb-3">
            @if($template?->facebook)
                <a href="#" class="whatsapp-facebook-check" style="color: #3b5998; margin: 0 8px; font-size: 20px;">
                    <i class="tio-facebook"></i>
                </a>
            @endif
            @if($template?->instagram)
                <a href="#" class="whatsapp-instagram-check" style="color: #E1306C; margin: 0 8px; font-size: 20px;">
                    <i class="tio-instagram"></i>
                </a>
            @endif
            @if($template?->twitter)
                <a href="#" class="whatsapp-twitter-check" style="color: #1DA1F2; margin: 0 8px; font-size: 20px;">
                    <i class="tio-twitter"></i>
                </a>
            @endif
            @if($template?->linkedin)
                <a href="#" class="whatsapp-linkedin-check" style="color: #0077b5; margin: 0 8px; font-size: 20px;">
                    <i class="tio-linkedin"></i>
                </a>
            @endif
            @if($template?->pinterest)
                <a href="#" class="whatsapp-pinterest-check" style="color: #bd081c; margin: 0 8px; font-size: 20px;">
                    <i class="tio-pinterest"></i>
                </a>
            @endif
        </div>

        <!-- Copyright -->
        @if($template?->copyright_text)
            <div id="whatsapp-copyright" style="color: #999; font-size: 12px; text-align: center;">
                {{ $template->copyright_text }}
            </div>
        @endif
    </div>
</div>