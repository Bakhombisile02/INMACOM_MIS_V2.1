<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\EmailExists;
use Kreait\Firebase\Request\CreateUser;
use Throwable;

/**
 * Provisions the INMACOM testing team (data upload + testing personnel).
 *
 * Creates each user in Firebase Auth (with a generated password) and a
 * matching local `users` row with the correct role, country and language
 * preference. Idempotent: re-running re-uses any existing Firebase account
 * (looked up by email) and updates the local row in place.
 *
 * After running, the command prints a table of credentials.
 */
class SeedInmacomTeam extends Command
{
    protected $signature = 'user:seed-team {--reset-passwords : Reset passwords for users that already exist}';

    protected $description = 'Provision the INMACOM data-upload / testing team in Firebase + local DB and print credentials.';

    /**
     * @var array<int, array{name: string, email: string, role: string, country: string, language: 'en'|'pt'}>
     */
    private array $team = [
        [
            'name' => 'Simon Malose Ngoepe',
            'email' => 'ngoepem@dws.gov.za',
            'role' => User::ROLE_MANAGER,
            'country' => 'South Africa',
            'language' => 'en',
        ],
        [
            'name' => 'Thomas Hulisani Rananga',
            'email' => 'RanangaH@dws.gov.za',
            'role' => User::ROLE_ADMIN,
            'country' => 'South Africa',
            'language' => 'en',
        ],
        [
            'name' => 'Andre Moiane',
            'email' => 'andremoiane16@gmail.com',
            'role' => User::ROLE_ADMIN,
            'country' => 'Mozambique',
            'language' => 'pt',
        ],
        [
            'name' => 'David Mucambe',
            'email' => 'davidmucambe@gmail.com',
            'role' => User::ROLE_ADMIN,
            'country' => 'Mozambique',
            'language' => 'pt',
        ],
        [
            'name' => 'Sakhiwe Nkomo',
            'email' => 'sakhiwe.nkomo@gwpsaf.org',
            'role' => User::ROLE_ADMIN,
            'country' => 'Eswatini',
            'language' => 'en',
        ],
        [
            'name' => 'Spencer Green-Thomson',
            'email' => 'greenthompsons@gmail.com',
            'role' => User::ROLE_ADMIN,
            'country' => 'Eswatini',
            'language' => 'en',
        ],
    ];

    public function handle(FirebaseAuth $firebase): int
    {
        $rows = [];

        foreach ($this->team as $entry) {
            try {
                $email = strtolower(trim($entry['email']));
                $existing = User::whereRaw('lower(email) = ?', [$email])->first();

                $password = $this->generatePassword();
                $passwordWasReset = false;

                // 1. Ensure Firebase user exists, capture UID.
                $firebaseUid = null;
                try {
                    $firebaseUser = $firebase->createUser(
                        CreateUser::new()
                            ->withEmail($email)
                            ->withDisplayName($entry['name'])
                            ->withClearTextPassword($password)
                            ->markEmailAsVerified()
                    );
                    $firebaseUid = $firebaseUser->uid;
                } catch (EmailExists) {
                    $firebaseUser = $firebase->getUserByEmail($email);
                    $firebaseUid = $firebaseUser->uid;

                    if ($this->option('reset-passwords')) {
                        $firebase->changeUserPassword($firebaseUid, $password);
                        $passwordWasReset = true;
                    } else {
                        $password = '(unchanged — Firebase account already existed)';
                    }
                }

                // 2. Upsert local users row.
                $attributes = [
                    'display_name' => $entry['name'],
                    'email' => $email,
                    'role' => $entry['role'],
                    'country' => $entry['country'],
                    'firebase_uid' => $firebaseUid,
                    'email_verified_at' => now(),
                    'preferences' => array_merge(
                        $existing?->preferences ?? [],
                        ['language' => $entry['language']]
                    ),
                ];

                if ($passwordWasReset || ! $existing) {
                    $attributes['password'] = Hash::make(is_string($password) && ! str_starts_with($password, '(') ? $password : Str::random(32));
                }

                if ($existing) {
                    $existing->fill($attributes)->save();
                } else {
                    User::create($attributes);
                }

                $rows[] = [
                    'Name' => $entry['name'],
                    'Email' => $email,
                    'Password' => $password,
                    'Role' => $entry['role'],
                    'Country' => $entry['country'],
                    'Lang' => $entry['language'],
                ];
            } catch (Throwable $e) {
                $this->error("Failed for {$entry['email']}: ".$e->getMessage());
                $rows[] = [
                    'Name' => $entry['name'],
                    'Email' => $entry['email'],
                    'Password' => 'ERROR: '.$e->getMessage(),
                    'Role' => $entry['role'],
                    'Country' => $entry['country'],
                    'Lang' => $entry['language'],
                ];
            }
        }

        $this->newLine();
        $this->info('INMACOM team provisioned. Credentials below — share with each user.');
        $this->table(['Name', 'Email', 'Password', 'Role', 'Country', 'Lang'], $rows);

        return self::SUCCESS;
    }

    /**
     * Generate a 14-char password with uppercase, lowercase, digits and a symbol.
     */
    private function generatePassword(): string
    {
        return Str::password(14, letters: true, numbers: true, symbols: true, spaces: false);
    }
}
