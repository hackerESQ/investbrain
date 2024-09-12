<?php

namespace Tests;

use Tests\TestCase;
use App\Models\User;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\DailyChange;
use App\Models\Transaction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SyncDailyChangeTest extends TestCase
{
    use RefreshDatabase;

    /**
     */
    public function test_can_sync_daily_change_history(): void
    {
        $this->actingAs($user = User::factory()->create());

        $portfolio = Portfolio::factory()->create();

        Transaction::factory(5)->buy()->yearsAgo()->portfolio($portfolio->id)->symbol('ACME')->create();
        Transaction::factory()->sell()->lastMonth()->portfolio($portfolio->id)->symbol('ACME')->create();
        Transaction::factory(5)->buy()->yearsAgo()->portfolio($portfolio->id)->symbol('AAPL')->create();
        Transaction::factory(5)->buy()->yearsAgo()->portfolio($portfolio->id)->symbol('GOOG')->create();
        Transaction::factory(5)->buy()->yearsAgo()->portfolio($portfolio->id)->symbol('FOO')->create();
        Transaction::factory(5)->buy()->yearsAgo()->portfolio($portfolio->id)->symbol('BAR')->create();

        $portfolio->syncDailyChanges();

        $count_of_daily_changes = $portfolio->daily_change()->count('date');
        $days_between_now_and_first_trans = (int) now()->diffInDays($portfolio->transactions()->min('date'), true) + 1;

        $this->assertEquals($count_of_daily_changes, $days_between_now_and_first_trans);
    }

    /**
     */
    public function test_sales_are_captured_as_realized_gains(): void
    {
        $this->actingAs($user = User::factory()->create());

        $portfolio = Portfolio::factory()->create();

        Transaction::factory(5)->buy()->yearsAgo()->portfolio($portfolio->id)->symbol('ACME')->create();
        $sale_transaction = Transaction::factory()->sell()->lastMonth()->portfolio($portfolio->id)->symbol('ACME')->create();
        Transaction::factory(5)->buy()->yearsAgo()->portfolio($portfolio->id)->symbol('AAPL')->create();
        Transaction::factory(5)->buy()->yearsAgo()->portfolio($portfolio->id)->symbol('GOOG')->create();
        Transaction::factory(5)->buy()->yearsAgo()->portfolio($portfolio->id)->symbol('FOO')->create();
        Transaction::factory(5)->buy()->yearsAgo()->portfolio($portfolio->id)->symbol('BAR')->create();

        $portfolio->syncDailyChanges();

        $daily_change = DailyChange::query()
            ->portfolio($portfolio->id)
            ->whereDate('date', $sale_transaction->date)
            ->first();

        $realized_gain = ($sale_transaction->sale_price - $sale_transaction->cost_basis) * $sale_transaction->quantity;

        $this->assertEqualsWithDelta($daily_change->realized_gains, $realized_gain, 0.01);

        $day_before = DailyChange::query()
            ->portfolio($portfolio->id)
            ->whereDate('date', $sale_transaction->date->subDays(1))
            ->first();

        $this->assertEquals($day_before->realized_gains, 0);

        $day_after = DailyChange::query()
            ->portfolio($portfolio->id)
            ->whereDate('date', $sale_transaction->date->addDays(1))
            ->first();

        $this->assertEqualsWithDelta($day_after->realized_gains, $realized_gain, 0.01);
    }

    public function test_dividends_captured_in_daily_change_sync(): void
    {
        $this->actingAs($user = User::factory()->create());

        $portfolio = Portfolio::factory()->create();

        Transaction::factory(5)->buy()->yearsAgo()->portfolio($portfolio->id)->symbol('ACME')->create();

        Artisan::call('refresh:dividend-data');

        $portfolio->syncDailyChanges();

        $holding = Holding::query()->portfolio($portfolio->id)->symbol('ACME')->first();
        $dividends = $holding->dividends()->get()->sortBy('date');

        $first_dividend_change = DailyChange::query()
            ->portfolio($portfolio->id)
            ->whereDate('date', $dividends->first()->date)
            ->first();

        $owned = $dividends->first()->purchased - $dividends->first()->sold;

        $this->assertEqualsWithDelta($dividends->first()->dividend_amount * $owned, $first_dividend_change->total_dividends_earned, 0.01);

        $last_dividend_change = DailyChange::query()
            ->portfolio($portfolio->id)
            ->whereDate('date', $dividends->last()->date)
            ->first();

        $total_dividends = $dividends->reduce(function (?float $carry, $dividend) {
            return $carry + ($dividend['dividend_amount'] * ($dividend['purchased'] - $dividend['sold']));
        });

        $owned = $dividends->last()->purchased - $dividends->last()->sold;

        $this->assertEqualsWithDelta($total_dividends, $last_dividend_change->total_dividends_earned, 0.01);
    }
}