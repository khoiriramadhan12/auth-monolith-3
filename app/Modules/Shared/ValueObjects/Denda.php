<?php

namespace Modules\Shared\ValueObjects;

use InvalidArgumentException;

class Denda
{
    private float $amount;
    private int $daysLate;
    private float $ratePerDay;
    private string $currency;

    public function __construct(int $daysLate, float $ratePerDay = 1000.0, string $currency = 'IDR')
    {
        if ($daysLate < 0) {
            throw new InvalidArgumentException('Keterlambatan hari tidak boleh negatif');
        }
        if ($ratePerDay < 0) {
            throw new InvalidArgumentException('Tarif denda per hari tidak boleh negatif');
        }

        $this->daysLate = $daysLate;
        $this->ratePerDay = $ratePerDay;
        $this->currency = $currency;
        $this->amount = (float)($daysLate * $ratePerDay);
    }

    public static function fromAmount(float $amount, string $currency = 'IDR'): self
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Nominal denda tidak boleh negatif');
        }
        $instance = new self(0, 0, $currency);
        $instance->amount = $amount;
        return $instance;
    }

    public static function calculate(string|\DateTimeInterface $dueDate, string|\DateTimeInterface $returnDate, float $ratePerDay = 1000.0): self
    {
        $due = is_string($dueDate) ? new \DateTime($dueDate) : $dueDate;
        $ret = is_string($returnDate) ? new \DateTime($returnDate) : $returnDate;

        if ($ret <= $due) {
            return new self(0, $ratePerDay);
        }

        $diff = $due->diff($ret);
        $daysLate = (int)$diff->days;

        return new self($daysLate, $ratePerDay);
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDaysLate(): int
    {
        return $this->daysLate;
    }

    public function getRatePerDay(): float
    {
        return $this->ratePerDay;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(Denda $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Mata uang tidak sesuai');
        }
        return self::fromAmount($this->amount + $other->amount, $this->currency);
    }

    public function equals(Denda $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function __toString(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.') . ' ' . $this->currency;
    }
}
