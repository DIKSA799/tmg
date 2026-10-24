<?php

namespace App\Models;

use Database\Factories\CommunityLinkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CommunityLink extends Model
{
    /** @use HasFactory<CommunityLinkFactory> */
    use HasFactory;

    private const VERSION_KEY = 'tmg.community.version';

    public const PLATFORM_TELEGRAM = 'telegram';

    public const PLATFORM_WHATSAPP = 'whatsapp';

    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_STATE = 'state';

    public const SCOPE_DISTRICT = 'district';

    protected $fillable = [
        'platform',
        'scope_type',
        'scope_id',
        'label',
        'url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::bumpVersion());
        static::deleted(fn () => self::bumpVersion());
    }

    /**
     * Monotonic version included in resolved-link cache keys. Every admin edit
     * invalidates all previously cached resolutions at once.
     */
    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn (): int => 1);
    }

    private static function bumpVersion(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    /**
     * @param  Builder<CommunityLink>  $query
     * @return Builder<CommunityLink>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Resolve the most specific active link for a platform:
     * district -> state -> global.
     */
    public static function resolve(string $platform, ?int $stateId, ?int $districtId): ?self
    {
        $candidates = collect([
            [self::SCOPE_DISTRICT, $districtId],
            [self::SCOPE_STATE, $stateId],
            [self::SCOPE_GLOBAL, null],
        ]);

        foreach ($candidates as [$scope, $id]) {
            if ($scope !== self::SCOPE_GLOBAL && $id === null) {
                continue;
            }

            $link = static::query()
                ->active()
                ->where('platform', $platform)
                ->where('scope_type', $scope)
                ->when($scope === self::SCOPE_GLOBAL, fn (Builder $query) => $query->whereNull('scope_id'))
                ->when($scope !== self::SCOPE_GLOBAL, fn (Builder $query) => $query->where('scope_id', $id))
                ->latest('id')
                ->first();

            if ($link !== null) {
                return $link;
            }
        }

        return null;
    }

    public static function platforms(): array
    {
        return [
            self::PLATFORM_TELEGRAM => 'Telegram',
            self::PLATFORM_WHATSAPP => 'WhatsApp',
        ];
    }

    public static function scopeTypes(): array
    {
        return [
            self::SCOPE_GLOBAL => 'General',
            self::SCOPE_STATE => 'State',
            self::SCOPE_DISTRICT => 'Senatorial district',
        ];
    }
}
