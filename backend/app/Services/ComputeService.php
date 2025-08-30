<?php

namespace App\Services;

class ComputeService
{
    /**
     * Evaluate a simple arithmetic expression with field references.
     * Supported operators: + - * /
     * Field references use dot notation.
     */
    public function evaluate(string $expr, array $data): float
    {
        $tokens = preg_match_all('/[A-Za-z_][A-Za-z0-9_.]*|\d+(?:\.\d+)?|[()+\-*\/]/', $expr, $m)
            ? $m[0]
            : [];
        $output = [];
        $ops = [];
        $prec = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];

        $apply = function () use (&$output, &$ops) {
            $op = array_pop($ops);
            $b = array_pop($output) ?? 0;
            $a = array_pop($output) ?? 0;
            switch ($op) {
                case '+': $output[] = $a + $b; break;
                case '-': $output[] = $a - $b; break;
                case '*': $output[] = $a * $b; break;
                case '/': $output[] = $b == 0 ? 0 : $a / $b; break;
            }
        };

        foreach ($tokens as $token) {
            if (preg_match('/^\d/', $token)) {
                $output[] = (float) $token;
            } elseif (preg_match('/^[A-Za-z_]/', $token)) {
                $output[] = (float) ($this->getField($token, $data) ?? 0);
            } elseif ($token === '(') {
                $ops[] = $token;
            } elseif ($token === ')') {
                while ($ops && end($ops) !== '(') {
                    $apply();
                }
                array_pop($ops);
            } elseif (isset($prec[$token])) {
                while ($ops && end($ops) !== '(' && $prec[end($ops)] >= $prec[$token]) {
                    $apply();
                }
                $ops[] = $token;
            }
        }
        while ($ops) {
            $apply();
        }
        return array_pop($output) ?? 0.0;
    }

    protected function getField(string $path, array $data): mixed
    {
        $segments = explode('.', $path);
        foreach ($segments as $seg) {
            if (! is_array($data) || ! array_key_exists($seg, $data)) {
                return 0;
            }
            $data = $data[$seg];
        }
        return $data;
    }
}
