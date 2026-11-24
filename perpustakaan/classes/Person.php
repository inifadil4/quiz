<?php

abstract class Person extends BaseModel
{
    protected int $maxLoan = 3;

    abstract public function role(): string;

    protected function validPerson(array $d): bool
    {
        if (trim($d['name'] ?? '') === '') {
            $this->addError('Nama wajib diisi.');
            return false;
        }
        if (!filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $this->addError('Format email tidak valid.');
            return false;
        }
        return true;
    }

    public function getMaxLoan(): int
    {
        return $this->maxLoan;
    }
}
