<?php

namespace App;

enum Semestre: int
{
    case PremierSemestre = 1;
    case DeuxiemeSemestre = 2;

    public function code(): string
    {
        return 'S'.$this->value;
    }

    public function name(): string
    {
        return 'Semestre '.$this->value;
    }

    /**
     * @return list<array{id: int, code: string, name: string}>
     */
    public static function options(): array
    {
        return array_map(static fn (self $semestre): array => [
            'id' => $semestre->value,
            'code' => $semestre->code(),
            'name' => $semestre->name(),
        ], self::cases());
    }
}
