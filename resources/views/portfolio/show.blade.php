<x-app-layout>
    <div x-data>

        <x-ib-drawer 
            key="manage-portfolio"
            title="{{ $portfolio->title }}"
        >

            @livewire('manage-portfolio-form', [
                'portfolio' => $portfolio, 
                'hideCancel' => true
            ])

        </x-ib-drawer>

        <x-ib-toolbar :title="$portfolio->title">

            @if($portfolio->wishlist)
            <x-badge value="{{ __('Wishlist') }}" class="badge-primary mr-3" />
            @endif

            <x-button 
                title="{{ __('Edit Portfolio') }}" 
                icon="o-pencil" 
                class="btn-circle btn-ghost btn-sm text-secondary" 
                @click="$dispatch('toggle-manage-portfolio')"
            />
        </x-ib-toolbar>

        @livewire('portfolio-performance-cards', [
            'name' => 'portfolio-'.$portfolio->id,
            'portfolio' => $portfolio
        ])

        <div class="grid sm:grid-cols-5 gap-5">
            @php
                $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
            @endphp

            <x-card class="col-span-5 sm:col-span-1 bg-slate-100 dark:bg-base-200 rounded-lg">
                <div class="text-sm text-gray-400 whitespace-nowrap">{{ __('Market Gain/Loss') }}</div>
                <div class="font-black text-xl"> {{ $formatter->formatCurrency($portfolio->marketGainLoss, 'USD') }} </div>
            </x-card>
            
            <x-card class="col-span-5 sm:col-span-1 bg-slate-100 dark:bg-base-200 rounded-lg">
                <div class="text-sm text-gray-400 whitespace-nowrap">{{ __('Total Cost Basis') }}</div>
                <div class="font-black text-xl"> {{ $formatter->formatCurrency($portfolio->totalCostBasis, 'USD') }} </div>
            </x-card>
            
            <x-card class="col-span-5 sm:col-span-1 bg-slate-100 dark:bg-base-200 rounded-lg">
                <div class="text-sm text-gray-400 whitespace-nowrap">{{ __('Total Market Value') }}</div>
                <div class="font-black text-xl"> {{ $formatter->formatCurrency($portfolio->totalMarketValue, 'USD') }} </div>
            </x-card>
            
            <x-card class="col-span-5 sm:col-span-1 bg-slate-100 dark:bg-base-200 rounded-lg">
                <div class="text-sm text-gray-400 whitespace-nowrap">{{ __('Realized Gain/Loss') }}</div>
                <div class="font-black text-xl"> {{ $formatter->formatCurrency($portfolio->realizedGainLoss, 'USD') }} </div>
            </x-card>

            <x-card class="col-span-5 sm:col-span-1 bg-slate-100 dark:bg-base-200 rounded-lg">
                <div class="text-sm text-gray-400 whitespace-nowrap">{{ __('Dividends Earned') }}</div>
                <div class="font-black text-xl"> {{ $formatter->formatCurrency($portfolio->dividendsEarned, 'USD') }} </div>
            </x-card>
                
        </div>


        <div class="mt-6 grid md:grid-cols-7 gap-5">

            <x-ib-card title="All portfolio holdings" class="md:col-span-4">
            
                @php
                    $users = App\Models\User::take(3)->get();
                @endphp
                
                @foreach($users as $user)
                    <x-list-item no-separator :item="$user" avatar="profile_photo_url" link="/docs/installation" />
                @endforeach

            </x-ib-card>

            <x-ib-card title="Top performers" class="md:col-span-3">
            
                @php
                    $users = App\Models\User::take(3)->get();
                @endphp
                
                @foreach($users as $user)
                    <x-list-item no-separator :item="$user" avatar="profile_photo_url" link="/docs/installation" />
                @endforeach

            </x-ib-card>
            
            <x-ib-card title="Top headlines" class="md:col-span-3">
            
                @php
                    $users = App\Models\User::take(3)->get();
                @endphp
                
                @foreach($users as $user)
                    <x-list-item no-separator :item="$user" avatar="profile_photo_url" link="/docs/installation" />
                @endforeach

            </x-ib-card>

            <x-ib-card title="Recent activity" class="md:col-span-4">
            
                @php
                    $users = App\Models\User::take(3)->get();
                @endphp
                
                @foreach($users as $user)
                    <x-list-item no-separator :item="$user" avatar="profile_photo_url" link="/docs/installation" />
                @endforeach

            </x-ib-card>

        </div>
        
    </div>
</x-app-layout>