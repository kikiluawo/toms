@php
    $month = now()->startOfMonth();
    $daysInMonth = $month->daysInMonth;
    $offset = $month->dayOfWeekIso - 1;
    $today = now()->toDateString();

    $cells = [];

    for ($week = 0; $week < 6; $week++) {
        for ($day = 0; $day < 7; $day++) {
            $number = ($week * 7) + $day - $offset + 1;
            $cells[$week][$day] = $number > 0 && $number <= $daysInMonth ? $number : null;
        }
    }
@endphp

<div class="horizon-card flex w-full h-full flex-col px-3 py-3">
    <div class="mb-4 flex items-center justify-between px-2">
        <h4 class="text-lg font-bold text-navy-700 dark:text-white">{{ $month->format('F Y') }}</h4>
        <div class="flex items-center gap-1">
            <button type="button" class="rounded-lg p-1 text-gray-600 transition hover:bg-lightPrimary hover:cursor-pointer dark:text-white">
                <x-icon name="chevron-left" class="h-5 w-5" />
            </button>
            <button type="button" class="rounded-lg p-1 text-gray-600 transition hover:bg-lightPrimary hover:cursor-pointer dark:text-white">
                <x-icon name="chevron-right" class="h-5 w-5" />
            </button>
        </div>
    </div>

    <div class="grid grid-cols-7 gap-y-1 text-center">
        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekDay)
            <div class="text-xs font-bold text-gray-500">{{ substr($weekDay, 0, 2) }}</div>
        @endforeach

        @foreach ($cells as $week)
            @foreach ($week as $day)
                @php
                    $date = $day ? $month->copy()->addDays($day - 1)->toDateString() : null;
                    $isToday = $date === $today;
                @endphp
                <div class="flex items-center justify-center">
                    @if ($day)
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg text-sm font-medium {{ $isToday ? 'bg-brand-500 text-white' : 'text-navy-700 hover:bg-lightPrimary dark:text-white' }}">
                            {{ $day }}
                        </span>
                    @else
                        <span class="h-8 w-8"></span>
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>
</div>
