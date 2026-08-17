<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * יצירת משתמש אדמין ראשון באתר חדש (למשל בהקמת אתר-אח).
 * אם המייל כבר קיים — המשתמש הקיים מקודם לאדמין ומאופסת לו הסיסמה.
 */
class MakeAdmin extends Command
{
    protected $signature = 'user:make-admin
        {email : כתובת המייל של האדמין}
        {--name= : שם לתצוגה (ברירת מחדל: החלק שלפני ה-@)}
        {--password= : סיסמה. אם לא הועברה — תיווצר סיסמה אקראית ותוצג פעם אחת}';

    protected $description = 'יוצר (או מקדם) משתמש אדמין פעיל — שימושי בהקמת אתר חדש שאין בו עדיין אף משתמש';

    public function handle(): int
    {
        $email = trim($this->argument('email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("כתובת מייל לא תקינה: {$email}");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: str()->password(14);
        $name = $this->option('name') ?: str($email)->before('@')->toString();

        $existing = User::where('email', $email)->exists();

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => $password, // ה-cast 'hashed' במודל מצפין
                'role'     => 'admin',
                'status'   => 'active',
            ],
        );

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->info(($existing ? 'עודכן' : 'נוצר') . " אדמין: {$user->name} <{$user->email}>");

        if (! $this->option('password')) {
            $this->warn("סיסמה: {$password}   (מוצגת פעם אחת בלבד — שמרו אותה)");
        }

        return self::SUCCESS;
    }
}
