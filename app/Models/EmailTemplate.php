<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'subject', 'body'])]
class EmailTemplate extends Model
{
    /**
     * The placeholders each template may use, and the ones it must keep for
     * the email to work (e.g. the link that lets an employee set a password).
     *
     * @var array<string, array{variables: array<string, string>, required: array<int, string>}>
     */
    public const DEFINITIONS = [
        'employee_welcome' => [
            'variables' => [
                'employee_name' => "The employee's name",
                'set_password_url' => 'Link to set their password',
                'firm_name' => 'Your firm name',
            ],
            'required' => ['set_password_url'],
        ],
        'customer_otp' => [
            'variables' => [
                'customer_name' => "The client's name",
                'otp_code' => 'The one-time login code',
                'firm_name' => 'Your firm name',
            ],
            'required' => ['otp_code'],
        ],
        'service_reassigned' => [
            'variables' => [
                'employee_name' => "The employee's name",
                'service_number' => 'The service number',
                'service_name' => 'The service name',
                'firm_name' => 'Your firm name',
            ],
            'required' => [],
        ],
        'service_completed' => [
            'variables' => [
                'customer_name' => "The client's name",
                'service_number' => 'The service number',
                'service_name' => 'The service name',
                'firm_name' => 'Your firm name',
            ],
            'required' => [],
        ],
        'payment_received' => [
            'variables' => [
                'customer_name' => "The client's name",
                'amount' => 'The amount received',
                'enquiry_number' => 'The enquiry number',
                'firm_name' => 'Your firm name',
            ],
            'required' => [],
        ],
    ];

    /**
     * @return array<string, string>
     */
    public function variables(): array
    {
        return self::DEFINITIONS[$this->key]['variables'] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public function requiredVariables(): array
    {
        return self::DEFINITIONS[$this->key]['required'] ?? [];
    }

    /**
     * Every {{placeholder}} name used in the given text.
     *
     * @return array<int, string>
     */
    public static function placeholdersIn(string $text): array
    {
        preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/i', $text, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Replace {{placeholders}} in the subject and body with the given values.
     * Placeholders without a value are left as written.
     *
     * @param  array<string, string>  $values
     * @return array{subject: string, body: string}
     */
    public function render(array $values): array
    {
        $replace = function (string $text) use ($values): string {
            return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/i', function (array $match) use ($values) {
                return array_key_exists($match[1], $values) ? (string) $values[$match[1]] : $match[0];
            }, $text);
        };

        return [
            'subject' => $replace($this->subject),
            'body' => $replace($this->body),
        ];
    }
}
