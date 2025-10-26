<?php

namespace App\Traits;

trait GeneratesPassword
{
    /**
     * Generate a random secure password.
     *
     * @param int $length
     * @param bool $includeSymbols
     * @return string
     */
    public function generatePassword(int $length = 12, bool $includeSymbols = true): string
    {
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+<>?';

        $characters = $letters . $numbers;
        if ($includeSymbols) {
            $characters .= $symbols;
        }

        $password = '';
        $charactersLength = strlen($characters);

        // Always include at least one of each type for security
        $password .= $letters[random_int(0, strlen($letters) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        if ($includeSymbols) {
            $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        }

        // Fill remaining characters
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $characters[random_int(0, $charactersLength - 1)];
        }

        // Shuffle for randomness
        return str_shuffle($password);
    }
}
