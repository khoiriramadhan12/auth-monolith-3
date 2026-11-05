<?php

namespace Modules\Shared\ValueObjects;

use InvalidArgumentException;

class Isbn
{
    private string $value;

    public function __construct(string $value)
    {
        $clean = preg_replace('/[-\s]/', '', $value);

        if (!$this->isValid($clean)) {
            throw new InvalidArgumentException("Format ISBN tidak valid: {$value}");
        }

        $this->value = strtoupper($clean);
    }

    private function isValid(string $isbn): bool
    {
        $len = strlen($isbn);
        if ($len === 10) {
            return $this->isValidIsbn10($isbn);
        }
        if ($len === 13) {
            return $this->isValidIsbn13($isbn);
        }
        return false;
    }

    private function isValidIsbn10(string $isbn): bool
    {
        if (!preg_match('/^[0-9]{9}[0-9X]$/i', $isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$isbn[$i] * (10 - $i);
        }
        $lastChar = strtoupper($isbn[9]);
        $sum += ($lastChar === 'X') ? 10 : (int)$lastChar;

        return ($sum % 11) === 0;
    }

    private function isValidIsbn13(string $isbn): bool
    {
        if (!preg_match('/^[0-9]{13}$/', $isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$isbn[$i] * (($i % 2 === 0) ? 1 : 3);
        }
        $checksum = (10 - ($sum % 10)) % 10;

        return $checksum === (int)$isbn[12];
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getFormatted(): string
    {
        if (strlen($this->value) === 13) {
            return substr($this->value, 0, 3) . '-' .
                   substr($this->value, 3, 1) . '-' .
                   substr($this->value, 4, 4) . '-' .
                   substr($this->value, 8, 4) . '-' .
                   substr($this->value, 12, 1);
        }
        return substr($this->value, 0, 1) . '-' .
               substr($this->value, 1, 4) . '-' .
               substr($this->value, 5, 4) . '-' .
               substr($this->value, 9, 1);
    }

    public function equals(Isbn $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
