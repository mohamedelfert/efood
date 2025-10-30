<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Models\WhatsAppTemplate;
use App\Model\Translation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;

class WhatsAppTemplateController extends Controller
{
    public function index(Request $request)
    {
        $type = 'user';
        $tab = 'wallet-topup';
        $whatsappType = 'wallet_topup';
        $template = WhatsAppTemplate::withoutGlobalScope('translate')
            ->with('translations')
            ->where('type', $type)
            ->where('whatsapp_type', $whatsappType)
            ->first();

        $selectedTemplate = $template ? ($template->whatsapp_template ?? 1) : 1;

        return view('admin-views.business-settings.whatsapp-format-setting.wallet-topup-format', compact('template', 'type', 'tab', 'selectedTemplate'));
    }

    public function updateIndex(Request $request): RedirectResponse
    {
        if (env('APP_MODE') == 'demo') {
            Toastr::info(translate('messages.update_option_is_disable_for_demo'));
            return back();
        }

        $request->validate([
            'body' => 'required|array',
            'body.0' => 'required|string|max:1600',
            'title' => 'nullable|array',
            'title.0' => 'nullable|string|max:255',
            'footer_text' => 'nullable|array',
            'footer_text.0' => 'nullable|string|max:500',
            'button_name' => 'nullable|array',
            'button_name.0' => 'nullable|string|max:15',
            'copyright_text' => 'nullable|array',
            'copyright_text.0' => 'nullable|string|max:50',
            'button_url' => 'nullable|url',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'whatsapp_template' => 'required|in:1,2,3',
            'privacy' => 'nullable|boolean',
            'refund' => 'nullable|boolean',
            'cancelation' => 'nullable|boolean',
            'contact' => 'nullable|boolean',
            'facebook' => 'nullable|boolean',
            'instagram' => 'nullable|boolean',
            'twitter' => 'nullable|boolean',
            'linkedin' => 'nullable|boolean',
            'pinterest' => 'nullable|boolean',
        ]);

        $type = 'user';
        $whatsappType = 'wallet_topup';
        $template = WhatsAppTemplate::where('type', $type)->where('whatsapp_type', $whatsappType)->first() ?? new WhatsAppTemplate();

        $defaultIndex = array_search('default', $request->lang ?? ['default']);
        
        $template->title = $request->title[$defaultIndex] ?? '';
        $template->body = $request->body[$defaultIndex] ?? '';
        $template->footer_text = $request->footer_text[$defaultIndex] ?? '';
        $template->button_name = $request->button_name[$defaultIndex] ?? '';
        $template->copyright_text = $request->copyright_text[$defaultIndex] ?? '';
        $template->button_url = $request->button_url;
        $template->whatsapp_template = $request->whatsapp_template;
        
        if ($request->hasFile('image')) {
            $template->image = Helpers::update('whatsapp_template/', $template->image, 'png', $request->file('image'));
        }
        
        if ($request->hasFile('logo')) {
            $template->logo = Helpers::update('whatsapp_template/', $template->logo, 'png', $request->file('logo'));
        }
        
        $template->privacy = $request->boolean('privacy', false);
        $template->refund = $request->boolean('refund', false);
        $template->cancelation = $request->boolean('cancelation', false);
        $template->contact = $request->boolean('contact', false);
        $template->facebook = $request->boolean('facebook', false);
        $template->instagram = $request->boolean('instagram', false);
        $template->twitter = $request->boolean('twitter', false);
        $template->linkedin = $request->boolean('linkedin', false);
        $template->pinterest = $request->boolean('pinterest', false);
        
        $template->whatsapp_type = $whatsappType;
        $template->type = $type;
        $template->save();

        $this->handleTranslations($request, $template);

        Toastr::success(translate('whatsapp_template_updated_successfully'));
        return back();
    }

    public function updateStatus(Request $request, $status): RedirectResponse
    {
        if (env('APP_MODE') == 'demo') {
            Toastr::info(translate('update_option_is_disable_for_demo'));
            return back();
        }

        $key = 'wallet_topup_whatsapp_status_user';
        $this->InsertOrUpdateBusinessData(['key' => $key], ['value' => $status]);

        Toastr::success(translate('whatsapp_status_updated'));
        return back();
    }

    private function handleTranslations(Request $request, $template): void
    {
        $langs = $request->lang ?? ['default'];

        foreach ($langs as $index => $key) {
            if ($key != 'default') {
                // Title
                if (isset($request->title[$index])) {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\WhatsAppTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'title'
                        ],
                        ['value' => $request->title[$index]]
                    );
                }

                // Body
                if (isset($request->body[$index])) {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\WhatsAppTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'body'
                        ],
                        ['value' => $request->body[$index]]
                    );
                }

                // Footer Text
                if (isset($request->footer_text[$index])) {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\WhatsAppTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'footer_text'
                        ],
                        ['value' => $request->footer_text[$index]]
                    );
                }

                // Button Name
                if (isset($request->button_name[$index])) {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\WhatsAppTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'button_name'
                        ],
                        ['value' => $request->button_name[$index]]
                    );
                }

                // Copyright Text
                if (isset($request->copyright_text[$index])) {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\WhatsAppTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'copyright_text'
                        ],
                        ['value' => $request->copyright_text[$index]]
                    );
                }
            }
        }
    }

    private function InsertOrUpdateBusinessData($key, $value): void
    {
        $businessSetting = BusinessSetting::where(['key' => $key['key']])->first();
        if ($businessSetting) {
            $businessSetting->value = $value['value'];
            $businessSetting->save();
        } else {
            BusinessSetting::create(array_merge($key, $value));
        }
    }
}