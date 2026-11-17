@extends('layouts.portal')

@section('title', __('Settings'))

@section('content')
    <x-page-header :title="__('Platform Settings')" :subtitle="__('Contact details and figures shown on the public website.')" />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Homepage statistics bar') }}</h2></div>
            <div class="grid gap-4 p-6 sm:grid-cols-3 lg:grid-cols-5">
                @foreach (['stat_students' => __('Students & Pupils'), 'stat_trainees' => __('Trainees'), 'stat_teachers' => __('Teachers & Trainers'), 'stat_programs' => __('Training Programs'), 'stat_groups' => __('Groups & Classes')] as $key => $label)
                    <x-input :name="$key" :label="$label" :value="$values[$key]" dir="ltr" />
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Contact information') }}</h2></div>
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                <x-input name="contact_phone_1" :label="__('Phone 1')" :value="$values['contact_phone_1']" dir="ltr" />
                <x-input name="contact_phone_2" :label="__('Phone 2')" :value="$values['contact_phone_2']" dir="ltr" />
                <x-input name="contact_whatsapp" :label="__('WhatsApp number (international, digits only)')" :value="$values['contact_whatsapp']" dir="ltr" />
                <x-input name="contact_email" :label="__('Email')" type="email" :value="$values['contact_email']" dir="ltr" />
                <x-input name="contact_address_ar" :label="__('Address (Arabic)')" :value="$values['contact_address_ar']" />
                <x-input name="contact_address_en" :label="__('Address (English)')" :value="$values['contact_address_en']" dir="ltr" />
                <x-input name="working_hours_ar" :label="__('Working hours (Arabic)')" :value="$values['working_hours_ar']" />
                <x-input name="working_hours_en" :label="__('Working hours (English)')" :value="$values['working_hours_en']" dir="ltr" />
                <x-input name="map_embed_url" :label="__('Google Maps embed URL')" :value="$values['map_embed_url']" dir="ltr" class="sm:col-span-2" />
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ __('Identity & social') }}</h2></div>
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                <x-input name="director_name_ar" :label="__('Director name (Arabic)')" :value="$values['director_name_ar']" />
                <x-input name="director_name_en" :label="__('Director name (English)')" :value="$values['director_name_en']" dir="ltr" />
                <x-input name="social_facebook" label="Facebook" :value="$values['social_facebook']" dir="ltr" />
                <x-input name="social_youtube" label="YouTube" :value="$values['social_youtube']" dir="ltr" />
                <x-input name="social_instagram" label="Instagram" :value="$values['social_instagram']" dir="ltr" />
            </div>
        </div>

        <div class="flex justify-end"><button class="btn btn-primary"><x-lucide-save class="size-4" />{{ __('Save settings') }}</button></div>
    </form>
@endsection
