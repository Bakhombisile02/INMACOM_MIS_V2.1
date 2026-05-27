# INMACOM Team Credentials

Provisioned via `php artisan user:seed-team` on 28 May 2026.

Sign-in URL: `/login` (Firebase email/password). No registration PIN required — accounts are pre-provisioned.

Mozambique users have their preference language set to **Portuguese (pt)** and will see the UI in Portuguese on first login. All others default to **English (en)**.

| Name | Email | Password | Role | Country | Language |
|---|---|---|---|---|---|
| Simon Malose Ngoepe | ngoepem@dws.gov.za | `\c}1gF6[L31]ED` | manager | South Africa | en |
| Thomas Hulisani Rananga | ranangah@dws.gov.za | `;a9QwH8kj5k_/v` | admin | South Africa | en |
| Andre Moiane | andremoiane16@gmail.com | `YS?!T>bOT<[:4R` | admin | Mozambique | pt |
| David Mucambe | davidmucambe@gmail.com | `3RP];#lU631NZy` | admin | Mozambique | pt |
| Sakhiwe Nkomo | sakhiwe.nkomo@gwpsaf.org | `r3p6E%d#wu-S_w` | admin | Eswatini | en |
| Spencer Green-Thomson | greenthompsons@gmail.com | `6/y)4xD;(<0.8k` | admin | Eswatini | en |

## Notes

- Passwords are stored hashed in both Firebase Auth and the local `users` table — they cannot be retrieved later. Save this file securely.
- To rotate any of these passwords, run: `php artisan user:seed-team --reset-passwords`.
- Users can change their UI language at any time via the language switcher in the app.
- Emails are case-insensitive (Firebase Auth normalises them to lowercase).
