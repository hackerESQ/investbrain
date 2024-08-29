<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Laravel\Sanctum\HasApiTokens;
use Laravel\Jetstream\HasProfilePhoto;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasUuids;
    use HasRelationships;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function portfolios()
    {
        return $this->belongsToMany(Portfolio::class)->withPivot('owner');
    }

    public function daily_changes()
    {
        return $this->hasManyDeep(DailyChange::class, ['portfolio_user', Portfolio::class]);
    }

    public function holdings(): HasManyDeep
    {
        return $this->hasManyDeep(Holding::class, ['portfolio_user', Portfolio::class])
            ->withMarketData()
            ->selectRaw('COALESCE(market_data.market_value * holdings.quantity, 0) AS total_market_value')
            ->selectRaw('COALESCE((market_data.market_value - holdings.average_cost_basis) * holdings.quantity, 0) AS market_gain_dollars')
            ->selectRaw('COALESCE(((market_data.market_value - holdings.average_cost_basis) / holdings.average_cost_basis), 0) AS market_gain_percent');    
    }

    public function transactions(): HasManyDeep
    {
        return $this->hasManyDeep(Transaction::class, ['portfolio_user', Portfolio::class])
            ->withMarketData()
            ->withAggregate('portfolio', 'title')
            ->selectRaw('
                CASE
                    WHEN transaction_type = \'SELL\' 
                    THEN COALESCE(transactions.sale_price - transactions.cost_basis, 0)
                    ELSE COALESCE(market_data.market_value - transactions.cost_basis, 0)
                END AS gain_dollars');   
    }
}
