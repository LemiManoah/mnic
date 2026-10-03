<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

final class PioneerMemberSeeder extends Seeder
{
    /** @var list<string> */
    private const array EMAILS = [
        'lemi.manoah@gmail.com',
        'mubuukefredrick24@gmail.com',
        'fetaowen@gmail.com',
        'jamesbenjaminmarvin@gmail.com',
        'ndagijeronnie@gmail.com',
        'jacksonsimeon17@gmail.com',
        'kmnuwagaba@gmail.com',
        'johnesaut@gmail.com',
    ];

    /** @var list<string> */
    private const array NAMES = [
        'Lemi Manoah',
        'Ssekweyama Fredrick', 'Fredrick Ssekweyama',
        'Feta Jeff Owen',
        'Lubega James Benjamin', 'James Benjamin Lubega',
        'Ndagije Ronald', 'Ronald Ndagije',
        'Luate Simon Jackson', 'Simon Jackson Luate',
        'Nuwagaba Michael Kanyima', 'Michael Nuwagaba',
        'Tumwine John Esau', 'John Esau Tumwine',
    ];

    public function run(): void
    {
        Member::query()
            ->whereIn('full_name', self::NAMES)
            ->orWhereHas('user', fn (Builder $query): Builder => $query->whereIn('email', self::EMAILS))
            ->update(['is_pioneer' => true]);
    }
}
