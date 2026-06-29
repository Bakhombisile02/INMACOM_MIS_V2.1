import { router } from '@inertiajs/react';
import type { User } from 'firebase/auth';

/**
 * Exchange a Firebase ID token for a Laravel session.
 * Call this after any successful Firebase sign-in.
 *
 * @param opts.registrationPin — optional 6-char PIN to associate with a brand-new account.
 */
export async function authenticateWithLaravel(
    firebaseUser: User,
    onError?: (msg: string) => void,
    opts?: { registrationPin?: string },
): Promise<void> {
    const idToken = await firebaseUser.getIdToken();

    const payload: Record<string, string> = { id_token: idToken };
    if (opts?.registrationPin) {
        payload.registration_pin = opts.registrationPin.toUpperCase();
    }

    router.post(route('auth.firebase'), payload, {
        onError: (errors) => {
            const msg = errors.auth ?? errors.id_token ?? 'Authentication failed.';
            onError?.(String(msg));
        },
    });
}
