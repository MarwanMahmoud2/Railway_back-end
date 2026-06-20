<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Child extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nurse_id',
        'name',
        'gender',
        'birth_date',
        'mother_name',
        'father_name',
        'father_phone',
        'father_national_id',
        'parent_email',
        'is_linked',
        'footprint_path',
        'child_photo_path',
        'estimated_age',
        'found_location',
        'date_found',
        'notes',
        'nfc_tag_id',
        'status',
        'child_id',
    ];

    protected $appends = ['footprint_url'];

    /**
     * Auto-generate a unique random child_id on creation.
     */
    protected static function booted(): void
    {
        static::creating(function (Child $child) {
            if (empty($child->child_id)) {
                $child->child_id = static::generateUniqueId();
            }
        });
    }

    private static function generateUniqueId(): string
    {
        do {
            $id = 'NBIS-' . strtoupper(Str::random(6));
        } while (static::where('child_id', $id)->exists());

        return $id;
    }

    /**
     * علاقة الطفل بولي الأمر (حساب الأب/الأم على الموبايل)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * علاقة الطفل بالممرضة اللي سجلت البيانات
     */
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    /**
     * دالة للحصول على رابط الصورة المباشر
     */
    public function getFootprintUrlAttribute(): string
    {
        return $this->footprint_path
            ? asset('storage/' . $this->footprint_path)
            : asset('images/default-footprint.png');
    }

    /**
     * Missing reports associated with this child
     */
    public function missingReports(): HasMany
    {
        return $this->hasMany(MissingReport::class);
    }
}
