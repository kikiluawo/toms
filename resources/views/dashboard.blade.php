<x-app-layout title="Dashboard">
    @php
        $widgets = [
            ['icon' => 'chart', 'title' => 'Earnings', 'subtitle' => '$340.5'],
            ['icon' => 'document', 'title' => 'Spend this month', 'subtitle' => '$642.39'],
            ['icon' => 'cart', 'title' => 'Sales', 'subtitle' => '$574.34'],
            ['icon' => 'dashboard', 'title' => 'Your Balance', 'subtitle' => '$1,000'],
            ['icon' => 'check-circle', 'title' => 'New Tasks', 'subtitle' => '145'],
            ['icon' => 'home', 'title' => 'Total Projects', 'subtitle' => '$2433'],
        ];

        $checkTable = [
            ['name' => 'Minyak Diesel 2024', 'quantity' => 2458, 'progress' => '17.5%', 'date' => '12 Jan 2021'],
            ['name' => 'Minyak Bensin 92', 'quantity' => 1485, 'progress' => '10.8%', 'date' => '21 Feb 2021'],
            ['name' => 'Minyak Pelumas 4T', 'quantity' => 1024, 'progress' => '21.3%', 'date' => '13 Mar 2021'],
            ['name' => 'Minyak Sawah 2T', 'quantity' => 858, 'progress' => '31.5%', 'date' => '24 Jan 2021'],
            ['name' => 'Minyak Grease EP2', 'quantity' => 258, 'progress' => '12.2%', 'date' => '24 Oct 2022'],
        ];

        $tasks = [
            'Input pembelian Solar',
            'Cek stok gudang',
            'Update harga jual',
            'Retur pelanggan',
            'Rekap penjualan harian',
        ];
    @endphp

    <div>
        <div class="mt-3 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-3 3xl:grid-cols-6">
            @foreach ($widgets as $widget)
                <div class="horizon-card !flex-row flex-grow items-center rounded-[20px]">
                    <div class="ml-[18px] flex h-[90px] w-auto flex-row items-center">
                        <div class="rounded-full bg-lightPrimary p-3 dark:bg-navy-700">
                            <span class="flex items-center text-brand-500 dark:text-white">
                                <x-icon :name="$widget['icon']" class="h-6 w-6" />
                            </span>
                        </div>
                    </div>

                    <div class="h-50 ml-4 flex w-auto flex-col justify-center">
                        <p class="font-dm text-sm font-medium text-gray-600">{{ $widget['title'] }}</p>
                        <h4 class="text-xl font-bold text-navy-700 dark:text-white">{{ $widget['subtitle'] }}</h4>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="horizon-card !p-[20px] text-center">
                <div class="flex justify-between">
                    <button type="button" class="linear mt-1 flex items-center justify-center gap-2 rounded-lg bg-lightPrimary p-2 text-gray-600 transition duration-200 hover:cursor-pointer hover:bg-gray-100 active:bg-gray-200 dark:bg-navy-700 dark:hover:opacity-90 dark:active:opacity-80">
                        <x-icon name="calendar" class="h-5 w-5" />
                        <span class="text-sm font-medium text-gray-600">This month</span>
                    </button>
                    <button type="button" class="!linear z-[1] flex items-center justify-center rounded-lg bg-lightPrimary p-2 text-brand-500 !transition !duration-200 hover:bg-gray-100 active:bg-gray-200 dark:bg-navy-700 dark:text-white dark:hover:bg-white/20 dark:active:bg-white/10">
                        <x-icon name="chart" class="h-6 w-6" />
                    </button>
                </div>

                <div class="flex h-full w-full flex-row justify-between sm:flex-wrap lg:flex-nowrap 2xl:overflow-hidden">
                    <div class="flex flex-col">
                        <p class="mt-[20px] text-3xl font-bold text-navy-700 dark:text-white">$37.5K</p>
                        <div class="flex flex-col items-start">
                            <p class="mt-2 text-sm text-gray-600">Total Spent</p>
                            <div class="flex flex-row items-center justify-center">
                                <x-icon name="arrow-up" class="h-4 w-4 font-medium text-green-500" />
                                <p class="text-sm font-bold text-green-500"> +2.45% </p>
                            </div>
                        </div>
                    </div>
                    <div class="h-full w-full">
                        <div id="chart-total-spent"></div>
                    </div>
                </div>
            </div>

            <div class="horizon-card flex flex-col bg-white w-full rounded-3xl py-6 px-2 text-center">
                <div class="mb-auto flex items-center justify-between px-6">
                    <h2 class="text-lg font-bold text-navy-700 dark:text-white">Weekly Revenue</h2>
                    <button type="button" class="!linear z-[1] flex items-center justify-center rounded-lg bg-lightPrimary p-2 text-brand-500 !transition !duration-200 hover:bg-gray-100 active:bg-gray-200 dark:bg-navy-700 dark:text-white dark:hover:bg-white/20 dark:active:bg-white/10">
                        <x-icon name="chart" class="h-6 w-6" />
                    </button>
                </div>

                <div class="md:mt-16 lg:mt-0">
                    <div class="h-[250px] w-full xl:h-[350px]">
                        <div id="chart-weekly-revenue"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
            <div class="horizon-card w-full h-full sm:overflow-auto px-6">
                <header class="relative flex items-center justify-between pt-4">
                    <div class="text-xl font-bold text-navy-700 dark:text-white">Check Table</div>

                    @include('layouts.partials.card-menu')
                </header>

                <div class="mt-8 overflow-x-scroll xl:overflow-x-hidden">
                    <table class="w-full">
                        <thead>
                            <tr class="!border-px !border-gray-400">
                                <th class="cursor-pointer border-b-[1px] border-gray-200 pt-4 pb-2 pr-4 text-start">
                                    <div class="items-center justify-between text-xs text-gray-200">
                                        <p class="text-sm font-bold text-gray-600 dark:text-white">NAME</p>
                                    </div>
                                </th>
                                <th class="cursor-pointer border-b-[1px] border-gray-200 pt-4 pb-2 pr-4 text-start">
                                    <div class="items-center justify-between text-xs text-gray-200">
                                        <p class="text-sm font-bold text-gray-600 dark:text-white">PROGRESS</p>
                                    </div>
                                </th>
                                <th class="cursor-pointer border-b-[1px] border-gray-200 pt-4 pb-2 pr-4 text-start">
                                    <div class="items-center justify-between text-xs text-gray-200">
                                        <p class="text-sm font-bold text-gray-600 dark:text-white">QUANTITY</p>
                                    </div>
                                </th>
                                <th class="cursor-pointer border-b-[1px] border-gray-200 pt-4 pb-2 pr-4 text-start">
                                    <div class="items-center justify-between text-xs text-gray-200">
                                        <p class="text-sm font-bold text-gray-600 dark:text-white">DATE</p>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($checkTable as $row)
                                <tr>
                                    <td class="min-w-[150px] border-white/0 py-3 pr-4">
                                        <div class="flex items-center">
                                            <input type="checkbox" checked class="defaultCheckbox relative flex h-[20px] min-h-[20px] w-[20px] min-w-[20px] appearance-none items-center justify-center rounded-md border border-gray-300 text-white/0 outline-none transition duration-[0.2s] checked:border-none checked:text-white hover:cursor-pointer dark:border-white/10 checked:bg-indigo-500 dark:checked:bg-indigo-400">
                                            <p class="ml-3 text-sm font-bold text-navy-700 dark:text-white">{{ $row['name'] }}</p>
                                        </div>
                                    </td>
                                    <td class="min-w-[150px] border-white/0 py-3 pr-4">
                                        <p class="text-sm font-bold text-navy-700 dark:text-white">{{ $row['progress'] }}</p>
                                    </td>
                                    <td class="min-w-[150px] border-white/0 py-3 pr-4">
                                        <p class="text-sm font-bold text-navy-700 dark:text-white">{{ $row['quantity'] }}</p>
                                    </td>
                                    <td class="min-w-[150px] border-white/0 py-3 pr-4">
                                        <p class="text-sm font-bold text-navy-700 dark:text-white">{{ $row['date'] }}</p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 rounded-[20px] md:grid-cols-2">
                <div class="horizon-card pb-7 p-[20px]">
                    <div class="flex flex-row justify-between">
                        <div class="ml-1 pt-2">
                            <p class="text-sm font-medium leading-4 text-gray-600">Daily Traffic</p>
                            <p class="text-[34px] font-bold text-navy-700 dark:text-white">
                                2.579
                                <span class="text-sm font-medium leading-6 text-gray-600">Visitors</span>
                            </p>
                        </div>
                        <div class="mt-2 flex items-start">
                            <div class="flex items-center text-sm text-green-500">
                                <x-icon name="arrow-up" class="h-5 w-5" />
                                <p class="font-bold"> +2.45% </p>
                            </div>
                        </div>
                    </div>

                    <div class="h-[300px] w-full pt-10 pb-0">
                        <div id="chart-daily-traffic"></div>
                    </div>
                </div>

                <div class="horizon-card rounded-[20px] p-3">
                    <div class="flex flex-row justify-between px-3 pt-2">
                        <div>
                            <h4 class="text-lg font-bold text-navy-700 dark:text-white">Your Pie Chart</h4>
                        </div>

                        <div class="mb-6 flex items-center justify-center">
                            <select class="mb-3 mr-2 flex items-center justify-center text-sm font-bold text-gray-600 hover:cursor-pointer dark:!bg-navy-800 dark:text-white">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-auto flex h-[220px] w-full items-center justify-center">
                        <div id="chart-pie"></div>
                    </div>

                    <div class="flex flex-row !justify-between rounded-2xl px-6 py-3 shadow-2xl shadow-shadow-500 dark:!bg-navy-700 dark:shadow-none">
                        <div class="flex flex-col items-center justify-center">
                            <div class="flex items-center justify-center">
                                <div class="h-2 w-2 rounded-full bg-brand-500"></div>
                                <p class="ml-1 text-sm font-normal text-gray-600">Your Files</p>
                            </div>
                            <p class="mt-px text-xl font-bold text-navy-700 dark:text-white">63%</p>
                        </div>

                        <div class="h-11 w-px bg-gray-300 dark:bg-white/10"></div>

                        <div class="flex flex-col items-center justify-center">
                            <div class="flex items-center justify-center">
                                <div class="h-2 w-2 rounded-full bg-[#6AD2FF]"></div>
                                <p class="ml-1 text-sm font-normal text-gray-600">System</p>
                            </div>
                            <p class="mt-px text-xl font-bold text-navy-700 dark:text-white">25%</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="horizon-card pb-7 p-[20px]">
                <div class="relative flex flex-row justify-between">
                    <div class="flex items-center">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 dark:bg-white/5">
                            <x-icon name="check-circle" class="h-6 w-6 text-brand-500 dark:text-white" />
                        </div>
                        <h4 class="ml-4 text-xl font-bold text-navy-700 dark:text-white">Tasks</h4>
                    </div>

                    @include('layouts.partials.card-menu')
                </div>

                <div class="h-full w-full">
                    @foreach ($tasks as $task)
                        <div class="mt-5 flex items-center justify-between p-2">
                            <div class="flex items-center justify-center gap-2">
                                <input type="checkbox" class="defaultCheckbox relative flex h-[20px] min-h-[20px] w-[20px] min-w-[20px] appearance-none items-center justify-center rounded-md border border-gray-300 text-white/0 outline-none transition duration-[0.2s] checked:border-none checked:text-white hover:cursor-pointer dark:border-white/10 checked:bg-brand-500 dark:checked:bg-brand-400">
                                <p class="text-base font-bold text-navy-700 dark:text-white">{{ $task }}</p>
                            </div>
                            <div>
                                <x-icon name="drag" class="h-6 w-6 text-navy-700 dark:text-white" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 rounded-[20px]">
                <x-horizon.mini-calendar />
            </div>
        </div>
    </div>
</x-app-layout>
