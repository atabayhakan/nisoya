<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** İş ilanı çalışma tipi. */
enum EmploymentType: string implements HasLabel
{
    case TamZamanli = 'tam_zamanli';
    case YariZamanli = 'yari_zamanli';
    case Sozlesmeli = 'sozlesmeli';
    case Staj = 'staj';
    case Serbest = 'serbest';

    public function getLabel(): string
    {
        return match ($this) {
            self::TamZamanli => 'Tam zamanlı',
            self::YariZamanli => 'Yarı zamanlı',
            self::Sozlesmeli => 'Sözleşmeli',
            self::Staj => 'Staj',
            self::Serbest => 'Serbest (Freelance)',
        };
    }

    /** Google Jobs / schema.org JobPosting.employmentType değeri. */
    public function schemaOrgType(): string
    {
        return match ($this) {
            self::TamZamanli => 'FULL_TIME',
            self::YariZamanli => 'PART_TIME',
            self::Sozlesmeli => 'CONTRACTOR',
            self::Staj => 'INTERN',
            self::Serbest => 'OTHER',
        };
    }
}
