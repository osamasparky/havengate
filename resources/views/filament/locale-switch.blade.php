{{-- Admin language switch (topbar, before the user menu). --}}
<div class="flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 text-xs font-semibold dark:bg-white/5">
    @foreach (\App\Http\Middleware\SetAdminLocale::LOCALES as $code => $name)
        <a href="{{ route('admin.locale', $code) }}" lang="{{ $code }}"
           @class([
               'rounded-md px-2.5 py-1.5 transition',
               'bg-white text-primary-600 shadow-sm dark:bg-gray-800 dark:text-primary-400' => app()->getLocale() === $code,
               'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => app()->getLocale() !== $code,
           ])>{{ $code === 'ar' ? 'ع' : 'EN' }}<span class="sr-only"> {{ $name }}</span></a>
    @endforeach
</div>
