<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * 一括代入で更新する可能性がある項目を列挙
     * フォーム等で employee_id を直接更新しないなら外してOK
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
    ];

    /**
     * 配列/JSONに含めたくない属性
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * モデル属性のキャスト
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'employee_id'       => 'integer', // ← 追加
        ];
    }

    /**
     * ログインユーザーが紐づく社員
     * users.employee_id → employees.employee_id
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    /**
     * 表示名（社員名があれば優先）
     * $user->display_name で参照可能
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->employee->employee_name ?? $this->name;
    }

    // ↓ もし API などで JSON に display_name を常に含めたい場合のみ有効化（任意）
    // protected $appends = ['display_name'];
}
