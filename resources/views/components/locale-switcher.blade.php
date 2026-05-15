<form method="POST" action="{{ route('locale.update') }}" class="inline-flex items-center gap-2 rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-700">
    @csrf
    <label for="locale" class="font-medium text-stone-600">{{ __('web.locale.switch') }}</label>
    <select id="locale" name="locale" class="border-0 bg-transparent pe-6 text-sm font-medium focus:ring-0" onchange="this.form.submit()">
        @foreach (config('app.supported_locales', ['ar', 'en']) as $locale)
            <option value="{{ $locale }}" @selected(app()->getLocale() === $locale)>
                {{ __("web.locale.{$locale}") }}
            </option>
        @endforeach
    </select>
</form>
